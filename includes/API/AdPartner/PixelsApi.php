<?php
/**
 * API module for managing Ad Partner pixels.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Pixel API via WooCommerce Connect Server (WCS). It supports operations
 * such as listing all pixels associated with the configured business.
 *
 * Requests are proxied through WCS, which manages authentication and
 * secure communication with the Ad Partner's remote API.
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
 * API module for managing Ad Partner pixels.
 *
 * This class provides methods to fetch the list of tracking pixels
 * associated with the currently configured business. Pixels are used
 * for event tracking, conversion attribution, and retargeting
 * within the Ad Partner’s platform.
 *
 * @since 0.1.0
 */
class PixelsApi extends BaseAdPartnerApi {

	/**
	 * Retrieves the list of pixels for the configured business.
	 *
	 * This method calls the Ad Partner API to return all pixels
	 * associated with the business ID stored in plugin options
	 * ({@see OptionDefaults::BUSINESS_ID}).
	 *
	 * If no business ID is set, a {@see WP_Error} is returned.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the list of pixels
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
			sprintf( '/ads/businesses/%s/pixels', rawurlencode( $business_id ) )
		);
	}
}
