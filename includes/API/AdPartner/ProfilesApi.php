<?php
/**
 * API module for managing Ad Partner profiles.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Profile API via WooCommerce Connect Server (WCS). It supports operations
 * such as listing all profiles associated with the configured business.
 *
 * Requests are proxied through WCS, which manages authentication and
 * secure communication with the Ad Partner's remote API.
 *
 * @since 1.0.3
 * @package RedditForWooCommerce\API\AdPartner
 */

namespace RedditForWooCommerce\API\AdPartner;

use RedditForWooCommerce\API\AdPartner\BaseAdPartnerApi;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use WP_Error;

/**
 * API module for managing Ad Partner profiles.
 *
 * This class provides methods to fetch the list of profiles
 * associated with the currently configured business. Profiles are used
 * for ad targeting and campaign management within the Ad Partner’s platform.
 *
 * @since 1.0.3
 */
class ProfilesApi extends BaseAdPartnerApi {

	/**
	 * Retrieves the list of profiles for the configured business.
	 *
	 * This method calls the Ad Partner API to return all profiles
	 * associated with the business ID stored in plugin options
	 * ({@see OptionDefaults::BUSINESS_ID}).
	 *
	 * If no business ID is set, a {@see WP_Error} is returned.
	 *
	 * @since 1.0.3
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the list of profiles
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
			sprintf( '/ads/businesses/%s/profiles', rawurlencode( $business_id ) )
		);
	}
}
