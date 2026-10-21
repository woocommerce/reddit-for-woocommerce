<?php
/**
 * Tests for the TrackerSnapshot class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking
 */

namespace RedditForWooCommerce\Tests\Unit\Tracking;

use WP_UnitTestCase;
use RedditForWooCommerce\Tracking\TrackerSnapshot;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Tracking\TrackerSnapshot
 */
class TrackerSnapshotTest extends WP_UnitTestCase {

	public function tear_down(): void {
		Options::delete( OptionDefaults::COLLECT_PII );
		Options::delete( OptionDefaults::ONBOARDING_STATUS );
		Options::delete( OptionDefaults::CONVERSIONS_ENABLED );
		Options::delete( OptionDefaults::PIXEL_ENABLED );
		parent::tear_down();
	}

	/**
	 * Returns the plugin's settings from a snapshot built on empty tracker data.
	 *
	 * @return array<string,string>
	 */
	private function get_snapshot_settings(): array {
		$data = TrackerSnapshot::add_snapshot_data( array() );

		return $data['extensions']['reddit-for-woocommerce']['settings'];
	}

	public function test_register_hooks_adds_tracker_data_filter(): void {
		TrackerSnapshot::register_hooks();

		$this->assertSame(
			10,
			has_filter( 'woocommerce_tracker_data', array( TrackerSnapshot::class, 'add_snapshot_data' ) )
		);
	}

	public function test_collect_pii_is_no_when_option_is_unset(): void {
		Options::delete( OptionDefaults::COLLECT_PII );

		$this->assertSame( 'no', $this->get_snapshot_settings()['collect_pii'] );
	}

	public function test_collect_pii_matches_saved_setting(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );
		$this->assertSame( 'yes', $this->get_snapshot_settings()['collect_pii'] );

		Options::set( OptionDefaults::COLLECT_PII, 'no' );
		$this->assertSame( 'no', $this->get_snapshot_settings()['collect_pii'] );
	}

	public function test_reports_connection_and_tracking_flags(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'no' );
		Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );

		$settings = $this->get_snapshot_settings();

		$this->assertSame( 'yes', $settings['connected'] );
		$this->assertSame( 'no', $settings['conversions_enabled'] );
		$this->assertSame( 'yes', $settings['pixel_enabled'] );
	}

	public function test_connected_is_no_before_onboarding_completes(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'incomplete' );

		$this->assertSame( 'no', $this->get_snapshot_settings()['connected'] );
	}

	public function test_payload_contains_only_settings_flags_and_version(): void {
		$settings = $this->get_snapshot_settings();

		$this->assertSame(
			array( 'version', 'connected', 'collect_pii', 'conversions_enabled', 'pixel_enabled' ),
			array_keys( $settings )
		);
		$this->assertSame( REDDIT_FOR_WOOCOMMERCE_VERSION, $settings['version'] );

		foreach ( array( 'connected', 'collect_pii', 'conversions_enabled', 'pixel_enabled' ) as $flag ) {
			$this->assertContains( $settings[ $flag ], array( 'yes', 'no' ) );
		}
	}

	public function test_keeps_existing_tracker_data(): void {
		$data = TrackerSnapshot::add_snapshot_data(
			array(
				'url'        => 'https://example.com',
				'extensions' => array(
					'another-extension' => array( 'settings' => array( 'enabled' => 'yes' ) ),
				),
			)
		);

		$this->assertSame( 'https://example.com', $data['url'] );
		$this->assertSame( array( 'settings' => array( 'enabled' => 'yes' ) ), $data['extensions']['another-extension'] );
		$this->assertArrayHasKey( 'reddit-for-woocommerce', $data['extensions'] );
	}

	public function test_non_array_tracker_data_is_replaced_with_snapshot_only(): void {
		$data = TrackerSnapshot::add_snapshot_data( null );

		$this->assertSame( array( 'reddit-for-woocommerce' ), array_keys( $data['extensions'] ) );
	}
}
