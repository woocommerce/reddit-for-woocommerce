<?php
/**
 * Tests for ConversionTrackingService AJAX handlers.
 *
 * These focus on REDTWOO-158: the public tracking beacons must process events
 * even when no valid nonce is present (e.g. a request from a page served from a
 * cache older than the nonce lifetime).
 *
 * @package RedditForWooCommerce\Tests\Integration\Tracking
 */

namespace RedditForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use RedditForWooCommerce\Tracking\ConversionTrackingService;
use RedditForWooCommerce\Tracking\ConversionTrackerInterface;

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionTrackingService
 */
class ConversionTrackingServiceTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $_POST['payload'], $_POST['security'], $_REQUEST['security'] );
		parent::tear_down();
	}

	/**
	 * A PageVisit beacon with no nonce still reaches the tracker.
	 *
	 * Before REDTWOO-158 the handler called check_ajax_referer(), which would
	 * wp_die() here and fail the test.
	 */
	public function test_page_view_processes_without_nonce(): void {
		$_POST['payload'] = wp_slash( wp_json_encode( array( 'conversionId' => 'evt-page' ) ) );

		$tracker = $this->createMock( ConversionTrackerInterface::class );
		$tracker->expects( $this->once() )
			->method( 'track_page_view' )
			->with( 'evt-page' );

		( new ConversionTrackingService( $tracker ) )->handle_async_page_view();
	}

	/**
	 * A ViewContent beacon with no nonce still reaches the tracker.
	 */
	public function test_view_content_processes_without_nonce(): void {
		$_POST['payload'] = wp_slash( wp_json_encode( array(
			'products'     => array( 'id' => 123 ),
			'conversionId' => 'evt-view',
		) ) );

		$tracker = $this->createMock( ConversionTrackerInterface::class );
		$tracker->expects( $this->once() )
			->method( 'track_view_content' )
			->with( 123, 'evt-view' );

		( new ConversionTrackingService( $tracker ) )->handle_async_view_content();
	}

	/**
	 * An AddToCart beacon with no nonce still reaches the tracker.
	 */
	public function test_add_to_cart_processes_without_nonce(): void {
		$_POST['payload'] = wp_slash( wp_json_encode( array(
			'productId'    => 55,
			'quantity'     => 2,
			'conversionId' => 'evt-cart',
		) ) );

		$tracker = $this->createMock( ConversionTrackerInterface::class );
		$tracker->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( 55, 2, 'evt-cart' );

		( new ConversionTrackingService( $tracker ) )->handle_async_add_to_cart();
	}
}
