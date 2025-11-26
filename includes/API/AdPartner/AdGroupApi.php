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
		$targeting_type = $campaign_data['targeting_type'] ?? 'PROSPECTING';

		$shopping_targeting = array(
			'targeting_type'       => 'PROSPECTING',
			'lookback_window_days' => 30,
		);

		// If targeting type is retargeting, set the conversion event types and excluded conversion event types.
		if ( 'RETARGETING' === $targeting_type ) {
			$shopping_targeting['targeting_type']                  = 'RETARGETING';
			$shopping_targeting['conversion_event_types']          = array(
				'VIEW_CONTENT',
				'ADD_TO_CART',
			);
			$shopping_targeting['excluded_conversion_event_types'] = array(
				'PURCHASE',
			);
		}

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
				'bid_type'                     => null,
				'campaign_id'                  => $campaign_id,
				'configured_status'            => 'ACTIVE',
				'goal_type'                    => 'DAILY_SPEND',
				'goal_value'                   => Helper::amount_to_microcurrency( (float) $daily_budget ),
				'name'                         => Helper::get_store_name( 'ad_group_' . $targeting_type ),
				'optimization_goal'            => 'PURCHASE',
				'view_through_conversion_type' => 'SEVEN_DAY_CLICKS_ONE_DAY_VIEW',
				'shopping_type'                => 'DYNAMIC',
				'shopping_targeting'           => $shopping_targeting,
				'product_set_id'               => $product_set_id,
				'bid_strategy'                 => 'BIDLESS',
				'start_time'                   => wp_date( 'c' ),
			),
		);

		/*
		 * If store is not allowed to sell to all countries, set the targeting to the allowed countries.
		 * If we don't set the targeting, the ad group will be created with the default targeting, which is global.
		 */
		if ( ! $this->is_all_countries_allowed() ) {
			$payload['data']['targeting'] = array(
				'geolocations' => $this->get_allowed_countries(),
			);
		}

		return $this->wcs->proxy_post(
			sprintf( '/ads/ad_accounts/%s/ad_groups', rawurlencode( $ad_account_id ) ),
			$payload
		);
	}

	/**
	 * Check if all countries are allowed.
	 * If Shipping is disabled we check if all countries are allowed to sell to.
	 * If Shipping is enabled we check if all countries are allowed to ship to.
	 *
	 * @return bool True if all countries are allowed, false otherwise.
	 */
	private function is_all_countries_allowed(): bool {
		$shipping_countries = get_option( 'woocommerce_ship_to_countries' );
		// If shipping is disabled or shipping countries are set to "Ship to all countries you sell to", return true if all countries are allowed.
		if ( 'disabled' === $shipping_countries || '' === $shipping_countries ) {
			return 'all' === get_option( 'woocommerce_allowed_countries' );
		}

		return 'all' === $shipping_countries;
	}

	/**
	 * Get allowed countries.
	 *
	 * @return array Allowed countries.
	 */
	private function get_allowed_countries(): array {
		// If shipping is disabled, return all countries allowed to sell to.
		if ( 'disabled' === get_option( 'woocommerce_ship_to_countries' ) ) {
			return array_keys( WC()->countries->get_allowed_countries() );
		}
		return array_keys( WC()->countries->get_shipping_countries() );
	}
}
