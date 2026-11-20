<?php
/**
 * Unit tests for the ProductEntityProvider class.
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\EntityProvider;

use WP_UnitTestCase;
use WC_Product_Simple;
use RedditForWooCommerce\Admin\Export\EntityProvider\ProductEntityProvider;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Admin\Export\EntityProvider\ProductEntityProvider
 */
class ProductEntityProviderTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var ProductEntityProvider
	 */
	private $provider;

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->provider = new ProductEntityProvider();
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
	}

	/**
	 * Tears down the test environment.
	 */
	public function tear_down(): void {
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );
		parent::tear_down();
	}

	/**
	 * Tests that get_total() returns the correct count of exportable products.
	 */
	public function test_get_total_returns_correct_count() {
		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, array( 1, 2, 3, 4 ) );

		$this->assertSame( 4, $this->provider->get_total() );
	}

	/**
	 * Tests that get_ids() returns a paginated slice from the cached list.
	 */
	public function test_get_ids_respects_offset_and_limit() {
		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, array( 10, 20, 30, 40, 50 ) );

		$ids = $this->provider->get_ids( 1, 3 );

		$this->assertSame( array( 20, 30, 40 ), $ids );
	}

	/**
	 * Tests that get_entities() returns WC_Product objects for valid IDs.
	 */
	public function test_get_entities_returns_valid_product_objects() {
		$product_ids = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$product = new WC_Product_Simple();
			$product->set_name( 'Exportable Product ' . $i );
			$product->set_regular_price( 25 + $i );
			$product->save();

			$product_ids[] = $product->get_id();
		}

		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, $product_ids );

		$resolved = $this->provider->get_entities( $product_ids );

		$this->assertCount( 3, $resolved );

		foreach ( $resolved as $product ) {
			$this->assertInstanceOf( WC_Product_Simple::class, $product );
		}
	}

	/**
	 * A product can leave the published state after its ID was cached
	 * but before its row is written.
	 *
	 * @dataProvider unpublished_status_provider
	 *
	 * @param string $status Status the product moves to after its ID was cached.
	 */
	public function test_get_entities_skips_products_unpublished_after_ids_were_cached( string $status ) {
		$published = new WC_Product_Simple();
		$published->set_name( 'Still published' );
		$published->save();

		$unpublished = new WC_Product_Simple();
		$unpublished->set_name( 'Unpublished later' );
		$unpublished->save();

		$product_ids = array( $published->get_id(), $unpublished->get_id() );
		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, $product_ids );

		$unpublished->set_status( $status );
		$unpublished->save();

		$resolved_ids = array_map(
			function ( $product ) {
				return $product->get_id();
			},
			$this->provider->get_entities( $product_ids )
		);

		$this->assertSame( array( $published->get_id() ), $resolved_ids );
	}

	/**
	 * Statuses WooCommerce returns by default that must not be exported.
	 *
	 * @return array<string, array{string}>
	 */
	public function unpublished_status_provider(): array {
		return array(
			'draft'   => array( 'draft' ),
			'pending' => array( 'pending' ),
			'private' => array( 'private' ),
		);
	}
}
