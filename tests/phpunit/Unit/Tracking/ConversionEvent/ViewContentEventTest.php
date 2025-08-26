<?php

use PHPUnit\Framework\TestCase;
use RedditForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;
use RedditForWooCommerce\Utils\UserIdentifier;

class ViewContentEventTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
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

		$this->assertSame( 'ViewContent', $payload['event_type']['tracking_type'] );
		$this->assertSame( 'abc_123', $payload['event_metadata']['conversion_id'] );
		$this->assertArrayHasKey( 'event_at', $payload );
		$this->assertEquals( array( array( 'id' => $product->get_id(), 'name' => $product->get_name() ) ), $payload['event_metadata']['products'] );
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
