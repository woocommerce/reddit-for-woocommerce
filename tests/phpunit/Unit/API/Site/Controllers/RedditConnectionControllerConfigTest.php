<?php
/**
 * Unit tests for RedditConnectionController::do_config().
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RedditForWooCommerce\API\Site\Controllers\RedditConnectionController;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\API\AdPartner\CatalogApi;
use RedditForWooCommerce\API\AdPartner\AdAccountsApi;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\RedditConnectionController::do_config
 */
class RedditConnectionControllerConfigTest extends WP_UnitTestCase {

	/**
	 * @var WcsClient
	 */
	private $wcs_mock;

	/**
	 * @var AdPartnerApi
	 */
	private $ad_partner_api_mock;

	/**
	 * @var CatalogApi
	 */
	private $catalog_mock;

	/**
	 * @var AdAccountsApi
	 */
	private $ad_accounts_mock;

	/**
	 * @var RedditConnectionController
	 */
	private $controller;

	/**
	 * @var string
	 */
	private $original_currency;

	public function set_up(): void {
		parent::set_up();

		$this->wcs_mock            = $this->createMock( WcsClient::class );
		$this->ad_partner_api_mock = $this->createMock( AdPartnerApi::class );
		$this->catalog_mock        = $this->createMock( CatalogApi::class );
		$this->ad_accounts_mock    = $this->createMock( AdAccountsApi::class );

		$this->ad_partner_api_mock->catalog     = $this->catalog_mock;
		$this->ad_partner_api_mock->ad_accounts = $this->ad_accounts_mock;

		$this->ad_accounts_mock->method( 'get' )
			->willReturn( new WP_Error( 'error', 'not relevant to this test' ) );

		$this->controller = new RedditConnectionController(
			$this->wcs_mock,
			$this->ad_partner_api_mock
		);

		$this->original_currency = get_woocommerce_currency();

		Options::set( OptionDefaults::IS_JETPACK_CONNECTED, 'yes' );
	}

	public function tear_down(): void {
		update_option( 'woocommerce_currency', $this->original_currency );

		Options::delete( OptionDefaults::CATALOG_ID );
		Options::delete( OptionDefaults::CATALOG_ERROR );
		Options::delete( OptionDefaults::BUSINESS_ID );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::PIXEL_ID );
		Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );

		parent::tear_down();
	}

	/**
	 * Builds a request carrying the params needed to trigger catalog creation.
	 */
	private function build_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'business_id'   => 'biz_123',
					'ad_account_id' => 'acc_456',
					'pixel_id'      => 'pix_789',
				)
			)
		);

		return $request;
	}

	public function test_skips_catalog_creation_and_sets_unsupported_currency_when_locally_unsupported() {
		update_option( 'woocommerce_currency', 'INR' );

		$this->catalog_mock->expects( $this->never() )
			->method( 'create' );

		$this->controller->do_config( $this->build_request() );

		$this->assertSame( 'UNSUPPORTED_CURRENCY', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}

	public function test_creates_catalog_when_currency_is_supported() {
		update_option( 'woocommerce_currency', 'USD' );

		$this->catalog_mock->expects( $this->once() )
			->method( 'create' )
			->willReturn( new WP_REST_Response( array( 'data' => array( 'id' => 'cat_123' ) ), 200 ) );

		$this->controller->do_config( $this->build_request() );

		$this->assertSame( 'cat_123', Options::get( OptionDefaults::CATALOG_ID ) );
		$this->assertSame( '', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}

	public function test_maps_reddit_currency_rejection_to_unsupported_currency() {
		update_option( 'woocommerce_currency', 'USD' );

		$this->catalog_mock->method( 'create' )
			->willReturn(
				new WP_Error(
					'reddit_for_woocommerce_request_failed',
					'Request failed',
					array( 'body' => '{"error":{"code":400,"message":"The provided default_currency is not supported"}}' )
				)
			);

		$this->controller->do_config( $this->build_request() );

		$this->assertSame( 'UNSUPPORTED_CURRENCY', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}

	public function test_permission_error_handling_is_not_regressed() {
		update_option( 'woocommerce_currency', 'USD' );

		$this->catalog_mock->method( 'create' )
			->willReturn(
				new WP_Error(
					'reddit_for_woocommerce_request_failed',
					'Request failed',
					array( 'body' => '{"error":{"code":403,"message":"no permissions to Create Catalog action"}}' )
				)
			);

		$this->controller->do_config( $this->build_request() );

		$this->assertSame( 'PERMISSION_ERROR', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}

	public function test_catalog_already_exists_handling_is_not_regressed() {
		update_option( 'woocommerce_currency', 'USD' );

		$this->catalog_mock->method( 'create' )
			->willReturn(
				new WP_Error(
					'reddit_for_woocommerce_request_failed',
					'Request failed',
					array( 'body' => '{"error":{"code":400,"message":"pixels already attached to a catalog"}}' )
				)
			);

		$this->controller->do_config( $this->build_request() );

		$this->assertSame( 'CATALOG_ALREADY_EXISTS', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}
}
