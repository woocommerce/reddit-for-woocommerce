<?php
/**
 * Unit tests for SettingsController export settings.
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use RedditForWooCommerce\API\Site\Controllers\SettingsController;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\SettingsController::get_settings
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
}
