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

		$order         = wc_get_order( $order_id );
		$track_states  = array( 'queued', 'tracked' );
		$current_state = $order->get_meta( self::ORDER_CONVERSION_TRACKED_META_KEY, true );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || in_array( $current_state, $track_states, true ) ) {
			return;
		} else {
			// Mark the order as queued for tracking.
			$order->update_meta_data( self::ORDER_CONVERSION_TRACKED_META_KEY, 'queued' );
			$order->save_meta_data();
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
				'args'          => array( 'event' => PurchaseEvent::ID ),
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
				'args'          => array( 'event' => AddToCartEvent::ID ),
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

		$this->send( $payload, array( 'event' => ViewContentEvent::ID ) );
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

		$this->send( $payload, array( 'event' => PageVisitEvent::ID ) );
	}

	/**
	 * Tracks a dummy purchase event during the onboarding process with latest order or sample data.
	 *
	 * This method is called just before the Reddit onboarding process is marked as completed.
	 * As we want to send this as early as possible, during the onboarding process.
	 *
	 * This method is used to track a dummy purchase event with latest order or sample data during onboarding,
	 * to enable the "PURCHASE" optimization goal for the ad campaign.
	 *
	 * Instantiates a {@see PurchaseEvent} object using the latest order ID,
	 * generates a valid API payload, and sends it immediately to the Ad Partner
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function track_dummy_purchase(): void {
		// Bail if the dummy purchase has already been tracked.
		if ( 'yes' === Options::get( OptionDefaults::DUMMY_PURCHASE_TRACKED ) ) {
			return;
		}

		// Get latest order.
		$orders = wc_get_orders(
			array(
				'status'  => array( 'processing', 'completed', 'on-hold' ), // Paid statuses.
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);

		$latest_order_id = ! empty( $orders ) ? $orders[0] : 0;

		$latest_order = wc_get_order( $latest_order_id );
		$event        = new PurchaseEvent( $latest_order_id );
		$payload      = $event->build_payload(
			array(
				'conversion_id' => $latest_order ? $latest_order->get_order_key() : wp_generate_uuid4(),
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		// Trigger the send_conversion_event action to immediately send the payload to the Ad Partner.
		do_action(
			Helper::with_prefix( 'send_conversion_event' ),
			$payload,
			array( 'event' => PurchaseEvent::ID )
		);

		// Mark the dummy purchase as tracked.
		Options::set( OptionDefaults::DUMMY_PURCHASE_TRACKED, 'yes' );
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
		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $pixel_id ) {
			return;
		}

		$path = sprintf( '/ads/pixels/%s/conversion_events', rawurldecode( $pixel_id ) );

		/* @var WP_REST_Response|WP_Error $response The response from the WCS proxy. */
		$response = $this->client->proxy_post(
			$path,
			$event_payload,
			false
		);

		if ( Helper::is_logging_enabled() ) {
			$event = $args['event'] ?? 'unknown_event';

			if ( is_wp_error( $response ) ) {
				$body       = json_decode( wp_remote_retrieve_body( $response->get_error_data() ), true );
				$error_data = $response->get_error_data();
				$status     = $error_data['response']['code'];
				$message    = $response->get_error_message();
				$body       = Helper::deep_replace_double_quotes( $body );

				$info = array(
					'context'    => 'tracking',
					'payload'    => $event_payload,
					'args'       => $args,
					'error'      => $message,
					'error_data' => $body,
				);
			} else {
				$status = $response->get_status();
				$info   = array(
					'context' => 'tracking',
					'payload' => $event_payload,
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
