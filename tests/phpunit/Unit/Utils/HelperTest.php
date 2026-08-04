<?php
/**
 * Tests for the Helper utility class.
 *
 * @package RedditForWooCommerce\Tests\Integration\Utils
 */

namespace RedditForWooCommerce\Tests\Integration\Utils;

use WP_UnitTestCase;
use RedditForWooCommerce\Utils\Helper;

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

	public function set_up(): void {
		parent::set_up();
		$this->original_server = $_SERVER;
	}

	public function tear_down(): void {
		$_SERVER = $this->original_server;
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

		$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
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

		$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
		$_SERVER['HTTP_REFERER'] = 'https://evil.example.net/phish';
		unset( $_REQUEST['_wp_http_referer'] );

		$this->assertSame( '', Helper::get_event_source_url() );

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}
}
