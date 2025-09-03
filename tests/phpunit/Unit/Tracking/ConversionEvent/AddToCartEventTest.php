<?php
/**
 * Unit test for AddToCartEvent class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent;

use WP_UnitTestCase;
use WC_Product_Simple;
use RedditForWooCommerce\Tracking\ConversionEvent\AddToCartEvent;
use RedditForWooCommerce\Utils\UserIdentifier;

require_once 'Utils.php';

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionEvent\AddToCartEvent
 */
final class AddToCartEventTest extends WP_UnitTestCase {

	/**
	 * Sample quantity.
	 *
	 * @var int
	 */
	protected $quantity = 3;

	/**
	 * Sets a referer URL before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		r4w_setup_globals();
	}

	/**
	 * Unsets referer after test.
	 */
	public function tear_down(): void {
		r4w_destroy_globals();
		parent::tear_down();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_returns_expected_data(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Product One' );
		$product->set_regular_price( 20 );
		$product->save();

		$event   = new AddToCartEvent( $product->get_id(), $this->quantity );
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'abc_123',
				'user_data'     => UserIdentifier::get_user_data()
			)
		);

		$this->assertIsArray( $payload );

		$this->assertSame( 'AddToCart', $payload['event_type']['tracking_type'] );
		$this->assertSame( 'abc_123', $payload['event_metadata']['conversion_id'] );
		$this->assertSame( $this->quantity, $payload['event_metadata']['item_count'] );
		$this->assertSame( $this->quantity * floatval( $product->get_regular_price( 20 ) ), $payload['event_metadata']['value_decimal'] );
		$this->assertSame( 'USD', $payload['event_metadata']['currency'] );
		$this->assertArrayHasKey( 'event_at', $payload );
		$this->assertEquals( array( array( 'id' => $product->get_id(), 'name' => $product->get_name() ) ), $payload['event_metadata']['products'] );
	}
}
