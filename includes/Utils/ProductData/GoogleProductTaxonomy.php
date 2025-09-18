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
 * @package RedditForWooCommerce\Utils\ProductData
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Utils\ProductData;

use WC_Product;
use WP_Term;

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
class GoogleProductTaxonomy {
	/**
	 * Build the google_product_category value for a given product.
	 *
	 * Returns the product category string.
	 * If the product has no categories, an empty string is returned.
	 *
	 * Example: 'Apparel & Accessories > Clothing > Activewear'
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Product $product Product instance.
	 * @return string Associative array with GPC field or empty if unavailable.
	 */
	public function get_google_product_category( $product ): string {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		// Select the deepest assigned category.
		usort(
			$terms,
			function ( $a, $b ) {
				return count( get_ancestors( $b->term_id, 'product_cat' ) )
					- count( get_ancestors( $a->term_id, 'product_cat' ) );
			}
		);

		$primary = array_shift( $terms );

		// Build breadcrumb-style hierarchy.
		$ancestors = array_reverse( get_ancestors( $primary->term_id, 'product_cat' ) );

		// Prime caches for all ancestor terms in a single call.
		_prime_term_caches( $ancestors, 'product_cat' );

		$categories = array();

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );

			if ( ! is_wp_error( $ancestor ) && $ancestor instanceof WP_Term ) {
				$categories[] = $ancestor->name;
			}
		}

		$categories[] = $primary->name;
		$gpc          = implode( ' > ', $categories );

		return substr( $gpc, 0, 250 );
	}
}
