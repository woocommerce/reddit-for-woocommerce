<?php
/**
 * Unit tests for the ConversionTrackingService class.
 *
 * Validates how the service resolves the add-to-cart event ID before delegating
 * to the tracker, in particular the server-side fallback used by the classic
 * `?add-to-cart=ID` GET flow, which carries no client-generated event ID.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking
 */

namespace RedditForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use RedditForWooCommerce\Tracking\ConversionTrackingService;
use RedditForWooCommerce\Tracking\ConversionTrackerInterface;
use RedditForWooCommerce\Utils\Helper;

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionTrackingService
 */
class ConversionTrackingServiceTest extends WP_UnitTestCase {

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
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->tracker_mock = $this->createMock( ConversionTrackerInterface::class );
		$this->service      = new ConversionTrackingService( $this->tracker_mock );
	}

	/**
	 * Cleans up request superglobals modified during a test.
	 */
	public function tear_down(): void {
		unset( $_POST[ Helper::with_prefix( 'event_id' ) ] );

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
}
