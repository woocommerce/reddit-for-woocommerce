<?php
/**
 * Order attribution data helper for the Edit Order admin screen.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Provides helpers for determining whether the current Edit Order screen
 * is attributed to Reddit.
 *
 * @since 0.1.0
 */
class OrderAttributionData {

	/**
	 * Returns true when the current admin screen is the WooCommerce Edit Order screen.
	 *
	 * Guards against a null return from get_current_screen(), which can occur before
	 * the current_screen action fires. Delegates to OrderUtil::is_order_edit_screen()
	 * which handles both HPOS (woocommerce_page_wc-orders) and legacy (shop_order) screens.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_wc_order_edit_screen(): bool {
		if ( null === get_current_screen() ) {
			return false;
		}
		return OrderUtil::is_order_edit_screen( 'shop_order' );
	}

	/**
	 * Returns 'reddit' when the order currently being edited is attributed to Reddit.
	 *
	 * Returns null in every other case — not on the edit order screen, missing or
	 * invalid order ID, deleted/trashed order, attribution meta absent or any value
	 * other than the exact string 'reddit'.
	 *
	 * @since 0.1.0
	 *
	 * @return string|null
	 */
	public function get_order_attribution_source_for_edit_screen(): ?string {
		if ( ! $this->is_wc_order_edit_screen() ) {
			return null;
		}

		$order_id = $this->get_order_id_from_request();
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$source = $order->get_meta( '_wc_order_attribution_utm_source', true );

		return 'reddit' === $source ? 'reddit' : null;
	}

	/**
	 * Resolves the order ID from the current request.
	 *
	 * Checks the 'id' parameter first (HPOS), then falls back to 'post' (legacy posts storage).
	 * Uses filter_input() rather than direct $_GET access to satisfy PHPCS
	 * WordPress.Security.NonceVerification rules without suppression comments.
	 *
	 * @since 0.1.0
	 *
	 * @return int Zero when no valid order ID is present.
	 */
	protected function get_order_id_from_request(): int {
		$order_id = absint( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ) );
		if ( ! $order_id ) {
			$order_id = absint( filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT ) );
		}
		return $order_id;
	}
}
