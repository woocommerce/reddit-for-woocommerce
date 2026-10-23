<?php
/**
 * Unit tests for the ProductExportService AJAX callbacks.
 *
 * Ensures the admin nonce is never treated as authorization: users without
 * `manage_woocommerce` are rejected before any option, job or Ad Partner API
 * side effect, while managers keep access.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;
use WC_Helper_Product;
use RedditForWooCommerce\Admin\Export\BatchExportJob;
use RedditForWooCommerce\Admin\Export\Contract\ExportableEntityProviderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;
use RedditForWooCommerce\Admin\Export\Contract\ExportWriterInterface;
use RedditForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use RedditForWooCommerce\API\AdPartner\AdPartnerApi;
use RedditForWooCommerce\CsvExporter\ProductExportService;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\CsvExporter\ProductExportService
 */
class ProductExportServiceAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Service under test.
	 *
	 * @var ProductExportService
	 */
	private $service;

	/**
	 * Export writer mock.
	 *
	 * @var ExportWriterInterface
	 */
	private $writer;

	public function set_up(): void {
		parent::set_up();

		$this->writer = $this->createMock( ExportWriterInterface::class );
		$job          = new BatchExportJob(
			new ProductIdCacheBuilder(),
			$this->createMock( ExportableEntityProviderInterface::class ),
			$this->createMock( ExportRowBuilderInterface::class ),
			$this->writer
		);

		$this->service = new ProductExportService( $job, $this->createMock( AdPartnerApi::class ) );

		Options::set( OptionDefaults::EXPORT_FILE_URL, 'https://example.com/products-token.csv' );
	}

	public function tear_down(): void {
		as_unschedule_all_actions( Helper::with_prefix( ProductExportService::ACTION_HOOK ) );
		Options::delete( OptionDefaults::EXPORT_FILE_URL );
		Options::delete( OptionDefaults::CATALOG_ID );
		unset( $_REQUEST['security'], $_POST['delete_existing_catalog'] );

		parent::tear_down();
	}

	/**
	 * Logs in a new user with the given role and sends a valid admin nonce.
	 *
	 * @param string $role User role.
	 */
	private function act_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$_REQUEST['security'] = wp_create_nonce( 'admin_nonce' );
	}

	/**
	 * Runs an AJAX callback and returns the decoded JSON response.
	 *
	 * @param string $method Name of the ProductExportService callback.
	 * @return array|null
	 */
	private function call( string $method ): ?array {
		$this->_last_response = '';

		ob_start();
		try {
			$this->service->{$method}();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Asserts the response is the capability rejection.
	 *
	 * @param array|null $response Decoded JSON response.
	 */
	private function assertForbidden( ?array $response ): void {
		$this->assertFalse( $response['success'] );
		$this->assertSame(
			'You do not have permission to perform this action.',
			$response['data']['message']
		);
	}

	/**
	 * @dataProvider provide_callbacks
	 *
	 * @param string $method Name of the ProductExportService callback.
	 */
	public function test_user_without_capability_is_rejected_with_valid_nonce( string $method ): void {
		WC_Helper_Product::create_simple_product();
		$this->writer->expects( $this->never() )->method( 'create_file' );
		$this->act_as( 'contributor' );

		$response = $this->call( $method );

		$this->assertForbidden( $response );
		$this->assertArrayNotHasKey( 'fileUrl', $response );
		$this->assertSame( 'https://example.com/products-token.csv', Options::get( OptionDefaults::EXPORT_FILE_URL ) );
		$this->assertFalse( as_has_scheduled_action( Helper::with_prefix( ProductExportService::ACTION_HOOK ) ) );
		$this->assertEmpty( Options::get( OptionDefaults::CATALOG_ID ) );
	}

	public function provide_callbacks(): array {
		return array(
			'generate_feed'  => array( 'trigger_export_callback' ),
			'export_status'  => array( 'check_export_status' ),
			'create_catalog' => array( 'create_catalog_manually' ),
		);
	}

	public function test_user_without_capability_cannot_delete_catalog(): void {
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-123' );
		$_POST['delete_existing_catalog'] = 'true';
		$this->act_as( 'subscriber' );

		// The AdPartnerApi mock has no catalog client, so reaching the sink would fatal.
		$this->assertForbidden( $this->call( 'create_catalog_manually' ) );

		Options::delete( OptionDefaults::PIXEL_ID );
	}

	public function test_manager_can_trigger_export(): void {
		WC_Helper_Product::create_simple_product();
		$this->writer->method( 'create_file' )->willReturn( '/tmp/dummy.csv' );
		$this->act_as( 'administrator' );

		$response = $this->call( 'trigger_export_callback' );

		$this->assertTrue( $response['success'] );
	}

	public function test_manager_can_check_export_status(): void {
		$this->act_as( 'administrator' );

		$response = $this->call( 'check_export_status' );

		$this->assertSame( 'completed', $response['status'] );
		$this->assertSame( 'https://example.com/products-token.csv', $response['fileUrl'] );
	}

	public function test_manager_can_reach_create_catalog(): void {
		// An existing catalog ID makes the callback bail before any API call.
		Options::set( OptionDefaults::CATALOG_ID, 'catalog-123' );
		$this->act_as( 'administrator' );

		$response = $this->call( 'create_catalog_manually' );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Catalog already exists.', $response['data']['message'] );
	}

	public function test_invalid_nonce_is_rejected(): void {
		$this->act_as( 'administrator' );
		$_REQUEST['security'] = 'invalid';

		$this->expectException( WPAjaxDieStopException::class );
		$this->expectExceptionMessage( '-1' );

		// The AJAX die handler closes this buffer.
		ob_start();
		$this->service->trigger_export_callback();
	}
}
