<?php
/**
 * Conditional admin assets for Reddit UI on the WooCommerce Edit Order screen.
 *
 * @package RedditForWooCommerce\Admin\MetaBox
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\MetaBox;

use RedditForWooCommerce\Config;
use RedditForWooCommerce\Utils\AssetLoader;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\OnboardingStatus;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;

/**
 * Enqueues the order-attribution bundle only on the WC order edit screen and inlines mount data.
 *
 * @since 0.1.0
 */
class MetaBoxAssets {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Loads order-attribution scripts and localize data as `redditAdsMetaBoxData`.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->should_enqueue_order_attribution_bundle() ) {
			return;
		}

		AssetLoader::enqueue_script( 'order-attribution', 'order-attribution' );

		$urls = Helper::get_wc_admin_reddit_metabox_urls();

		AssetLoader::localize_script(
			'order-attribution',
			'MetaBoxData',
			array(
				'slug'                   => Config::PLUGIN_SLUG,
				'onboardingComplete'     => OnboardingStatus::is_setup_completed(),
				'hasCampaign'            => ! empty( Options::get( OptionDefaults::CAMPAIGN_IDS ) ),
				'orderAttributionSource' => OrderAttributionData::get_order_attribution_source(),
				'urls'                   => array(
					'start'          => $urls['start'],
					'campaignCreate' => $urls['campaignCreate'],
					'settings'       => $urls['settings'],
				),
			)
		);
	}

	/**
	 * Gates the enqueue to the WooCommerce order edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	protected function should_enqueue_order_attribution_bundle(): bool {
		return OrderAttributionData::is_wc_order_edit_screen();
	}
}
