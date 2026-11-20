<?php
/**
 * Unit tests for the ProductIdCacheBuilder class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\Export\Service
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\Export\Service;

use WP_UnitTestCase;
use WC_Product_Simple;
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
	 * Instance under test.
	 *
	 * @var ProductIdCacheBuilder
	 */
	private $builder;

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->builder = new ProductIdCacheBuilder();
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
	 * Creates and saves a published simple product.
	 *
	 * @param array $props Product props to set before saving.
	 * @return int Product ID.
	 */
	private function create_product( array $props = array() ): int {
		$product = new WC_Product_Simple();
		$product->set_props(
			array_merge(
				array(
					'name'          => 'Test product',
					'regular_price' => '10',
					'status'        => 'publish',
				),
				$props
			)
		);

		return $product->save();
	}

	/**
	 * Scans the first page and returns the cached product IDs.
	 *
	 * @return array<int>
	 */
	private function scan_first_page(): array {
		$this->builder->handle_batch( 1 );

		return Options::get( OptionDefaults::EXPORT_PRODUCT_IDS, array() );
	}

	public function test_handle_batch_excludes_hidden_products(): void {
		$visible_id = $this->create_product();
		$hidden_id  = $this->create_product( array( 'catalog_visibility' => 'hidden' ) );

		$ids = $this->scan_first_page();

		$this->assertContains( $visible_id, $ids );
		$this->assertNotContains( $hidden_id, $ids );
	}

	public function test_handle_batch_excludes_password_protected_products(): void {
		$public_id    = $this->create_product();
		$protected_id = $this->create_product( array( 'post_password' => 'secret' ) );

		$ids = $this->scan_first_page();

		$this->assertContains( $public_id, $ids );
		$this->assertNotContains( $protected_id, $ids );
	}

	public function test_handle_batch_keeps_shop_only_and_search_only_products(): void {
		$shop_only_id   = $this->create_product( array( 'catalog_visibility' => 'catalog' ) );
		$search_only_id = $this->create_product( array( 'catalog_visibility' => 'search' ) );

		$ids = $this->scan_first_page();

		$this->assertContains( $shop_only_id, $ids );
		$this->assertContains( $search_only_id, $ids );
	}

	public function test_handle_batch_keeps_excluding_products_turned_off_in_channel_visibility(): void {
		$included_id = $this->create_product();
		$excluded_id = $this->create_product();
		update_post_meta( $excluded_id, Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM ), '0' );

		$ids = $this->scan_first_page();

		$this->assertContains( $included_id, $ids );
		$this->assertNotContains( $excluded_id, $ids );
	}
}
