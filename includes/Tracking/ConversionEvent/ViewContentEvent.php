<?php
/**
 * Server-side Ad Partner Conversion event representing an "View Content" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

/**
 * Constructs a Conversion request payload for the ViewContent event type.
 *
 * This class captures minimal single product page data for tracking
 * view content conversions.
 *
 * @since 0.1.0
 */
final class ViewContentEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'ViewContent';

	/**
	 * Product ID being viewed.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $product_id;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 */
	public function __construct( int $product_id ) {
		$this->product_id = $product_id;
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
				'tracking_type' => self::ID,
			),
			'event_metadata' => $meta_data,
		);

		return array_merge( $base, $default, $args['user_data'] );
	}
}
