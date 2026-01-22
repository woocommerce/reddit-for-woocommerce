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
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\TransientDefaults;
use RedditForWooCommerce\Utils\Storage\Transients;
use WP_REST_Request;
use WP_Error;

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
					'args'                => array(
						'amount' => array(
							'type'              => 'number',
							'description'       => __( 'Daily budget amount in the ad account currency.', 'reddit-for-woocommerce' ),
							'required'          => true,
							'validate_callback' => array( $this, 'validate_amount_callback' ),
						),
					),
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
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function create_campaign_callback( $request ) {
		$params        = $request->get_json_params();
		$amount        = floatval( wp_unslash( $params['amount'] ) );
		$ad_account_id = Options::get( OptionDefaults::AD_ACCOUNT_ID );

		// Create a new campaign. If the campaign creation fails, return an error.
		$campaign_id = $this->create_campaign();

		if ( is_wp_error( $campaign_id ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $campaign_id->get_error_message(),
				),
				500
			);
		}

		// Create a new product set for the catalog. If the product set creation fails, return an error.
		$product_set_id = $this->get_product_set_id();

		if ( is_wp_error( $product_set_id ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $product_set_id->get_error_message(),
				),
				500
			);
		}

		// Create ad groups for the campaign to set the daily budget and targeting type.
		$ad_group_ids = $this->create_ad_groups( $campaign_id, $product_set_id, $amount );

		if ( is_wp_error( $ad_group_ids ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $ad_group_ids->get_error_message(),
				),
				500
			);
		}

		// Create a new ads for the ad groups.
		$ad_ids = $this->create_ads( $ad_group_ids );

		if ( is_wp_error( $ad_ids ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => $ad_ids->get_error_message(),
				),
				500
			);
		}

		// Delete the transients.
		Transients::delete( sprintf( '%s_%s', TransientDefaults::PRODUCT_SET_ID, $ad_account_id ) );

		// Set created campaign ID to the options, to archive it while disconnect account.
		$campaign_ids   = Options::get( OptionDefaults::CAMPAIGN_IDS );
		$campaign_ids[] = $campaign_id;
		Options::set( OptionDefaults::CAMPAIGN_IDS, $campaign_ids );

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
	 * @return string|WP_Error Campaign ID or WP_Error if something went wrong.
	 */
	private function create_campaign() {
		$campaign = $this->ad_partner_api->campaigns->create();

		if ( is_wp_error( $campaign ) ) {
			return $campaign;
		}

		$campaign_data = $campaign->get_data();
		$campaign_id   = $campaign_data['data']['id'] ?? '';
		if ( empty( $campaign_id ) ) {
			return new WP_Error(
				'something_went_wrong',
				__( 'Something went wrong while creating the campaign.', 'reddit-for-woocommerce' ),
			);
		}

		return $campaign_id;
	}

	/**
	 * Returns the product set ID of the set that is created by Reddit when
	 * a Catalog is created. This ID refers to the set pointing to All Products.
	 */
	private function get_product_set_id() {
		$product_set_id = null;
		$response       = $this->ad_partner_api->product_sets->get_all_products_set_id();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();

		$product_set = current(
			array_filter(
				$data['data'],
				fn( $ps ) => 'All Products' === $ps['name']
			)
		);

		if ( ! empty( $product_set['id'] ) ) {
			return $product_set['id'];
		}

		$product_set_id = $this->create_product_set();

		return $product_set_id ?? new WP_Error(
			'product_set_id_not_found',
			__( 'Product set ID not found', 'reddit-for-woocommerce' )
		);
	}

	/**
	 * Create a product set.
	 *
	 * @return string|WP_Error Product set ID or WP_Error if something went wrong.
	 */
	private function create_product_set() {
		// Get the product set ID from the transients.
		$ad_account_id  = Options::get( OptionDefaults::AD_ACCOUNT_ID );
		$transient_key  = sprintf( '%s_%s', TransientDefaults::PRODUCT_SET_ID, $ad_account_id );
		$product_set_id = Transients::get( $transient_key );

		// If the product set ID is not set, create a new product set.
		if ( ! $product_set_id ) {
			$product_set = $this->ad_partner_api->product_sets->create();

			if ( is_wp_error( $product_set ) ) {
				return $product_set;
			}

			$product_set_data = $product_set->get_data();
			$product_set_id   = $product_set_data['data']['id'] ?? '';

			if ( empty( $product_set_id ) ) {
				return new WP_Error(
					'something_went_wrong',
					__( 'Something went wrong while creating the product set.', 'reddit-for-woocommerce' ),
				);
			}

			Transients::set( $transient_key, $product_set_id );
		}

		return $product_set_id;
	}

	/**
	 * Create ad groups.
	 *
	 * Creates ad groups for the campaign to set the daily budget and targeting type.
	 *
	 * 2 Ad Groups are created with different targeting types and budgets:
	 * - Prospecting
	 * - Retargeting
	 *   - Retarget people with previous events: add to cart + view content
	 *
	 * If a user enters a custom daily budget less than $17:
	 * - Prospecting: 50% budget
	 * - Retargeting: 50% budget
	 *
	 * If a user enters a custom daily budget greater than or equal to $17:
	 * - Prospecting: 70% budget
	 * - Retargeting: 30% budget
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id    Campaign ID.
	 * @param string $product_set_id Product set ID.
	 * @param float  $daily_budget   Daily budget.
	 *
	 * @return array|WP_Error Ad group IDs or WP_Error if something went wrong.
	 */
	private function create_ad_groups( $campaign_id, $product_set_id, $daily_budget ) {
		$ad_group_ids = array();

		$targeting_types = array(
			'PROSPECTING' => 0.70,
			'RETARGETING' => 0.30,
		);

		// If the daily budget is less than $17, set the budget to 50% for both targeting types.
		if ( $daily_budget < 17 ) {
			$targeting_types['PROSPECTING'] = 0.50;
			$targeting_types['RETARGETING'] = 0.50;
		}

		foreach ( $targeting_types as $targeting_type => $budget_percentage ) {
			$ad_group_data = array(
				'campaign_id'    => $campaign_id,
				'product_set_id' => $product_set_id,
				'daily_budget'   => floatval( $daily_budget * $budget_percentage ),
				'targeting_type' => $targeting_type,
			);

			$ad_group = $this->ad_partner_api->ad_groups->create( $ad_group_data );
			if ( is_wp_error( $ad_group ) ) {
				return $ad_group;
			}

			$ad_group_data = $ad_group->get_data();
			$ad_group_id   = $ad_group_data['data']['id'] ?? '';
			if ( empty( $ad_group_id ) ) {
				return new WP_Error(
					'something_went_wrong',
					__( 'Something went wrong while creating the ad group.', 'reddit-for-woocommerce' ),
				);
			}
			$ad_group_ids[ $targeting_type ] = $ad_group_id;
		}

		return $ad_group_ids;
	}

	/**
	 * Create an ads for the ad groups.
	 *
	 * @param array $ad_group_ids Ad group IDs.
	 *
	 * @return array|WP_Error Ad IDs or WP_Error if something went wrong.
	 */
	private function create_ads( $ad_group_ids ) {
		$ad_ids = array();

		$profile_id = $this->get_profile_id();
		if ( is_wp_error( $profile_id ) ) {
			return $profile_id;
		}

		foreach ( $ad_group_ids as $ad_group_id ) {
			// Create a new ad for the campaign.
			$ad = $this->ad_partner_api->ads->create( $ad_group_id, $profile_id );
			if ( is_wp_error( $ad ) ) {
				return $ad;
			}

			$ad_data = $ad->get_data();
			$ad_id   = $ad_data['data']['id'] ?? '';

			if ( empty( $ad_id ) ) {
				return new WP_Error(
					'something_went_wrong',
					__( 'Something went wrong while creating the ad.', 'reddit-for-woocommerce' ),
				);
			}
			$ad_ids[] = $ad_id;
		}

		return $ad_ids;
	}

	/**
	 * Validate the amount argument.
	 *
	 * @param mixed           $amount   The amount to validate.
	 * @param WP_REST_Request $request  The request object.
	 * @param string          $param    The parameter name.
	 *
	 * @return bool|WP_Error True if the amount is valid, WP_Error if something went wrong.
	 */
	public function validate_amount_callback( $amount, $request, $param ) {
		$validation_result = rest_validate_request_arg( $amount, $request, $param );
		if ( true !== $validation_result ) {
			return $validation_result;
		}

		$amount = floatval( wp_unslash( $amount ) );

		if ( $amount < 10 ) {
			return new WP_Error(
				'invalid_daily_budget',
				__( 'Daily budget must be greater than 10.', 'reddit-for-woocommerce' )
			);
		}

		return true;
	}

	/**
	 * Get the profile ID for the configured business.
	 *
	 * This method fetches all profiles for the configured business and returns the first profile ID.
	 *
	 * @return string|WP_Error Profile ID or WP_Error if profile ID is not found.
	 */
	private function get_profile_id() {
		// Get the list of profiles.
		$profiles = $this->ad_partner_api->profiles->list();

		if ( is_wp_error( $profiles ) ) {
			return $profiles;
		}

		$profiles_data = $profiles->get_data();
		$profile       = current( $profiles_data['data'] ?? array() );
		$profile_id    = $profile['id'] ?? '';
		if ( empty( $profile_id ) ) {
			return new WP_Error(
				'profile_id_not_found',
				__( 'Profile ID not found.', 'reddit-for-woocommerce' )
			);
		}

		return $profile_id;
	}
}
