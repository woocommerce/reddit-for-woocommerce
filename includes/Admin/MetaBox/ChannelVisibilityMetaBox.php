<?php
/**
 * Persists Channel Visibility product meta from the Edit Product screen.
 *
 * The meta box UI itself (heading, promo, settings dropdown) is registered and
 * rendered by {@see MetaBoxAssets} and its React bundle; this class only owns
 * the `product_catalog_item` meta key and saves it on product save.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use RedditForWooCommerce\Utils\Helper;

/**
 * Handles Channel visibility product meta persistence.
 *
 * @since 0.1.0
 */
class ChannelVisibilityMetaBox {

	/**
	 * Meta key controlling whether a product is eligible for catalog export.
	 *
	 * When set to '1' the product is included in catalog generation.
	 * When set to '0' or absent the product is excluded.
	 *
	 * @since 0.1.0
	 */
	public const CATALOG_ITEM = 'product_catalog_item';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
	}

	/**
	 * Saves the catalog item meta when a product is saved.
	 *
	 * Mode-agnostic: runs regardless of whether Reddit owns the meta box or GLA does,
	 * because the form field lives in the same <form id="post"> either way.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The product post ID.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		$meta_key = Helper::with_prefix( self::CATALOG_ITEM );

		// Nonce verification is handled upstream by WooCommerce Core.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST[ $meta_key ] ) && '1' === $_POST[ $meta_key ];

		update_post_meta( $post_id, $meta_key, $enabled ? '1' : '0' );
	}
}
