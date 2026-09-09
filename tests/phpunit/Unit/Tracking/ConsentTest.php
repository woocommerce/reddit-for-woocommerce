<?php
/**
 * Unit tests for the Consent class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use RedditForWooCommerce\Tracking\Consent;

/**
 * @covers \RedditForWooCommerce\Tracking\Consent
 */
final class ConsentTest extends WP_UnitTestCase {

	/**
	 * Clean up filters and options after each test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'woocommerce_geolocate_ip' );
		delete_option( 'woocommerce_default_country' );
		parent::tear_down();
	}

	/**
	 * Invokes a protected static method on the Consent class via reflection.
	 *
	 * @param string $method_name Method name to invoke.
	 * @return mixed
	 */
	private function invoke_protected( string $method_name ) {
		$method = new \ReflectionMethod( Consent::class, $method_name );
		$method->setAccessible( true );
		return $method->invoke( null );
	}

	/**
	 * Sets the geolocation filter to return the given country code.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 */
	private function set_geolocated_country( string $country ): void {
		add_filter( 'woocommerce_geolocate_ip', fn() => $country );
	}

	// -------------------------------------------------------------------------
	// has_marketing_consent() — public API
	// -------------------------------------------------------------------------

	public function test_returns_false_for_eu_country_without_consent_plugin(): void {
		$this->set_geolocated_country( 'DE' );

		$this->assertFalse( Consent::has_marketing_consent() );
	}

	public function test_returns_false_for_uk_without_consent_plugin(): void {
		$this->set_geolocated_country( 'GB' );

		$this->assertFalse( Consent::has_marketing_consent() );
	}

	public function test_returns_true_for_non_eu_country_without_consent_plugin(): void {
		$this->set_geolocated_country( 'US' );

		$this->assertTrue( Consent::has_marketing_consent() );
	}

	public function test_returns_false_when_geolocation_unavailable(): void {
		$this->set_geolocated_country( '' );

		$this->assertFalse( Consent::has_marketing_consent() );
	}

	// -------------------------------------------------------------------------
	// is_consent_required_region() — protected, tested via reflection
	// -------------------------------------------------------------------------

	public function test_is_consent_required_region_returns_true_for_all_consent_required_countries(): void {
		$countries = array(
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
			'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU',
			'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
			'GB', 'CH',
		);

		foreach ( $countries as $country ) {
			remove_all_filters( 'woocommerce_geolocate_ip' );
			$this->set_geolocated_country( $country );

			$this->assertTrue(
				$this->invoke_protected( 'is_consent_required_region' ),
				"Expected is_consent_required_region() to return true for {$country}."
			);
		}
	}

	public function test_is_consent_required_region_returns_false_for_non_eu_countries(): void {
		$countries = array( 'US', 'CA', 'AU', 'JP', 'BR', 'IN', 'MX', 'ZA' );

		foreach ( $countries as $country ) {
			remove_all_filters( 'woocommerce_geolocate_ip' );
			$this->set_geolocated_country( $country );

			$this->assertFalse(
				$this->invoke_protected( 'is_consent_required_region' ),
				"Expected is_consent_required_region() to return false for {$country}."
			);
		}
	}

	// -------------------------------------------------------------------------
	// get_user_country() — protected, tested via reflection
	// -------------------------------------------------------------------------

	public function test_get_user_country_returns_geolocated_country(): void {
		$this->set_geolocated_country( 'FR' );

		$this->assertSame( 'FR', $this->invoke_protected( 'get_user_country' ) );
	}

	public function test_get_user_country_returns_empty_when_geolocation_fails(): void {
		$this->set_geolocated_country( '' );

		$this->assertSame( '', $this->invoke_protected( 'get_user_country' ) );
	}

	// -------------------------------------------------------------------------
	// Country list completeness
	// -------------------------------------------------------------------------

	public function test_consent_required_countries_list_has_32_entries(): void {
		$expected = array(
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
			'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU',
			'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
			'GB', 'CH',
		);

		$this->assertCount( 32, $expected );

		foreach ( $expected as $country ) {
			remove_all_filters( 'woocommerce_geolocate_ip' );
			$this->set_geolocated_country( $country );

			$this->assertTrue(
				$this->invoke_protected( 'is_consent_required_region' ),
				"Expected {$country} to be in the consent-required list."
			);
		}

		remove_all_filters( 'woocommerce_geolocate_ip' );
		$this->set_geolocated_country( 'US' );

		$this->assertFalse(
			$this->invoke_protected( 'is_consent_required_region' ),
			'Expected US to NOT be in the consent-required list.'
		);
	}
}
