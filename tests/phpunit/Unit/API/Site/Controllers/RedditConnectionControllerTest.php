<?php
/**
 * Unit tests for RedditConnectionController::delete_connection().
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use WP_REST_Response;
use WP_Error;
use RedditForWooCommerce\API\Site\Controllers\RedditConnectionController;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\API\AdPartner\CatalogApi;
use RedditForWooCommerce\API\AdPartner\CampaignApi;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Helper;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\RedditConnectionController::delete_connection
 */
class RedditConnectionControllerTest extends WP_UnitTestCase {

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
	 * @var CampaignApi
	 */
	private $campaign_mock;

	/**
	 * @var RedditConnectionController
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();

		$this->wcs_mock            = $this->createMock( WcsClient::class );
		$this->ad_partner_api_mock = $this->createMock( AdPartnerApi::class );
		$this->catalog_mock        = $this->createMock( CatalogApi::class );
		$this->campaign_mock       = $this->createMock( CampaignApi::class );

		$this->ad_partner_api_mock->catalog   = $this->catalog_mock;
		$this->ad_partner_api_mock->campaigns = $this->campaign_mock;

		$this->controller = new RedditConnectionController(
			$this->wcs_mock,
			$this->ad_partner_api_mock
		);
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::CATALOG_ID );
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::BUSINESS_ID );
		Options::delete( OptionDefaults::BUSINESS_NAME );
		Options::delete( OptionDefaults::AD_ACCOUNT_NAME );
		Options::delete( OptionDefaults::PIXEL_ID );
		Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );
		Options::delete( OptionDefaults::ONBOARDING_STATUS );
		Options::delete( OptionDefaults::ONBOARDING_STEP );
		Options::delete( OptionDefaults::CAMPAIGN_IDS );

		parent::tear_down();
	}

	/**
	 * Sets up plugin options to simulate a connected state.
	 *
	 * @param string $catalog_id    Catalog ID to set (empty string = no catalog).
	 * @param string $ad_account_id Ad account ID.
	 * @param array  $campaign_ids  Campaign IDs.
	 */
	private function set_up_connected_state( string $catalog_id = 'cat_123', string $ad_account_id = 'acc_456', array $campaign_ids = array() ): void {
		if ( $catalog_id ) {
			Options::set( OptionDefaults::CATALOG_ID, $catalog_id );
		}
		Options::set( OptionDefaults::AD_ACCOUNT_ID, $ad_account_id );
		Options::set( OptionDefaults::BUSINESS_ID, 'biz_789' );
		Options::set( OptionDefaults::BUSINESS_NAME, 'Test Business' );
		Options::set( OptionDefaults::AD_ACCOUNT_NAME, 'Test Account' );
		Options::set( OptionDefaults::PIXEL_ID, 'pix_101' );
		Options::set( OptionDefaults::IS_JETPACK_CONNECTED, 'yes' );
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'complete' );
		Options::set( OptionDefaults::ONBOARDING_STEP, 'done' );
		Options::set( OptionDefaults::CAMPAIGN_IDS, $campaign_ids );
	}

	/**
	 * Configures the WCS mock to return a successful disconnect response.
	 */
	private function mock_successful_stop_connection(): void {
		$this->wcs_mock->method( 'proxy_get' )
			->with( 'connection/disconnect' )
			->willReturn( new WP_REST_Response( array( 'status' => 'disconnected' ), 200 ) );
	}

	public function test_disconnect_succeeds_when_catalog_deletion_succeeds() {
		$this->set_up_connected_state();
		$this->mock_successful_stop_connection();

		$this->catalog_mock->expects( $this->once() )
			->method( 'delete' )
			->with( 'cat_123' )
			->willReturn( new WP_REST_Response( array( 'status' => 'ok' ), 200 ) );

		$response = $this->controller->delete_connection();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'disconnected', $response->get_data()['status'] );
	}

	public function test_disconnect_succeeds_when_catalog_deletion_returns_403() {
		$this->set_up_connected_state();
		$this->mock_successful_stop_connection();

		$this->catalog_mock->expects( $this->once() )
			->method( 'delete' )
			->with( 'cat_123' )
			->willReturn(
				new WP_Error(
					'reddit_for_woocommerce_request_failed',
					'Request failed',
					array( 'body' => '{"error":{"code":403,"message":"no permissions to Delete Catalog action"}}' )
				)
			);

		$response = $this->controller->delete_connection();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'disconnected', $response->get_data()['status'] );
	}

	public function test_disconnect_succeeds_when_no_catalog_exists() {
		$this->set_up_connected_state( '' );
		$this->mock_successful_stop_connection();

		$this->catalog_mock->expects( $this->never() )
			->method( 'delete' );

		$response = $this->controller->delete_connection();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'disconnected', $response->get_data()['status'] );
	}

	public function test_disconnect_fails_when_stop_connection_returns_error() {
		$this->set_up_connected_state( '' );

		$this->wcs_mock->method( 'proxy_get' )
			->with( 'connection/disconnect' )
			->willReturn( new WP_Error( 'connection_error', 'Connection failed' ) );

		$response = $this->controller->delete_connection();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'error', $response->get_data()['status'] );
	}

	public function test_options_are_cleared_after_disconnect_despite_catalog_failure() {
		$this->set_up_connected_state();
		$this->mock_successful_stop_connection();

		$this->catalog_mock->method( 'delete' )
			->willReturn( new WP_Error( 'forbidden', 'Forbidden', array( 'body' => '{}' ) ) );

		$this->controller->delete_connection();

		$defaults = OptionDefaults::get_all();
		$this->assertEquals( $defaults[ OptionDefaults::BUSINESS_ID ], Options::get( OptionDefaults::BUSINESS_ID ) );
		$this->assertEquals( $defaults[ OptionDefaults::AD_ACCOUNT_ID ], Options::get( OptionDefaults::AD_ACCOUNT_ID ) );
		$this->assertEquals( $defaults[ OptionDefaults::CATALOG_ID ], Options::get( OptionDefaults::CATALOG_ID ) );
		$this->assertEquals( $defaults[ OptionDefaults::PIXEL_ID ], Options::get( OptionDefaults::PIXEL_ID ) );
	}

	public function test_options_not_cleared_when_stop_connection_fails() {
		$this->set_up_connected_state( '' );

		$this->wcs_mock->method( 'proxy_get' )
			->with( 'connection/disconnect' )
			->willReturn( new WP_Error( 'connection_error', 'Connection failed' ) );

		$this->controller->delete_connection();

		$this->assertEquals( 'biz_789', Options::get( OptionDefaults::BUSINESS_ID ) );
		$this->assertEquals( 'acc_456', Options::get( OptionDefaults::AD_ACCOUNT_ID ) );
		$this->assertEquals( 'yes', Options::get( OptionDefaults::IS_JETPACK_CONNECTED ) );
	}

	public function test_campaigns_are_archived_when_catalog_deletion_fails() {
		$this->set_up_connected_state( 'cat_123', 'acc_456', array( 'camp_1', 'camp_2' ) );
		$this->mock_successful_stop_connection();

		$this->catalog_mock->method( 'delete' )
			->willReturn( new WP_Error( 'forbidden', 'Forbidden', array( 'body' => '{}' ) ) );

		$archived_ids = array();
		$this->campaign_mock->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->willReturnCallback(
				function ( $campaign_id, $data ) use ( &$archived_ids ) {
					$archived_ids[] = $campaign_id;
					$this->assertEquals( array( 'configured_status' => 'ARCHIVED' ), $data );
					return new WP_REST_Response( array(), 200 );
				}
			);

		$response = $this->controller->delete_connection();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'disconnected', $response->get_data()['status'] );
		$this->assertEquals( array( 'camp_1', 'camp_2' ), $archived_ids );
	}

	public function test_disconnect_action_fires_on_success() {
		$this->set_up_connected_state( '' );
		$this->mock_successful_stop_connection();

		$action_fired = false;
		add_action(
			Helper::with_prefix( 'reddit_disconnected' ),
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		$this->controller->delete_connection();

		$this->assertTrue( $action_fired );
	}
}
