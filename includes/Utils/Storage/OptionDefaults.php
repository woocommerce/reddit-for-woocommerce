<?php
/**
 * Defines default values and canonical keys for Ad Partner plugin options.
 *
 * This class acts as a centralized registry for all WordPress option keys
 * used by the Ad Partner integration, along with their default values.
 * It ensures consistent key usage and safe fallbacks when no user-defined
 * value has been saved to the database.
 *
 * @package RedditForWooCommerce\Utils\Storage
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Utils\Storage;

/**
 * Central registry of option keys and default values for the Ad Partner plugin.
 *
 * Defines the list of valid WordPress option keys used by the plugin,
 * along with their default values. This class ensures consistent key usage
 * across the codebase and provides safe fallbacks for unset options.
 *
 * Used by {@see Options} to populate defaults and resolve missing values.
 *
 * @since 0.1.0
 */
final class OptionDefaults {
	/**
	 * Option key for storing the current onboarding status.
	 *
	 * Can be values like 'disconnected', 'connected', or 'in_progress'.
	 *
	 * @since 0.1.0
	 */
	public const ONBOARDING_STATUS = 'onboarding_status';


	/**
	 * Option key for storing the current onboarding step.
	 *
	 * Represents the plugin setup progress step, e.g., 'setup', 'connect', 'configure'.
	 *
	 * @since 0.1.0
	 */
	public const ONBOARDING_STEP = 'onboarding_step';

	/**
	 * Option key for storing the status of Jetpack's connection.
	 *
	 * @since 0.1.0
	 */
	public const IS_JETPACK_CONNECTED = 'is_jetpack_connected';

	/**
	 * Option key for the Ad Partner ad account ID.
	 *
	 * @since 0.1.0
	 */
	public const AD_ACCOUNT_ID = 'ad_account_id';

	/**
	 * Option key for the Ad Partner ad account name.
	 *
	 * @since 0.1.0
	 */
	public const AD_ACCOUNT_NAME = 'ad_account_name';

	/**
	 * Option key for the Reddit Business ID.
	 *
	 * @since 0.1.0
	 */
	public const BUSINESS_ID = 'business_id';

	/**
	 * Option key for storing the name of the selected Reddit business.
	 *
	 * Stored locally after fetching the business from the Reddit API.
	 *
	 * @since 0.1.0
	 */
	public const BUSINESS_NAME = 'business_name';

	/**
	 * Option key that toggles whether pixel tracking is enabled.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_ENABLED = 'ads_pixel_enabled';

	/**
	 * Option key for the Ad Partner's Pixel ID.
	 *
	 * @since 0.1.0
	 */
	public const PIXEL_ID = 'pixel_id';

	/**
	 * Option key that toggles whether Conversion tracking is enabled.
	 *
	 * @since 0.1.0
	 */
	public const CONVERSIONS_ENABLED = 'conversion_enabled';

	/**
	 * Option key for the Ad Partner's Catalog ID.
	 *
	 * @since 0.1.0
	 */
	public const CATALOG_ID = 'catalog_id';

	/**
	 * Option key indicating whether the catalog creation has an error.
	 */
	public const CATALOG_ERROR = 'catalog_error';

	/**
	 * Option key for the Ad Partner's Product Feed ID.
	 *
	 * @since 0.1.0
	 */
	public const PRODUCT_FEED_ID = 'product_feed_id';

	/**
	 * Option key indicating whether the feed has been created.
	 *
	 * @since 0.1.0
	 */
	public const FEED_STATUS = 'feed_status';

	/**
	 * Option key to store the full file system path of the most recent export file.
	 *
	 * This value is written during the first export batch and reused across
	 * subsequent batches to avoid recreating the file. It is cleared when a new
	 * export is initiated.
	 *
	 * @since 0.1.0
	 */
	public const EXPORT_FILE_PATH = 'catalog_export_path';

	/**
	 * Option key to store the public download URL of the most recent export file.
	 *
	 * This value is generated once the first batch creates the CSV file,
	 * and is shown to the user as a downloadable link once the export completes.
	 *
	 * @since 0.1.0
	 */
	public const EXPORT_FILE_URL = 'catalog_export_url';

	/**
	 * Option key to store the list of product IDs to be exported.
	 *
	 * Cached once at export start to support consistent batch processing.
	 *
	 * @since 0.1.0
	 */
	public const EXPORT_PRODUCT_IDS = 'catalog_export_product_ids';

	/**
	 * Option key to store a human-readable export timestamp.
	 *
	 * @since 0.1.0
	 */
	public const LAST_EXPORT_TIMESTAMP = 'last_export_timestamp';

	/**
	 * Option key to store the WCS token to create file name for
	 * generating catalog CSV filename.
	 *
	 * @since 0.1.0
	 */
	public const WCS_PRODUCTS_TOKEN = 'wcs_products_token';

	/**
	 * Option key to store the dummy purchase tracked status.
	 *
	 * @since 0.1.0
	 */
	public const DUMMY_PURCHASE_TRACKED = 'dummy_purchase_tracked';

	/**
	 * Option key to store the Ad Partner's currency.
	 *
	 * @since 0.1.0
	 */
	public const ADS_ACCOUNT_CURRENCY = 'ad_account_currency';

	/**
	 * Option key to store the Ad Partner's Campaign IDs.
	 *
	 * @since 0.1.0
	 */
	public const CAMPAIGN_IDS = 'campaign_ids';

	/**
	 * Returns default values for all known Ad Partner options.
	 *
	 * Used by {@see Options} to provide fallbacks when option values
	 * are not yet persisted in the database.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Map of option keys to their default values.
	 */
	public static function get_all(): array {
		return array(
			self::ONBOARDING_STATUS      => 'incomplete',
			self::ONBOARDING_STEP        => 'accounts',
			self::IS_JETPACK_CONNECTED   => 'no',
			self::AD_ACCOUNT_ID          => '',
			self::AD_ACCOUNT_NAME        => '',
			self::BUSINESS_ID            => '',
			self::BUSINESS_NAME          => '',
			self::PIXEL_ENABLED          => 'yes',
			self::PIXEL_ID               => '',
			self::CONVERSIONS_ENABLED    => 'yes',
			self::CATALOG_ID             => '',
			self::CATALOG_ERROR          => '',
			self::PRODUCT_FEED_ID        => '',
			self::FEED_STATUS            => 'empty',
			self::EXPORT_FILE_PATH       => '',
			self::EXPORT_FILE_URL        => '',
			self::EXPORT_PRODUCT_IDS     => array(),
			self::LAST_EXPORT_TIMESTAMP  => 0,
			self::WCS_PRODUCTS_TOKEN     => '',
			self::DUMMY_PURCHASE_TRACKED => 'no',
			self::ADS_ACCOUNT_CURRENCY   => get_woocommerce_currency(),
			self::CAMPAIGN_IDS           => array(),
		);
	}
}
