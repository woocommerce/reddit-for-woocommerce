<?php
/**
 * Server-side Ad Partner Conversion event representing an "Page View" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Utils\Helper;

/**
 * Constructs a Conversion request payload for the PageVisit event type.
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
	public const ID = 'PAGE_VISIT';


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
		$meta_data = array(
			'conversion_id' => $args['conversion_id'] ?? '',
		);

		$events = array(
			'event_at'      => Helper::get_event_time(),
			'action_source' => 'WEBSITE',
			'type'          => array(
				'tracking_type' => self::ID,
			),
			'metadata'      => $meta_data,
			'user'          => $args['user_data']['user'],
		);

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
