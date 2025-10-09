<?php
/**
 * REST controller for managing Campaigns.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;

/**
 * Controller for the `/campaigns` endpoint.
 *
 * @since 0.1.0
 */
class CampaignController extends RESTBaseController {
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
	 * Registers REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		/**
		 * POST /campaigns
		 * - Create a campaign.
		 */
		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'/campaigns',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_campaign' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Creates a campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function create_campaign( $request ) {
		$params = $request->get_json_params();

		if ( ! isset( $params['amount'] ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => __( 'Amount is required.', 'reddit-for-woocommerce' ),
				),
				400
			);
		}

		$amount = floatval( wp_unslash( $params['amount'] ) );
		if ( $amount <= 0 ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => __( 'Amount must be greater than 0.', 'reddit-for-woocommerce' ),
				),
				400
			);
		}

		// Get the campaign ID from the options.
		$campaign_id = Options::get( OptionDefaults::CAMPAIGN_ID );

		// If the campaign ID is not set, create a new campaign.
		if ( ! $campaign_id ) {
			$campaign = $this->ad_partner_api->campaigns->create();

			if ( is_wp_error( $campaign ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => $campaign->get_error_message(),
					),
					500
				);
			}

			$campaign_data = $campaign->get_data();
			$campaign_id   = $campaign_data['data']['id'] ?? '';
			if ( empty( $campaign_id ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => __( 'Something went wrong while creating the campaign.', 'reddit-for-woocommerce' ),
					),
					500
				);
			}

			Options::set( OptionDefaults::CAMPAIGN_ID, $campaign_id );
		}

		// Create a new ad group for the campaign to set the daily budget.
		$ad_group = $this->ad_partner_api->ad_groups->create(
			array(
				'campaign_id'  => $campaign_id,
				'daily_budget' => $amount,
			)
		);

		// If the ad group creation fails, return an error.
		if ( is_wp_error( $ad_group ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $ad_group->get_error_message(),
				),
				500
			);
		}

		// As Campaign and Ad Group are created, Remove the campaign ID from the options and return success.
		Options::delete( OptionDefaults::CAMPAIGN_ID );

		return rest_ensure_response(
			array(
				'status'  => 'success',
				'message' => __( 'Campaign created successfully.', 'reddit-for-woocommerce' ),
			)
		);
	}
}
