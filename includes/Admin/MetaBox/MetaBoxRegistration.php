<?php
/**
 * Registers standalone Reddit meta boxes on product and order edit screens
 * when third-party plugins (e.g. Google Listings & Ads) that normally provide
 * the mount containers are inactive.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Provides fallback PHP meta boxes so JS bundles always have a DOM mount point.
 *
 * When GLA is active it supplies:
 *  - `#channel_visibility .inside`  → channel-visibility bundle mount point on Edit Product.
 *  - The WooCommerce order-attribution panel enhancements → order-attribution bundle mount
 *    point on Edit Order.
 *
 * When GLA is inactive those elements are absent and the JS bundles return early without
 * rendering anything. This class registers lightweight Reddit meta boxes in their place so
 * the JS always has a container to mount into.
 *
 * @since 0.1.0
 */
class MetaBoxRegistration {

	/**
	 * GLA plugin basename used for active-plugin detection.
	 *
	 * @since 0.1.0
	 */
	const GLA_PLUGIN = 'google-listings-and-ads/google-listings-and-ads.php';

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'maybe_register_product_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'maybe_register_order_meta_box' ) );
	}

	/**
	 * Registers a Reddit meta box on the product edit screen when GLA is inactive.
	 *
	 * GLA's "Channel visibility" meta box (`#channel_visibility`) is the default mount
	 * container used by the channel-visibility JS bundle. When GLA is inactive that
	 * element is absent, so we register our own box which renders the
	 * `#reddit-channel-visibility-box` div the JS already looks for first.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_register_product_meta_box(): void {
		if ( $this->is_gla_active() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || 'product' !== $screen->id ) {
			return;
		}

		add_meta_box(
			'reddit-channel-visibility',
			__( 'Reddit', 'reddit-for-woocommerce' ),
			array( $this, 'render_channel_visibility_meta_box' ),
			'product',
			'side'
		);
	}

	/**
	 * Registers a Reddit meta box on the order edit screen when GLA is inactive.
	 *
	 * When GLA is inactive the order attribution panel enhancements that the
	 * order-attribution JS bundle hooks into are not rendered. This meta box
	 * provides the `#reddit-order-attribution-box` div the JS falls back to.
	 *
	 * Registered on both the CPT (`shop_order`) and HPOS (`woocommerce_page_wc-orders`)
	 * order edit screens.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_register_order_meta_box(): void {
		if ( $this->is_gla_active() ) {
			return;
		}

		if ( ! class_exists( OrderUtil::class ) || ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		add_meta_box(
			'reddit-order-attribution',
			__( 'Reddit', 'reddit-for-woocommerce' ),
			array( $this, 'render_order_attribution_meta_box' ),
			array( 'shop_order', 'woocommerce_page_wc-orders' ),
			'side'
		);
	}

	/**
	 * Renders the channel-visibility meta box content.
	 *
	 * The `#reddit-channel-visibility-box` div is the primary mount point checked by
	 * the channel-visibility JS bundle (js/src/meta-boxes/channel-visibility/index.js).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render_channel_visibility_meta_box(): void {
		printf( '<div id="%s"></div>', esc_attr( 'reddit-channel-visibility-box' ) );
	}

	/**
	 * Renders the order-attribution meta box content.
	 *
	 * The `#reddit-order-attribution-box` div is the fallback mount point checked by
	 * the order-attribution JS bundle (js/src/meta-boxes/order-attribution/index.js).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render_order_attribution_meta_box(): void {
		printf( '<div id="%s"></div>', esc_attr( 'reddit-order-attribution-box' ) );
	}

	/**
	 * Whether Google Listings & Ads is currently active.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function is_gla_active(): bool {
		return function_exists( 'is_plugin_active' ) && is_plugin_active( self::GLA_PLUGIN );
	}
}
