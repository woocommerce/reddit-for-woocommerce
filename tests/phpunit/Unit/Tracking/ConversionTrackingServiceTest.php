<?php
/**
 * Unit tests for the ConversionTrackingService class.
 *
 * Validates how the service resolves the add-to-cart event ID before delegating
 * to the tracker, in particular the server-side fallback used by the classic
 * `?add-to-cart=ID` GET flow, which carries no client-generated event ID, and
 * how the public AJAX tracking endpoints reject invalid requests.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking
 */

namespace RedditForWooCommerce\Tests\Unit\Tracking;

use RuntimeException;
use WP_UnitTestCase;
use RedditForWooCommerce\Tracking\ConversionTrackingService;
use RedditForWooCommerce\Tracking\ConversionTrackerInterface;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionTrackingService
 */
class ConversionTrackingServiceTest extends WP_UnitTestCase {

	/**
	 * Conversion ID used by valid AJAX tracking requests.
	 */
	private const CONVERSION_ID = '0f8fad5b-d9cb-4c3a-a3b0-1e7a4c2d9f01';

	/**
	 * Public AJAX tracking handlers, keyed by the tracker method each one calls.
	 */
	private const ASYNC_HANDLERS = array(
		'track_add_to_cart'  => 'handle_async_add_to_cart',
		'track_view_content' => 'handle_async_view_content',
		'track_page_view'    => 'handle_async_page_view',
	);

	/**
	 * Mocked ConversionTrackerInterface instance.
	 *
	 * @var ConversionTrackerInterface
	 */
	private $tracker_mock;

	/**
	 * Service under test.
	 *
	 * @var ConversionTrackingService
	 */
	private $service;

	/**
	 * `$_SERVER` entries the rate-limit tests change, as they were before the test.
	 *
	 * @var array
	 */
	private $original_server = array();

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->tracker_mock = $this->createMock( ConversionTrackerInterface::class );
		$this->service      = $this->create_service( $this->tracker_mock );

		foreach ( array( 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' ) as $key ) {
			$this->original_server[ $key ] = $_SERVER[ $key ] ?? null;
		}

		Options::set( OptionDefaults::PIXEL_ID, 'pixel-id' );
		$_REQUEST['security'] = wp_create_nonce( 'capi_nonce' );
	}

	/**
	 * Cleans up request superglobals modified during a test.
	 */
	public function tear_down(): void {
		unset( $_POST[ Helper::with_prefix( 'event_id' ) ], $_POST['payload'], $_REQUEST['security'] );
		Options::delete( OptionDefaults::PIXEL_ID );

		foreach ( $this->original_server as $key => $value ) {
			if ( null === $value ) {
				unset( $_SERVER[ $key ] );
			} else {
				$_SERVER[ $key ] = $value;
			}
		}

		parent::tear_down();
	}

	/**
	 * A synchronous add-to-cart with no submitted event ID (the `?add-to-cart=ID`
	 * GET flow) generates a server-side UUID so the conversion ID is never empty.
	 */
	public function test_add_to_cart_without_event_id_falls_back_to_generated_uuid() {
		$uuid_pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with(
				$this->identicalTo( 145 ),
				$this->identicalTo( 1 ),
				$this->matchesRegularExpression( $uuid_pattern )
			);

		$this->service->handle_single_product_add_to_cart( 'cart_item_key', 145, 1, 0 );
	}

	/**
	 * A submitted client event ID is passed through unchanged (single-product form
	 * and any flow that posts the hidden field), preserving Pixel/CAPI deduplication.
	 */
	public function test_add_to_cart_with_event_id_passes_it_through() {
		$_POST[ Helper::with_prefix( 'event_id' ) ] = 'client-generated-id';

		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with(
				$this->identicalTo( 145 ),
				$this->identicalTo( 1 ),
				$this->identicalTo( 'client-generated-id' )
			);

		$this->service->handle_single_product_add_to_cart( 'cart_item_key', 145, 1, 0 );
	}

	/**
	 * When a variation is added, the variation ID is tracked instead of the parent ID.
	 */
	public function test_add_to_cart_prefers_variation_id() {
		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with(
				$this->identicalTo( 200 ),
				$this->identicalTo( 2 ),
				$this->isType( 'string' )
			);

		$this->service->handle_single_product_add_to_cart( 'cart_item_key', 145, 2, 200 );
	}

