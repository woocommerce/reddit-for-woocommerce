<?php
/**
 * Tests for WcsClient non-blocking dispatch (REDTWOO-157).
 *
 * @package RedditForWooCommerce\Tests\Integration\Connection
 */

namespace RedditForWooCommerce\Tests\Integration\Connection;

use WP_UnitTestCase;
use WP_REST_Response;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Connection\JetpackClient;
use RedditForWooCommerce\Connection\JetpackAuthenticator;

/**
 * @covers \RedditForWooCommerce\Connection\WcsClient
 */
class WcsClientTest extends WP_UnitTestCase {

	/**
	 * A non-blocking POST passes blocking=false to the transport and returns a
	 * stub 202 without waiting for or parsing a response.
	 */
	public function test_non_blocking_post_dispatches_without_waiting(): void {
		$captured = null;

		$jetpack = $this->createMock( JetpackClient::class );
		$jetpack->expects( $this->once() )
			->method( 'remote_request' )
			->willReturnCallback( function ( $args ) use ( &$captured ) {
				$captured = $args;
				// A non-blocking wp_remote_request returns an empty array; the
				// client must not try to parse it.
				return array();
			} );

		$client = new WcsClient( $this->createMock( JetpackAuthenticator::class ), $jetpack );

		$response = $client->proxy_post( '/ads/pixels/1/conversion_events', array( 'data' => array() ), false, array(), false );

		$this->assertFalse( $captured['blocking'], 'Transport must receive blocking=false.' );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 202, $response->get_status() );
	}

	/**
	 * A blocking POST (the default) waits for the transport and parses the response.
	 */
	public function test_blocking_post_parses_response(): void {
		$jetpack = $this->createMock( JetpackClient::class );
		$jetpack->method( 'remote_request' )->willReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'ok' => true ) ),
			)
		);

		$client = new WcsClient( $this->createMock( JetpackAuthenticator::class ), $jetpack );

		$response = $client->proxy_post( '/ads/pixels/1/conversion_events', array( 'data' => array() ), false );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
	}
}
