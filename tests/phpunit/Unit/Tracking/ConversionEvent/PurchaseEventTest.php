<?php
/**
 * Tests for the PurchaseEvent class.
 *
 * @package RedditForWooCommerce\Tests\Integration\Tracking\ConversionEvent
 */

namespace RedditForWooCommerce\Tests\Integration\Tracking\ConversionEvent;

use WC_Product_Simple;
use RedditForWooCommerce\Utils\UserIdentifier;
use RedditForWooCommerce\Tracking\ConversionEvent\PurchaseEvent;
use WP_UnitTestCase;

require_once 'Utils.php';

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionEvent\PurchaseEvent
 */
class PurchaseEventTest extends WP_UnitTestCase {

	/**
	 * Set up environment for the test.
	 */
	public function set_up(): void {
		parent::set_up();
		r4w_setup_globals();
		$_SERVER['HTTP_REFERER'] = 'https://example.com/order-complete';
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		r4w_destroy_globals();
		parent::tear_down();
	}

	/**
	 * Tests that the payload contains valid purchase data.
	 */
	public function test_build_payload_contains_expected_fields(): void {
		// Create and save a simple product.
		$product_one = new WC_Product_Simple();
		$product_one->set_name( 'Product One' );
		$product_one->set_regular_price( 20 );
		$product_one->save();

		$product_two = new WC_Product_Simple();
		$product_two->set_name( 'Product Two' );
		$product_two->set_regular_price( 15 );
		$product_two->save();

		$order = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => 1,
			)
		);

		$order->add_product( $product_one, 1 );
		$order->add_product( $product_two, 2 );

		// Optionally, add shipping manually
		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( 'Flat Rate' );
		$shipping_item->set_method_id( 'flat_rate' );
		$shipping_item->set_total( 10 ); // shipping cost
		$order->add_item( $shipping_item );

		// Finalize order totals
		$order->calculate_totals();

		// Build the payload.
		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id' => $order->get_order_key(),
			'user_data'     => UserIdentifier::get_user_data(),
		) );

		$this->assertIsArray( $payload );

		$this->assertArrayHasKey( 'data', $payload );
		$this->assertArrayHasKey( 'partner', $payload['data'] );
		$this->assertArrayHasKey( 'partner_version', $payload['data'] );
		$this->assertArrayHasKey( 'events', $payload['data'] );
		$this->assertSame( REDDIT_FOR_WOOCOMMERCE_VERSION, $payload['data']['partner_version'] );

		$events = $payload['data']['events'][0];

		$this->assertArrayHasKey( 'event_at', $events );
		$this->assertSame( 'WEBSITE', $events['action_source'] );
		$this->assertSame( 'PURCHASE', $events['type']['tracking_type'] );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( $order->get_order_key(), $metadata['conversion_id'] );
		$this->assertSame( 3, $metadata['item_count'] );
		$this->assertSame( floatval( $order->get_total() ), $metadata['value'] );
		$this->assertSame( 'USD', $metadata['currency'] );
		$this->assertArrayHasKey( 'event_at', $events );
		$this->assertEquals( array(
			array( 'id' => $product_one->get_id(), 'name' => $product_one->get_name() ),
			array( 'id' => $product_two->get_id(), 'name' => $product_two->get_name() ),
		), $metadata['products'] );
	}

	/**
	 * The event_source_url, when supplied, is added at the event level (alongside
	 * click_id and user), not inside metadata.
	 */
	public function test_build_payload_includes_event_source_url_when_provided(): void {
		$order = wc_create_order( array( 'status' => 'pending' ) );

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id'    => $order->get_order_key(),
			'user_data'        => UserIdentifier::get_user_data(),
			'event_source_url' => 'https://example.com/checkout/order-received/42/?rdt_cid=abc',
		) );

		$events = $payload['data']['events'][0];

		$this->assertSame(
			'https://example.com/checkout/order-received/42/?rdt_cid=abc',
			$events['event_source_url']
		);
		$this->assertArrayNotHasKey( 'event_source_url', $events['metadata'] );
	}

	/**
	 * The event_source_url key is omitted entirely when no URL is supplied, so an
	 * empty value is never sent to Reddit.
	 */
	public function test_build_payload_omits_event_source_url_when_absent(): void {
		$order = wc_create_order( array( 'status' => 'pending' ) );

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id' => $order->get_order_key(),
			'user_data'     => UserIdentifier::get_user_data(),
		) );

		$this->assertArrayNotHasKey( 'event_source_url', $payload['data']['events'][0] );
	}

	/**
	 * The hashed customer match keys flow through to events[].user alongside the
	 * existing device identifiers, and the click ID stays at the event level.
	 */
	public function test_build_payload_passes_match_keys_to_event_user(): void {
		$order = wc_create_order( array( 'status' => 'pending' ) );

		$user_data = array(
			'user'     => array(
				'ip_address'   => '203.0.113.10',
				'user_agent'   => 'UA',
				'uuid'         => 'pixel-uuid',
				'email'        => hash( 'sha256', 'buyer@example.com' ),
				'phone_number' => hash( 'sha256', '+14155550100' ),
				'external_id'  => hash( 'sha256', '42' ),
			),
			'click_id' => 'click-id',
		);

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload(
			array(
				'conversion_id' => $order->get_order_key(),
				'user_data'     => $user_data,
			)
		);

		$events = $payload['data']['events'][0];

		$this->assertSame( $user_data['user'], $events['user'] );
		$this->assertSame( 'click-id', $events['click_id'] );
	}
}
