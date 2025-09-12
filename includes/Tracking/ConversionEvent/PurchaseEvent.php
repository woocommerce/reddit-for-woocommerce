<?php
/**
 * Server-side Ad Partner Conversion event representing a completed purchase.
 *
 * Builds a structured payload using WooCommerce order details
 * to send to the Ad Partner's Conversions API.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\AbstractEventPayloadBase;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionFinalPayloadInterface;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionProductsPayloadInterface;
use WC_Order;

/**
 * Constructs a Conversion request payload for the Purchase event type.
 *
 * Extracts item details, order totals, and identifiers from
 * the WooCommerce order object to track conversions accurately.
 *
 * @since 0.1.0
 */
final class PurchaseEvent extends AbstractEventPayloadBase implements ConversionProductsPayloadInterface, ConversionFinalPayloadInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'PURCHASE';

	/**
	 * WooCommerce order object.
	 *
	 * @since 0.1.0
	 * @var WC_Order|null
	 */
	private $order;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function __construct( int $order_id ) {
		$this->order = wc_get_order( $order_id );
	}

	/**
	 * Retrieves the unique tracking type identifier for this event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier (`PURCHASE`).
	 */
	public function get_tracking_type(): string {
		return self::ID;
	}

	/**
	 * Retrieves the currency used for the order.
	 *
	 * Falls back to the store’s default WooCommerce currency if the
	 * order object is not available. Otherwise, retrieves the currency
	 * directly from the WooCommerce order instance.
	 *
	 * @since 0.1.0
	 *
	 * @return string ISO 4217 currency code.
	 */
	public function get_currency(): string {
		if ( ! $this->order instanceof WC_Order ) {
			return get_woocommerce_currency();
		}

		return $this->order->get_currency();
	}

	/**
	 * Retrieves the total monetary value of the order.
	 *
	 * Returns `0.0` if the order is not available. Otherwise,
	 * returns the WooCommerce order total as a float.
	 *
	 * @since 0.1.0
	 *
	 * @return float Order total or `0.0` if unavailable.
	 */
	public function get_value(): float {
		if ( ! $this->order instanceof WC_Order ) {
			return 0.0;
		}

		return (float) $this->order->get_total();
	}

	/**
	 * Retrieves the total item count from the order.
	 *
	 * Returns `0` if the order object is not available.
	 * Otherwise, returns the number of line items in the order.
	 *
	 * @since 0.1.0
	 *
	 * @return int Total item count.
	 */
	public function get_item_count(): int {
		if ( ! $this->order instanceof WC_Order ) {
			return 0;
		}

		return (int) $this->order->get_item_count();
	}

	/**
	 * Retrieves metadata for the products included in the order.
	 *
	 * Iterates through order line items and extracts basic product
	 * details (ID and name) for each valid product. Returns an
	 * empty array if the order object is unavailable or no valid
	 * products are found.
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
	 * @return array<int,array<string,string>> List of product metadata.
	 */
	public function get_products(): array {
		$products = array();

		if ( ! $this->order instanceof WC_Order ) {
			return $products;
		}

		/**
		 * Product from the Order Line Item.
		 *
		 * @var \WC_Order_Item_Product $item Product item.
		 */
		foreach ( $this->order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$products[] = array(
				'id'   => (string) $product->get_id(),
				'name' => (string) $product->get_name(),
			);
		}

		return $products;
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
