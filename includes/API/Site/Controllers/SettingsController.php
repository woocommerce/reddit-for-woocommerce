<?php
/**
 * REST controller for managing Site settings.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API\Site\Controllers;

use WP_Error;
use WP_REST_Response;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Helper;

/**
 * Controller for the `/settings` endpoint.
 *
 * @since 0.1.0
 */
class SettingsController extends RESTBaseController {
	/**
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * GET /settings
		 * - Returns site settings.
		 *
		 * POST /settings
		 * - Set site settings.
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'set_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Returns the Site settings.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response(
			$this->get_settings_response()
		);
	}

	/**
	 * Sets the Site settings.
	 *
	 * Rejects the whole request with a 400 when it sets `collect_pii` while
	 * Conversions API tracking is off, so no setting is saved.
	 *
	 * @since 0.1.0
	 * @since n.e.x.t Returns a WP_Error when `collect_pii` is sent while Conversions API tracking is off.
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_settings( $request ) {
		if ( isset( $request['collect_pii'] ) ) {
			// Check the tracking status this request would leave behind, so one request can't turn tracking off and change PII together.
			$capi_enabled = isset( $request['capi_enabled'] )
				? rest_sanitize_boolean( $request['capi_enabled'] )
				: 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED );

			if ( ! $capi_enabled ) {
				return new WP_Error(
					'rest_conversions_api_disabled',
					__( 'Collect Customer PII can only be changed while Conversions API tracking is on.', 'reddit-for-woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		if ( isset( $request['capi_enabled'] ) ) {
			$capi_status = rest_sanitize_boolean( $request['capi_enabled'] );
			Options::set( OptionDefaults::CONVERSIONS_ENABLED, $capi_status ? 'yes' : 'no' );
		}

		if ( isset( $request['products_token'] ) ) {
			Options::set( OptionDefaults::WCS_PRODUCTS_TOKEN, sanitize_text_field( $request['products_token'] ) );
		}

		if ( isset( $request['collect_pii'] ) ) {
			$collect_pii_status = rest_sanitize_boolean( $request['collect_pii'] );
			Options::set( OptionDefaults::COLLECT_PII, $collect_pii_status ? 'yes' : 'no' );
		}

		return rest_ensure_response(
			$this->get_settings_response()
		);
	}

	/**
	 * Returns the settings response structure.
	 *
	 * This method encapsulates the logic to build the response for the settings endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	private function get_settings_response() {
		$timestamp        = Options::get( OptionDefaults::LAST_EXPORT_TIMESTAMP );
		$csv_path         = Options::get( OptionDefaults::EXPORT_FILE_PATH );
		$campaign_created = ! empty( Options::get( OptionDefaults::CAMPAIGN_IDS ) );

		return rest_ensure_response(
			array(
				'capi_enabled'          => 'yes' === Options::get( OptionDefaults::CONVERSIONS_ENABLED ),
				'trigger_export'        => ! file_exists( $csv_path ) && Helper::has_products() && (int) $timestamp <= ( time() - DAY_IN_SECONDS ),
				'last_export_timestamp' => Helper::get_formatted_timestamp( $timestamp ),
				'export_file_url'       => file_exists( $csv_path ) ? Options::get( OptionDefaults::EXPORT_FILE_URL ) : '',
				'products_token'        => Options::get( OptionDefaults::WCS_PRODUCTS_TOKEN ),
				'campaign_created'      => $campaign_created,
				'collect_pii'           => 'yes' === Options::get( OptionDefaults::COLLECT_PII ),
			)
		);
	}
}
