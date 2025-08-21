<?php
/**
 * Server-side Ad Partner Conversion event representing an "Add to Cart" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Utils\Helper;

/**
 * Constructs a Conversion request payload for the AddToCart event type.
 *
 * This class captures minimal cart data for tracking add-to-cart conversions.
 *
 * @since 0.1.0
 */
final class AddToCartEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'AddToCart';

	/**
	 * Product ID being added to the cart.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $product_id;

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
		$this->product_id = $product_id;
		$this->quantity   = $quantity;
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
	public function build_payload( array $args = array() ): array {
		$product = wc_get_product( $this->product_id );

		if ( ! $product instanceof \WC_Product ) {
			return array();
		}

		$meta_data = array(
			'conversion_id' => $args['conversion_id'] ?? '',
			'item_count'    => (int) $this->quantity,
			'value_decimal' => floatval( $product->get_price() ) * $this->quantity,
			'currency'      => get_woocommerce_currency(),
			'products'      => array(
				array(
					'id'   => (string) $this->product_id,
					'name' => $product->get_name(),
				),
			),
		);

		$base    = parent::build_payload();
		$default = array(
			'event_type'     => array(
				'tracking_type' => self::ID
			),
			'event_metadata' => $meta_data,
		);

		return array_merge( $base, $default, $args['user_data'] );
	}
}
