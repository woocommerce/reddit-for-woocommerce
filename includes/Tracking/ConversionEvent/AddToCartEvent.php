<?php
/**
 * Server-side Ad Partner Conversion event representing an "Add to Cart" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\AbstractEventPayloadBase;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionFinalPayloadInterface;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionProductsPayloadInterface;

/**
 * Constructs a Conversion request payload for the AddToCart event type.
 *
 * This class captures minimal cart data for tracking add-to-cart conversions.
 *
 * @since 0.1.0
 */
final class AddToCartEvent extends AbstractEventPayloadBase implements ConversionFinalPayloadInterface, ConversionProductsPayloadInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'ADD_TO_CART';

	/**
	 * Product instance.
	 *
	 * @since 0.1.0
	 * @var \WC_Product|null
	 */
	private $product;

	/**
	 * Quantity of the product added.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $quantity;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity added.
	 */
	public function __construct( int $product_id, int $quantity ) {
		$this->quantity = $quantity;
		$this->product  = wc_get_product( $product_id );
	}

	/**
	 * Retrieves the unique tracking type identifier for this event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier (`ADD_TO_CART`).
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
	 * Retrieves the total monetary value of the event.
	 *
	 * The value is calculated as the product’s price multiplied by the
	 * quantity added. Returns `0.0` if the product instance is invalid.
	 *
	 * @since 0.1.0
	 *
	 * @return float Event value (subtotal amount).
	 */
	public function get_value(): float {
		if ( ! $this->product instanceof \WC_Product ) {
			return 0.0;
		}

		return floatval( $this->product->get_price() ) * $this->quantity;
	}

	/**
	 * Retrieves the item count for the event.
	 *
	 * For AddToCart events, this corresponds to the quantity of the product
	 * added to the cart.
	 *
	 * @since 0.1.0
	 *
	 * @return int Quantity added.
	 */
	public function get_item_count(): int {
		return (int) $this->quantity;
	}

	/**
	 * Retrieves metadata for the product added to the cart.
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
			'currency'      => $this->get_currency(),
			'value'         => $this->get_value(),
			'item_count'    => $this->get_item_count(),
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
