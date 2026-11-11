<?php
/**
 * Interface definition for product-level Conversion Event payload fields.
 *
 * This interface defines the contract for classes that provide product-related
 * metadata within Conversion Event payloads sent to an Ad Partner’s Conversions API.
 * It ensures that all event types involving products (e.g., AddToCart, Purchase,
 * ViewContent) expose a consistent structure for product details.
 *
 * Required data points include:
 * - Products: IDs and names of involved WooCommerce products
 * - Currency: Store currency in ISO 4217 format
 * - Item count: Total number of items in the event
 * - Value: Monetary value (e.g., line subtotal or product price)
 *
 * ---
 * 🔗 Related Interfaces:
 * - ConversionCommonPayloadInterface — defines shared event-level fields.
 * - ConversionFinalPayloadInterface — ensures payload can be fully built.
 * ---
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent\Contract
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent\Contract;

/**
 * Interface for product-level Conversion Event payload fields.
 *
 * Implementing classes are responsible for extracting product-specific metadata
 * from WooCommerce entities and formatting them into a structure suitable for
 * inclusion in the final Conversions API payload.
 *
 * @since 0.1.0
 */
interface ConversionProductsPayloadInterface {
	/**
	 * Retrieves product metadata included in the Conversion Event.
	 *
	 * Each product must be represented as an associative array containing at
	 * minimum its ID, name, unit price, and quantity. Implementations may
	 * extend this to include additional attributes (e.g., category, variant,
	 * SKU) depending on the Ad Partner’s requirements.
	 *
	 * Example:
	 * ```php
	 * array(
	 *     array(
	 *         'id'         => '123',
	 *         'name'       => 'Sample Product',
	 *         'item_price' => 14.99,
	 *         'quantity'   => 1,
	 *     ),
	 * )
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return array<int,array<string,int|float|string>> List of product metadata.
	 */
	public function get_products(): array;

	/**
	 * Retrieves the store currency code for the Conversion Event.
	 *
	 * The returned value should follow the ISO 4217 format (e.g., `USD`, `EUR`).
	 * This ensures proper monetary interpretation by the Ad Partner API.
	 *
	 * @since 0.1.0
	 *
	 * @return string ISO 4217 currency code.
	 */
	public function get_currency(): string;

	/**
	 * Retrieves the total item count for the Conversion Event.
	 *
	 * Represents the number of items included in the event (e.g., the quantity
	 * of a single product in AddToCart, or the sum of all line items in Purchase).
	 *
	 * @since 0.1.0
	 *
	 * @return int Total item count.
	 */
	public function get_item_count(): int;

	/**
	 * Retrieves the monetary value of the Conversion Event.
	 *
	 * The value represents the relevant total for the event:
	 * - For **ViewContent** → product price
	 * - For **AddToCart**   → product subtotal (price × quantity)
	 * - For **Purchase**    → order total or revenue
	 *
	 * @since 0.1.0
	 *
	 * @return float Event value (monetary amount).
	 */
	public function get_value(): float;
}
