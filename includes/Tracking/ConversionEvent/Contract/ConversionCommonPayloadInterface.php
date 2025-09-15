<?php
/**
 * Interface definition for common Conversion Event payload fields.
 *
 * This contract enforces a consistent set of shared fields across all
 * server-side Ad Partner Conversion Events. These fields represent the
 * universal data points required to build a valid Conversions API request,
 * such as:
 *
 * - Partner identifier (system of origin)
 * - Event timestamp
 * - Source channel (e.g., website, app)
 * - Tracking type identifier
 *
 * By standardizing these values, all event payload implementations remain
 * interoperable with Ad Partner APIs (e.g., Reddit, Meta, TikTok) while
 * maintaining predictable data formats inside WooCommerce integrations.
 *
 * ---
 * 🔗 Related Interfaces:
 * - ConversionProductsPayloadInterface — adds product-level metadata.
 * - ConversionFinalPayloadInterface — ensures payload can be built for dispatch.
 * ---
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent\Contract
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent\Contract;

/**
 * Interface for common Ad Partner Conversion event payload fields.
 *
 * Defines the shared contract for all event payload classes. Every Conversion
 * Event (Purchase, AddToCart, ViewContent, PageView, etc.) must provide these
 * fields to guarantee consistent attribution, reporting, and API compatibility.
 *
 * @since 0.1.0
 */
interface ConversionCommonPayloadInterface {
	/**
	 * Retrieves the identifier of the data source partner.
	 *
	 * Typically this is a constant string like `WOOCOMMERCE` that indicates
	 * the system of origin for the conversion payload. Ad Partners use this
	 * field to distinguish between integrations.
	 *
	 * @since 0.1.0
	 *
	 * @return string Partner identifier (e.g., `WOOCOMMERCE`).
	 */
	public static function get_partner(): string;

	/**
	 * Retrieves the Unix timestamp when the event occurred.
	 *
	 * The timestamp should represent the moment the WooCommerce user action
	 * (purchase, add-to-cart, product view, etc.) took place. This value is
	 * critical for accurate attribution and sequencing of conversion events.
	 *
	 * @since 0.1.0
	 *
	 * @return int Unix timestamp (in seconds).
	 */
	public static function get_event_at(): int;

	/**
	 * Retrieves the source channel of the conversion event.
	 *
	 * This field identifies the environment where the conversion was recorded
	 * (e.g., `WEBSITE`, `MOBILE_APP`). Most WooCommerce-based integrations
	 * default to `WEBSITE`, but alternative implementations may specify other
	 * values depending on the integration context.
	 *
	 * @since 0.1.0
	 *
	 * @return string Action source identifier (e.g., `WEBSITE`).
	 */
	public static function get_action_source(): string;

	/**
	 * Retrieves the unique tracking type identifier for the event.
	 *
	 * Each event (e.g., `PURCHASE`, `ADD_TO_CART`, `VIEW_CONTENT`, `PAGE_VIEW`)
	 * declares its own constant identifier. This allows the Ad Partner API to
	 * classify the event correctly and apply the appropriate attribution logic.
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier (e.g., `PURCHASE`).
	 */
	public function get_tracking_type(): string;
}
