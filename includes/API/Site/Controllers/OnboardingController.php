<?php
/**
 * REST controller for managing the Reddit onboarding state.
 *
 * This controller handles the retrieval and update of onboarding status
 * and step for the authenticated merchant, allowing the plugin to track
 * the merchant's progress through the setup flow.
 *
 * Onboarding data is stored locally in WordPress options.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API\Site\Controllers;

use WP_REST_Response;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Controller for the `/onboarding/setup` endpoint.
 *
 * @since 0.1.0
 */
class OnboardingController extends RESTBaseController {
	/**
	 * Registers REST API routes.
	 *
	 * Provides GET and POST methods for onboarding state:
	 * - GET  /setup
	 * - POST /setup
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'/setup',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'get_setup_state' ),
				),
				'schema' => array( $this, 'setup_state_schema' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/reddit',
			'/setup/complete',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $this, 'permissions_check' ),
					'callback'            => array( $this, 'complete_setup' ),
				),
				'schema' => array( $this, 'setup_state_schema' ),
			)
		);
	}

	/**
	 * Returns the current onboarding setup state.
	 *
	 * @since 0.1.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_setup_state(): WP_REST_Response {
		$status = Options::get( OptionDefaults::ONBOARDING_STATUS );
		$step   = Options::get( OptionDefaults::ONBOARDING_STEP );

		return rest_ensure_response(
			array(
				'status' => $status,
				'step'   => $step,
			)
		);
	}

	/**
	 * Completes the onboarding setup.
	 *
	 * @since 1.0.3
	 *
	 * @return WP_REST_Response
	 */
	public function complete_setup(): WP_REST_Response {
		/**
		 * Triggers before the Reddit onboarding process marked as completed.
		 *
		 * @since 0.1.0
		 */
		do_action( Helper::with_prefix( 'before_onboarding_complete' ) );

		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		Options::set( OptionDefaults::ONBOARDING_STEP, 'paid_ads' );

		/**
		 * Triggers when the Reddit onboarding process is completed.
		 *
		 * @since 1.0.3
		 */
		do_action( Helper::with_prefix( 'onboarding_complete' ) );

		return $this->get_setup_state();
	}

	/**
	 * Returns the JSON schema for the `/onboarding/setup` endpoint.
	 *
	 * This schema defines the expected structure of the onboarding state,
	 * including the `status` and `step` fields, both of which are strings.
	 *
	 * @since 0.1.0
	 *
	 * @return array JSON Schema for onboarding setup state.
	 */
	public function setup_state_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'reddit_setup_state',
			'type'       => 'object',
			'properties' => array(
				'status' => array(
					'description' => 'The status of merchant onboarding.',
					'type'        => 'string',
				),
				'step'   => array(
					'description' => 'The current step of merchant onboarding process.',
					'type'        => 'string',
				),
			),
			'required'   => array( 'status', 'step' ),
		);
	}
}
