<?php
/**
 * Unit tests for the ProductExportService::create_catalog_manually() AJAX callback.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use RedditForWooCommerce\Admin\Export\BatchExportJob;
use RedditForWooCommerce\Admin\Export\Contract\ExportableEntityProviderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportWriterInterface;
use RedditForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\API\AdPartner\CatalogApi;
use RedditForWooCommerce\CsvExporter\ProductExportService;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\CsvExporter\ProductExportService::create_catalog_manually
 */
class ProductExportServiceCreateCatalogTest extends WP_Ajax_UnitTestCase {

	/**
	 * Service under test.
	 *
	 * @var ProductExportService
	 */
	private $service;

	/**
	 * Catalog API mock.
	 *
	 * @var CatalogApi
	 */
	private $catalog;

	/**
	 * Backup the store currency option.
	 *
	 * @var string
	 */
	private $original_currency;

	public function set_up(): void {
		parent::set_up();

		$this->original_currency = get_woocommerce_currency();

		$job = new BatchExportJob(
			new ProductIdCacheBuilder(),
			$this->createMock( ExportableEntityProviderInterface::class ),
			$this->createMock( ExportRowBuilderInterface::class ),
			$this->createMock( ExportWriterInterface::class )
		);

		$this->catalog = $this->createMock( CatalogApi::class );
		$api           = $this->createMock( AdPartnerApi::class );
		$api->catalog  = $this->catalog;

		$this->service = new ProductExportService( $job, $api );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_REQUEST['security'] = wp_create_nonce( 'admin_nonce' );
	}

	public function tear_down(): void {
		update_option( 'woocommerce_currency', $this->original_currency );
		Options::delete( OptionDefaults::CATALOG_ERROR );
		Options::delete( OptionDefaults::PIXEL_ID );
		unset( $_REQUEST['security'], $_POST['delete_existing_catalog'] );

		parent::tear_down();
	}

	/**
	 * Runs the AJAX callback and returns the decoded JSON response.
	 *
	 * @return array|null
	 */
	private function call(): ?array {
		$this->_last_response = '';

		ob_start();
		try {
			$this->service->create_catalog_manually();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Tests that an unsupported currency is rejected before any catalog is deleted or created.
	 */
	public function test_unsupported_currency_is_rejected_before_catalog_api(): void {
		update_option( 'woocommerce_currency', 'INR' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-123' );
		$_POST['delete_existing_catalog'] = 'true';

		$this->catalog->expects( $this->never() )->method( 'list' );
		$this->catalog->expects( $this->never() )->method( 'delete' );
		$this->catalog->expects( $this->never() )->method( 'create' );

		$response = $this->call();

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'UNSUPPORTED_CURRENCY', $response['data']['error_code'] );
		$this->assertSame( 'UNSUPPORTED_CURRENCY', Options::get( OptionDefaults::CATALOG_ERROR ) );
	}

	/**
	 * Tests that a supported currency still reaches the Catalog API.
	 */
	public function test_supported_currency_creates_catalog(): void {
		update_option( 'woocommerce_currency', 'USD' );

		$this->catalog->expects( $this->once() )
			->method( 'create' )
			->willReturn(
				new \WP_Error(
					'catalog_error',
					'Simulated failure',
					array( 'body' => wp_json_encode( array( 'error' => array( 'code' => 500, 'message' => 'Simulated failure' ) ) ) )
				)
			);

		$response = $this->call();

		$this->assertFalse( $response['success'] );
		$this->assertNotSame( 'UNSUPPORTED_CURRENCY', $response['data']['error_code'] );
	}
}
