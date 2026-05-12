<?php
/**
 * Tests for WooCommerce order edit screen helpers and Reddit attribution detection.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\MetaBox\OrderAttributionData;

/**
 * @covers \RedditForWooCommerce\Admin\MetaBox\OrderAttributionData
 */
final class OrderAttributionDataTest extends WP_UnitTestCase {

	public function test_get_order_attribution_source_for_order_returns_reddit_when_utm_contains_reddit(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'reddit_ads' );
		$order->save();

		$this->assertSame(
			'reddit',
			OrderAttributionData::get_order_attribution_source_for_order( $order->get_id() )
		);
	}

	public function test_get_order_attribution_source_for_order_returns_null_for_non_matching_utm(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_utm_source', 'google' );
		$order->save();

		$this->assertNull(
			OrderAttributionData::get_order_attribution_source_for_order( $order->get_id() )
		);
	}

	public function test_get_order_attribution_source_for_order_via_reddit_referrer_meta(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wc_order_attribution_referrer', 'https://www.reddit.com/r/example' );
		$order->save();

		$this->assertSame(
			'reddit',
			OrderAttributionData::get_order_attribution_source_for_order( $order->get_id() )
		);
	}

	public function test_get_editing_order_id_recognizes_hpos_subtype_menu_page_slug(): void {
		$get_backup = $_GET;

		try {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$_GET = array(
				'page'   => 'wc-orders--bookable',
				'action' => 'edit',
				'id'     => '42',
			);
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$this->assertSame( 42, OrderAttributionData::get_editing_order_id() );
		} finally {
			$_GET = $get_backup;
		}
	}
}
