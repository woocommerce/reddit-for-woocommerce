<?php
/**
 * Unit tests for the admin Assets class.
 *
 * Ensures admin assets and the localized AdminData (which carries the admin
 * nonce, feed URL and account details) are only exposed to users who can
 * manage the plugin, on the real wc-admin screen.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin
 */

namespace RedditForWooCommerce\Tests\Unit\Admin;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\Assets;
use RedditForWooCommerce\Config;

/**
 * @covers \RedditForWooCommerce\Admin\Assets
 */
final class AdminAssetsTest extends WP_UnitTestCase {

	/**
	 * Script handle enqueued by the admin Assets class.
	 */
	private const HANDLE = Config::ASSET_HANDLE_PREFIX . 'index';

	/**
	 * Original value of the `$pagenow` global.
	 *
	 * @var string|null
	 */
	private $original_pagenow;

	public function set_up(): void {
		parent::set_up();

		$this->original_pagenow = $GLOBALS['pagenow'] ?? null;
	}

	public function tear_down(): void {
		wp_dequeue_script( self::HANDLE );
		wp_deregister_script( self::HANDLE );
		wp_dequeue_style( self::HANDLE );
		wp_deregister_style( self::HANDLE );

		unset( $_GET['page'], $_GET['path'] );
		$GLOBALS['pagenow'] = $this->original_pagenow;

		parent::tear_down();
	}

	/**
	 * Simulates a request to the given admin script with the given query args.
	 *
	 * @param string $pagenow Admin script name, e.g. `admin.php`.
	 * @param string $path    Value of the `path` query arg.
	 */
	private function visit( string $pagenow, string $path ): void {
		$GLOBALS['pagenow'] = $pagenow;
		$_GET['page']       = 'wc-admin';
		$_GET['path']       = $path;
	}

	/**
	 * Returns the inline data localized for the admin script, if any.
	 *
	 * @return string
	 */
	private function get_localized_data(): string {
		return (string) wp_scripts()->get_data( self::HANDLE, 'data' );
	}

	public function test_assets_enqueued_for_manager_on_reddit_page(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->visit( 'admin.php', '/reddit/settings' );

		( new Assets() )->enqueue_assets();

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertStringContainsString( 'adminNonce', $this->get_localized_data() );
	}

	/**
	 * @dataProvider provide_low_privilege_roles
	 *
	 * @param string $role Role without `manage_woocommerce`.
	 */
	public function test_assets_not_enqueued_for_user_without_capability( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$this->visit( 'admin.php', '/reddit/settings' );

		( new Assets() )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertStringNotContainsString( 'adminNonce', $this->get_localized_data() );
	}

	public function provide_low_privilege_roles(): array {
		return array(
			'subscriber'  => array( 'subscriber' ),
			'customer'    => array( 'customer' ),
			'contributor' => array( 'contributor' ),
			'editor'      => array( 'editor' ),
		);
	}

	public function test_assets_not_enqueued_for_user_without_capability_on_forged_screen(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );
		$this->visit( 'profile.php', '/reddit' );

		( new Assets() )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	public function test_assets_not_enqueued_for_manager_outside_wc_admin_entry_point(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->visit( 'profile.php', '/reddit/settings' );

		( new Assets() )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	public function test_assets_not_enqueued_for_manager_on_other_wc_admin_page(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->visit( 'admin.php', '/analytics/overview' );

		( new Assets() )->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}
}
