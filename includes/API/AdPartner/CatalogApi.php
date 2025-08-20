<?php
/**
 * API module for managing product catalogs.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Catalog API via WooCommerce Connect Server (WCS). It supports catalog-related
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
 * API module for managing product catalogs.
 *
 * This class provides the ability to create a new catalog under the merchant's
 * Ad Partner account, associating it with an existing Pixel.
 *
 * @since 0.1.0
 */
class CatalogApi extends BaseAdPartnerApi {

	/**
	 * Creates a product catalog for the current merchant business.
	 *
	 * This method builds and submits the catalog creation request using the
	 * business ID and Pixel ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * Requirements:
	 * - Business ID must be saved in plugin options.
	 * - Pixel ID must be saved in plugin options.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create() {
		$business_id = Options::get( OptionDefaults::BUSINESS_ID );

		if ( ! $business_id ) {
			return new WP_Error(
				'business_id_not_set',
				__( 'Business ID not found.', 'snapchat-for-woocommerce' ),
			);
		}

		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( ! $pixel_id ) {
			return new WP_Error(
				'pixel_id_not_set',
				__( 'Pixel ID not found.', 'snapchat-for-woocommerce' ),
			);
		}

		$payload = array(
			'data' => array(
				array(
					'name'             => Helper::get_store_name( 'catalog' ),
					'default_language' => 'en',
					'default_currency' => get_woocommerce_currency(),
					'event_sources'    => array( $pixel_id ),
				),
			),
		);

		$response = $this->wcs->proxy_post(
			'/v3/businesses/' . $business_id . '/product_catalogs',
			$payload
		);

		return $response;
	}
}
