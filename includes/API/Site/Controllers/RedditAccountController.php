<?php
/**
 * REST controller for managing Reddit Account details on the merchant site.
 *
 * This controller allows retrieving, selecting, and getting the selected business
 * details associated with the authenticated merchant's Reddit account.
 *
 * It fetches business and ad account data from the local WordPress options.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/businesss` endpoint.
 *
 * @since 0.1.0
 */
class RedditAccountController extends RESTBaseController {
	/**
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /account
		 * - Returns an array of merchant account details.
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'/account',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_account_details' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Returns an array of Merchant Account details.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_account_details() {
		return rest_ensure_response(
			array(
				'business_id'   => Options::get( OptionDefaults::BUSINESS_ID ),
				'business_name' => Options::get( OptionDefaults::BUSINESS_NAME ),
				'ad_acc_id'     => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'ad_acc_name'   => Options::get( OptionDefaults::AD_ACCOUNT_NAME ),
				'pixel_id'      => Options::get( OptionDefaults::PIXEL_ID ),
			)
		);
	}


	/**
	 * Returns the JSON schema for the `/account` REST endpoint.
	 *
	 * This schema defines a single object with details about the merchant
	 * account.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for a selected business.
	 */
	public function business_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'reddit_business',
			'type'       => 'object',
			'properties' => array(
				'business_id'   => array(
					'description' => 'Reddit Business id.',
					'type'        => 'string',
				),
				'business_name' => array(
					'description' => 'Reddit Business name.',
					'type'        => 'string',
				),
				'ad_acc_id'     => array(
					'description' => 'Reddit Account id.',
					'type'        => 'string',
				),
				'ad_acc_name'   => array(
					'description' => 'Reddit Account name.',
					'type'        => 'string',
				),
				'pixel_id'      => array(
					'description' => 'Reddit Pixel id.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'business_id', 'business_name', 'ad_acc_id', 'ad_acc_name', 'pixel_id' ),
		);
	}
}
