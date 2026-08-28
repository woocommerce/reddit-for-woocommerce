<?php
/**
 * Integration tests for the RemotePixelTracker class.
 *
 * These tests validate that pixel injection behaves correctly based on plugin settings,
 * including caching, fallbacks, and integration with the WCS proxy layer.
 *
 * @package RedditForWooCommerce\Tests\Integration\Tracking
 */

namespace RedditForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use WC_Product_Simple;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Tracking\RemotePixelTracker;
use RedditForWooCommerce\Connection\WcsClient;

/**
 * @covers \RedditForWooCommerce\Tracking\RemotePixelTracker
 */
class RemotePixelTrackerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		// wp_scripts() is a global whose inline-script data accumulates across
		// tests in the same process. Reset it so each test observes only the
		// scripts it queues, and inline payloads don't leak between tests.
		$GLOBALS['wp_scripts'] = null;

		// Enable pixel tracking.
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-123' );

		// Provide a dummy ad account ID for API path construction.
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'fake-account-id' );

		add_filter( Helper::with_prefix( 'filter_pixel_script' ), array( $this, 'mock_script' ) );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ENABLED );
		Options::delete( OptionDefaults::PIXEL_ID );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		remove_filter( Helper::with_prefix( 'filter_pixel_script' ), array( $this, 'mock_script' ) );

		parent::tear_down();
	}

	public function mock_script() {
		return '<script src="https://redditstatic.net/scevent.min.js"></script>';
	}

	/**
	 * Builds a real order on the order-received page and invokes the purchase
	 * tracker, returning the order so callers can assert on its side effects.
	 *
	 * @param string|null $request_key Value to expose as `$_GET['key']`, or null to omit it.
	 * @return \WC_Order The created order.
	 */
	private function invoke_track_purchase_with_key( ?string $request_key ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Tracked Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = wc_create_order( array( 'status' => 'processing' ) );
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		// Force the order-received page conditions without a full request.
		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		set_query_var( 'order-received', $order->get_id() );

		// Register the handle the tracker attaches its inline script to, so
		// wp_add_inline_script() has somewhere to record it.
		$handle = \RedditForWooCommerce\Config::ASSET_HANDLE_PREFIX . 'tracking';
		wp_register_script( $handle, 'https://example.test/tracking.js', array(), '1.0.0', true );

		if ( null === $request_key ) {
			unset( $_GET['key'] );
		} else {
			$_GET['key'] = $request_key;
		}

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );
		$tracker->track_purchase_event();

		unset( $_GET['key'] );
		set_query_var( 'order-received', '' );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

		// Re-read the order so meta written during tracking is reflected.
		return wc_get_order( $order->get_id() );
	}

	/**
	 * Returns the inline script data queued against the tracking handle.
	 *
	 * @return string
	 */
	private function get_tracking_inline_script(): string {
		$handle = \RedditForWooCommerce\Config::ASSET_HANDLE_PREFIX . 'tracking';
		$data   = wp_scripts()->get_data( $handle, 'after' );

		return is_array( $data ) ? implode( '', $data ) : (string) $data;
	}

	/**
	 * A valid order key authorizes the purchase event: the order is marked
	 * tracked and the Purchase pixel is emitted with the order details.
	 */
	public function test_track_purchase_event_fires_with_valid_order_key() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Tracked Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = wc_create_order( array( 'status' => 'processing' ) );
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		set_query_var( 'order-received', $order->get_id() );
		$handle = \RedditForWooCommerce\Config::ASSET_HANDLE_PREFIX . 'tracking';
		wp_register_script( $handle, 'https://example.test/tracking.js', array(), '1.0.0', true );

		$_GET['key'] = $order->get_order_key();

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );
		$tracker->track_purchase_event();

		$tracked_order = wc_get_order( $order->get_id() );
		$inline        = $this->get_tracking_inline_script();

		unset( $_GET['key'] );
		set_query_var( 'order-received', '' );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$this->assertSame( 1, (int) $tracked_order->get_meta( '_reddit_pixel_tracked', true ) );
		$this->assertStringContainsString( 'rdt("track", "Purchase"', $inline );
		$this->assertStringContainsString( $order->get_order_key(), $inline );
	}

	/**
	 * A missing order key must not expose the order: no Purchase pixel is
	 * emitted and the order is not marked tracked.
	 */
	public function test_track_purchase_event_skips_when_order_key_missing() {
		$order = $this->invoke_track_purchase_with_key( null );

		$this->assertSame( '', (string) $order->get_meta( '_reddit_pixel_tracked', true ) );
		$this->assertStringNotContainsString( 'rdt("track", "Purchase"', $this->get_tracking_inline_script() );
	}

	/**
	 * An incorrect order key must not expose the order: no Purchase pixel is
	 * emitted and the order is not marked tracked.
	 */
	public function test_track_purchase_event_skips_when_order_key_invalid() {
		$order = $this->invoke_track_purchase_with_key( 'wc_order_totally-wrong-key' );

		$this->assertSame( '', (string) $order->get_meta( '_reddit_pixel_tracked', true ) );
		$this->assertStringNotContainsString( 'rdt("track", "Purchase"', $this->get_tracking_inline_script() );
	}

	/**
	 * An array-valued key (e.g. `?key[]=x`) must be rejected safely rather than
	 * reaching hash_equals() with a non-string and raising an error.
	 */
	public function test_track_purchase_event_skips_when_order_key_is_array() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Tracked Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = wc_create_order( array( 'status' => 'processing' ) );
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );
		set_query_var( 'order-received', $order->get_id() );
		$handle = \RedditForWooCommerce\Config::ASSET_HANDLE_PREFIX . 'tracking';
		wp_register_script( $handle, 'https://example.test/tracking.js', array(), '1.0.0', true );

		$_GET['key'] = array( $order->get_order_key() );

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );
		$tracker->track_purchase_event();

		$tracked_order = wc_get_order( $order->get_id() );

		unset( $_GET['key'] );
		set_query_var( 'order-received', '' );
		remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

		$this->assertSame( '', (string) $tracked_order->get_meta( '_reddit_pixel_tracked', true ) );
	}

	/**
	 * Test that the pixel script is rendered from cache if present.
	 */
	public function test_maybe_inject_pixel_outputs_cached_script() {
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script', $output );
		$this->assertStringContainsString( 'scevent.min.js', $output );
	}

	public function test_maybe_inject_pixel_outputs_partner_config_in_generated_script() {
		remove_filter( Helper::with_prefix( 'filter_pixel_script' ), array( $this, 'mock_script' ) );

		$wcs     = $this->createMock( WcsClient::class );
		$tracker = new RemotePixelTracker( $wcs );

		ob_start();
		$tracker->maybe_inject_pixel();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'https://www.redditstatic.com/ads/pixel.js?pixel_id=pixel-123', $output );
		$this->assertStringContainsString( 'rdt(\'init\', "pixel-123", {', $output );
		$this->assertStringContainsString( '"partner":"WOOCOMMERCE"', $output );
		$this->assertStringContainsString(
			'"partner_version":"' . REDDIT_FOR_WOOCOMMERCE_VERSION . '"',
			$output
		);
	}
}
