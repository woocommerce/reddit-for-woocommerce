<?php
/**
 * Registers and manages WooCommerce hooks for conversion tracking.
 *
 * This class integrates with core WooCommerce events (such as purchases and add-to-cart actions)
 * and delegates conversion tracking responsibilities to an underlying tracker implementation.
 *
 * It acts as a coordinator between WooCommerce events and the Ad Partner's Conversions API.
 *
 * @package RedditForWooCommerce\Tracking
 */

namespace RedditForWooCommerce\Tracking;

use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Helper;

/**
 * Service class for registering WooCommerce conversion tracking hooks.
 *
 * This class listens to WooCommerce events and triggers corresponding
 * server-side conversion tracking via the provided {@see ConversionTrackerInterface}.
 *
 * - `woocommerce_thankyou` - triggers purchase event tracking
 * - `woocommerce_add_to_cart` - triggers add-to-cart tracking
 * - Custom async action (e.g., Action Scheduler) can be used via `send_conversion_event`
 *
 * @since 0.1.0
 */
class ConversionTrackingService implements ServiceStatusInterface {

	/**
	 * Largest `payload` accepted by the public AJAX tracking endpoints, in bytes.
	 *
	 * Payloads sent by the plugin's own tracking script are well under this.
	 *
	 * @since n.e.x.t
	 *
	 * @var int
	 */
	protected const MAX_PAYLOAD_BYTES = 4096;

	/**
	 * Length of a rate-limit window for the public AJAX tracking endpoints, in seconds.
	 *
	 * @since n.e.x.t
	 *
	 * @var int
	 */
	protected const RATE_LIMIT_WINDOW = 60;

	/**
	 * Requests one client can make to the public AJAX tracking endpoints per window.
	 *
	 * @since n.e.x.t
	 *
	 * @var int
	 */
	protected const RATE_LIMIT_PER_CLIENT = 30;

	/**
	 * Requests the whole store accepts on the public AJAX tracking endpoints per window.
	 *
	 * @since n.e.x.t
	 *
	 * @var int
	 */
	protected const RATE_LIMIT_PER_STORE = 300;

	/**
	 * Instance of a class implementing conversion tracking logic.
	 *
	 * @var ConversionTrackerInterface
	 */
	protected ConversionTrackerInterface $tracker;

