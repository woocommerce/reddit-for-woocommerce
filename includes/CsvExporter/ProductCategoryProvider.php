<?php
/**
 * Provides the Google Product Category (GPC) field for WooCommerce products.
 *
 * This class inspects the WooCommerce product categories assigned to a product
 * and derives a breadcrumb-style string representation (e.g.,
 * "Apparel & Accessories > Clothing > Activewear"). The resulting value is
 * returned as an associative array suitable for merging into an export row or
 * any other product payload.
 *
 * By implementing {@see RowBuilderAdditionalData}, this provider can be injected
 * into {@see ProductRowBuilder} or any similar row builder, ensuring that
 * product category logic remains isolated, reusable, and testable.
 *
 * @package RedditForWooCommerce\Utils\ProductData
 * @since 0.1.0
 */

namespace RedditForWooCommerce\CsvExporter;

use WC_Product;
use RedditForWooCommerce\Admin\Export\Contract\RowBuilderAdditionalData;
use RedditForWooCommerce\Utils\ProductData\GoogleProductTaxonomy;

/**
 * Derives the google_product_category field from WooCommerce product categories.
 *
 * The provider walks the term hierarchy to build a breadcrumb-style path,
 * ensuring that deeper category context is captured. The final string is
 * truncated to 250 characters, in accordance with Google’s product data
 * specification.
 *
 * @since 0.1.0
 */
class ProductCategoryProvider implements RowBuilderAdditionalData {

	/**
	 * Build the google_product_category value for a given product.
	 *
	 * Returns an associative array with a single key 'google_product_category'.
	 * If the product has no categories, an empty array is returned to avoid
	 * polluting the export row with invalid data.
	 *
	 * Example:
	 * [
	 *   'google_product_category' => 'Apparel & Accessories > Clothing > Activewear'
	 * ]
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product instance.
	 * @return array<string,string> Associative array with GPC field or empty if unavailable.
	 */
	public function get_additional_data( $product ): array {
		$data = ( new GoogleProductTaxonomy() )->get_google_product_category( $product );

		return array(
			'google_product_category' => $data,
		);
	}
}
