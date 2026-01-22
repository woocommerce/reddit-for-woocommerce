<?php
/**
 * Builds exportable CSV rows from WooCommerce product entities.
 *
 * This class transforms a `WC_Product` instance into an associative array of
 * fields required by Reddit's product catalog format. It ensures that
 * all expected keys (such as title, price, and availability) are present,
 * and performs minimal transformation where needed (e.g., appending currency to price).
 *
 * @package RedditForWooCommerce\Admin\Export\RowBuilder
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Admin\Export\RowBuilder;

use WC_Product;
use RedditForWooCommerce\Admin\Export\Contract\ExportRowBuilderInterface;
use RedditForWooCommerce\Admin\Export\Contract\RowBuilderAdditionalData;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use RedditForWooCommerce\Utils\Helper;

/**
 * Converts WooCommerce products into catalog-compatible row arrays.
 *
 * Implements {@see ExportRowBuilderInterface} to define how a WooCommerce product
 * should be exported into a format suitable for Reddit’s product feed.
 * Handles common fields such as title, price, and availability, and attempts
 * to extract optional fields like brand, GTIN, and MPN from product attributes or meta.
 *
 * This class is intended to be reused across batch exporters or file writers.
 *
 * @since 0.1.0
 */
class ProductRowBuilder implements ExportRowBuilderInterface {
	/**
	 * Additional data providers that can enrich the export row.
	 *
	 * @var RowBuilderAdditionalData[]
	 */
	protected $additional_data_providers = array();

	/**
	 * Constructor.
	 *
	 * Accepts an array of {@see RowBuilderAdditionalData} providers which will
	 * contribute extra fields to the final row. This allows for a clean
	 * separation between core attributes and optional enrichments.
	 *
	 * @since 0.1.0
	 *
	 * @param RowBuilderAdditionalData[] $additional_data_providers One or more providers.
	 */
	public function __construct( array $additional_data_providers = array() ) {
		$this->additional_data_providers = $additional_data_providers;
	}

	/**
	 * Builds a single exportable row from a product entity.
	 *
	 * Validates that the input is a `WC_Product`, then extracts relevant product data
	 * into an associative array with Reddit catalog keys. If the input is not a product,
	 * returns `null` to skip the row.
	 *
	 * The resulting row includes:
	 * - Core attributes like ID, title, description, image, and price
	 * - Inventory status
	 * - Optional metadata: brand, GTIN, and MPN
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $product A `WC_Product` instance expected.
	 * @return array<string,scalar>|null Associative export row or null to skip.
	 */
	public function build_row( $product ): ?array {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

		$regular_price = $product->get_regular_price();
		$is_price_set  = '' !== $regular_price;
		$currency      = get_woocommerce_currency();

		$row = array(
			'id'          => (string) $product->get_id(),
			'title'       => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_description() ),
			'link'        => get_permalink( $product->get_id() ),
			'image_link'  => $image_url,
			'price'       => ( $is_price_set ? $regular_price : '0' ) . ' ' . $currency,
			'sale_price'  => '',
			'gtin'        => $product->get_global_unique_id(),
		);

		$sale_price = $product->get_sale_price();

		// @see https://business.reddithelp.com/s/article/catalog-requirements
		if ( '' !== $sale_price ) {
			$row['sale_price'] = $sale_price . ' ' . $currency;
		}

		$stock_status = $product->get_stock_status();

		/**
		 * In case the price is not set, we set the availability to 'out_of_stock'
		 * as that indicates the product is not available for purchase.
		 *
		 * However, if the price is explicitly set to `0`, we consider it as 'in_stock'
		 * as the product can be sold as a free product (for example, a free digital download).
		 */
		if ( ProductStockStatus::ON_BACKORDER === $stock_status ) {
			$row['availability'] = 'backorder';
		} elseif ( $product->is_in_stock() && $is_price_set ) {
			$row['availability'] = 'in_stock';
		} elseif ( $product->is_in_stock() && ! $is_price_set ) {
			$row['availability'] = 'out_of_stock';
		} elseif ( ! $product->is_in_stock() ) {
			$row['availability'] = 'out_of_stock';
		}

		// Merge additional data from all providers.
		foreach ( $this->additional_data_providers as $provider ) {
			$row = array_merge( $row, $provider->get_additional_data( $product ) );
		}

		/**
		 * Filters the row built to be written.
		 *
		 * @param array      $row     Row of a CSV describing product properties.
		 * @param WC_Product $product A WooCommerce product.
		 * @return array
		 */
		return apply_filters( Helper::with_prefix( 'filter_builder_row' ), $row, $product );
	}
}