	/**
	 * Valid requests from the plugin's tracking script reach the tracker with the
	 * product, quantity and conversion ID they carry.
	 */
	public function test_valid_async_requests_are_tracked() {
		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_add_to_cart' )
			->with( $this->identicalTo( 145 ), $this->identicalTo( 1 ), $this->identicalTo( self::CONVERSION_ID ) );
		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_view_content' )
			->with( $this->identicalTo( 145 ), $this->identicalTo( self::CONVERSION_ID ) );
		$this->tracker_mock
			->expects( $this->once() )
			->method( 'track_page_view' )
			->with( $this->identicalTo( self::CONVERSION_ID ) );

		$this->set_payload(
			array(
				'productId'    => 145,
				'quantity'     => 1,
				'conversionId' => self::CONVERSION_ID,
			)
		);
		$this->service->handle_async_add_to_cart();

		$this->set_payload(
			array(
				'products'     => array( 'id' => 145 ),
				'conversionId' => self::CONVERSION_ID,
			)
		);
		$this->service->handle_async_view_content();

		$this->set_payload( array( 'conversionId' => self::CONVERSION_ID ) );
		$this->service->handle_async_page_view();
	}

	/**
	 * With no pixel configured, each endpoint rejects the request before tracking.
	 */
	public function test_async_requests_are_rejected_without_pixel_id() {
		Options::set( OptionDefaults::PIXEL_ID, '' );
		$this->set_payload( array( 'conversionId' => self::CONVERSION_ID ) );

		$this->assert_all_handlers_reject_with( 400 );
	}

	/**
	 * Requests whose payload doesn't match what the tracking script sends are
	 * rejected by every endpoint before tracking.
	 *
	 * @dataProvider invalid_payload_provider
	 *
	 * @param mixed $payload Raw `payload` request field.
	 */
	public function test_invalid_payloads_are_rejected( $payload ) {
		if ( null === $payload ) {
			unset( $_POST['payload'] );
		} else {
			$_POST['payload'] = is_string( $payload ) ? wp_slash( $payload ) : $payload;
		}

		$this->assert_all_handlers_reject_with( 400 );
	}

	/**
	 * Invalid raw `payload` fields.
	 *
	 * @return array
	 */
	public function invalid_payload_provider(): array {
		return array(
			'missing payload'       => array( null ),
			'payload is an array'   => array( array( 'conversionId' => self::CONVERSION_ID ) ),
			'oversized payload'     => array(
				wp_json_encode(
					array(
						'conversionId' => self::CONVERSION_ID,
						'padding'      => str_repeat( 'a', 4096 ),
					)
				),
			),
			'invalid JSON'          => array( '{"conversionId":' ),
			'JSON is not an object' => array( '"' . self::CONVERSION_ID . '"' ),
			'missing conversion ID' => array( '{"productId":145}' ),
			'non-UUID conversion ID' => array( '{"conversionId":"not-a-uuid"}' ),
			'non-string conversion ID' => array( '{"conversionId":123}' ),
		);
	}

