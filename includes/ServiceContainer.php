<?php
/**
 * Central service container for managing Ad Partner plugin dependencies.
 *
 * This static container resolves and stores instances of services like connection handlers,
 * authenticators, API clients, and tracking systems, ensuring singleton-like access.
 *
 * @package RedditForWooCommerce
 */

namespace RedditForWooCommerce;

use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Tracking\PixelTrackingService;
use RedditForWooCommerce\Tracking\RemotePixelTracker;
use RedditForWooCommerce\API;
use RedditForWooCommerce\Connection;
use RedditForWooCommerce\Tracking;
use RedditForWooCommerce\Admin;
use RedditForWooCommerce\Admin\Export;
use RedditForWooCommerce\Admin\ProductMeta;
use RedditForWooCommerce\Tracking\ConversionEventLogger;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\Utils\ProductData\ProductCategoryProvider;
use function wc_get_logger;


/**
 * Static service container for resolving shared instances across the Ad Partner plugin.
 *
 * This container lazily initializes services the first time they are requested and stores
 * them for subsequent use. Services are identified by string keys (e.g., 'connection', 'wcs_client').
 *
 * It simplifies dependency injection and ensures consistency across the plugin’s components.
 */
final class ServiceContainer {
	/**
	 * Stores resolved service instances.
	 *
	 * @var array<string,object>
	 */
	private static $instances = array();

	/**
	 * Retrieves a shared instance of a requested service.
	 *
	 * If the service has not yet been created, it is resolved and cached internally.
	 *
	 * @since 0.1.0
	 *
	 * @param string $service Name of the service to retrieve (e.g., 'connection').
	 *
	 * @return object The resolved service instance.
	 */
	public static function get( string $service ) {
		if ( ! isset( self::$instances[ $service ] ) ) {
			self::$instances[ $service ] = self::resolve( $service );
		}

		return self::$instances[ $service ];
	}

	/**
	 * Resolves and instantiates a service based on its string identifier.
	 *
	 * This acts as the internal factory for all supported services.
	 *
	 * @since 0.1.0
	 *
	 * @param string $service The service name.
	 *
	 * @return object The newly instantiated service.
	 *
	 * @throws \InvalidArgumentException If the requested service is unknown.
	 */
	private static function resolve( string $service ) {
		switch ( $service ) {
			case ServiceKey::JETPACK_AUTHENTICATOR:
				return new Connection\JetpackAuthenticator();
			case ServiceKey::WCS_CLIENT:
				return new WcsClient(
					self::get( ServiceKey::JETPACK_AUTHENTICATOR ),
					new Connection\JetpackClient()
				);

			default:
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \InvalidArgumentException( "Unknown service: $service" );
		}
	}
}
