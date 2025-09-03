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

		// Enable pixel tracking.
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );

		// Provide a dummy ad account ID for API path construction.
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'fake-account-id' );

		add_filter( Helper::with_prefix( 'filter_pixel_script' ), array( $this, 'mock_script' ) );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::PIXEL_ENABLED );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		remove_filter( Helper::with_prefix( 'filter_pixel_script' ), array( $this, 'mock_script' ) );

		parent::tear_down();
	}

	public function mock_script() {
		return '<script src="https://redditstatic.net/scevent.min.js"></script>';
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
}
