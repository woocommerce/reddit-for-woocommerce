<?php
/**
 * Unit tests for SettingsController Collect PII handling.
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use WP_REST_Request;
use RedditForWooCommerce\API\Site\Controllers\SettingsController;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\SettingsController
 */
class SettingsControllerTest extends WP_UnitTestCase {

	/**
	 * @var SettingsController
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();

		$this->controller = new SettingsController();
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::COLLECT_PII );
		Options::delete( OptionDefaults::CONVERSIONS_ENABLED );

		parent::tear_down();
	}

	public function test_get_response_reflects_stored_collect_pii(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$data = $this->controller->get_settings()->get_data();

		$this->assertArrayHasKey( 'collect_pii', $data );
		$this->assertTrue( $data['collect_pii'] );
	}

	public function test_get_response_collect_pii_false_when_disabled(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'no' );

		$data = $this->controller->get_settings()->get_data();

		$this->assertFalse( $data['collect_pii'] );
	}

	public function test_post_persists_collect_pii_enabled(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'collect_pii', true );

		$response = $this->controller->set_settings( $request );

		$this->assertSame( 'yes', Options::get( OptionDefaults::COLLECT_PII ) );
		$this->assertTrue( $response->get_data()['collect_pii'] );
	}

	public function test_post_persists_collect_pii_disabled(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'collect_pii', false );

		$response = $this->controller->set_settings( $request );

		$this->assertSame( 'no', Options::get( OptionDefaults::COLLECT_PII ) );
		$this->assertFalse( $response->get_data()['collect_pii'] );
	}

	public function test_post_rejects_collect_pii_while_conversions_api_is_off(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'no' );
		Options::set( OptionDefaults::COLLECT_PII, 'no' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'collect_pii', true );

		$response = $this->controller->set_settings( $request );

		$this->assertWPError( $response );
		$this->assertSame( 'rest_conversions_api_disabled', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertSame( 'no', Options::get( OptionDefaults::COLLECT_PII ) );
	}

	public function test_post_rejects_collect_pii_when_same_request_turns_conversions_api_off(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		Options::set( OptionDefaults::COLLECT_PII, 'no' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'capi_enabled', false );
		$request->set_param( 'collect_pii', true );

		$response = $this->controller->set_settings( $request );

		$this->assertWPError( $response );
		$this->assertSame( 'yes', Options::get( OptionDefaults::CONVERSIONS_ENABLED ) );
		$this->assertSame( 'no', Options::get( OptionDefaults::COLLECT_PII ) );
	}

	public function test_post_accepts_collect_pii_when_same_request_turns_conversions_api_on(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'no' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'capi_enabled', true );
		$request->set_param( 'collect_pii', true );

		$response = $this->controller->set_settings( $request );

		$this->assertSame( 'yes', Options::get( OptionDefaults::CONVERSIONS_ENABLED ) );
		$this->assertSame( 'yes', Options::get( OptionDefaults::COLLECT_PII ) );
		$this->assertTrue( $response->get_data()['collect_pii'] );
	}

	public function test_post_turning_conversions_api_off_keeps_collect_pii(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'capi_enabled', false );

		$response = $this->controller->set_settings( $request );

		$this->assertFalse( $response->get_data()['capi_enabled'] );
		$this->assertSame( 'yes', Options::get( OptionDefaults::COLLECT_PII ) );
	}
}
