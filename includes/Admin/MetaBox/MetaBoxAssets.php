<?php
/**
 * Conditional admin assets for Reddit UI on WooCommerce Edit Order and Edit Product screens.
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
 * Enqueues meta box bundles only on matching WC admin screens and inlines mount data.
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
	 * Loads order-attribution or channel-visibility scripts and localize data as `redditAdsMetaBoxData`.
	 *
	 * At most one bundle is enqueued per request.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( $this->should_enqueue_order_attribution_bundle() ) {
			$this->enqueue_order_attribution_assets();
			return;
		}

		if ( ProductChannelVisibilityData::should_enqueue_channel_visibility_bundle() ) {
			$this->enqueue_channel_visibility_assets();
		}
	}

	/**
	 * Gates the order-attribution bundle to the WooCommerce order edit screen.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	protected function should_enqueue_order_attribution_bundle(): bool {
		return OrderAttributionData::is_wc_order_edit_screen();
	}

	/**
	 * Enqueues the order-attribution bundle and passes localized data for the Edit Order screen.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function enqueue_order_attribution_assets(): void {
		AssetLoader::enqueue_script( 'order-attribution', 'order-attribution' );

		$urls = Helper::get_wc_admin_reddit_metabox_urls();

		AssetLoader::localize_script(
			'order-attribution',
			'MetaBoxData',
			array_merge(
				$this->get_shared_metabox_bootstrap(),
				array(
					'hasCampaign'            => ! empty( Options::get( OptionDefaults::CAMPAIGN_IDS ) ),
					'orderAttributionSource' => OrderAttributionData::get_order_attribution_source(),
					'urls'                   => array(
						'start'          => $urls['start'],
						'campaignCreate' => $urls['campaignCreate'],
						'settings'       => $urls['settings'],
					),
				)
			)
		);
	}

	/**
	 * Enqueues the channel-visibility bundle and passes localized data for the Edit Product screen.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function enqueue_channel_visibility_assets(): void {
		$channel_visibility = ProductChannelVisibilityData::get_channel_visibility_inline_block();

		if ( null === $channel_visibility ) {
			return;
		}

		AssetLoader::enqueue_script( 'channel-visibility-meta-box', 'channel-visibility-meta-box' );

		AssetLoader::localize_script(
			'channel-visibility-meta-box',
			'MetaBoxData',
			array_merge(
				$this->get_shared_metabox_bootstrap(),
				array(
					'channelVisibility' => $channel_visibility,
				)
			)
		);
	}

	/**
	 * Keys shared between order-attribution and channel-visibility localized payloads.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed>
	 */
	private function get_shared_metabox_bootstrap(): array {
		return array(
			'slug'               => Config::PLUGIN_SLUG,
			'onboardingComplete' => OnboardingStatus::is_setup_completed(),
		);
	}
}
