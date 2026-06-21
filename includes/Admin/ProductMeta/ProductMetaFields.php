<?php
/**
 * Adds Reddit tab and exportable checkbox to WooCommerce product editor.
 *
 * This integration allows store admins to explicitly include or exclude
 * individual products from Reddit’s product catalog export.
 *
 * @package RedditForWooCommerce\Admin\ProductMeta
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\ProductMeta;

use RedditForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
use RedditForWooCommerce\Utils\Helper;

/**
 * Registers the "Reddit" tab and checkbox in the WooCommerce product editor.
 *
 * This allows merchants to designate which products should be included
 * in the Reddit product catalog via a simple checkbox UI.
 *
 * @since 0.1.0
 */
class ProductMetaFields {

	/**
	 * Meta key used to determine if a product is eligible for export.
	 *
	 * When this custom post meta is set to true (or a truthy value), the corresponding
	 * product is considered exportable and will be included in catalog generation logic.
	 * If this flag is missing or set to false, the product will be skipped.
	 *
	 * Used by:
	 * - {@see ProductEntityProvider} to filter exportable product IDs.
	 * - Admin UI or automation logic to toggle export eligibility.
	 *
	 * @since 0.1.0
	 */
	public const CATALOG_ITEM = ChannelVisibilityMetaBox::CATALOG_ITEM;

	/**
	 * Registers all WooCommerce hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		add_action(
			'current_screen',
			function ( $screen ) {
				if ( 'product' !== $screen->post_type || 'post' !== $screen->base ) {
					return;
				}

				add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
				add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ) );
			}
		);
	}

	/**
	 * Adds the Reddit tab to the product data panel.
	 *
	 * @since 0.1.0
	 *
	 * @param array $tabs Existing WooCommerce product data tabs.
	 * @return array Modified tabs.
	 */
	public function add_tab( array $tabs ): array {
		$tabs['reddit'] = array(
			'label'    => __( 'Reddit', 'reddit-for-woocommerce' ),
			'target'   => 'reddit_product_data',
			'class'    => array(),
			'priority' => 90,
		);
		return $tabs;
	}

	/**
	 * Renders the Reddit product data panel.
	 *
	 * The catalog-item value is managed by the channel-visibility sidebar select
	 * control (ChannelVisibilitySettings), which posts the same meta key. This
	 * panel exists solely to register the "Reddit" tab in the product data tabs.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_panel(): void {
		?>
		<div id="reddit_product_data" class="panel woocommerce_options_panel hidden"></div>
		<?php
	}

	/**
	 * Saves the Reddit exportable flag when the product is saved.
	 *
	 * The value is posted by the channel-visibility sidebar SelectControl. When
	 * the select is not rendered (e.g. promo banner is shown instead), the field
	 * is absent from POST and the existing meta value is preserved.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The ID of the current product.
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		$meta_key = Helper::with_prefix( self::CATALOG_ITEM );

		// Nonce verification done in the Woo Core parent method.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $meta_key ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$value = '0' === $_POST[ $meta_key ] ? '0' : '1';
		update_post_meta( $post_id, $meta_key, $value );
	}
}
