<?php
/**
 * Unit tests for OnboardingController::get_setup_state().
 *
 * @package RedditForWooCommerce\Tests\Unit\API\Site\Controllers
 */

namespace RedditForWooCommerce\Tests\Unit\API\Site\Controllers;

use WP_UnitTestCase;
use RedditForWooCommerce\API\Site\Controllers\OnboardingController;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \RedditForWooCommerce\API\Site\Controllers\OnboardingController::get_setup_state
 */
class OnboardingControllerTest extends WP_UnitTestCase {

	/**
	 * @var OnboardingController
	 */
	private $controller;

	/**
	 * @var string
	 */
	private $original_currency;

	public function set_up(): void {
		parent::set_up();

		$this->controller        = new OnboardingController();
		$this->original_currency = get_woocommerce_currency();
	}

	public function tear_down(): void {
		update_option( 'woocommerce_currency', $this->original_currency );
		Options::delete( OptionDefaults::ONBOARDING_STATUS );
		Options::delete( OptionDefaults::ONBOARDING_STEP );

		parent::tear_down();
	}

	public function test_get_setup_state_reports_true_for_supported_currency() {
		update_option( 'woocommerce_currency', 'USD' );

		$response = $this->controller->get_setup_state();

		$this->assertTrue( $response->get_data()['isCurrencySupported'] );
	}

	public function test_get_setup_state_reports_false_for_unsupported_currency() {
		update_option( 'woocommerce_currency', 'INR' );

		$response = $this->controller->get_setup_state();

		$this->assertFalse( $response->get_data()['isCurrencySupported'] );
	}
}
