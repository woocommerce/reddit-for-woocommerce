<?php

use PHPUnit\Framework\TestCase;
use RedditForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;
use RedditForWooCommerce\Utils\UserIdentifier;

require_once 'Utils.php';

class ViewContentEventTest extends TestCase {

	/**
	 * Set up environment for the test.
	 */
	protected function setUp(): void {
		r4w_setup_globals();
		parent::setUp();
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		r4w_destroy_globals();
		parent::tear_down();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_for_simple_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Simple Product' );
		$product->set_sku( 'SIMPLE-SKU' );
		$product->set_price( 10.00 );
		$product->save();

		$event   = new ViewContentEvent( $product->get_id() );
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'abc_123',
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$this->assertIsArray( $payload );

		$this->assertArrayHasKey( 'data', $payload );
		$this->assertArrayHasKey( 'partner', $payload['data'] );
		$this->assertArrayHasKey( 'events', $payload['data'] );

		$events = $payload['data']['events'][0];

		$this->assertArrayHasKey( 'event_at', $events );
		$this->assertSame( 'WEBSITE', $events['action_source'] );
		$this->assertSame( 'VIEW_CONTENT', $events['type']['tracking_type'] );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( 'abc_123', $metadata['conversion_id'] );
		$this->assertEquals( array( array( 'id' => $product->get_id(), 'name' => $product->get_name() ) ), $metadata['products'] );
	}

	/**
	 * Test that build_payload() returns empty array for non-existent product.
	 */
	public function test_build_payload_for_invalid_product_returns_empty_array() {
		$invalid_product_id = 999999; // ID that doesn't exist
		$event              = new ViewContentEvent( $invalid_product_id );
		$payload            = $event->build_payload();

		$this->assertEmpty( $payload );
	}
}
