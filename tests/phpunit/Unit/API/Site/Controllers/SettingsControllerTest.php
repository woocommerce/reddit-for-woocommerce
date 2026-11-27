<?php
/**
 * Unit tests for SettingsController Collect PII handling and export settings.
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

		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::LAST_EXPORT_TIMESTAMP );
	}

	public function tear_down(): void {
		Options::delete( OptionDefaults::COLLECT_PII );
		Options::delete( OptionDefaults::CONVERSIONS_ENABLED );
		delete_option( 'reddit_external_id_secret' );

		parent::tear_down();
	}

	/**
	 * Creates a published simple product.
	 *
	 * @param bool $is_virtual Whether the product is virtual.
	 *
	 * @return void
	 */
	private function create_product( bool $is_virtual ): void {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_status( 'publish' );
		$product->set_virtual( $is_virtual );
		$product->save();
	}

	/**
	 * Tests that a store with only virtual products is not asked to export, since the
	 * export would never produce a CSV and the settings page would keep requesting one.
	 */
	public function test_does_not_trigger_export_when_only_virtual_products_are_published(): void {
		$this->create_product( true );

		$data = $this->controller->get_settings()->get_data();

		$this->assertFalse( $data['trigger_export'] );
		$this->assertFalse( $data['has_exportable_products'] );
	}

	/**
	 * Tests that a store with a physical product and no export yet is asked to export.
	 */
	public function test_triggers_export_when_a_physical_product_is_published(): void {
		$this->create_product( false );

		$data = $this->controller->get_settings()->get_data();

		$this->assertTrue( $data['trigger_export'] );
		$this->assertTrue( $data['has_exportable_products'] );
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

	public function test_post_enabling_collect_pii_creates_external_id_secret(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		delete_option( 'reddit_external_id_secret' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'collect_pii', true );

		$this->controller->set_settings( $request );

		$this->assertSame( 64, strlen( get_option( 'reddit_external_id_secret', '' ) ) );
	}

	public function test_post_disabling_collect_pii_does_not_create_external_id_secret(): void {
		Options::set( OptionDefaults::CONVERSIONS_ENABLED, 'yes' );
		delete_option( 'reddit_external_id_secret' );

		$request = new WP_REST_Request( 'POST', '/wc/rfw/reddit/settings' );
		$request->set_param( 'collect_pii', false );

		$this->controller->set_settings( $request );

		$this->assertFalse( get_option( 'reddit_external_id_secret' ) );
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
