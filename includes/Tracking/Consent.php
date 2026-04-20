<?php
/**
 * Consent handler for marketing tracking.
 *
 * Provides a static interface for checking user consent for marketing-related
 * tracking, using the WordPress Consent API. When the Consent API is not
 * available, consent is only granted when the visitor can be geolocated to a
 * region that does not require explicit consent. Visitors in the EU/EEA, UK,
 * or Switzerland are denied, and visitors whose location cannot be determined
 * are also denied.
 *
 * @package RedditForWooCommerce\Tracking
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking;

/**
 * Static interface for checking marketing consent.
 *
 * Checks whether the current visitor has granted consent for marketing tracking,
 * using the WordPress Consent API. When the API is not present, falls back to
 * regional consent logic based on WooCommerce geolocation.
 *
 * @since 0.1.0
 */
final class Consent {

	/**
	 * Determines whether the visitor has granted marketing consent.
	 *
	 * Uses the WordPress Consent API to verify if the visitor has opted into
	 * marketing tracking. If the API is not available (e.g., no Consent plugin
	 * installed), falls back to regional logic: returns false for EU/EEA, UK,
	 * and Switzerland visitors, and also returns false when the visitor's
	 * location cannot be determined. Returns true only for visitors confirmed
	 * to be in non-consent-required regions.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if consent is granted; false if denied, in a consent-required region, or location is unknown.
	 */
	public static function has_marketing_consent(): bool {
		if ( function_exists( 'wp_has_consent' ) ) {
			return wp_has_consent( 'marketing' );
		}

		$country = self::get_user_country();

		if ( '' === $country ) {
			return false;
		}

		if ( self::is_consent_required_region() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks whether the visitor is in a region that requires explicit consent.
	 *
	 * Returns true for EU/EEA member states, the United Kingdom, and Switzerland.
	 * The country list matches the GLA `get_consent_mode_config()` region list.
	 *
	 * @since 1.0.4
	 *
	 * @return bool True if the visitor's country requires explicit consent.
	 */
	protected static function is_consent_required_region(): bool {
		$required = array(
			'AT',
			'BE',
			'BG',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HU',
			'IS',
			'IE',
			'IT',
			'LV',
			'LI',
			'LT',
			'LU',
			'MT',
			'NL',
			'NO',
			'PL',
			'PT',
			'RO',
			'SK',
			'SI',
			'ES',
			'SE',
			'GB',
			'CH',
		);

		return in_array( self::get_user_country(), $required, true );
	}

	/**
	 * Determines the visitor's country code.
	 *
	 * Attempts WooCommerce geolocation first. Returns an empty string if
	 * geolocation is unavailable or returns no result, so that callers
	 * can treat an unknown location as "consent denied".
	 *
	 * @since 1.0.4
	 *
	 * @return string ISO 3166-1 alpha-2 country code, or empty string if unknown.
	 */
	protected static function get_user_country(): string {
		if ( class_exists( 'WC_Geolocation' ) ) {
			$location = \WC_Geolocation::geolocate_ip();

			if ( ! empty( $location['country'] ) ) {
				return $location['country'];
			}
		}

		return '';
	}
}
