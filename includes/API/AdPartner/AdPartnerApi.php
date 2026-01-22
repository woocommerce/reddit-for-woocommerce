<?php
/**
 * Entry point for interacting with the Ad Partner's catalog and feed APIs.
 *
 * This class acts as a centralized service layer for managing all API-related
 * operations required to interface with the Ad Partner's systems via WooCommerce Connect Server (WCS).
 * It encapsulates submodules like {@see CatalogApi} and {@see FeedApi}, and ensures that only
 * a single instance of this class is instantiated during the request lifecycle.
 *
 * This wrapper ensures a clean and testable separation between API logic and
 * higher-level business workflows, facilitating future extension with additional
 * API modules.
 *
 * @package RedditForWooCommerce\API\AdPartner
 */

namespace RedditForWooCommerce\API\AdPartner;

use RedditForWooCommerce\Connection\WcsClient;

/**
 * Central service class for Ad Partner API interactions.
 *
 * Designed as a singleton to ensure a consistent WCS client instance is shared
 * across all subcomponents.
 *
 * @since 0.1.0
 */
class AdPartnerApi {
	/**
	 * Singleton instance of the API service.
	 *
	 * Ensures only one instance of this class exists per request lifecycle.
	 *
	 * @since 0.1.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Handles product catalog operations.
	 *
	 * @since 0.1.0
	 * @var CatalogApi
	 */
	public CatalogApi $catalog;

	/**
	 * Handles product feed operations.
	 *
	 * @since 0.1.0
	 * @var FeedApi
	 */
	public FeedApi $feed;

	/**
	 * Handles business operations.
	 *
	 * @since 0.1.0
	 * @var BusinessApi
	 */
	public BusinessApi $business;

	/**
	 * Handles Ad Account operations.
	 *
	 * @since 0.1.0
	 * @var AdAccountsApi
	 */
	public AdAccountsApi $ad_accounts;

	/**
	 * Handles Pixels operations.
	 *
	 * @since 0.1.0
	 * @var PixelsApi
	 */
	public PixelsApi $pixels;

	/**
	 * Handles Members operations.
	 *
	 * @since 0.1.0
	 * @var MembersApi
	 */
	public MembersApi $members;

	/**
	 * Handles Campaigns operations.
	 *
	 * @since 0.1.0
	 * @var CampaignApi
	 */
	public CampaignApi $campaigns;

	/**
	 * Handles Ad Groups operations.
	 *
	 * @since 0.1.0
	 * @var AdGroupApi
	 */
	public AdGroupApi $ad_groups;

	/**
	 * Handles Product Sets operations.
	 *
	 * @since 0.1.0
	 * @var ProductSetApi
	 */
	public ProductSetApi $product_sets;

	/**
	 * Handles Ads operations.
	 *
	 * @since 0.1.0
	 * @var AdApi
	 */
	public AdApi $ads;

	/**
	 * Handles Profiles operations.
	 *
	 * @since 1.0.3
	 * @var ProfilesApi
	 */
	public ProfilesApi $profiles;

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * Initializes all API submodules with the shared {@see WcsClient} instance,
	 * ensuring consistent communication with WooCommerce Connect Server.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient $wcs WCS client used for authenticated proxy API requests.
	 */
	private function __construct( WcsClient $wcs ) {
		$this->catalog      = new CatalogApi( $wcs );
		$this->feed         = new FeedApi( $wcs );
		$this->business     = new BusinessApi( $wcs );
		$this->ad_accounts  = new AdAccountsApi( $wcs );
		$this->pixels       = new PixelsApi( $wcs );
		$this->members      = new MembersApi( $wcs );
		$this->campaigns    = new CampaignApi( $wcs );
		$this->ad_groups    = new AdGroupApi( $wcs );
		$this->product_sets = new ProductSetApi( $wcs );
		$this->ads          = new AdApi( $wcs );
		$this->profiles     = new ProfilesApi( $wcs );
	}

	/**
	 * Returns the singleton instance of the API service.
	 *
	 * Creates a new instance on the first call and reuses it for subsequent requests.
	 * Ensures that all API modules use the same underlying {@see WcsClient}.
	 *
	 * @since 0.1.0
	 *
	 * @param WcsClient $wcs WCS client used for authenticated proxy API requests.
	 * @return self Singleton instance of this class.
	 */
	public static function get_instance( WcsClient $wcs ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $wcs );
		}

		return self::$instance;
	}
}
