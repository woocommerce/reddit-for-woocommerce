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

		$this->assertArrayHasKey( 'data', $payload );
		$this->assertArrayHasKey( 'partner', $payload['data'] );
		$this->assertArrayHasKey( 'partner_version', $payload['data'] );
		$this->assertArrayHasKey( 'events', $payload['data'] );
		$this->assertSame( REDDIT_FOR_WOOCOMMERCE_VERSION, $payload['data']['partner_version'] );

		$events = $payload['data']['events'][0];

		$this->assertArrayHasKey( 'event_at', $events );
		$this->assertSame( 'WEBSITE', $events['action_source'] );
		$this->assertSame( 'ADD_TO_CART', $events['type']['tracking_type'] );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( 'abc_123', $metadata['conversion_id'] );
		$this->assertSame( $this->quantity, $metadata['item_count'] );
		$this->assertSame( $this->quantity * floatval( $product->get_regular_price( 20 ) ), $metadata['value'] );
		$this->assertSame( 'USD', $metadata['currency'] );
		$this->assertEquals( array( array( 'id' => $product->get_id(), 'name' => $product->get_name() ) ), $metadata['products'] );
	}
}
