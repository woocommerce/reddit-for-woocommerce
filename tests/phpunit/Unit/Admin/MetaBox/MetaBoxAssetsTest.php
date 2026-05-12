<?php
/**
 * Tests for conditional Edit Order meta box assets.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\MetaBox;

use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use RedditForWooCommerce\Admin\MetaBox\MetaBoxAssets;
use RedditForWooCommerce\Admin\MetaBox\OrderAttributionData;
use RedditForWooCommerce\Config;
use WP_UnitTestCase;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;

/**
 * @covers \RedditForWooCommerce\Admin\MetaBox\MetaBoxAssets
 */
final class MetaBoxAssetsTest extends WP_UnitTestCase {

	private const SCRIPT_HANDLE = 'order-attribution';

	public function set_up(): void {
		parent::set_up();

		wp_mkdir_p( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		$basename = self::SCRIPT_HANDLE;

		file_put_contents( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js', '// test script' );
		file_put_contents(
			REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js.asset.php',
			'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
		);

		wp_deregister_script( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE );
	}

	public function tear_down(): void {
		$this->cleanup_order_attribution_script();
		parent::tear_down();
	}

	public function test_order_attribution_script_not_enqueued_when_screen_gate_disabled(): void {
		$sut = new MetaBoxAssetsTestDouble();
		$sut->set_should_enqueue( false );

		$sut->enqueue_assets();

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' )
		);

		global $wp_scripts;

		if ( isset( $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE ] ) ) {
			$extra = $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE ]->extra;
			$this->assertArrayNotHasKey( 'data', is_array( $extra ) ? $extra : array() );
		}
	}

	public function test_order_attribution_script_enqueued_when_screen_gate_enabled(): void {
		$sut = new MetaBoxAssetsTestDouble();
		$sut->set_should_enqueue( true );

		$sut->enqueue_assets();

		$this->assertTrue(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' )
		);

		global $wp_scripts;

		$registered = $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE ] ?? null;
		$this->assertNotNull( $registered );

		$data_str = isset( $registered->extra['data'] ) ? (string) $registered->extra['data'] : ''; // phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar

		$this->assertStringContainsString( 'var redditAdsMetaBoxData =', $data_str );
	}

	public function test_localized_data_shape_connected_without_campaign_without_reddit_attribution(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		Options::set( OptionDefaults::CAMPAIGN_IDS, array() );

		$sut = new MetaBoxAssetsTestDouble();
		$sut->set_should_enqueue( true );

		$sut->enqueue_assets();

		$decoded = $this->decode_registered_metabox_payload();

		$this->assertSame( Config::PLUGIN_SLUG, $decoded['slug'] ?? null );
		$this->assertTrue( $decoded['onboardingComplete'] ?? false );
		$this->assertFalse( $decoded['hasCampaign'] ?? null );
		$this->assertArrayHasKey( 'orderAttributionSource', $decoded );
		$this->assertNull( $decoded['orderAttributionSource'] );

		$this->assertIsArray( $decoded['urls'] ?? null );
		$this->assertStringContainsString( 'admin.php?page=wc-admin&path=/reddit/start', $decoded['urls']['start'] ?? '' );
		$this->assertStringContainsString( '/reddit/campaigns/create', $decoded['urls']['campaignCreate'] ?? '' );
		$this->assertStringContainsString( '/reddit/settings', $decoded['urls']['settings'] ?? '' );
	}

	public function test_localized_data_when_not_connected_with_campaign_flag(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'disconnected' );
		Options::set( OptionDefaults::CAMPAIGN_IDS, array( 'c1_12345' ) );

		$sut = new MetaBoxAssetsTestDouble();
		$sut->set_should_enqueue( true );

		$sut->enqueue_assets();

		$decoded = $this->decode_registered_metabox_payload();

		$this->assertFalse( $decoded['onboardingComplete'] ?? null );
		$this->assertTrue( $decoded['hasCampaign'] ?? null );
	}

	public function test_wc_admin_urls_match_helper_output(): void {
		$sut = new MetaBoxAssetsTestDouble();
		$sut->set_should_enqueue( true );

		$sut->enqueue_assets();

		$decoded = $this->decode_registered_metabox_payload();
		$urls    = Helper::get_wc_admin_reddit_metabox_urls();

		$this->assertSame( $urls['start'], $decoded['urls']['start'] ?? null );
		$this->assertSame( $urls['campaignCreate'], $decoded['urls']['campaignCreate'] ?? null );
		$this->assertSame( $urls['settings'], $decoded['urls']['settings'] ?? null );
	}

	public function test_real_metabox_not_enqueued_on_dashboard_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context( array(), 'dashboard' );
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent on Dashboard.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_plugins_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context( array(), 'plugins' );
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent on Plugins list.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_product_edit_screen(): void {
		$get_backup       = $_GET;
		$product_id       = self::factory()->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);

		try {
			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $product_id,
					'action' => 'edit',
				),
				'product'
			);
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent when editing a product.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_wc_admin_shell_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'page' => 'wc-admin',
				),
				'woocommerce_page_wc-admin'
			);
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent on wc-admin.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_general_settings_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context( array(), 'options-general' );
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent on Settings > General.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assertNotContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE,
				wp_scripts()->queue,
				'wp_scripts()->queue should not list order-attribution on core Settings.'
			);
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_woocommerce_settings_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'page' => 'wc-settings',
				),
				'woocommerce_page_wc-settings'
			);
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution absent on WooCommerce > Settings.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assertNotContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE,
				wp_scripts()->queue,
				'wp_scripts()->queue should not list order-attribution on WooCommerce settings.'
			);
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_enqueued_on_legacy_shop_order_edit_screen(): void {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped(
				'Legacy orders screen test only applies when WooCommerce CPT orders are authoritative (HPOS off).'
			);
			return;
		}

		$get_backup = $_GET;
		$order      = \WC_Helper_Order::create_order();

		try {
			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $order->get_id(),
					'action' => 'edit',
				),
				'shop_order'
			);

			$this->assertTrue(
				OrderAttributionData::is_wc_order_edit_screen(),
				'Order edit helper should delegate to WooCommerce OrderUtil.'
			);
			$this->assertTrue(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution on legacy Edit Order screen.'
			);
			$this->assertContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE,
				wp_scripts()->queue,
				'wp_scripts()->queue should list order-attribution on legacy Edit Order.'
			);
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_enqueued_on_hpos_order_edit_screen(): void {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'Requires HPOS (custom order tables) as authoritative order store.' );
			return;
		}

		$get_backup = $_GET;
		global $pagenow, $plugin_page;
		$pagenow_backup     = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : null;
		$plugin_page_backup = isset( $GLOBALS['plugin_page'] ) ? $GLOBALS['plugin_page'] : null;

		$order = \WC_Helper_Order::create_order();

		try {
			self::ensure_admin_request_context_for_tests();

			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			$this->purge_order_attribution_script_state();

			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Mirrors WooCommerce PageControllerTest HPOS bootstrap.
			$pagenow     = 'admin.php';
			$plugin_page = 'wc-orders';
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

			$_GET = array(
				'page'   => 'wc-orders',
				'action' => 'edit',
				'id'     => (string) $order->get_id(),
			);

			$controller = wc_get_container()->get( PageController::class );
			$controller->setup();

			self::maybe_load_wp_screen_helpers();
			set_current_screen( 'woocommerce_page_wc-orders' );
			do_action( 'current_screen', get_current_screen() );

			( new MetaBoxAssets() )->enqueue_assets();

			$this->assertTrue(
				OrderAttributionData::is_wc_order_edit_screen(),
				'Order edit screen helper should be true after PageController::setup() on HPOS edit URL.'
			);
			$this->assertTrue(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE, 'enqueued' ),
				'Expected order-attribution on HPOS Edit Order screen.'
			);
			$this->assertContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE,
				wp_scripts()->queue,
				'wp_scripts()->queue should list order-attribution on HPOS Edit Order.'
			);
		} finally {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
				$pagenow     = 'admin.php';
				$plugin_page = 'wc-orders';
				// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
				$_GET = array(
					'page' => 'wc-orders',
				);
				wc_get_container()->get( PageController::class )->setup();
			}

			$_GET = $get_backup;
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( null !== $pagenow_backup ) {
				$pagenow = $pagenow_backup;
			} else {
				unset( $GLOBALS['pagenow'] );
			}
			if ( null !== $plugin_page_backup ) {
				$plugin_page = $plugin_page_backup;
			} else {
				unset( $GLOBALS['plugin_page'] );
			}
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @param array<string,string> $get Query parameters (admin context).
	 * @param string               $screen_id WordPress {@see WP_Screen} id passed to {@see set_current_screen()}.
	 */
	private function run_real_enqueue_in_admin_screen_context( array $get, string $screen_id ): void {
		self::ensure_admin_request_context_for_tests();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->purge_order_attribution_script_state();

		$_GET = $get;

		self::maybe_load_wp_screen_helpers();
		set_current_screen( $screen_id );
		do_action( 'current_screen', get_current_screen() );

		( new MetaBoxAssets() )->enqueue_assets();
	}

	/**
	 * @return void
	 */
	private static function ensure_admin_request_context_for_tests(): void {
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		self::maybe_load_wp_screen_helpers();
	}

	/**
	 * @return void
	 */
	private function purge_order_attribution_script_state(): void {
		$prefixed_handle = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE;
		wp_dequeue_script( $prefixed_handle );
		wp_deregister_script( $prefixed_handle );
	}

	/**
	 * @return void
	 */
	private static function maybe_load_wp_screen_helpers(): void {
		if ( function_exists( 'set_current_screen' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	private function cleanup_order_attribution_script(): void {
		$prefixed_handle = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE;
		wp_dequeue_script( $prefixed_handle );
		wp_deregister_script( $prefixed_handle );
		@unlink( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_HANDLE . '.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . self::SCRIPT_HANDLE . '.js.asset.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * @return array<mixed,mixed>
	 */
	private function decode_registered_metabox_payload(): array {
		global $wp_scripts;

		$handle     = Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE;
		$registered = $wp_scripts->registered[ $handle ] ?? null;

		$this->assertNotNull( $registered );

		$data_str = isset( $registered->extra['data'] ) ? (string) $registered->extra['data'] : ''; // phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar

		$this->assertStringContainsString( 'var redditAdsMetaBoxData =', $data_str );

		$equals_at = strpos( $data_str, '=' );
		$this->assertNotFalse( $equals_at );
		$brace_left = strpos( $data_str, '{', $equals_at );
		$this->assertNotFalse( $brace_left );
		$brace_right = strrpos( $data_str, '}' );
		$this->assertNotFalse( $brace_right );
		$json_fragment = substr( $data_str, $brace_left, $brace_right - $brace_left + 1 );

		$decoded = json_decode( $json_fragment, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			$this->fail( 'Could not json_decode redditAdsMetaBoxData blob: ' . (string) json_last_error_msg() );
		}

		return $decoded;
	}

}

/**
 * Test harness with controllable enqueue gate so OrderUtil/current_screen bootstrap is isolated.
 *
 * @internal
 */
final class MetaBoxAssetsTestDouble extends MetaBoxAssets {

	private $should_enqueue = false;

	public function set_should_enqueue( bool $flag ): void {
		$this->should_enqueue = $flag;
	}

	protected function should_enqueue_order_attribution_bundle(): bool {
		return $this->should_enqueue;
	}
}
