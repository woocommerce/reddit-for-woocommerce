<?php
/**
 * Unit tests for AdGroupApi payload construction.
 *
 * @package RedditForWooCommerce\Tests\Unit\API\AdPartner
 */

namespace RedditForWooCommerce\Tests\Unit\API\AdPartner;

use WP_UnitTestCase;
use RedditForWooCommerce\API\AdPartner\AdGroupApi;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\AdPartner\AdGroupApi
 */
class AdGroupApiTest extends WP_UnitTestCase {

	/**
	 * Cleanup options after each test.
	 */
	public function tear_down(): void {
		Options::delete( OptionDefaults::AD_ACCOUNT_ID );
		Options::delete( OptionDefaults::PIXEL_ID );
		parent::tear_down();
	}

	/**
	 * Captures the payload passed to proxy_post and returns it.
	 *
	 * @return array Captured payload.
	 */
	private function capture_create_payload(): array {
		Options::set( OptionDefaults::AD_ACCOUNT_ID, 'act_123' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel_abc' );

		$captured = array();

		$wcs = $this->createMock( WcsClient::class );
		$wcs->method( 'proxy_post' )->willReturnCallback(
			function ( $path, $payload ) use ( &$captured ) {
				$captured = $payload;
				return array( 'ok' => true );
			}
		);

		$api = new AdGroupApi( $wcs );
		$api->create(
			array(
				'campaign_id'    => 'cmp_1',
				'product_set_id' => 'ps_1',
				'daily_budget'   => 10,
				'targeting_type' => 'PROSPECTING',
			)
		);

		return $captured['data'] ?? array();
	}

	/**
	 * The ad group payload MUST include conversion_pixel_id (Reddit requirement,
	 * effective 2026-07-13). This test FAILS on current code, PASSES after fix.
	 */
	public function test_ad_group_payload_includes_conversion_pixel_id(): void {
		$data = $this->capture_create_payload();

		$this->assertArrayHasKey(
			'conversion_pixel_id',
			$data,
			'Ad group payload is missing conversion_pixel_id.'
		);
		$this->assertSame( 'pixel_abc', $data['conversion_pixel_id'] );
	}
}
