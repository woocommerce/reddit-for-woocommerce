<?php
/**
 * Enqueues admin-specific assets for Reddit for WooCommerce.
 *
 * This class is responsible for loading JavaScript and CSS assets
 * used within the WordPress admin interface. Assets are only loaded
 * on admin pages and are expected to be registered using the plugin's
 * shared {@see AssetLoader} utility.
 *
 * @package RedditForWooCommerce\Admin
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin;

use RedditForWooCommerce\Utils\AssetLoader;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\ServiceKey;
use RedditForWooCommerce\ServiceContainer;
use RedditForWooCommerce\CsvExporter\ProductExportService;
use RedditForWooCommerce\Utils\Helper;

/**
 * Handles admin script and style enqueues.
 *
 * Registers the `admin_enqueue_scripts` action to load plugin-specific
 * admin assets such as JavaScript for UI components or custom styles
 * for Gutenberg integrations or settings pages.
 *
 * @since 0.1.0
 */
class Assets {

	/**
	 * Registers WordPress admin-side hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueues plugin admin assets (JS/CSS).
	 *
	 * Handles both the main WC admin app and meta-box scripts for product/order edit screens.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );

		if ( 'wc-admin' === $page ) {
			$csv_path = Options::get( OptionDefaults::EXPORT_FILE_PATH );

			AssetLoader::enqueue_script( 'index', 'index' );
			AssetLoader::enqueue_style( 'index', 'index' );
			AssetLoader::localize_script(
				'index',
				'AdminData',
				array(
					'setupComplete'      => boolval( Options::get( OptionDefaults::ONBOARDING_STATUS ) === 'connected' ),
					'status'             => Options::get( OptionDefaults::ONBOARDING_STATUS ),
					'step'               => Options::get( OptionDefaults::ONBOARDING_STEP ),
					'adminNonce'         => wp_create_nonce( 'admin_nonce' ),
					'isExportInProgress' => ServiceContainer::get( ServiceKey::PRODUCT_EXPORT_SERVICE )->job->is_job_in_progress( ProductExportService::ACTION_HOOK ),
					'exportFileUrl'      => file_exists( $csv_path ) ? Options::get( OptionDefaults::EXPORT_FILE_URL ) : '',
					'lastTimestamp'      => Helper::get_formatted_timestamp( Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP ) ),
					'slug'               => 'rfw',
					'trackingSlug'       => 'redtwoo',
					'pluginVersion'      => REDDIT_FOR_WOOCOMMERCE_VERSION,
					'adsAccountId'       => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
					'prefix'             => Helper::with_prefix( '' ),
				)
			);

			return;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_connected = Options::get( OptionDefaults::ONBOARDING_STATUS ) === 'connected';
		$setup_url    = admin_url( 'admin.php?page=wc-admin&path=/reddit/setup' );
		$settings_url = admin_url( 'admin.php?page=wc-admin&path=/reddit/settings' );

		if ( 'product' === $screen->id ) {
			AssetLoader::enqueue_script( 'channel-visibility-meta-box', 'channel-visibility-meta-box' );
			AssetLoader::localize_script(
				'channel-visibility-meta-box',
				'ChannelVisibilityData',
				array(
					'isConnected' => $is_connected,
					'setupUrl'    => $setup_url,
					'settingsUrl' => $settings_url,
				)
			);
		}

		if ( in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			AssetLoader::enqueue_script( 'order-attribution', 'order-attribution' );
			AssetLoader::localize_script(
				'order-attribution',
				'OrderAttributionData',
				array(
					'isConnected' => $is_connected,
					'setupUrl'    => $setup_url,
					'settingsUrl' => $settings_url,
				)
			);
		}
	}
}
