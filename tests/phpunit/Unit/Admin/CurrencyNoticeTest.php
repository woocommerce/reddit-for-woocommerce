<?php
/**
 * Unit tests for the CurrencyNotice class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin
 */

namespace RedditForWooCommerce\Tests\Unit\Admin;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\CurrencyNotice;

/**
 * @covers \RedditForWooCommerce\Admin\CurrencyNotice
 */
class CurrencyNoticeTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var CurrencyNotice
	 */
	private CurrencyNotice $notice;

	/**
	 * Backup the store currency option.
	 *
	 * @var string
	 */
	private $original_currency;

	/**
	 * Set up the instance under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->notice            = new CurrencyNotice();
		$this->original_currency = get_woocommerce_currency();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore the store currency.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_currency', $this->original_currency );
		parent::tearDown();
	}

	/**
	 * Test that the notice renders when the currency is unsupported.
	 */
	public function test_render_notice_outputs_notice_for_unsupported_currency(): void {
		update_option( 'woocommerce_currency', 'INR' );

		ob_start();
		$this->notice->render_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringContainsString( 'Store currency is not supported', $output );
	}

	/**
	 * Test that no notice renders when the currency is supported.
	 */
	public function test_render_notice_outputs_nothing_for_supported_currency(): void {
		update_option( 'woocommerce_currency', 'USD' );

		ob_start();
		$this->notice->render_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Test that users who can't manage WooCommerce don't see the notice.
	 *
	 * @dataProvider low_privilege_role_provider
	 *
	 * @param string $role Role without `manage_woocommerce`.
	 */
	public function test_render_notice_outputs_nothing_for_user_without_capability( string $role ): void {
		update_option( 'woocommerce_currency', 'INR' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );

		ob_start();
		$this->notice->render_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Data provider of roles without `manage_woocommerce`.
	 *
	 * @return array<string,array<string>>
	 */
	public function low_privilege_role_provider(): array {
		return array(
			'contributor' => array( 'contributor' ),
			'author'      => array( 'author' ),
			'editor'      => array( 'editor' ),
		);
	}

	/**
	 * Test that the notice is hooked into `admin_notices`.
	 */
	public function test_register_hooks_adds_admin_notices_action(): void {
		$this->notice->register_hooks();

		$this->assertNotFalse( has_action( 'admin_notices', array( $this->notice, 'render_notice' ) ) );
	}
}
