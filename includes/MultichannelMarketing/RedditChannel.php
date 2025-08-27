<?php
/**
 * Defines the Reddit marketing channel for WooCommerce.
 *
 * This file implements the `RedditChannel` class, which registers Reddit
 * as a marketing channel in WooCommerce Admin. It provides setup
 * URLs, icon paths, and integration points for marketing campaigns.
 *
 * @package RedditForWooCommerce\MultichannelMarketing
 *
 * @since 0.1.0
 */

namespace RedditForWooCommerce\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Class RedditChannel
 *
 * @package RedditForWooCommerce\MultichannelMarketing
 *
 * @since 0.1.0
 */
class RedditChannel implements MarketingChannelInterface {
	/**
	 * Returns the unique identifier string for the marketing channel extension, also known as the plugin slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return Config::PLUGIN_SLUG;
	}

	/**
	 * Returns the name of the marketing channel.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Reddit', 'reddit-for-woocommerce' );
	}

	/**
	 * Returns the description of the marketing channel.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Promote your products with targeted ads in relevant Reddit communities.', 'reddit-for-woocommerce' );
	}

	/**
	 * Returns the path to the channel icon.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_icon_url(): string {
		return sprintf( '%s/js/build/images/js/src/images/logo/reddit.svg', REDDIT_FOR_WOOCOMMERCE_PLUGIN_URL );
	}

	/**
	 * Returns the setup status of the marketing channel.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_setup_completed(): bool {
		return 'connected' === Options::get( OptionDefaults::ONBOARDING_STATUS );
	}

	/**
	 * Returns the URL to the settings page, or the link to complete the setup/onboarding if the channel has not been set up yet.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_setup_url(): string {
		if ( ! $this->is_setup_completed() ) {
			return admin_url( 'admin.php?page=wc-admin&path=/reddit/setup' );
		}

		return admin_url( 'admin.php?page=wc-admin&path=/reddit/settings' );
	}

	/**
	 * Returns the status of the marketing channel's product listings.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_product_listings_status(): string {
		return self::PRODUCT_LISTINGS_NOT_APPLICABLE;
	}

	/**
	 * Returns the number of channel issues/errors (e.g. account-related errors, product synchronization issues, etc.).
	 *
	 * @since 0.1.0
	 *
	 * @return int The number of issues to resolve, or 0 if there are no issues with the channel.
	 */
	public function get_errors_count(): int {
		return 0;
	}

	/**
	 * Returns an array of marketing campaign types that the channel supports.
	 *
	 * @since 0.1.0
	 *
	 * @return MarketingCampaignType[] Array of marketing campaign type objects.
	 */
	public function get_supported_campaign_types(): array {
		return array();
	}

	/**
	 * Returns an array of the channel's marketing campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return MarketingCampaign[]
	 */
	public function get_campaigns(): array {
		return array();
	}
}
