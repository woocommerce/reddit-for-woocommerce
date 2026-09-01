<?php
/**
 * Tests for RemoteConversionTracker::send() logging behaviour.
 *
 * Failed conversion events must be recorded even when debug logging
 * is off, so lost events are visible to support instead of being silently dropped.
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
	 * A non-2xx response is logged even without debug mode enabled.
	 */
	public function test_send_logs_failed_event_without_debug(): void {
		$this->assertFalse(
			defined( 'REDDIT_FOR_WOOCOMMERCE_DEBUG' ) && REDDIT_FOR_WOOCOMMERCE_DEBUG,
			'This test asserts default (non-debug) behaviour.'
		);

		$client = $this->createMock( WcsClient::class );
		$client->method( 'proxy_post' )->willReturn( new WP_REST_Response( array(), 400 ) );

		$logger = $this->createMock( ConversionEventLogger::class );
		$logger->expects( $this->once() )
			->method( 'log_event' )
			->with( 'PAGE_VISIT', 400, $this->anything() );

		( new RemoteConversionTracker( $client, $logger ) )
			->send( array( 'data' => array() ), array( 'event' => 'PAGE_VISIT' ) );
	}

	/**
	 * A successful (2xx) response is not logged when debug mode is off, to avoid
	 * flooding the logs on normal traffic.
	 */
	public function test_send_does_not_log_success_without_debug(): void {
		$client = $this->createMock( WcsClient::class );
		$client->method( 'proxy_post' )->willReturn( new WP_REST_Response( array(), 200 ) );

		$logger = $this->createMock( ConversionEventLogger::class );
		$logger->expects( $this->never() )->method( 'log_event' );

		( new RemoteConversionTracker( $client, $logger ) )
			->send( array( 'data' => array() ), array( 'event' => 'PAGE_VISIT' ) );
	}
}
