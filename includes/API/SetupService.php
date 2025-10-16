<?php
/**
 * Initializes all settings-related REST controllers for the Ad Partner plugin.
 *
 * This class acts as the central router for registering plugin-specific REST endpoints,
 * primarily under the `wc/rfw/<ad_partner>` namespace.
 *
 * Controllers are instantiated with their required dependencies here and hooked into the REST API lifecycle.
 *
 * @package RedditForWooCommerce\API\Site\Controllers
 */

namespace RedditForWooCommerce\API;

use Action_Scheduler\Migration\Controller;
use Automattic\Jetpack\Connection\Manager;
use RedditForWooCommerce\ServiceContainer;
use RedditForWooCommerce\ServiceKey;
use RedditForWooCommerce\Config;
use RedditForWooCommerce\API\Site\Controllers;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;

/**
 * Bootstrap class for registering REST API routes related to plugin settings.
 *
 * Instantiates individual controllers and registers their routes.
 *
 * @since 0.1.0
 */
class SetupService {

	/**
	 * Registers all REST API routes used in the plugin settings.
	 *
	 * This method is typically called during the `rest_api_init` hook.
	 *
	 * Example:
	 * ```php
	 * add_action( 'rest_api_init', array( new Setup(), 'register_routes' ) );
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$wcs_client     = ServiceContainer::get( ServiceKey::WCS_CLIENT );
		$manager        = new Manager( Config::PLUGIN_SLUG );
		$ad_partner_api = AdPartnerApi::get_instance( $wcs_client );

		( new Controllers\JetpackAccountController( $wcs_client, $manager ) )->register_routes();
		( new Controllers\OnboardingController() )->register_routes();
		( new Controllers\RedditConnectionController( $wcs_client, $ad_partner_api ) )->register_routes();
		( new Controllers\SettingsController() )->register_routes();
		( new Controllers\CampaignController( $wcs_client, $ad_partner_api ) )->register_routes();
	}
}
