<?php
/**
 * API module for managing Ad Partner businesses.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Business API via WooCommerce Connect Server (WCS). It supports operations
 * such as listing available businesses for the authenticated merchant and
 * retrieving details for a specific business.
 *
 * Requests are proxied through WCS, which handles secure authentication
 * and communication with the Ad Partner's remote API.
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
 * API module for managing Ad Partner businesses.
 *
 * This class provides methods to fetch a list of businesses available
 * to the authenticated merchant and to retrieve detailed information
 * for the currently selected business.
 *
 * @since 0.1.0
 */
class BusinessApi extends BaseAdPartnerApi {
	/**
	 * Retrieves the list of businesses for the current merchant.
	 *
	 * This method calls the Ad Partner API to return all businesses
	 * associated with the authenticated account. The request is proxied
	 * through WCS for authentication.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the list of businesses
	 *                                    or error if the request fails.
	 */
	public function list() {
		return $this->wcs->proxy_get(
			'/ads/me/businesses'
		);
	}

	/**
	 * Retrieves details of the configured business.
	 *
	 * This method fetches detailed information about the currently
	 * selected business. The business ID must first be stored in plugin
	 * options via {@see OptionDefaults::BUSINESS_ID}.
	 *
	 * If no business ID is set, a {@see WP_Error} is returned.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the business details
	 *                                    or error if no business ID is configured.
	 */
	public function get() {
		$business_id = Options::get( OptionDefaults::BUSINESS_ID );

		if ( ! $business_id ) {
			return new WP_Error(
				'business_id_not_set',
				__( 'Business ID not found.', 'reddit-for-woocommerce' ),
			);
		}

		return $this->wcs->proxy_get(
			sprintf( '/ads/businesses/%s', $business_id )
		);
	}
}
