<?php
/**
 * Unit tests for CampaignController::create_campaign_callback() pixel validation
 * (REDTWOO-120).
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;
use RedditForWooCommerce\API\Site\Controllers\CampaignController;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\API\AdPartner\CampaignApi;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\CampaignController::create_campaign_callback
 */
class CampaignControllerTest extends WP_UnitTestCase {

	public function tear_down(): void {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::PIXEL_ID );
		parent::tear_down();
	}

	/**
	 * Builds a request carrying a valid daily budget.
	 *
	 * @return WP_REST_Request
	 */
	private function amount_request(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/campaigns' );
		$request->add_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'amount' => 20 ) ) );

		return $request;
	}

	/**
	 * With no pixel configured, creation is blocked up front: a 400 with
	 * actionable guidance is returned and no campaign is created.
	 */
	public function test_blocks_creation_and_guides_user_when_pixel_missing(): void {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'acc_456' );
		Options::delete( OptionDefaults::PIXEL_ID );

		$campaigns = $this->createMock( CampaignApi::class );
		$campaigns->expects( $this->never() )->method( 'create' );

		$ad_partner_api            = $this->createMock( AdPartnerApi::class );
		$ad_partner_api->campaigns = $campaigns;

		$controller = new CampaignController( $this->createMock( WcsClient::class ), $ad_partner_api );

		$response = $controller->create_campaign_callback( $this->amount_request() );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'error', $data['status'] );
		$this->assertStringContainsString( 'Reddit Events Manager', $data['message'] );
	}
}
