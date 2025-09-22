<?php
/**
 * Server-side Ad Partner Conversion event representing an "View Content" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\AbstractEventPayloadBase;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionFinalPayloadInterface;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionProductsPayloadInterface;

/**
 * Constructs a Conversion request payload for the ViewContent event type.
 *
 * This class captures minimal single product page data for tracking
 * view content conversions.
 *
 * @since 0.1.0
 */
final class ViewContentEvent extends AbstractEventPayloadBase implements ConversionFinalPayloadInterface, ConversionProductsPayloadInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'VIEW_CONTENT';

	/**
	 * Product instance.
	 *
	 * @since 0.1.0
	 * @var \WC_Product|null
	 */
	private $product;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 */
	public function __construct( int $product_id ) {
		$this->product = wc_get_product( $product_id );
	}

	/**
	 * Retrieves the unique tracking type identifier for this event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier (`VIEW_CONTENT`).
	 */
	public function get_tracking_type(): string {
		return self::ID;
	}

	/**
	 * Retrieves the store currency code for the event.
	 *
	 * Uses the WooCommerce store currency setting, returned in ISO 4217 format
	 * (e.g., `USD`, `EUR`). Ensures compatibility with Ad Partner APIs that
	 * require explicit currency codes in payloads.
	 *
	 * @since 0.1.0
	 *
	 * @return string ISO 4217 currency code.
	 */
	public function get_currency(): string {
		return get_woocommerce_currency();
	}

	/**
	 * Retrieves the monetary value associated with the event.
	 *
	 * For a VIEW_CONTENT event, this corresponds to the product’s price.
	 * Returns `0.0` if the product instance is invalid or unavailable.
	 *
	 * @since 0.1.0
	 *
	 * @return float Product price or `0.0` if unavailable.
	 */
	public function get_value(): float {
		if ( $this->product instanceof \WC_Product ) {
			return (float) $this->product->get_price();
		}

		return 0.0;
	}

	/**
	 * Retrieves the total item count for the event.
	 *
	 * Always returns `1` for a VIEW_CONTENT event since it represents a single
	 * product view.
	 *
	 * @since 0.1.0
	 *
	 * @return int Item count (always `1`).
	 */
	public function get_item_count(): int {
		return 1;
	}

	/**
	 * Retrieves metadata for the viewed product.
	 *
	 * Returns an array with product details including its ID and name.
	 * If the product instance is invalid, an empty array is returned.
	 *
	 * Example:
	 * ```php
	 * array(
	 *     array(
	 *         'id'   => '123',
	 *         'name' => 'Sample Product',
	 *     ),
	 * )
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return array<int,array<string,string>> Product metadata array.
	 */
	public function get_products(): array {
		if ( ! $this->product instanceof \WC_Product ) {
			return array();
		}

		return array(
			array(
				'id'   => (string) $this->product->get_id(),
				'name' => $this->product->get_name(),
			),
		);
	}

	/**
	 * Builds the raw Conversion payload for the Ad Partner.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Overrideable payload args.
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload( array $args ): array {
		$meta_data = array(
			'conversion_id' => $args['conversion_id'] ?? '',
			'products'      => $this->get_products(),
		);

		$events = array(
			'event_at'      => self::get_event_at(),
			'action_source' => self::get_action_source(),
			'type'          => array(
				'tracking_type' => $this->get_tracking_type(),
			),
			'metadata'      => $meta_data,
		);

		if ( isset( $args['user_data']['click_id'] ) ) {
			$events['click_id'] = $args['user_data']['click_id'];
		}

		if ( isset( $args['user_data']['user'] ) ) {
			$events['user'] = $args['user_data']['user'];
		}

		$payload = array(
			'data' => array(
				'partner' => self::get_partner(),
				'events'  => array( $events ),
			),
		);

		return $payload;
	}
}
