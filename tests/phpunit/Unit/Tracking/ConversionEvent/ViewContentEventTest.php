<?php
/**
 * Unit test for ViewContentEvent class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent;

use WP_UnitTestCase;
use WC_Product_Simple;
use RedditForWooCommerce\Tracking\ConversionEvent\ViewContentEvent;
use RedditForWooCommerce\Utils\UserIdentifier;

require_once 'Utils.php';

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionEvent\ViewContentEvent
 */
final class ViewContentEventTest extends WP_UnitTestCase {

	/**
	 * Sets up environment for the test.
	 */
	public function set_up(): void {
		parent::set_up();
		r4w_setup_globals();
	}

	/**
	 * Tears down after the test.
	 */
	public function tear_down(): void {
		r4w_destroy_globals();
		parent::tear_down();
	}

	/**
	 * Test that build_payload() returns expected structure and values.
	 */
	public function test_build_payload_for_simple_product(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Simple Product' );
		$product->set_sku( 'SIMPLE-SKU' );
		$product->set_regular_price( 10.00 );
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
		$this->assertSame( 'USD', $metadata['currency'] );
		$this->assertSame( 10.00, $metadata['value'] );
		$this->assertSame( 1, $metadata['item_count'] );
		$this->assertEquals(
			array(
				array(
					'id'         => $product->get_id(),
					'name'       => $product->get_name(),
					'item_price' => 10.00,
					'quantity'   => 1,
				),
			),
			$metadata['products']
		);
	}

	/**
	 * Test that item_price/value follow the store's tax-display configuration.
	 */
	public function test_build_payload_respects_tax_display_configuration(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );

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
		$product->set_name( 'Taxed Product' );
		$product->set_regular_price( 10.00 );
		$product->set_tax_status( 'taxable' );
		$product->set_tax_class( '' );
		$product->save();

		$expected_price = wc_get_price_to_display( $product );

		$event   = new ViewContentEvent( $product->get_id() );
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'abc_123',
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( 12.0, $expected_price );
		$this->assertSame( $expected_price, $metadata['value'] );
		$this->assertSame( $expected_price, $metadata['products'][0]['item_price'] );
	}

	/**
	 * Test that an invalid product still yields the zero/empty payload shape.
	 */
	public function test_build_payload_for_invalid_product(): void {
		$event   = new ViewContentEvent( 0 );
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'abc_123',
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$metadata = $payload['data']['events'][0]['metadata'];

		$this->assertSame( 0.0, $metadata['value'] );
		$this->assertSame( 1, $metadata['item_count'] );
		$this->assertSame( array(), $metadata['products'] );
	}
}
