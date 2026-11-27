<?php
/**
 * Tests for the Helper utility class.
 *
 * @package RedditForWooCommerce\Tests\Integration\Utils
 */

namespace RedditForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Utils\Helper
 */
class HelperTest extends WP_UnitTestCase {

	/**
	 * Backup of $_SERVER before each test.
	 *
	 * @var array<string,mixed>
	 */
	private $original_server;

	/**
	 * Backup of the store base country before each test.
	 *
	 * @var string
	 */
	private $original_default_country;

	public function set_up(): void {
		parent::set_up();
		$this->original_server          = $_SERVER;
		$this->original_default_country = get_option( 'woocommerce_default_country', '' );
	}

	public function tear_down(): void {
		$_SERVER = $this->original_server;
		Options::delete( OptionDefaults::COLLECT_PII );
		update_option( 'woocommerce_default_country', $this->original_default_country );
		parent::tear_down();
	}

	/**
	 * For a synchronous (non-AJAX) request, the source URL is built from the
	 * current request URI and includes the query string (e.g. rdt_cid).
	 */
	public function test_get_event_source_url_uses_request_uri_for_sync_requests(): void {
		$_SERVER['REQUEST_URI'] = '/product/sample/?rdt_cid=click-123';

		$this->assertSame(
			esc_url_raw( home_url( '/product/sample/?rdt_cid=click-123' ) ),
			Helper::get_event_source_url()
		);
	}

	/**
	 * For an asynchronous request (AJAX/REST), the source URL comes from the
	 * referer — the page that fired the event — not the admin-ajax endpoint.
	 */
	public function test_get_event_source_url_uses_referer_for_async_requests(): void {
		add_filter( 'wp_doing_ajax', '__return_true' );

		$_SERVER['REQUEST_URI']  = '/wp-admin/admin-ajax.php';
		$_SERVER['HTTP_REFERER'] = home_url( '/shop/?rdt_cid=click-xyz' );

		$this->assertSame(
			esc_url_raw( home_url( '/shop/?rdt_cid=click-xyz' ) ),
			Helper::get_event_source_url()
		);

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * A cross-host referer is rejected (wp_get_referer only returns same-host
	 * URLs), so a foreign domain can't be injected as the source URL.
	 */
	public function test_get_event_source_url_rejects_cross_host_referer(): void {
		add_filter( 'wp_doing_ajax', '__return_true' );

		$_SERVER['REQUEST_URI']  = '/wp-admin/admin-ajax.php';
		$_SERVER['HTTP_REFERER'] = 'https://evil.example.net/phish';
		unset( $_REQUEST['_wp_http_referer'] );

		$this->assertSame( '', Helper::get_event_source_url() );

		remove_filter( 'wp_doing_ajax', '__return_true' );
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
