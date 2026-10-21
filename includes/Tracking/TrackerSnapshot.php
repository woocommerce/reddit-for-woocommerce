<?php
/**
 * Adds Reddit for WooCommerce settings to the WooCommerce usage tracker snapshot.
 *
 * @package RedditForWooCommerce\Tracking
 */

namespace RedditForWooCommerce\Tracking;

use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * Reports the plugin's current settings in WooCommerce's usage tracker data.
 *
 * Click events only record changes, so a store that keeps the default
 * settings would otherwise never report its state. The snapshot holds
 * settings flags only, never customer data.
 *
 * @since n.e.x.t
 */
class TrackerSnapshot {
	/**
	 * Key for the plugin's entry in the tracker's `extensions` list.
	 *
	 * @since n.e.x.t
	 */
	private const EXTENSION_KEY = 'reddit-for-woocommerce';

	/**
	 * Registers the tracker data filter.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'woocommerce_tracker_data', array( self::class, 'add_snapshot_data' ) );
	}

	/**
	 * Adds the plugin's settings to the tracker data.
	 *
	 * @since n.e.x.t
	 *
	 * @param mixed $data Tracker data collected so far.
	 *
	 * @return array Tracker data with the plugin's settings added.
	 */
	public static function add_snapshot_data( $data ): array {
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( ! isset( $data['extensions'] ) || ! is_array( $data['extensions'] ) ) {
			$data['extensions'] = array();
		}

		$data['extensions'][ self::EXTENSION_KEY ] = array(
			'settings' => self::get_settings(),
		);

		return $data;
	}

	/**
	 * Returns the settings reported in the snapshot.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,string> Plugin version and `yes`/`no` setting flags.
	 */
	private static function get_settings(): array {
		return array(
			'version'             => REDDIT_FOR_WOOCOMMERCE_VERSION,
			'connected'           => 'connected' === Options::get( OptionDefaults::ONBOARDING_STATUS ) ? 'yes' : 'no',
			'collect_pii'         => Helper::is_collect_pii_enabled() ? 'yes' : 'no',
			'conversions_enabled' => ConversionTrackingService::is_enabled() ? 'yes' : 'no',
			'pixel_enabled'       => PixelTrackingService::is_enabled() ? 'yes' : 'no',
		);
	}
}
