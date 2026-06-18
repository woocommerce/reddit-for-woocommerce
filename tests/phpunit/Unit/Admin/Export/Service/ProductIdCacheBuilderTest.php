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
}
