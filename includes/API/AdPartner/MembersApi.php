<?php
/**
 * API module for getting authenticated member.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Members API via WooCommerce Connect Server (WCS). It supports operations
 * such as getting the authenticated member.
 *
 * Requests are proxied through WCS, which manages authentication and
 * secure communication with the Ad Partner's remote API.
 *
 * @since 0.1.0
 * @package RedditForWooCommerce\API\AdPartner
 */

namespace RedditForWooCommerce\API\AdPartner;

use RedditForWooCommerce\API\AdPartner\BaseAdPartnerApi;

/**
 * API module for getting authenticated member.
 *
 * This class provides methods to fetch the authenticated member.
 *
 * @since 0.1.0
 */
class MembersApi extends BaseAdPartnerApi {

	/**
	 * Retrieves the authenticated member.
	 *
	 * This method calls the Ad Partner API to return the authenticated member via the `/ads/me` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response containing the authenticated member
	 *                                    or error if the request fails.
	 */
	public function me() {
		return $this->wcs->proxy_get( '/ads/me' );
	}
}
