<?php
/**
 * API module for creating campaigns.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Campaign API via WooCommerce Connect Server (WCS). It supports campaign-related
 * operations such as creation, and can be extended to include retrieval,
 * deletion, or updates in the future.
 *
 * Requests are constructed using merchant-specific identifiers and sent to
 * the WCS proxy endpoint, which handles secure authentication and communication
 * with the Ad Partner's remote API.
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
 * API module for creating campaigns.
 *
 * This class provides the ability to create a new campaign under the merchant's
 * Ad Partner account, associating it with an existing Ads Account.
 *
 * @since 0.1.0
 */
class CampaignApi extends BaseAdPartnerApi {

	/**
	 * Creates a campaign for the current merchant business.
	 *
	 * This method builds and submits the campaign creation request using the
	 * business ID and Ads Account ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * Requirements:
	 * - Business ID must be saved in plugin options.
	 * - Ads Account ID must be saved in plugin options.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create() {
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		if ( ! $ad_account_id ) {
			return new WP_Error(
				'ad_account_id_not_set',
				__( 'Ad Account ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		$payload = array(
			'data' => array(
				'name'              => Helper::get_store_name( 'campaign' ),
				'configured_status' => 'ACTIVE',
				'objective'         => 'CONVERSIONS',
			),
		);

		return $this->wcs->proxy_post(
			sprintf( '/ads/ad_accounts/%s/campaigns', rawurlencode( $ad_account_id ) ),
			$payload
		);
	}
}
