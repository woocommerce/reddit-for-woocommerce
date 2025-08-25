<?php
/**
 * Implements the ConversionTrackerInterface to send data via the Ad Partner's Conversions API.
 *
 * This class builds WooCommerce event payloads and dispatches them via WCS (WooCommerce Connect Server)
 * to the Ad Partner's server-side tracking endpoint.
 *
 * Events such as Add to Cart and Purchase are tracked using Action Scheduler for asynchronous delivery.
 *
 * @package RedditForWooCommerce\Tracking
 */

namespace RedditForWooCommerce\Tracking;

use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Tracking\ConversionEvent\PurchaseEvent;
use RedditForWooCommerce\Tracking\ConversionEvent\AddToCartEvent;
use RedditForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;
use RedditForWooCommerce\Tracking\ConversionEvent\PageVisitEvent;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\UserIdentifier;
use RedditForWooCommerce\Tracking\Consent;

/**
 * Handles conversion tracking by sending server-side events to the Ad Partner Conversions API.
 *
 * This class is the concrete implementation of {@see ConversionTrackerInterface} responsible
 * for building and queuing tracking events (such as purchase and add-to-cart) and delivering them
 * to the Ad Partner's API through the WCS proxy endpoint.
 *
 * Events are queued asynchronously using Action Scheduler to avoid slowing down frontend or checkout flows.
 *
 * @since 0.1.0
 */
class RemoteConversionTracker implements ConversionTrackerInterface {

	/**
	 * Meta key used to mark orders that have already been tracked.
	 */
	public const ORDER_CONVERSION_TRACKED_META_KEY = '_reddit_conversion_tracked';

	/**
	 * WCS client used to proxy API requests to the Ad Partner.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $client;

	/**
	 * Logger instance for tracking conversion events.
	 *
	 * This logger is used to log successful and failed event transmissions,
	 * as well as debug information about the payloads being sent.
	 *
	 * @var ConversionEventLogger
	 */
	protected ConversionEventLogger $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient             $client WCS API proxy client.
	 * @param ConversionEventLogger $logger Logger instance for tracking conversion events.
	 */
	public function __construct( WcsClient $client, ConversionEventLogger $logger ) {
		$this->client = $client;
		$this->logger = $logger;
	}