	/**
	 * Constructor.
	 *
	 * Accepts a concrete implementation of the {@see ConversionTrackerInterface},
	 * which is responsible for sending the actual tracking payloads to the Ad Partner API.
	 *
	 * @since 0.1.0
	 *
	 * @param ConversionTrackerInterface $tracker Tracker implementation instance.
	 */
	public function __construct( ConversionTrackerInterface $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Registers WooCommerce hooks for conversion event tracking.
	 *
	 * It attaches conversion tracking to:
	 * - Purchase events (`woocommerce_thankyou`)
	 * - Add to Cart events (`woocommerce_add_to_cart`)
	 * - Asynchronous tracking hook via Action Scheduler
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_thankyou', array( $this, 'handle_purchase' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_single_product_add_to_cart' ), 10, 4 );
		Helper::register_ajax_action( 'AddToCart', array( $this, 'handle_async_add_to_cart' ) );
		Helper::register_ajax_action( 'ViewContent', array( $this, 'handle_async_view_content' ) );
		Helper::register_ajax_action( 'PageVisit', array( $this, 'handle_async_page_view' ) );
		add_action( Helper::with_prefix( 'send_conversion_event' ), array( $this->tracker, 'send' ), 10, 2 );
		add_action( Helper::with_prefix( 'conversion_sent' ), array( $this, 'mark_as_tracked' ), 10, 2 );
		add_action( Helper::with_prefix( 'ad_account_connected' ), array( $this->tracker, 'track_dummy_purchase' ) );
	}

	/**
	 * Determines whether Reddit Conversion tracking is currently enabled.
	 *
	 * This checks the persisted plugin option configured via the admin interface or defaults.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if pixel tracking is enabled; false otherwise.
	 */
	public static function is_enabled(): bool {
		return 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED );
	}

	/**
	 * Callback for WooCommerce purchase completion events.
	 *
	 * Invoked after a customer completes checkout. Delegates to the tracker
	 * to send the order data to the Ad Partner's Conversions API.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function handle_purchase( int $order_id ): void {
		$this->tracker->track_purchase( $order_id );
	}

	/**
	 * Callback for WooCommerce add-to-cart actions.
	 *
	 * Triggered when a product is added to the cart.
	 * Passes product ID and quantity to the tracker for building the tracking payload.
	 *
	 * @since 0.1.0
	 *
	 * @param string $cart_item_key Unique key for the cart item.
	 * @param int    $product_id    WooCommerce product ID added to the cart.
	 * @param int    $quantity      Quantity of product added.
	 * @param int    $variation_id  WooCommerce product variation ID added to the cart.
	 * @return void
	 */
	public function handle_single_product_add_to_cart( string $cart_item_key, int $product_id, int $quantity, int $variation_id ): void {
		/**
		 * We only track synchronous Add to Cart events in this handler.
		 *
		 * WooCommerce provides three ways for adding products to the cart:
		 *   1. Form submission on single product pages – Standard POST to `?add-to-cart=ID`
		 *   2. AJAX-based add to cart – Typically used in archive pages or custom JS
		 *   3. REST API-based add to cart – Used by modern frontends or headless implementations
		 *
		 * All of these methods trigger the `woocommerce_add_to_cart` hook.
		 * However, we must be careful *not* to process every add-to-cart action here blindly,
		 * because this hook is also triggered for asynchronous flows (AJAX/REST) that are handled elsewhere.
		 *
		 * Reddit's deduplication logic requires the Pixel (client-side) and CAPI (server-side) events
		 * to share a *common* unique `event_id`. Without this shared ID, Reddit will treat them
		 * as duplicate events and discard one of them.
		 *
		 * For synchronous form submissions (on single product pages), we inject an `event_id` hidden field
		 * via `render_event_id_field()`, and also add inline JavaScript that assigns a freshly generated
		 * `crypto.randomUUID()` into the field. This ensures every click on the Add to Cart button
		 * generates a new unique ID, even though the page reloads afterward.
		 *
		 * For asynchronous flows (AJAX or REST), this logic is different. There is *no* page reload,
		 * so the same field-based injection strategy doesn't apply. In these cases, the frontend JS
		 * is responsible for generating a new UUID and submitting it via an async request
		 * to the `handle_async_add_to_cart()` method.
		 *
		 * To avoid double-tracking, we skip processing async add-to-cart actions here by checking
		 * `Helper::is_request_async()`. These are already handled in their dedicated async flow.
		 */
		if ( Helper::is_request_async() ) {
			return;
		}

		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$event_id = sanitize_text_field( wp_unslash( $_POST[ Helper::with_prefix( 'event_id' ) ] ?? '' ) );

		// No client-generated event ID was submitted (the `?add-to-cart=ID` link flow and
		// other form-less add-to-cart paths), so mint a server-side UUID to keep the
		// conversion ID non-empty.
		if ( '' === $event_id ) {
			$event_id = wp_generate_uuid4();
		}

		$this->tracker->track_add_to_cart( $variation_id ? $variation_id : $product_id, $quantity, $event_id );
	}

	/**
	 * Handles AJAX-based Add to Cart requests for server-side tracking.
	 *
	 * This endpoint is called when a product is added to the cart synchronously
	 * via JavaScript.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_async_add_to_cart(): void {
		check_ajax_referer( 'capi_nonce', 'security' );

		$data = $this->get_validated_payload();

		if ( null === $data ) {
			return;
		}

		$product_id = absint( $data['productId'] ?? 0 );
		$quantity   = absint( $data['quantity'] ?? 0 );
		$event_id   = sanitize_text_field( $data['conversionId'] ?? '' );

		$this->tracker->track_add_to_cart( $product_id, $quantity, $event_id );
	}

	/**
	 * Handles asynchronous View Content tracking requests.
	 *
	 * This method is called via AJAX when a single product page is viewed.
	 * It extracts the product ID and event ID from the request and triggers
	 * the tracker.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_async_view_content(): void {
		check_ajax_referer( 'capi_nonce', 'security' );

		$data = $this->get_validated_payload();

		if ( null === $data ) {
			return;
		}

		$products   = $data['products'] ?? array();
		$product_id = absint( $products['id'] ?? 0 );
		$event_id   = sanitize_text_field( $data['conversionId'] ?? '' );

		$this->tracker->track_view_content( $product_id, $event_id );
	}

	/**
	 * Handles asynchronous Page View tracking requests.
	 *
	 * This method is called via AJAX when any page view event is triggered by frontend JavaScript.
	 * It extracts the `event_id` from the request and delegates tracking to the configured
	 * {@see ConversionTrackerInterface} implementation.
	 *
	 * This handler is used in low-priority tracking scenarios (e.g., audience seeding, general analytics)
	 * where page views are recorded. It is intended for broad usage across all site pages,
	 * including product and content pages, in an MVP setup.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_async_page_view(): void {
		check_ajax_referer( 'capi_nonce', 'security' );

		$data = $this->get_validated_payload();

		if ( null === $data ) {
			return;
		}

		$event_id = sanitize_text_field( $data['conversionId'] ?? '' );

		$this->tracker->track_page_view( $event_id );
	}

	/**
	 * Validates a public AJAX tracking request and returns its decoded payload.
	 *
	 * The tracking endpoints are open to anonymous visitors, so a request is
	 * rejected before any tracking work when the integration isn't configured,
	 * the payload doesn't match what the plugin's tracking script sends, or the
	 * client or store is over its rate limit.
	 *
	 * @since n.e.x.t
	 *
	 * @return array|null The decoded payload, or null when the request was rejected.
	 */
	protected function get_validated_payload(): ?array {
		if ( empty( Options::get( OptionDefaults::PIXEL_ID ) ) ) {
			$this->reject_request( 400 );
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The caller verifies the nonce, and each field is sanitized where it's read.
		$payload = wp_unslash( $_POST['payload'] ?? '' );

		if ( ! is_string( $payload ) || strlen( $payload ) > self::MAX_PAYLOAD_BYTES ) {
			$this->reject_request( 400 );
			return null;
		}

		$data = json_decode( $payload, true );

		if ( ! is_array( $data ) || ! wp_is_uuid( $data['conversionId'] ?? null ) ) {
			$this->reject_request( 400 );
			return null;
		}

		if ( ! $this->consume_rate_limit() ) {
			$this->reject_request( 429 );
			return null;
		}

		return $data;
	}

	/**
	 * Counts the current request toward the per-client and store-wide rate limits.
	 *
	 * The per-client limit is checked first, and a request it rejects isn't
	 * counted toward the store-wide limit. Otherwise a single client could use
	 * up the store-wide allowance and block tracking for every other visitor.
	 *
	 * Counters are keyed by window, so writing a new count doesn't extend the
	 * current window. Counts can race between simultaneous requests, which is
	 * acceptable because the limits are approximate.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True when the request is within both limits; false otherwise.
	 */
	protected function consume_rate_limit(): bool {
		$window     = $this->get_rate_limit_window();
		$client_key = Helper::with_prefix( 'capi_rl_' . wp_hash( $this->get_client_ip() ) . '_' . $window );
		$store_key  = Helper::with_prefix( 'capi_rl_store_' . $window );

		$client_count = (int) get_transient( $client_key );

		if ( $client_count >= self::RATE_LIMIT_PER_CLIENT ) {
			return false;
		}

		$store_count = (int) get_transient( $store_key );

		if ( $store_count >= self::RATE_LIMIT_PER_STORE ) {
			return false;
		}

		set_transient( $client_key, $client_count + 1, self::RATE_LIMIT_WINDOW );
		set_transient( $store_key, $store_count + 1, self::RATE_LIMIT_WINDOW );

		return true;
	}

	/**
	 * Returns the number of the current fixed rate-limit window.
	 *
	 * @since n.e.x.t
	 *
	 * @return int
	 */
	protected function get_rate_limit_window(): int {
		return (int) floor( time() / self::RATE_LIMIT_WINDOW );
	}

	/**
	 * Returns the IP address that identifies the client for rate limiting.
	 *
	 * Only `REMOTE_ADDR` is used. Forwarding headers such as `X-Forwarded-For`
	 * can be set by any client, so trusting them would give an attacker a fresh
	 * allowance on every request. Requests without a valid `REMOTE_ADDR` share
	 * one allowance rather than being rejected, and still count toward the
	 * store-wide limit.
	 *
	 * @since n.e.x.t
	 *
	 * @return string
	 */
	protected function get_client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return rest_is_ip_address( $ip ) ? $ip : 'unknown';
	}

	/**
	 * Ends a rejected public AJAX tracking request with a JSON error response.
	 *
	 * @since n.e.x.t
	 *
	 * @param int $status_code HTTP status code to send.
	 * @return void
	 */
	protected function reject_request( int $status_code ): void {
		wp_send_json_error( null, $status_code );
	}

	/**
	 * Marks the given order as tracked to prevent duplicate conversion reporting.
	 *
	 * This should be called after a successful conversion event has been sent
	 * to the Ad Partner. It updates the order meta with a flag to indicate
	 * that the conversion has already been tracked.
	 *
	 * @since 0.1.0
	 *
	 * @param array $event_payload The payload that was sent to the Ad Partner.
	 * @param array $args {
	 *     Additional arguments passed to the tracking action.
	 *
	 *     @type int $order_id The ID of the WooCommerce order to mark as tracked.
	 * }
	 */
	public function mark_as_tracked( $event_payload, $args ) {
		if ( empty( $args['order_id'] ) ) {
			return;
		}

		$order = wc_get_order( $args['order_id'] );

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( RemoteConversionTracker::ORDER_CONVERSION_TRACKED_META_KEY, 'tracked' );
		$order->save_meta_data();
	}
}
