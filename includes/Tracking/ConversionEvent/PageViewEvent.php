<?php
/**
 * Server-side Ad Partner Conversion event representing an "Page View" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

/**
 * Constructs a Conversion request payload for the PageView event type.
 *
 * This class captures minimal single product page data for tracking
 * view content conversions.
 *
 * @since 0.1.0
 */
final class PageVisitEvent extends EventPayloadBase implements ConversionEventInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'PageView';


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
		$base    = parent::build_payload();
		$default = array(
			'event_type' => array(
				'tracking_type' => self::ID,
			),
		);

		return array_merge( $base, $default, $args['user_data'] );
	}
}
