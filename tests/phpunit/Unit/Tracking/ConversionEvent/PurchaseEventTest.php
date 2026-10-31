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
			array( 'id' => $product_one->get_id(), 'name' => $product_one->get_name(), 'item_price' => 20.0, 'quantity' => 1 ),
			array( 'id' => $product_two->get_id(), 'name' => $product_two->get_name(), 'item_price' => 15.0, 'quantity' => 2 ),
		), $metadata['products'] );
	}

	/**
	 * item_price must use the post-discount, tax-inclusive unit price actually
	 * charged on the order line, not the product's current catalog price.
	 */
	public function test_build_payload_computes_post_discount_taxed_item_price(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product = new WC_Product_Simple();
		$product->set_name( 'Discounted Taxed Product' );
		$product->set_regular_price( 20 );
		$product->set_tax_status( 'taxable' );
		$product->set_tax_class( '' );
		$product->save();

		$order = wc_create_order( array( 'status' => 'pending' ) );

		// Quantity of 2 at a subtotal of 40 (pre-discount), discounted to a
		// total of 30 (as a coupon would), taxed at 20% on the discounted total.
		$order->add_product( $product, 2 );

		foreach ( $order->get_items() as $item ) {
			$item->set_total( 30 );
			$item->save();
		}

		$order->calculate_taxes();
		$order->calculate_totals( false );
		$order->save();

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id' => $order->get_order_key(),
			'user_data'     => UserIdentifier::get_user_data(),
		) );

		$metadata = $payload['data']['events'][0]['metadata'];

		// item_price = ( total 30 + total_tax 6 ) / quantity 2 = 18.0.
		$this->assertEquals(
			array(
				array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'item_price' => 18.0, 'quantity' => 2 ),
			),
			$metadata['products']
		);
	}

	/**
	 * A zero-quantity order line must not attempt a division and instead
	 * falls back to an item_price of 0.0.
	 */
	public function test_build_payload_zero_quantity_line_falls_back_to_zero_item_price(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Zero Quantity Product' );
		$product->set_regular_price( 20 );
		$product->save();

		$order = wc_create_order( array( 'status' => 'pending' ) );

		$item = new \WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 0 );
		$item->set_subtotal( 0 );
		$item->set_total( 0 );
		$order->add_item( $item );
		$order->save();

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id' => $order->get_order_key(),
			'user_data'     => UserIdentifier::get_user_data(),
		) );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertEquals(
			array(
				array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'item_price' => 0.0, 'quantity' => 0 ),
			),
			$metadata['products']
		);
	}

	/**
	 * item_price is rounded to wc_get_price_decimals() places so a division
	 * that doesn't divide evenly doesn't emit a long floating-point tail.
	 */
	public function test_build_payload_rounds_item_price_to_price_decimals(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Non-Dividing Product' );
		$product->set_regular_price( 10 );
		$product->save();

		$order = wc_create_order( array( 'status' => 'pending' ) );

		$order->add_product(
			$product,
			3,
			array(
				'subtotal'  => 10,
				'total'     => 10,
				'total_tax' => 0,
			)
		);
		$order->calculate_totals( false );

		$event   = new PurchaseEvent( $order->get_id() );
		$payload = $event->build_payload( array(
			'conversion_id' => $order->get_order_key(),
			'user_data'     => UserIdentifier::get_user_data(),
		) );

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( round( 10 / 3, wc_get_price_decimals() ), $metadata['products'][0]['item_price'] );
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
}
