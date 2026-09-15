<?php
/**
 * Registers the Channel Visibility meta box on the Edit Product screen.
 *
 * Supports two modes:
 * - Cohabit: Google for WooCommerce is active and has already registered its own
 *   Channel visibility meta box; Reddit's settings are injected into that box via React.
 * - Standalone: Reddit registers its own Channel visibility sidebar meta box.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use RedditForWooCommerce\Utils\Helper;

/**
 * Handles Channel visibility meta box registration and product meta persistence.
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
		add_action( 'add_meta_boxes', array( $this, 'maybe_register' ), 999, 2 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
	}

	/**
	 * Registers the Channel visibility meta box only when GLA's box is absent.
	 *
	 * Runs at priority 999 so GLA has already registered at its default priority.
	 * In cohabit mode the React component injects Reddit's settings into GLA's box;
	 * no second meta box should appear. In standalone mode Reddit owns the box.
	 *
	 * @since 0.1.0
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

		if ( $this->gla_box_registered( (array) $wp_meta_boxes ) ) {
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
	 * Checks whether GLA's channel_visibility meta box is registered for the product screen.
	 *
	 * @since 0.1.0
	 *
	 * @param array $wp_meta_boxes Global meta boxes registry.
	 * @return bool
	 */
	private function gla_box_registered( array $wp_meta_boxes ): bool {
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
	 * @since 0.1.0
	 * @return void
	 */
	public function render(): void {
		echo '<div id="reddit-channel-visibility-box"></div>';
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
