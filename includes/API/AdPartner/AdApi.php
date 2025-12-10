<?php
/**
 * API module for creating Ads.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Ad Group API via WooCommerce Connect Server (WCS). It supports ad group-related
 * operations such as creation, and can be extended to include retrieval,
 * deletion, or updates in the future.
 *
 * @since 0.1.0
 * @package RedditForWooCommerce\API\AdPartner
 */

namespace RedditForWooCommerce\API\AdPartner;

use RedditForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Helper;
use WP_Error;

/**
 * API module for creating Ads.
 *
 * This class provides the ability to create a new Ad under the merchant's
 * Ad Partner account, associating it with an existing Ad Group.
 *
 * @since 0.1.0
 */
class AdApi extends BaseAdPartnerApi {

	/**
	 * Creates an Ad for the current merchant business.
	 *
	 * This method builds and submits the Ad creation request using the
	 * Ad group ID and Profile ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * @since 0.1.0
	 *
	 * @param string $ad_group_id The ad group ID.
	 * @param string $profile_id  The profile ID.
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create( $ad_group_id, $profile_id ) {
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		if ( ! $ad_account_id ) {
			return new WP_Error(
				'ad_account_id_not_set',
				__( 'Ad Account ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		if ( ! $profile_id ) {
			return new WP_Error(
				'profile_id_not_set',
				__( 'Profile ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		/*
		 * Prepare payload for the Ad creation.
		 *
		 * @see https://ads-api.reddit.com/docs/v3/operations/Create%20Ad
		 */
		$payload = array(
			'data' => array(
				'type'                    => 'UNSPECIFIED',
				'campaign_objective_type' => 'CATALOG_SALES',
				'configured_status'       => 'ACTIVE',
				'name'                    => Helper::get_store_name( 'ad' ),
				'ad_group_id'             => $ad_group_id,
				'profile_id'              => $profile_id,
				'shopping_creative'       => array(
					'dpa_carousel_mode' => 'CAROUSEL_ONLY',
					'headline'          => Helper::get_ad_headline(),
					'call_to_action'    => 'Shop Now',
					'allow_comments'    => false,
					'second_line_cta'   => 'PRICE',
					'destination_url'   => '',
				),
			),
		);

		return $this->wcs->proxy_post(
			sprintf( '/ads/ad_accounts/%s/ads', rawurlencode( $ad_account_id ) ),
			$payload
		);
	}
}
