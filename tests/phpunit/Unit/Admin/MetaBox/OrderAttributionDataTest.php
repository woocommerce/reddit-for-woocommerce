<?php
/**
 * Unit tests for OrderAttributionData.
 *
 * Note: test_hpos_path_returns_reddit and test_legacy_path_returns_reddit appear identical
 * because get_order_id_from_request() is overridden in both. filter_input( INPUT_GET, ... )
 * always returns null in CLI (PHPUnit), so the HPOS (?id=) vs legacy (?post=) URL-param
 * branching inside that method cannot be exercised at unit-test level. The two cases are
 * kept as separate tests to document intent against the acceptance criteria; full end-to-end
 * coverage of both URL params requires an E2E or integration test.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\MetaBox\OrderAttributionData;

/**
 * @covers \RedditForWooCommerce\Admin\MetaBox\OrderAttributionData
 */
class OrderAttributionDataTest extends WP_UnitTestCase {

	/**
	 * Creates a real WC order with the given utm_source attribution meta.
	 *
	 * @param string $utm_source Value to store in _wc_order_attribution_utm_source.
	 * @return \WC_Order
	 */
	private function create_order_with_attribution( string $utm_source ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', $utm_source );
		$order->save();
		return $order;
	}

	/**
	 * Returns an anonymous subclass of OrderAttributionData with controlled screen and order ID.
	 *
	 * @param int  $mock_order_id Value returned by get_order_id_from_request().
	 * @param bool $mock_screen   Value returned by is_wc_order_edit_screen().
	 * @return OrderAttributionData
	 */
	private function create_testable_attribution_data( int $mock_order_id, bool $mock_screen ): OrderAttributionData {
		return new class( $mock_order_id, $mock_screen ) extends OrderAttributionData {
			private int $mock_order_id;
			private bool $mock_screen;

			public function __construct( int $mock_order_id, bool $mock_screen ) {
				$this->mock_order_id = $mock_order_id;
				$this->mock_screen   = $mock_screen;
			}

			public function is_wc_order_edit_screen(): bool {
				return $this->mock_screen;
			}

			protected function get_order_id_from_request(): int {
				return $this->mock_order_id;
			}
		};
	}

	/**
	 * HPOS path: simulates the order ID arriving via the ?id= query parameter.
	 */
	public function test_hpos_path_returns_reddit(): void {
		$order            = $this->create_order_with_attribution( 'reddit' );
		$attribution_data = $this->create_testable_attribution_data( $order->get_id(), true );

		$this->assertSame( 'reddit', $attribution_data->get_order_attribution_source_for_edit_screen() );
	}

	/**
	 * Legacy path: simulates the order ID arriving via the ?post= query parameter.
	 */
	public function test_legacy_path_returns_reddit(): void {
		$order            = $this->create_order_with_attribution( 'reddit' );
		$attribution_data = $this->create_testable_attribution_data( $order->get_id(), true );

		$this->assertSame( 'reddit', $attribution_data->get_order_attribution_source_for_edit_screen() );
	}

	/**
	 * A non-reddit utm_source must return null.
	 */
	public function test_non_reddit_utm_source_returns_null(): void {
		$order            = $this->create_order_with_attribution( 'google' );
		$attribution_data = $this->create_testable_attribution_data( $order->get_id(), true );

		$this->assertNull( $attribution_data->get_order_attribution_source_for_edit_screen() );
	}

	/**
	 * No order ID in the request must return null even when on the correct screen.
	 */
	public function test_no_order_id_returns_null(): void {
		$attribution_data = $this->create_testable_attribution_data( 0, true );

		$this->assertNull( $attribution_data->get_order_attribution_source_for_edit_screen() );
	}

	/**
	 * A valid reddit-attributed order must still return null when not on the edit order screen.
	 */
	public function test_wrong_screen_returns_null(): void {
		$order            = $this->create_order_with_attribution( 'reddit' );
		$attribution_data = $this->create_testable_attribution_data( $order->get_id(), false );

		$this->assertNull( $attribution_data->get_order_attribution_source_for_edit_screen() );
	}
}
