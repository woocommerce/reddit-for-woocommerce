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

use RedditForWooCommerce\Utils\Helper;
use WC_Order;

/**
 * Constructs a Conversion request payload for the Purchase event type.
 *
 * Extracts item details, order totals, and identifiers from
 * the WooCommerce order object to track conversions accurately.
 *
 * @since 0.1.0
 */
final class PurchaseEvent extends EventPayloadBase implements ConversionEventInterface {

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
	 * Builds the raw Conversion payload for the Ad Partner.
	 *
	 * Includes order totals, currency, line items, and metadata.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Overrideable payload args.
	 *
	 * @return array<string,mixed> Conversion event payload.
	 */
	public function build_payload( array $args = array() ): array {
		if ( ! $this->order ) {
			return array();
		}

		$products = array();

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

		$meta_data = array(
			'conversion_id' => $args['conversion_id'] ?? '',
			'item_count'    => (int) $this->order->get_item_count(),
			'value'         => floatval( $this->order->get_total() ),
			'currency'      => get_woocommerce_currency(),
			'products'      => $products,
		);

		$events = array(
			'event_at'      => Helper::get_event_time(),
			'action_source' => 'WEBSITE',
			'type'          => array(
				'tracking_type' => self::ID,
			),
			'metadata'      => $meta_data,
		);

		if ( isset( $args['user_data']['user'] ) ) {
			$events['user'] = $args['user_data']['user'];
		}

		if ( isset( $args['user_data']['click_id'] ) ) {
			$events['click_id'] = $args['user_data']['click_id'];
		}

		$payload = array(
			'data' => array(
				'partner' => 'WOOCOMMERCE',
				'events'  => array(
					$events,
				),
			),
		);

		return $payload;
	}
}
