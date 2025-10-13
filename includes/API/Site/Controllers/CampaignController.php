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
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\Utils\Storage\TransientDefaults;
use RedditForWooCommerce\Utils\Storage\Transients;

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
					'callback'            => array( $this, 'create_campaign_callback' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Create a campaign REST callback.
	 *
	 * Creates a campaign, a product set, an ad group, and an ad.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function create_campaign_callback( $request ) {
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

		// Create a new campaign. If the campaign creation fails, return an error.
		$campaign = $this->create_campaign();
		if ( is_wp_error( $campaign ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $campaign->get_error_message(),
				),
				500
			);
		}

		// Create a new product set for the catalog. If the product set creation fails, return an error.
		$product_set = $this->create_product_set();
		if ( is_wp_error( $product_set ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $product_set->get_error_message(),
				),
				500
			);
		}

		// Create a new ad group for the campaign to set the daily budget.
		$ad_group = $this->create_ad_group( $campaign, $product_set, $amount );
		if ( is_wp_error( $ad_group ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $ad_group->get_error_message(),
				),
				500
			);
		}

		// Create a new ad for the campaign.
		$ad = $this->create_ad( $ad_group );
		if ( is_wp_error( $ad ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $ad->get_error_message(),
				),
				500
			);
		}

		// Delete the transients.
		Transients::delete( TransientDefaults::CAMPAIGN_ID );
		Transients::delete( TransientDefaults::PRODUCT_SET_ID );

		return rest_ensure_response(
			array(
				'status'  => 'success',
				'message' => __( 'Ad campaign created successfully.', 'reddit-for-woocommerce' ),
			)
		);
	}

	/**
	 * Create a campaign.
	 *
	 * @return string|\WP_Error Campaign ID or WP_Error if something went wrong.
	 */
	private function create_campaign() {
		// Get the campaign ID from the transients.
		$campaign_id = Transients::get( TransientDefaults::CAMPAIGN_ID );

		// If the campaign ID is not set, create a new campaign.
		if ( ! $campaign_id ) {
			$campaign = $this->ad_partner_api->campaigns->create();

			if ( is_wp_error( $campaign ) ) {
				return $campaign;
			}

			$campaign_data = $campaign->get_data();
			$campaign_id   = $campaign_data['data']['id'] ?? '';
			if ( empty( $campaign_id ) ) {
				return new \WP_Error(
					'something_went_wrong',
					__( 'Something went wrong while creating the campaign.', 'reddit-for-woocommerce' ),
				);
			}

			Transients::set( TransientDefaults::CAMPAIGN_ID, $campaign_id );
		}

		return $campaign_id;
	}

	/**
	 * Create a product set.
	 *
	 * @return string|\WP_Error Product set ID or WP_Error if something went wrong.
	 */
	private function create_product_set() {
		// Get the product set ID from the transients.
		$product_set_id = Transients::get( TransientDefaults::PRODUCT_SET_ID );

		// If the product set ID is not set, create a new product set.
		if ( ! $product_set_id ) {
			$product_set = $this->ad_partner_api->product_sets->create();

			if ( is_wp_error( $product_set ) ) {
				return $product_set;
			}

			$product_set_data = $product_set->get_data();
			$product_set_id   = $product_set_data['data']['id'] ?? '';

			if ( empty( $product_set_id ) ) {
				return new \WP_Error(
					'something_went_wrong',
					__( 'Something went wrong while creating the product set.', 'reddit-for-woocommerce' ),
				);
			}

			Transients::set( TransientDefaults::PRODUCT_SET_ID, $product_set_id );
		}

		return $product_set_id;
	}

	/**
	 * Create an ad group.
	 *
	 * Creates an ad group for the campaign to set the daily budget.
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id    Campaign ID.
	 * @param string $product_set_id Product set ID.
	 * @param string $daily_budget   Daily budget.
	 *
	 * @return string|\WP_Error Ad group ID or WP_Error if something went wrong.
	 */
	private function create_ad_group( $campaign_id, $product_set_id, $daily_budget ) {
		$ad_group = $this->ad_partner_api->ad_groups->create(
			array(
				'campaign_id'    => $campaign_id,
				'product_set_id' => $product_set_id,
				'daily_budget'   => $daily_budget,
			)
		);

		// If the ad group creation fails, return an error.
		if ( is_wp_error( $ad_group ) ) {
			return $ad_group;
		}

		$ad_group_data = $ad_group->get_data();
		$ad_group_id   = $ad_group_data['data']['id'] ?? '';

		if ( empty( $ad_group_id ) ) {
			return new \WP_Error(
				'something_went_wrong',
				__( 'Something went wrong while creating the ad group.', 'reddit-for-woocommerce' ),
			);
		}

		return $ad_group_id;
	}

	/**
	 * Create an ad.
	 *
	 * @param string $ad_group_id Ad group ID.
	 *
	 * @return string|\WP_Error Ad ID or WP_Error if something went wrong.
	 */
	private function create_ad( $ad_group_id ) {
		// Create a new ad for the campaign.
		$ad = $this->ad_partner_api->ads->create( $ad_group_id );
		if ( is_wp_error( $ad ) ) {
			return $ad;
		}

		$ad_data = $ad->get_data();
		$ad_id   = $ad_data['data']['id'] ?? '';

		if ( empty( $ad_id ) ) {
			return new \WP_Error(
				'something_went_wrong',
				__( 'Something went wrong while creating the ad.', 'reddit-for-woocommerce' ),
			);
		}

		return $ad_id;
	}
}
