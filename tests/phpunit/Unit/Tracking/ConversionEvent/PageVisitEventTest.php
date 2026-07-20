<?php
/**
 * Tests for the PageVisitEvent class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Tracking\ConversionEvent;

use RedditForWooCommerce\Tracking\ConversionEvent\PageVisitEvent;
use RedditForWooCommerce\Utils\UserIdentifier;
use WP_UnitTestCase;

require_once 'Utils.php';

/**
 * @covers \RedditForWooCommerce\Tracking\ConversionEvent\PageVisitEvent
 */
final class PageVisitEventTest extends WP_UnitTestCase {

	/**
	 * Set up environment for the test.
	 */
	public function set_up(): void {
		parent::set_up();
		r4w_setup_globals();
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		r4w_destroy_globals();
		parent::tear_down();
	}

	/**
	 * Tests that the payload contains the expected partner metadata.
	 */
	public function test_build_payload_contains_partner_version(): void {
		$event   = new PageVisitEvent();
		$payload = $event->build_payload(
			array(
				'conversion_id' => 'visit_123',
				'user_data'     => UserIdentifier::get_user_data(),
			)
		);

		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( 'data', $payload );
		$this->assertSame( 'WOOCOMMERCE', $payload['data']['partner'] );
		$this->assertSame( REDDIT_FOR_WOOCOMMERCE_VERSION, $payload['data']['partner_version'] );
		$this->assertArrayHasKey( 'events', $payload['data'] );

		$events = $payload['data']['events'][0];

		$this->assertSame( 'WEBSITE', $events['action_source'] );
		$this->assertSame( 'PAGE_VISIT', $events['type']['tracking_type'] );
		$this->assertSame( 'visit_123', $events['metadata']['conversion_id'] );
	}
}
