<?php
/**
 * API module for creating Ad Groups.
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
 * API module for creating Ad Groups.
 *
 * This class provides the ability to create a new Ad Group under the merchant's
 * Ad Partner account, associating it with an existing Campaign.
 *
 * @since 0.1.0
 */
class AdGroupApi extends BaseAdPartnerApi {

	/**
	 * Creates an Ad Group for the current merchant business.
	 *
	 * This method builds and submits the Ad Group creation request using the
	 * campaign ID and Ad Account ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * @since 0.1.0
	 *
	 * @param array $campaign_data The campaign data.
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create( $campaign_data ) {
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		if ( ! $ad_account_id ) {
			return new WP_Error(
				'ad_account_id_not_set',
				__( 'Ad Account ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		$campaign_id    = $campaign_data['campaign_id'] ?? '';
		$product_set_id = $campaign_data['product_set_id'] ?? '';
		$daily_budget   = $campaign_data['daily_budget'] ?? '';

		if ( ! $campaign_id ) {
			return new WP_Error(
				'campaign_id_not_set',
				__( 'Campaign ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		/*
		 * Prepare payload for the Ad Group creation.
		 *
		 * @see https://ads-api.reddit.com/docs/v3/operations/Create%20Ad%20Group
		 */
		$payload = array(
			'data' => array(
				'bid_type'           => 'CPC',
				'bid_value'          => 1000000,
				'campaign_id'        => $campaign_id,
				'configured_status'  => 'ACTIVE',
				'goal_type'          => 'DAILY_SPEND',
				'goal_value'         => intval( $daily_budget * 1000000 ),
				'name'               => Helper::get_store_name( 'ad_group' ),
				// @todo This need to be finalized.
				'optimization_goal'  => 'CLICKS',
				// 'view_through_conversion_type' => 'SEVEN_DAY_CLICKS', // TODO: This is dependent on optimization goal.
				'shopping_type'      => 'DYNAMIC',
				'shopping_targeting' => array(
					'targeting_type'       => 'PROSPECTING',
					'lookback_window_days' => 30,
				),
				'product_set_id'     => $product_set_id,
				'bid_strategy'       => 'MAXIMIZE_VOLUME',
				'start_time'         => wp_date( 'c' ),
			),
		);

		return $this->wcs->proxy_post(
			sprintf( '/ads/ad_accounts/%s/ad_groups', rawurlencode( $ad_account_id ) ),
			$payload
		);
	}
}
