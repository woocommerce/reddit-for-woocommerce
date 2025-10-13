<?php
/**
 * REST controller for managing the Reddit Ads OAuth connection.
 *
 * This controller handles the Jetpack-authenticated connection flow with
 * WooCommerce Connect Server (WCS) for Reddit Ads.
 *
 * It supports three endpoints:
 * - POST   /connect     – Start the Reddit OAuth flow.
 * - GET    /connection  – Check current Reddit connection status.
 * - DELETE /connection  – Disconnect the Reddit account.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API\Site\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Transients;
use RedditForWooCommerce\Utils\Storage\TransientDefaults;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;

/**
 * Controller for setting up and managing the Reddit account connection.
 *
 * @since 0.1.0
 */
class RedditConnectionController extends RESTBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Instance of the Ad Partner API.
	 *
	 * @var AdPartnerApi
	 */
	protected AdPartnerApi $ad_partner_api;

	/**
	 * Constructor.
	 *
	 * @param WcsClient    $wcs            WCS proxy request client.
	 * @param AdPartnerApi $ad_partner_api Ad Partner API.
	 */
	public function __construct( WcsClient $wcs, AdPartnerApi $ad_partner_api ) {
		$this->wcs            = $wcs;
		$this->ad_partner_api = $ad_partner_api;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'connect',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'initiate_oauth' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'connection',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'check_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_connection' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			),
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'config',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'do_config' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'do_config' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'config_schema' ),
			),
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'businesses',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_businesses' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'ad_accounts',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ad_accounts' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'pixels',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_pixels' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);
	}

	/**
	 * Sends a GET request to the WCS `/connection/status` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Connection status or error.
	 */
	public function get_connection_status() {
		return $this->wcs->proxy_get( 'connection/status' );
	}

	/**
	 * Sends a POST request to the WCS `/connection/connect` endpoint to initiate a connection.
	 *
	 * @since 0.1.0
	 *
	 * @param string $return_url    URL to redirect the user to after authorization.
	 *
	 * @return WP_REST_Response|WP_Error Connection initiation response or error.
	 */
	public function start_connection( string $return_url ) {
		return $this->wcs->proxy_get( 'connection/connect', array( 'return_url' => $return_url ) );
	}

	/**
	 * Sends a GET request to the WCS `/connection` endpoint to disconnect the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response|WP_Error Disconnection response or error.
	 */
	public function stop_connection() {
		return $this->wcs->proxy_get( 'connection/disconnect' );
	}

	/**
	 * Starts the OAuth flow.
	 *
	 * @return WP_REST_Response
	 */
	public function initiate_oauth() {
		$return_url = rawurlencode( admin_url( 'admin.php?page=wc-admin&path=/reddit/setup' ) );
		$response   = $this->start_connection( $return_url );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$data = $response->get_data();

		if ( empty( $data['oauth_url'] ) ) {
			return rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid response from WCS. OAuth URL missing.', 'reddit-for-woocommerce' ),
				)
			);
		}

		return rest_ensure_response(
			array( 'url' => esc_url_raw( $data['oauth_url'] ) )
		);
	}

	/**
	 * Checks connection status.
	 *
	 * @return WP_REST_Response
	 */
	public function check_connection() {
		// Bail early if Jetpack is disconnected.
		if ( 'yes' !== Options::get( OptionDefaults::IS_JETPACK_CONNECTED ) ) {
			return rest_ensure_response(
				array(
					'status' => 'disconnected',
				)
			);
		}

		$response = $this->get_connection_status();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data  = $response->get_data();
		$email = Transients::get( TransientDefaults::REDDIT_ACCOUNT_EMAIL );

		if ( empty( $email ) ) {
			$member = $this->ad_partner_api->members->me();

			if ( is_wp_error( $member ) ) {
				$logger = wc_get_logger();
				$logger->alert(
					'Reddit member not found.',
				);
			} else {
				$member_data = $member->get_data();
				$email       = $member_data['data']['email'] ?? '';
				Transients::set( TransientDefaults::REDDIT_ACCOUNT_EMAIL, $email );
			}
		}

		return rest_ensure_response(
			array(
				'status' => $data['status'],
				'email'  => $email,
			)
		);
	}

	/**
	 * Disconnects the merchant account.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_connection() {
		$catalog_id    = Options::get( OptionDefaults::CATALOG_ID );
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		// Delete the catalog if it exists.
		if ( $catalog_id ) {
			$delete_catalog_response = $this->ad_partner_api->catalog->delete( $catalog_id );

			if ( is_wp_error( $delete_catalog_response ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $delete_catalog_response->get_error_message(),
						'data'    => $delete_catalog_response->get_error_data(),
					),
					500
				);
			}
		}

		$response = $this->stop_connection();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data         = $response->get_data();
		$oauth_status = '';

		if ( $data['status'] && 'disconnected' === $data['status'] ) {
			$oauth_status = $data['status'];
		}

		Options::delete( OptionDefaults::BUSINESS_ID );
		Options::delete( OptionDefaults::BUSINESS_NAME );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::AD_ACCOUNT_NAME );
		Options::delete( OptionDefaults::PIXEL_ID );
		Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );
		Options::delete( OptionDefaults::ONBOARDING_STATUS );
		Options::delete( OptionDefaults::LAST_EXPORT_TIMESTAMP );
		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
		Options::delete( OptionDefaults::CATALOG_ID );
		Options::delete( OptionDefaults::FEED_STATUS );
		Options::delete( OptionDefaults::WCS_PRODUCTS_TOKEN );
		Options::delete( OptionDefaults::PROFILE_ID );
		Options::delete( OptionDefaults::DUMMY_PURCHASE_TRACKED );
		Transients::delete( TransientDefaults::REDDIT_ACCOUNT_EMAIL );
		Transients::delete( TransientDefaults::PIXEL_SCRIPT );
		Transients::delete( sprintf( '%s_%s', TransientDefaults::CAMPAIGN_ID, $ad_account_id ) );
		Transients::delete( sprintf( '%s_%s', TransientDefaults::PRODUCT_SET_ID, $ad_account_id ) );

		/**
		 * Triggers when Reddit is disconnected.
		 *
		 * @since 0.1.0
		 */
		do_action( Helper::with_prefix( 'reddit_disconnected' ) );

		return rest_ensure_response(
			array(
				'status' => $oauth_status,
			)
		);
	}

	/**
	 * GET/POST the Reddit Ads configuration.
	 *
	 * Fetches or stores the related Business ID, Ad Account ID, Pixel ID, and Conversion API token.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function do_config( WP_REST_Request $request ) {
		if ( 'GET' === $request->get_method() ) {
			return $this->get_connection_details();
		}

		$params = $request->get_json_params();

		if ( isset( $params['business_id'] ) ) {
			Options::set( OptionDefaults::BUSINESS_ID, sanitize_text_field( $params['business_id'] ) );
		}

		if ( isset( $params['business_name'] ) ) {
			Options::set( OptionDefaults::BUSINESS_NAME, sanitize_text_field( $params['business_name'] ) );
		}

		if ( isset( $params['ad_account_id'] ) ) {
			Options::set( OptionDefaults::AD_ACCOUNT_ID, sanitize_text_field( $params['ad_account_id'] ) );
		}

		if ( isset( $params['ad_account_name'] ) ) {
			Options::set( OptionDefaults::AD_ACCOUNT_NAME, sanitize_text_field( $params['ad_account_name'] ) );
		}

		if ( isset( $params['pixel_id'] ) ) {
			Options::set( OptionDefaults::PIXEL_ID, sanitize_text_field( $params['pixel_id'] ) );
		}

		$is_jetpack_connected = 'yes' === Options::get( OptionDefaults::IS_JETPACK_CONNECTED );
		$business_id          = Options::get( OptionDefaults::BUSINESS_ID );
		$ad_account_id        = Options::get( OptionDefaults::AD_ACCOUNT_ID );
		$pixel_id             = Options::get( OptionDefaults::PIXEL_ID );

		// Mark the onboarding process as connected, if Jetpack is connected, and the business id, ad account id, and pixel id are set.
		if ( $is_jetpack_connected && ! empty( $business_id ) && ! empty( $ad_account_id ) && ! empty( $pixel_id ) ) {
			/**
			 * Triggers before the Reddit onboarding process marked as completed.
			 *
			 * @since 0.1.0
			 */
			do_action( Helper::with_prefix( 'before_onboarding_complete' ) );

			Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );

			// Set the profile ID.
			$member = $this->ad_partner_api->members->me();

			if ( is_wp_error( $member ) ) {
				$logger = wc_get_logger();
				$logger->alert(
					'Reddit member not found.',
				);
			} else {
				$member_data = $member->get_data();
				if ( ! empty( $member_data['data'] ) ) {
					Options::set( OptionDefaults::PROFILE_ID, $member_data['data']['id'] );
				}
			}

			// Create a new catalog for the business.
			$response = $this->ad_partner_api->catalog->create();

			if ( is_wp_error( $response ) ) {
				$logger = wc_get_logger();
				$logger->alert(
					'Catalog generation failed with error code: ' . $response->get_error_code(),
				);
			} else {
				$data         = $response->get_data();
				$catalog_data = $data['data'] ?? array();

				if ( ! empty( $catalog_data ) ) {
					Options::set( OptionDefaults::CATALOG_ID, $catalog_data['id'] );
				}
			}

			/**
			 * Triggers when the Reddit onboarding process is completed.
			 *
			 * @since 0.1.0
			 */
			do_action( Helper::with_prefix( 'onboarding_complete' ) );
		}

		return $this->get_connection_details();
	}

	/**
	 * Returns an array of Merchant Account details.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_connection_details() {
		return rest_ensure_response(
			array(
				'business_id'     => Options::get( OptionDefaults::BUSINESS_ID ),
				'business_name'   => Options::get( OptionDefaults::BUSINESS_NAME ),
				'ad_account_id'   => Options::get( OptionDefaults::AD_ACCOUNT_ID ),
				'ad_account_name' => Options::get( OptionDefaults::AD_ACCOUNT_NAME ),
				'pixel_id'        => Options::get( OptionDefaults::PIXEL_ID ),
			)
		);
	}

	/**
	 * Retrieves the list of businesses associated with the authenticated merchant.
	 *
	 * This method proxies a request to the Ad Partner's Business API via
	 * WooCommerce Connect Server (WCS) to fetch all businesses available
	 * to the merchant. The response is normalized into a simplified array
	 * of business IDs and names for use in the onboarding UI.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response REST response containing the formatted list of businesses
	 *                          (each with `business_id` and `business_name`) or error details.
	 */
	public function get_businesses() {
		$response = $this->ad_partner_api->business->list();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data = $response->get_data();
		$data = $data['data'] ?? array();

		$formatted = array_map(
			fn( $business ) => array(
				'business_id'   => $business['id'] ?? '',
				'business_name' => $business['name'] ?? '',
			),
			$data
		);

		return rest_ensure_response( $formatted );
	}

	/**
	 * Retrieves the list of ad accounts for the configured business.
	 *
	 * This method proxies a request to the Ad Partner's Ad Accounts API via
	 * WooCommerce Connect Server (WCS) to fetch all ad accounts associated
	 * with the business ID stored in plugin options. The response is
	 * normalized into a simplified array containing ad account IDs, names,
	 * and their associated business ID.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response REST response containing the formatted list of ad accounts
	 *                          (each with `ad_account_id`, `ad_account_name`, `business_id`)
	 *                          or error details.
	 */
	public function get_ad_accounts() {
		$response = $this->ad_partner_api->ad_accounts->list();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data = $response->get_data();
		$data = $data['data'] ?? array();

		$formatted = array_map(
			fn( $ad_account ) => array(
				'ad_account_id'   => $ad_account['id'] ?? '',
				'ad_account_name' => $ad_account['name'] ?? '',
				'business_id'     => $ad_account['business_id'] ?? '',
			),
			$data
		);

		return rest_ensure_response( $formatted );
	}

	/**
	 * Retrieves the list of pixels for the configured business.
	 *
	 * This method proxies a request to the Ad Partner's Pixel API via
	 * WooCommerce Connect Server (WCS) to fetch all tracking pixels
	 * associated with the current business. The response is normalized
	 * into a simplified array containing pixel IDs, names, and their
	 * associated business ID.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response REST response containing the formatted list of pixels
	 *                          (each with `pixel_id`, `pixel_name`, `business_id`)
	 *                          or error details.
	 */
	public function get_pixels() {
		$response = $this->ad_partner_api->pixels->list();

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
				),
				500
			);
		}

		$data = $response->get_data();
		$data = $data['data'] ?? array();

		$formatted = array_map(
			fn( $ad_account ) => array(
				'pixel_id'    => $ad_account['id'] ?? '',
				'pixel_name'  => $ad_account['name'] ?? '',
				'business_id' => $ad_account['business_id'] ?? '',
			),
			$data
		);

		return rest_ensure_response( $formatted );
	}

	/**
	 * Returns the JSON Schema for the `/config` endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema definition.
	 */
	public function config_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'reddit_merchant_config',
			'type'       => 'object',
			'properties' => array(
				'business_id'   => array(
					'description' => "Selected Business's id.",
					'type'        => 'string',
				),
				'business_name' => array(
					'description' => "Selected Business's name.",
					'type'        => 'string',
				),
				'ad_acc_id'     => array(
					'description' => 'Selected Ad Account id.',
					'type'        => 'string',
				),
				'ad_acc_name'   => array(
					'description' => 'Selected Ad Account name.',
					'type'        => 'string',
				),
				'pixel_id'      => array(
					'description' => 'Selected Pixel id.',
					'type'        => 'string',
				),
			),
		);
	}
}
