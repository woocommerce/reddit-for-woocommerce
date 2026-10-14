<?php
/**
 * Registers the Channel Visibility meta box on the Edit Product screen.
 *
 * Supports two modes:
 * - Cohabit: another extension has already registered a `channel_visibility`
 *   meta box; Reddit's settings are injected into that box via React.
 * - Standalone: Reddit registers its own Channel visibility sidebar meta box.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 1.0.7
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use RedditForWooCommerce\Utils\Helper;

/**
 * Handles Channel visibility meta box registration and product meta persistence.
 *
 * @since 1.0.7
 */
class ChannelVisibilityMetaBox {

	/**
	 * Meta key controlling whether a product is eligible for catalog export.
	 *
	 * When set to '1' the product is included in catalog generation.
	 * When set to '0' or absent the product is excluded.
	 *
	 * @since 1.0.7
	 */
	public const CATALOG_ITEM = 'product_catalog_item';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'maybe_register' ), 999, 2 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
	}

	/**
	 * Registers the Channel visibility meta box only when no shared `channel_visibility` box exists.
	 *
	 * Runs at priority 999 so any extension registering at the default priority has already done so.
	 * In cohabit mode the React component injects Reddit's settings into the shared box;
	 * no second meta box should appear. In standalone mode Reddit owns the box.
	 *
	 * @since 1.0.7
	 *
	 * @param string   $post_type Current post type.
	 * @param \WP_Post $post      Current post object.
	 * @return void
	 */
	public function maybe_register( string $post_type, $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'product' !== $post_type ) {
			return;
		}

		global $wp_meta_boxes;

		if ( $this->shared_box_registered( (array) $wp_meta_boxes ) ) {
			return;
		}

		add_meta_box(
			'reddit-channel-visibility',
			__( 'Channel visibility', 'reddit-for-woocommerce' ),
			array( $this, 'render' ),
			'product',
			'side'
		);
	}

	/**
	 * Checks whether a `channel_visibility` meta box is registered for the product screen.
	 *
	 * @since 1.0.7
	 *
	 * @param array $wp_meta_boxes Global meta boxes registry.
	 * @return bool
	 */
	private function shared_box_registered( array $wp_meta_boxes ): bool {
		if ( empty( $wp_meta_boxes['product'] ) ) {
			return false;
		}

		foreach ( $wp_meta_boxes['product'] as $contexts ) {
			foreach ( $contexts as $priorities ) {
				if ( array_key_exists( 'channel_visibility', $priorities ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Renders the React mount point for the Channel visibility widget.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public function render(): void {
		// `data-service` lets each extension sharing this meta box place its box alphabetically
		// (see insertAlphabetically() in js/src/meta-boxes/channel-visibility/utils.js).
		echo '<div id="reddit-channel-visibility-box" data-service="reddit"></div>';
	}

	/**
	 * Saves the catalog item meta when a product is saved.
	 *
	 * Mode-agnostic: runs regardless of whether Reddit owns the meta box or shares another extension's,
	 * because the form field lives in the same <form id="post"> either way.
	 *
	 * @since 1.0.7
	 *
	 * @param int $post_id The product post ID.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		$meta_key = Helper::with_prefix( self::CATALOG_ITEM );

		// The Channel Visibility field is absent from the submission before
		// onboarding completes, when only the promo banner shows, and while the
		// product is hidden from the store catalog. In that case leave the existing
		// opt-in untouched rather than resetting it, so an unrelated product save
		// (changing a price, say) doesn't silently flip the merchant's sync
		// preference to "Don't sync and show".
		// Nonce verification is handled upstream by WooCommerce Core.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $meta_key ] ) ) {
			return;
		}

		// Nonce verification is handled upstream by WooCommerce Core.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = '1' === $_POST[ $meta_key ];

		update_post_meta( $post_id, $meta_key, $enabled ? '1' : '0' );
	}
}
