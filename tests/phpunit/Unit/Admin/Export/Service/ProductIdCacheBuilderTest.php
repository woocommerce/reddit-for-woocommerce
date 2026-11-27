<?php
/**
 * Unit tests for the ProductIdCacheBuilder class.
 *
 * Validates that handle_batch() correctly filters to physical products,
 * fires cache_completed on empty results regardless of page, and continues
 * pagination when a page has only virtual products.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder;
use RedditForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\Admin\Export\Service\ProductIdCacheBuilder
 */
class ProductIdCacheBuilderTest extends WP_UnitTestCase {

	/**
	 * @var ProductIdCacheBuilder
	 */
	private $builder;

	/**
	 * @var bool Whether the cache_completed action has fired.
	 */
	private $cache_completed_fired;

	public function set_up(): void {
		parent::set_up();

		$this->builder               = new ProductIdCacheBuilder();
		$this->cache_completed_fired = false;

		Options::set( OptionDefaults::EXPORT_PRODUCT_IDS, array() );

		add_action(
			Helper::with_prefix( 'export_products_cache_completed' ),
			function () {
				$this->cache_completed_fired = true;
			}
		);
	}

	public function tear_down(): void {
		remove_all_actions( Helper::with_prefix( 'export_products_cache_completed' ) );
		as_unschedule_all_actions( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ) );
		Options::delete( OptionDefaults::EXPORT_PRODUCT_IDS );

		parent::tear_down();
	}

	/**
	 * Tests that handle_batch() caches only physical product IDs when a page
	 * contains both physical and virtual products.
	 */
	public function test_handle_batch_caches_only_physical_products(): void {
		$physical = new \WC_Product_Simple();
		$physical->set_name( 'Physical product' );
		$physical->set_status( 'publish' );
		$physical->save();

		$virtual = new \WC_Product_Simple();
		$virtual->set_name( 'Virtual product' );
		$virtual->set_status( 'publish' );
		$virtual->set_virtual( true );
		$virtual->save();

		$this->builder->handle_batch( 1 );

		$cached = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );

		$this->assertContains( $physical->get_id(), $cached, 'Physical product ID should be cached.' );
		$this->assertNotContains( $virtual->get_id(), $cached, 'Virtual product ID should not be cached.' );

		$this->assertTrue(
			as_has_scheduled_action( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ), array( 'page' => 2 ) ) !== false,
			'Next page should be enqueued.'
		);
	}

	/**
	 * Tests that handle_batch() enqueues the next page but does not write to
	 * cache when a page contains only virtual/downloadable products.
	 */
	public function test_handle_batch_skips_cache_write_for_all_virtual_page(): void {
		$virtual = new \WC_Product_Simple();
		$virtual->set_name( 'Virtual only' );
		$virtual->set_status( 'publish' );
		$virtual->set_virtual( true );
		$virtual->save();

		$downloadable = new \WC_Product_Simple();
		$downloadable->set_name( 'Downloadable only' );
		$downloadable->set_status( 'publish' );
		$downloadable->set_downloadable( true );
		$downloadable->save();

		$this->builder->handle_batch( 1 );

		$cached = Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );

		$this->assertEmpty( $cached, 'Cache should remain empty when all products are virtual/downloadable.' );

		$this->assertTrue(
			as_has_scheduled_action( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ), array( 'page' => 2 ) ) !== false,
			'Next page should still be enqueued to continue scanning.'
		);

		$this->assertFalse( $this->cache_completed_fired, 'cache_completed should not fire when raw results are non-empty.' );
	}

	/**
	 * Tests that handle_batch() fires export_products_cache_completed on page 1
	 * when the store has zero published products.
	 */
	public function test_handle_batch_fires_cache_completed_on_empty_page_one(): void {
		$this->builder->handle_batch( 1 );

		$this->assertTrue( $this->cache_completed_fired, 'cache_completed should fire on empty page 1.' );

		$this->assertFalse(
			as_has_scheduled_action( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ), array( 'page' => 2 ) ),
			'No next page should be enqueued when results are empty.'
		);
	}

	/**
	 * Tests that handle_batch() fires export_products_cache_completed on page > 1
	 * when the query returns empty (end of pagination).
	 */
	public function test_handle_batch_fires_cache_completed_on_empty_subsequent_page(): void {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Page 1 product' );
		$product->set_status( 'publish' );
		$product->save();

		$this->builder->handle_batch( 2 );

		$this->assertTrue( $this->cache_completed_fired, 'cache_completed should fire on empty page 2+.' );

		$this->assertFalse(
			as_has_scheduled_action( Helper::with_prefix( ProductIdCacheBuilder::ACTION_HOOK ), array( 'page' => 3 ) ),
			'No next page should be enqueued when results are empty.'
		);
	}

	/**
	 * Creates a simple product.
	 *
	 * @param bool   $is_virtual      Whether the product is virtual.
	 * @param bool   $is_downloadable Whether the product is downloadable.
	 * @param string $status          Product status.
	 *
	 * @return \WC_Product_Simple
	 */
	private function create_product( bool $is_virtual = false, bool $is_downloadable = false, string $status = 'publish' ): \WC_Product_Simple {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_status( $status );
		$product->set_virtual( $is_virtual );
		$product->set_downloadable( $is_downloadable );
		$product->save();

		return $product;
	}

	/**
	 * Tests that has_exportable_products() is false when the store has no products.
	 */
	public function test_has_exportable_products_is_false_without_products(): void {
		$this->assertFalse( $this->builder->has_exportable_products() );
	}

	/**
	 * Tests that has_exportable_products() is false when only virtual or downloadable
	 * products are published.
	 */
	public function test_has_exportable_products_is_false_with_only_virtual_and_downloadable_products(): void {
		$this->create_product( true );
		$this->create_product( false, true );

		$this->assertFalse( $this->builder->has_exportable_products() );
	}

	/**
	 * Tests that has_exportable_products() is false when the only physical product
	 * is not published.
	 */
	public function test_has_exportable_products_is_false_with_only_unpublished_physical_products(): void {
		$this->create_product( false, false, 'draft' );

		$this->assertFalse( $this->builder->has_exportable_products() );
	}

	/**
	 * Tests that has_exportable_products() is false when the only physical product
	 * is excluded from the Reddit catalog.
	 */
	public function test_has_exportable_products_is_false_when_physical_product_is_excluded_from_catalog(): void {
		$product = $this->create_product();
		update_post_meta( $product->get_id(), Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM ), '0' );

		$this->assertFalse( $this->builder->has_exportable_products() );
	}

	/**
	 * Tests that has_exportable_products() is true when a published physical product exists.
	 */
	public function test_has_exportable_products_is_true_with_a_physical_product(): void {
		$this->create_product( true );
		$this->create_product();

		$this->assertTrue( $this->builder->has_exportable_products() );
	}
}