	/**
	 * Tracks a WooCommerce purchase event.
	 *
	 * Instantiates a {@see PurchaseEvent} object using the given order ID,
	 * generates a valid API payload, and schedules it for dispatch to the Ad Partner
	 * via Action Scheduler. The payload includes product, revenue, user, and deduplication data.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function track_purchase( int $order_id ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_CONVERSION_TRACKED_META_KEY, true ) ) {
			return;
		}

		$event   = new PurchaseEvent( $order_id );
		$payload = $event->build_payload(
			array(
				'conversion_id' => $order->get_order_key(),
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);
		$args    = array( 'order_id' => $order_id );

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => $payload,
				'args'          => $args,
			),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Tracks a WooCommerce add-to-cart event.
	 *
	 * Instantiates an {@see AddToCartEvent} using the given product ID and quantity,
	 * builds a conversion payload, and schedules it for asynchronous dispatch.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $product_id WooCommerce product ID.
	 * @param int    $quantity   Quantity added to cart.
	 * @param string $event_id The unique event ID.
	 *
	 * @return void
	 */
	public function track_add_to_cart( int $product_id, int $quantity, string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new AddToCartEvent( $product_id, $quantity );
		$payload = $event->build_payload(
			array(
				'conversion_id' => $event_id,
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		as_enqueue_async_action(
			Helper::with_prefix( 'send_conversion_event' ),
			array(
				'event_payload' => $payload,
				'args'          => array(),
			),
			Config::PLUGIN_SLUG
		);
	}

	/**
	 * Tracks a view content event.
	 *
	 * This method should be called when a Single Product page is viewed.
	 * It extracts the product ID and event ID from the request body, builds a ViewContentEvent
	 * payload, and send the event immediately via the Conversions API using the internal
	 * `send()` method.
	 *
	 * The payload includes basic product information, deduplication ID, and user identifiers
	 * for improved event matching and attribution.
	 *
	 * Unlike critical events such as purchases or checkout starts, view content are considered
	 * low-impact and are dispatched directly without enqueuing in Action Scheduler.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $product_id WooCommerce product ID being viewed.
	 * @param string $event_id   The unique event ID used for deduplication.
	 *
	 * @return void
	 */
	public function track_view_content( int $product_id, string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new ViewContentEvent( $product_id );
		$payload = $event->build_payload(
			array(
				'conversion_id' => $event_id,
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$this->send( $payload );
	}

	/**
	 * Tracks a generic page view event.
	 *
	 * This method should be called when any frontend page (e.g., homepage, category,
	 * blog post) is viewed by a user. It instantiates a {@see PageVisitEvent} object,
	 * constructs the event payload, and sends it immediately via the Conversions API
	 * using the internal `send()` method.
	 *
	 * The payload includes contextual user metadata and an optional deduplication identifier
	 * (`event_id`) to align with a corresponding client-side pixel event.
	 *
	 * Unlike critical events such as purchases or checkout starts, page view events are considered
	 * low-impact and are dispatched directly without enqueuing in Action Scheduler.
	 *
	 * @since 0.1.0
	 *
	 * @param string $event_id Optional unique event ID for deduplication.
	 * @return void
	 */
	public function track_page_view( string $event_id = '' ): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$event   = new PageVisitEvent();
		$payload = $event->build_payload(
			array(
				'conversion_id' => $event_id,
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$this->send( $payload );
	}

	/**
	 * Sends a previously built payload to the Ad Partner Conversions API via WCS.
	 *
	 * This method is intended to be triggered asynchronously by Action Scheduler
	 * using the `send_conversion_event` hook. It retrieves the required pixel ID and
	 * access token from plugin options, adds user-level metadata (e.g. IP and user agent),
	 * and sends the payload to the Conversions API through the WCS proxy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $event_payload Single event payload.
	 * @param array               $args          Additional args.
	 * @return void
	 */
	public function send( array $event_payload, array $args = array() ): void {
		$token    = Options::get( OptionDefaults::CONVERSION_ACCESS_TOKEN );
		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $token || ! $pixel_id ) {
			return;
		}

		$path    = sprintf( '/conversions/events/%s', rawurldecode( $pixel_id ) );
		$payload = array( 'events' => array( $event_payload ) );

		/* @var WP_REST_Response|WP_Error $response The response from the WCS proxy. */
		$response = $this->client->proxy_post(
			$path,
			$payload,
			false,
			array(
				'reddit-authorization' => sprintf( 'Bearer %s', $token ),
			)
		);

		if ( Helper::is_logging_enabled() ) {
			$event = $event_payload['event_type']['tracking_type'] ?? '';

			if ( is_wp_error( $response ) ) {
				$error_data = $response->get_error_data();
				$status     = $error_data['response']['code'];
				$message    = $error_data['response']['message'];

				$info = array(
					'context' => 'tracking',
					'payload' => $payload,
					'args'    => $args,
					'error'   => $message,
				);
			} else {
				$status = $response->get_status();
				$info   = array(
					'context' => 'tracking',
					'payload' => $payload,
					'args'    => $args,
				);
			}

			$this->logger->log_event(
				$event,
				$status,
				$info
			);
		}

		/**
		 * Fires after a conversion event has been sent to the Ad Partner.
		 *
		 * This hook allows other plugins or custom code to perform actions after the conversion payload
		 * has been dispatched, such as logging, triggering additional integrations, or updating metadata.
		 *
		 * @since 0.1.0
		 *
		 * @param array $event_payload The payload that was sent to the Ad Partner.
		 * @param array $args          Additional args.
		 */
		do_action( Helper::with_prefix( 'conversion_sent' ), $event_payload, $args );
	}
}
