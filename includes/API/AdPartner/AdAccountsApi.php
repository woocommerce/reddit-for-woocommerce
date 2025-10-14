<?php
/**
 * API module for managing Ad Partner ad accounts.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Ad Accounts API via WooCommerce Connect Server (WCS). It supports operations
 * such as listing ad accounts for a configured business and retrieving details
 * for a specific ad account.
 *
 * Requests are proxied through WCS, which manages authentication and secure
 * communication with the Ad Partner's remote API.
 *
 * @since 0.1.0
 * @package RedditForWooCommerce\API\AdPartner
 */

namespace RedditForWooCommerce\API\AdPartner;

use RedditForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use WP_Error;

/**
 * API module for managing Ad Partner ad accounts.
 *
 * This class provides methods to fetch the list of ad accounts associated
 * with the configured business and to retrieve details for the currently
 * selected ad account.
 *
 * @since 0.1.0
 */
class AdAccountsApi extends BaseAdPartnerApi {

	/**
	 * Retrieves the list of ad accounts for the configured business.
	 *
	 * This method calls the Ad Partner API to return all ad accounts
	 * associated with the business ID stored in plugin options
	 * ({@see OptionDefaults::BUSINESS_ID}).
	 *
	 * If no business ID is set, a {@see WP_Error} is returned.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the list of ad accounts
	 *                                    or error if the business ID is not configured.
	 */
	public function list() {
		$business_id = Options::get( OptionDefaults::BUSINESS_ID );

		if ( ! $business_id ) {
			return new WP_Error(
				'business_id_not_set',
				__( 'Business ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		return $this->wcs->proxy_get(
			sprintf( '/ads/businesses/%s/ad_accounts', rawurlencode( $business_id ) )
		);
	}

	/**
	 * Retrieves details of the configured ad account.
	 *
	 * This method fetches detailed information about the ad account
	 * selected in plugin options via {@see OptionDefaults::AD_ACCOUNT_ID}.
	 *
	 * If no ad account ID is set, a {@see WP_Error} is returned.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the ad account details
	 *                                    or error if no ad account ID is configured.
	 */
	public function get() {
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		if ( ! $ad_account_id ) {
			return new WP_Error(
				'ad_account_id_not_set',
				__( 'Ad Account ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		return $this->wcs->proxy_get(
			'/ads/ad_accounts/' . $ad_account_id
		);
	}
}
