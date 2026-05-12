<?php
/**
 * Order edit screen and WooCommerce order attribution helpers.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * WC Edit Order screen detection and Reddit order-attribution context.
 *
 * @since 0.1.0
 */
final class OrderAttributionData {

	/**
	 * Whether the current admin request is the WooCommerce order edit screen (HPOS or CPT).
	 *
	 * Requires {@see is_admin()} so this is not treated as an order screen on the front end or in CLI.
	 * PHPUnit coverage that calls {@see OrderUtil::is_order_edit_screen()} should define `WP_ADMIN` and
	 * run after {@see set_current_screen()} (see {@see MetaBoxAssetsTest::ensure_admin_request_context_for_tests()}).
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_wc_order_edit_screen(): bool {
		if ( ! is_admin() || ! class_exists( OrderUtil::class ) ) {
			return false;
		}

		return OrderUtil::is_order_edit_screen();
	}

	/**
	 * Resolves the ID of the order being edited in admin, if any.
	 *
	 * @since 0.1.0
	 *
	 * @return int|null
	 */
	public static function get_editing_order_id(): ?int {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing context.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( isset( $_GET['action'], $_GET['id'] )
			&& 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) )
			&& ( 'wc-orders' === $page || 0 === strpos( $page, 'wc-orders--' ) ) ) {
			$id = absint( wp_unslash( $_GET['id'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return $id > 0 ? $id : null;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post'], $_GET['action'] ) ) {
			$post_id = absint( wp_unslash( $_GET['post'] ) );
			$action  = sanitize_text_field( wp_unslash( $_GET['action'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $post_id > 0 && 'edit' === $action && 'shop_order' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		global $post;

		if ( $post && 'shop_order' === $post->post_type ) {
			return (int) $post->ID;
		}

		return null;
	}

	/**
	 * Reddit-specific order attribution source for the order currently being edited.
	 *
	 * @since 0.1.0
	 *
	 * @return string|null 'reddit' when attribution matches Reddit traffic, null otherwise.
	 */
	public static function get_order_attribution_source(): ?string {
		$order_id = self::get_editing_order_id();

		if ( ! $order_id ) {
			return null;
		}

		return self::get_order_attribution_source_for_order( $order_id );
	}

	/**
	 * Reads WooCommerce order attribution meta and returns Reddit when it applies.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id Order ID.
	 * @return string|null 'reddit' or null.
	 */
	public static function get_order_attribution_source_for_order( int $order_id ): ?string {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		$field_prefix = (string) apply_filters(
			'wc_order_attribution_tracking_field_prefix',
			'wc_order_attribution_'
		);

		$field_prefix = trim( $field_prefix, '_' );
		$meta_prefix  = '_' . $field_prefix . '_';

		$utm_source = strtolower( (string) $order->get_meta( $meta_prefix . 'utm_source', true ) );
		$referrer   = (string) $order->get_meta( $meta_prefix . 'referrer', true );

		if ( '' !== $utm_source && str_contains( $utm_source, 'reddit' ) ) {
			return 'reddit';
		}

		if ( '' !== $referrer && 1 === preg_match( '/reddit\.com/i', $referrer ) ) {
			return 'reddit';
		}

		return null;
	}
}
