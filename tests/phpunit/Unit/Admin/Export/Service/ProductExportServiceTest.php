<?php
/**
 * Unit tests for the ProductExportService class.
 *
 * This test suite validates behavior of start_export(), ensuring it
 * correctly validates the filesystem and conditionally triggers the cache builder.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_UnitTestCase;
use RuntimeException;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Admin\Export\EntityProvider\ProductEntityProvider;
use RedditForWooCommerce\Admin\Export\RowBuilder\ProductRowBuilder;
use RedditForWooCommerce\Admin\Export\Writer\CsvExportWriter;
use RedditForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use RedditForWooCommerce\Admin\Export\Contract\ExportableEntityProviderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportWriterInterface;
use RedditForWooCommerce\Admin\Export\BatchExportJob;
use RedditForWooCommerce\CsvExporter\ProductExportService;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;

/**
 * @covers \RedditForWooCommerce\Admin\Export\Service\ProductExportService
 */
class ProductExportServiceTest extends WP_UnitTestCase {

	/**
	 * Mocks for injected dependencies.
	 */
	private $cache_builder;
	private $entity_provider;
	private $row_builder;
	private $writer;
	private $job;
	private $api;

	/**
	 * Service under test.
	 *
	 * @var ProductExportService
	 */
	private $service;

	public function set_up(): void {
		parent::set_up();

		$this->cache_builder   = new ProductIdCacheBuilder();
		$this->entity_provider = $this->createMock( ExportableEntityProviderInterface::class );
		$this->row_builder     = $this->createMock( ExportRowBuilderInterface::class );
		$this->writer          = $this->createMock( ExportWriterInterface::class );
		$this->api             = $this->createMock( AdPartnerApi::class );
		$this->job             = new BatchExportJob( $this->cache_builder, $this->entity_provider, $this->row_builder, $this->writer );

		$this->service = new ProductExportService( $this->job, $this->api );
	}

	public function tear_down(): void {
		remove_all_actions( Helper::with_prefix( 'export_products_cache_completed' ) );
		remove_all_actions( Helper::with_prefix( 'export_product_catalog' ) );

		parent::tear_down();
	}

	/**
	 * Tests that start_export() returns false if the writer throws during validation.
	 */
	public function test_start_export_returns_false_on_filesystem_error(): void {
		$this->writer->method( 'create_file' )
			->willThrowException( new RuntimeException( 'Simulated FS failure' ) );

		$this->assertFalse( $this->service->start_export() );
	}

	/**
	 * Tests that start_export() returns false if export jobs are already scheduled.
	 */
	public function test_start_export_returns_false_if_jobs_already_scheduled(): void {
		as_schedule_single_action(
			time() + 60,
			Helper::with_prefix( ProductExportService::ACTION_HOOK )
		);

		$this->writer->method( 'create_file' )
			->willReturn( '/tmp/dummy.csv' );

		$this->assertNull( $this->service->start_export() );
	}

	/**
	 * Tests that start_export() returns true and triggers cache builder when ready.
	 */
	public function test_start_export_returns_true_and_triggers_cache(): void {
		$this->writer->method( 'create_file' )
			->willReturn( '/tmp/dummy.csv' );

		$this->assertTrue( $this->service->start_export() );
	}

	/**
	 * Tests that start_writing() enqueues the first export batch with offset 0.
	 */
	public function test_start_writing_enqueues_initial_batch(): void {
		as_unschedule_all_actions( Helper::with_prefix( ProductExportService::ACTION_HOOK ) );

		$this->service->start_writing();

		$this->assertTrue( as_has_scheduled_action(
			Helper::with_prefix( ProductExportService::ACTION_HOOK ),
			array( 'offset' => 0 )
		) !== false );
	}

	/**
	 * Tests that handle_batch() creates the file, writes a product row, stores the file path,
	 * and schedules the next batch if not complete.
	 */
	public function test_handle_batch_first_batch_sets_file_path_and_schedules_next(): void {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( '29.99' );
		$product->save();

		$product_2 = new \WC_Product_Simple();
		$product_2->set_name( 'Sample Product' );
		$product_2->save();

		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, array( $product->get_id(), $product_2->get_id() ) );

		$entity_provider = new ProductEntityProvider();
		$row_builder     = new ProductRowBuilder();
		$writer          = new CsvExportWriter();
		$job             = new BatchExportJob( $this->cache_builder, $entity_provider, $row_builder, $writer );

		$service = new ProductExportService( $job, $this->api );

		$service->handle_batch( 0 );

		$file_path = Options::get( OptionDefaults::EXPORT_FILE_PATH );

		$this->assertNotEmpty( $file_path );
		$this->assertFileExists( $file_path );

		$this->assertFileExists( $file_path );

		$csv = array_map(
			static function( $line ) {
				return str_getcsv( $line, ',', '"', '\\' );
			},
			file( $file_path )
		);

		$this->assertEquals(
			array( 'id', 'title', 'description', 'link', 'image_link', 'price', 'sale_price', 'gtin', 'availability' ),
			$csv[0]
		);

		$this->assertEquals( (string) $product->get_id(), $csv[1][0] );
		$this->assertEquals( 'Test Product', $csv[1][1] );
		$this->assertEquals( '', $csv[1][2] );
		$this->assertStringContainsString( '?product=test-product', $csv[1][3] );
		$this->assertEquals( '', $csv[1][4] );
		$this->assertEquals( '29.99 USD', $csv[1][5] );
		$this->assertEquals( '', $csv[1][6] );
		$this->assertEquals( '', $csv[1][7] );
		$this->assertEquals( 'in_stock', $csv[1][8] );

		$this->assertEquals( (string) $product_2->get_id(), $csv[2][0] );
		$this->assertEquals( 'Sample Product', $csv[2][1] );
		$this->assertEquals( '', $csv[2][2] );
		$this->assertStringContainsString( '?product=sample-product', $csv[2][3] );
		$this->assertEquals( '', $csv[2][4] );
		$this->assertEquals( '0 USD', $csv[2][5] );
		$this->assertEquals( '', $csv[2][6] );
		$this->assertEquals( '', $csv[2][7] );
		$this->assertEquals( 'out_of_stock', $csv[2][8] );

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}

		Options::delete( OptionDefaults::EXPORT_FILE_PATH );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
	}
}
