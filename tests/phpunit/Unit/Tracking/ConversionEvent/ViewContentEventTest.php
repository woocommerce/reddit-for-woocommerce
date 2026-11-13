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
		$this->assertArrayHasKey( 'partner_version', $payload['data'] );
		$this->assertArrayHasKey( 'events', $payload['data'] );
		$this->assertSame( REDDIT_FOR_WOOCOMMERCE_VERSION, $payload['data']['partner_version'] );

		$events = $payload['data']['events'][0];

		$this->assertArrayHasKey( 'event_at', $events );
		$this->assertSame( 'WEBSITE', $events['action_source'] );
		$this->assertSame( 'VIEW_CONTENT', $events['type']['tracking_type'] );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( 'abc_123', $metadata['conversion_id'] );
		$this->assertEquals( array( array( 'id' => $product->get_id(), 'name' => $product->get_name() ) ), $metadata['products'] );
	}

	/**
	 * The hashed customer match keys flow through to events[].user alongside the
	 * existing device identifiers, and the click ID stays at the event level.
	 */
	public function test_build_payload_passes_match_keys_to_event_user() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Simple Product' );
		$product->set_price( 10.00 );
		$product->save();

		$user_data = array(
			'user'     => array(
				'ip_address'   => '203.0.113.10',
				'user_agent'   => 'UA',
				'uuid'         => 'pixel-uuid',
				'email'        => hash( 'sha256', 'member@example.com' ),
				'phone_number' => hash( 'sha256', '+14155550111' ),
				'external_id'  => hash( 'sha256', '7' ),
			),
			'click_id' => 'click-id',
		);

		$event   = new ViewContentEvent( $product->get_id() );
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'abc_123',
				'user_data'     => $user_data,
			)
		);

		$events = $payload['data']['events'][0];

		$this->assertSame( $user_data['user'], $events['user'] );
		$this->assertSame( 'click-id', $events['click_id'] );
	}
}
