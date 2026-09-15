<?php
/**
 * Tests for RemoteConversionTracker dispatch behaviour (REDTWOO-157).
 *
 * PageVisit and ViewContent are high-volume beacons and must be dispatched
 * fire-and-forget (non-blocking) so a visitor's page view is never held on the
 * WCS/Reddit round trip. Purchase and AddToCart continue to go through Action
 * Scheduler and are sent blocking from that background context.
 *
 * @package RedditForWooCommerce\Tests\Integration\Tracking
 */

namespace RedditForWooCommerce\Tests\Integration\Tracking;

use WP_UnitTestCase;
use WP_REST_Response;
use RedditForWooCommerce\Tracking\RemoteConversionTracker;
use RedditForWooCommerce\Tracking\ConversionEventLogger;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Tracking\RemoteConversionTracker
 */
class RemoteConversionTrackerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-123' );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ID );
		parent::tear_down();
	}

	/**
	 * Builds a tracker whose WCS client asserts the `blocking` flag it receives.
	 *
	 * @param bool $expected_blocking Expected value of proxy_post()'s $blocking arg.
	 * @return RemoteConversionTracker
	 */
	private function tracker_expecting_blocking( bool $expected_blocking ): RemoteConversionTracker {
		$client = $this->createMock( WcsClient::class );
		$client->expects( $this->once() )
			->method( 'proxy_post' )
			->with(
				$this->anything(), // path
				$this->anything(), // payload
				false,             // requires_auth (unchanged existing behaviour)
				array(),           // headers
				$expected_blocking // blocking — the assertion under test
			)
			->willReturn( new WP_REST_Response( array(), 202 ) );

		return new RemoteConversionTracker( $client, $this->createMock( ConversionEventLogger::class ) );
	}

	/**
	 * PageVisit is dispatched non-blocking.
	 */
	public function test_page_view_dispatches_non_blocking(): void {
		$this->tracker_expecting_blocking( false )->track_page_view( 'evt-page' );
	}

	/**
	 * ViewContent is dispatched non-blocking.
	 */
	public function test_view_content_dispatches_non_blocking(): void {
		$this->tracker_expecting_blocking( false )->track_view_content( 0, 'evt-view' );
	}

	/**
	 * The Action Scheduler path (send() with defaults) stays blocking so its
	 * result can still be inspected/logged in the background context.
	 */
	public function test_send_defaults_to_blocking(): void {
		$this->tracker_expecting_blocking( true )->send(
			array( 'data' => array() ),
			array( 'event' => 'PURCHASE' )
		);
	}
}
