<?php
/**
 * Abstract base class for Conversion Event payloads.
 *
 * This abstract class provides default implementations for common Conversion Event
 * fields defined in the {@see ConversionCommonPayloadInterface}. It ensures that
 * all implementing event classes share a consistent baseline for critical metadata
 * such as:
 *
 * - Partner identifier (`WOOCOMMERCE`)
 * - Event timestamp (via {@see Helper::get_event_time()})
 * - Action source (`WEBSITE`)
 *
 * Event-specific classes (e.g., PurchaseEvent, AddToCartEvent, ViewContentEvent)
 * extend this base to inherit the defaults, while defining their own unique
 * `tracking_type` identifiers.
 *
 * ---
 * 🔗 Related Interfaces & Classes:
 * - ConversionCommonPayloadInterface — defines shared required fields.
 * - ConversionProductsPayloadInterface — adds product-level details.
 * - ConversionFinalPayloadInterface — ensures payloads can be fully built.
 * ---
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionCommonPayloadInterface;
use RedditForWooCommerce\Utils\Helper;

/**
 * Abstract base class for Conversion Event payloads.
 *
 * Implements default logic for shared event fields, reducing duplication
 * across concrete event classes. Subclasses are required to implement
 * {@see AbstractEventPayloadBase::get_tracking_type()} to define their
 * event type identifier.
 *
 * @since 0.1.0
 */
abstract class AbstractEventPayloadBase implements ConversionCommonPayloadInterface {
	/**
	 * Retrieves the identifier of the data source partner.
	 *
	 * For all WooCommerce integrations, this value is fixed as `WOOCOMMERCE`.
	 * Ad Partners use this field to identify the system of origin for the event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Partner identifier (`WOOCOMMERCE`).
	 */
	public static function get_partner(): string {
		return 'WOOCOMMERCE';
	}

	/**
	 * Retrieves the Unix timestamp when the event occurred.
	 *
	 * Uses the {@see Helper::get_event_time()} utility method to return the
	 * current event time in seconds. This ensures consistent and reliable
	 * timestamp generation across all event payloads.
	 *
	 * @since 0.1.0
	 *
	 * @return int Unix timestamp (in seconds).
	 */
	public static function get_event_at(): int {
		return Helper::get_event_time();
	}

	/**
	 * Retrieves the source channel of the conversion event.
	 *
	 * Defaults to `WEBSITE`.
	 *
	 * @since 0.1.0
	 *
	 * @return string Action source identifier (`WEBSITE`).
	 */
	public static function get_action_source(): string {
		return 'WEBSITE';
	}

	/**
	 * Retrieves the unique tracking type identifier for the event.
	 *
	 * Subclasses must implement this method to define their event type constant
	 * (e.g., `PURCHASE`, `ADD_TO_CART`, `VIEW_CONTENT`, `PAGE_VIEW`).
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier.
	 */
	abstract public function get_tracking_type(): string;
}
