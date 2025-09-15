<?php
/**
 * Server-side Ad Partner Conversion event representing an "Page View" action.
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\AbstractEventPayloadBase;
use RedditForWooCommerce\Tracking\ConversionEvent\Contract\ConversionFinalPayloadInterface;

/**
 * Constructs a Conversion request payload for the PageVisit event type.
 *
 * This class captures minimal single product page data for tracking
 * view content conversions.
 *
 * @since 0.1.0
 */
final class PageVisitEvent extends AbstractEventPayloadBase implements ConversionFinalPayloadInterface {

	/**
	 * Unique identifier for this event type.
	 *
	 * Used to register and identify the event in the system.
	 *
	 * @since 0.1.0
	 */
	public const ID = 'PAGE_VISIT';

	/**
	 * Retrieves the unique tracking type identifier for this event.
	 *
	 * @since 0.1.0
	 *
	 * @return string Tracking type identifier (`PAGE_VISIT`).
	 */
	public function get_tracking_type(): string {
		return self::ID;
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
