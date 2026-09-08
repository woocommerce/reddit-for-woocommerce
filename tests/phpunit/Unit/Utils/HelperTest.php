<?php
/**
 * Unit tests for the Helper utility class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

namespace RedditForWooCommerce\Tests\Unit\Utils;

use WP_UnitTestCase;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Utils\Helper::is_collect_pii_enabled
 * @covers \RedditForWooCommerce\Utils\Helper::is_gdpr_region
 */
class HelperTest extends WP_UnitTestCase {

	private string $original_default_country;

	public function set_up(): void {
		parent::set_up();

		$this->original_default_country = get_option( 'woocommerce_default_country', '' );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::COLLECT_PII );
		update_option( 'woocommerce_default_country', $this->original_default_country );

		parent::tear_down();
	}

	public function test_is_collect_pii_enabled_true_when_yes(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$this->assertTrue( Helper::is_collect_pii_enabled() );
	}

	public function test_is_collect_pii_enabled_false_when_no(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'no' );

		$this->assertFalse( Helper::is_collect_pii_enabled() );
	}

	public function test_is_collect_pii_enabled_false_when_option_absent(): void {
		Options::delete( OptionDefaults::COLLECT_PII );

		$this->assertFalse( Helper::is_collect_pii_enabled() );
	}

	public function test_is_gdpr_region_true_for_eu_country(): void {
		update_option( 'woocommerce_default_country', 'FR' );

		$this->assertTrue( Helper::is_gdpr_region() );
	}

	public function test_is_gdpr_region_true_for_uk(): void {
		update_option( 'woocommerce_default_country', 'GB' );

		$this->assertTrue( Helper::is_gdpr_region() );
	}

	public function test_is_gdpr_region_false_for_non_gdpr_country(): void {
		update_option( 'woocommerce_default_country', 'US' );

		$this->assertFalse( Helper::is_gdpr_region() );
	}
}