	/**
	 * One client can make at most 30 requests per window, after which it gets a 429.
	 */
	public function test_client_is_rate_limited_after_30_requests() {
		$this->tracker_mock->expects( $this->exactly( 30 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 30; $i++ ) {
			$this->assertSame( 0, $this->send_page_view( '203.0.113.1' ) );
		}

		$this->assertSame( 429, $this->send_page_view( '203.0.113.1' ) );
	}

	/**
	 * Changing forwarding headers on each request doesn't give the client a new allowance.
	 */
	public function test_forwarding_headers_do_not_bypass_client_limit() {
		$this->tracker_mock->expects( $this->exactly( 30 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 31; $i++ ) {
			$_SERVER['HTTP_X_FORWARDED_FOR'] = "198.51.100.{$i}";
			$_SERVER['HTTP_X_REAL_IP']       = "198.51.100.{$i}";

			$status = $this->send_page_view( '203.0.113.1' );
		}

		$this->assertSame( 429, $status );
	}

	/**
	 * Requests without a valid `REMOTE_ADDR` share one client allowance.
	 */
	public function test_requests_without_valid_remote_addr_share_one_allowance() {
		$this->tracker_mock->expects( $this->exactly( 30 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 30; $i++ ) {
			$this->assertSame( 0, $this->send_page_view( 0 === $i % 2 ? null : 'not-an-ip' ) );
		}

		$this->assertSame( 429, $this->send_page_view( null ) );
	}

	/**
	 * The whole store accepts at most 300 requests per window, across all clients.
	 */
	public function test_store_is_rate_limited_after_300_requests() {
		$this->tracker_mock->expects( $this->exactly( 300 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 300; $i++ ) {
			$this->assertSame( 0, $this->send_page_view( '203.0.113.' . intdiv( $i, 30 ) ) );
		}

		$this->assertSame( 429, $this->send_page_view( '203.0.113.200' ) );
	}

	/**
	 * Requests a client makes over its own limit don't use up the store-wide allowance.
	 */
	public function test_client_over_its_limit_does_not_use_store_allowance() {
		$this->tracker_mock->expects( $this->exactly( 300 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 100; $i++ ) {
			$this->send_page_view( '203.0.113.1' );
		}

		for ( $i = 0; $i < 270; $i++ ) {
			$this->assertSame( 0, $this->send_page_view( '198.51.100.' . intdiv( $i, 30 ) ) );
		}
	}

	/**
	 * Requests rejected as invalid don't count toward the rate limits.
	 */
	public function test_invalid_requests_do_not_count_toward_rate_limits() {
		$this->tracker_mock->expects( $this->once() )->method( 'track_page_view' );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.1';

		for ( $i = 0; $i < 30; $i++ ) {
			$this->set_payload( array( 'conversionId' => 'not-a-uuid' ) );

			try {
				$this->service->handle_async_page_view();
			} catch ( RuntimeException $e ) {
				$this->assertSame( 400, $e->getCode() );
			}
		}

		$this->assertSame( 0, $this->send_page_view( '203.0.113.1' ) );
	}

	/**
	 * A client that hit its limit gets a new allowance in the next window.
	 */
	public function test_rate_limit_resets_in_next_window() {
		$this->tracker_mock->expects( $this->exactly( 31 ) )->method( 'track_page_view' );

		for ( $i = 0; $i < 30; $i++ ) {
			$this->send_page_view( '203.0.113.1' );
		}

		$this->assertSame( 429, $this->send_page_view( '203.0.113.1' ) );

		++$this->service->window;

		$this->assertSame( 0, $this->send_page_view( '203.0.113.1' ) );
	}

	/**
	 * Sends a valid PageVisit request from the given `REMOTE_ADDR`.
	 *
	 * @param string|null $remote_addr Client address, or null to leave it unset.
	 * @return int 0 when the request was accepted, otherwise the rejection status code.
	 */
	private function send_page_view( ?string $remote_addr ): int {
		if ( null === $remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $remote_addr;
		}

		$this->set_payload( array( 'conversionId' => self::CONVERSION_ID ) );

		try {
			$this->service->handle_async_page_view();
		} catch ( RuntimeException $e ) {
			return $e->getCode();
		}

		return 0;
	}

	/**
	 * Asserts that every public AJAX handler rejects the current request with the
	 * given status code and never calls the tracker.
	 *
	 * @param int $expected_status Expected HTTP status code.
	 */
	private function assert_all_handlers_reject_with( int $expected_status ): void {
		foreach ( self::ASYNC_HANDLERS as $tracker_method => $handler ) {
			$tracker = $this->createMock( ConversionTrackerInterface::class );
			$tracker->expects( $this->never() )->method( $tracker_method );

			try {
				$this->create_service( $tracker )->{$handler}();
				$this->fail( "{$handler}() did not reject the request." );
			} catch ( RuntimeException $e ) {
				$this->assertSame( $expected_status, $e->getCode(), "{$handler}() rejected with the wrong status." );
			}
		}
	}

	/**
	 * Creates the service under test, with rejected requests surfaced as an
	 * exception carrying the status code instead of ending the PHP process, and
	 * the rate-limit window fixed so tests don't depend on the clock.
	 *
	 * @param ConversionTrackerInterface $tracker Tracker the service delegates to.
	 * @return ConversionTrackingService
	 */
	private function create_service( ConversionTrackerInterface $tracker ): ConversionTrackingService {
		return new class( $tracker ) extends ConversionTrackingService {
			/**
			 * Current rate-limit window.
			 *
			 * @var int
			 */
			public $window = 1;

			protected function reject_request( int $status_code ): void {
				throw new RuntimeException( 'Request rejected.', $status_code );
			}

			protected function get_rate_limit_window(): int {
				return $this->window;
			}
		};
	}

	/**
	 * Sets the `payload` request field the way the tracking script sends it.
	 *
	 * @param array $data Payload data.
	 */
	private function set_payload( array $data ): void {
		$_POST['payload'] = wp_slash( wp_json_encode( $data ) );
	}
}
