<?php
/**
 * API module for managing product sets.
 *
 * This class provides an interface for interacting with the Ad Partner's
 * Product Set API via WooCommerce Connect Server (WCS). It supports product set-related
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
 * API module for managing product sets.
 *
 * This class provides the ability to create a new product set under the merchant's
 * Ad Partner account, associating it with an existing Ads Account.
 *
 * @since 0.1.0
 */
class ProductSetApi extends BaseAdPartnerApi {
	/**
	 * Returns the list of all product sets.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function get_all_products_set_id() {
		$catalog_id = Options::get( OptionDefaults::CATALOG_ID );

		if ( ! $catalog_id ) {
			return new WP_Error(
				'catalog_id_not_set',
				__( 'Catalog ID not found, Please create a catalog first.', 'reddit-for-woocommerce' ),
			);
		}

		return $this->wcs->proxy_get(
			sprintf(
				'/ads/product_catalogs/%s/product_sets',
				rawurlencode( $catalog_id )
			)
		);
	}

	/**
	 * Creates a product set for the current merchant business.
	 *
	 * This method builds and submits the product set creation request using the
	 * Catalog ID configured in the plugin settings.
	 *
	 * It returns a {@see WP_REST_Response} on success or a {@see WP_Error}
	 * on failure, depending on whether the required prerequisites are met.
	 *
	 * Requirements:
	 * - Catalog ID must be saved in plugin options.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response|WP_Error REST response from WCS or error if inputs are missing.
	 */
	public function create() {
		$catalog_id = Options::get( OptionDefaults::CATALOG_ID );

		if ( ! $catalog_id ) {
			return new WP_Error(
				'catalog_id_not_set',
				__( 'Catalog ID not found, Please create a catalog first.', 'reddit-for-woocommerce' ),
			);
		}

		$payload = array(
			'data' => array(
				'name'   => Helper::get_store_name( 'product_set' ),
				'filter' => '{"and":[{"availability":{"eq":"IN_STOCK"}}]}',
			),
		);

		return $this->wcs->proxy_post(
			sprintf( '/ads/product_catalogs/%s/product_sets', rawurlencode( $catalog_id ) ),
			$payload
		);
	}
}
