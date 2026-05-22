<?php
/**
 * Tests for conditional Edit Order and Edit Product meta box assets.
 *
 * @package RedditForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\MetaBox {

use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use RedditForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
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

	private const SCRIPT_HANDLE_ORDER = 'order-attribution';

	private const SCRIPT_HANDLE_CHANNEL = 'channel-visibility-meta-box';

	public function set_up(): void {
		parent::set_up();

		wp_mkdir_p( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH );

		foreach ( array( self::SCRIPT_HANDLE_ORDER, self::SCRIPT_HANDLE_CHANNEL ) as $basename ) {
			file_put_contents( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js', '// test script' );
			file_put_contents(
				REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js.asset.php',
				'<?php return [ "dependencies" => [], "version" => "1.0.0" ];'
			);
		}

		wp_deregister_script( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER );
		wp_deregister_script( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_CHANNEL );
	}

	public function tear_down(): void {
		$this->cleanup_metabox_build_scripts();
		parent::tear_down();
	}

	public function test_order_attribution_script_not_enqueued_when_screen_gate_disabled(): void {
		$asset = new MetaBoxAssetsTestDouble();
		$asset->set_should_enqueue( false );

		$asset->enqueue_assets();

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' )
		);

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_CHANNEL, 'enqueued' )
		);

		global $wp_scripts;

		if ( isset( $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER ] ) ) {
			$extra = $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER ]->extra;
			$this->assertArrayNotHasKey( 'data', is_array( $extra ) ? $extra : array() );
		}
	}

	public function test_order_attribution_script_enqueued_when_screen_gate_enabled(): void {
		$asset = new MetaBoxAssetsTestDouble();
		$asset->set_should_enqueue( true );

		$asset->enqueue_assets();

		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_CHANNEL, 'enqueued' )
		);

		$this->assertTrue(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' )
		);

		global $wp_scripts;

		$registered = $wp_scripts->registered[ Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER ] ?? null;
		$this->assertNotNull( $registered );

		$data_str = isset( $registered->extra['data'] ) ? (string) $registered->extra['data'] : ''; // phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar

		$this->assertStringContainsString( 'var redditAdsMetaBoxData =', $data_str );
	}

	public function test_localized_data_shape_connected_without_campaign_without_reddit_attribution(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'connected' );
		Options::set( OptionDefaults::CAMPAIGN_IDS, array() );

		$asset = new MetaBoxAssetsTestDouble();
		$asset->set_should_enqueue( true );

		$asset->enqueue_assets();

		$decoded = $this->decode_registered_metabox_payload();

		$this->assertSame( Config::PLUGIN_SLUG, $decoded['slug'] ?? null );
		$this->assert_metabox_payload_bool( $decoded['onboardingComplete'] ?? null, true );
		$this->assert_metabox_payload_bool( $decoded['hasCampaign'] ?? null, false );
		$this->assertArrayHasKey( 'orderAttributionSource', $decoded );
		$this->assertNull( $decoded['orderAttributionSource'] );

		$this->assertIsArray( $decoded['urls'] ?? null );
		$this->assert_wc_admin_url_has_reddit_path( $decoded['urls']['start'] ?? '', '/reddit/start' );
		$this->assert_wc_admin_url_has_reddit_path( $decoded['urls']['campaignCreate'] ?? '', '/reddit/campaigns/create' );
		$this->assert_wc_admin_url_has_reddit_path( $decoded['urls']['settings'] ?? '', '/reddit/settings' );
	}

	public function test_localized_data_when_not_connected_with_campaign_flag(): void {
		Options::set( OptionDefaults::ONBOARDING_STATUS, 'disconnected' );
		Options::set( OptionDefaults::CAMPAIGN_IDS, array( 'c1_12345' ) );

		$asset = new MetaBoxAssetsTestDouble();
		$asset->set_should_enqueue( true );

		$asset->enqueue_assets();

		$decoded = $this->decode_registered_metabox_payload();

		$this->assert_metabox_payload_bool( $decoded['onboardingComplete'] ?? null, false );
		$this->assert_metabox_payload_bool( $decoded['hasCampaign'] ?? null, true );
	}

	public function test_wc_admin_urls_match_helper_output(): void {
		$asset = new MetaBoxAssetsTestDouble();
		$asset->set_should_enqueue( true );

		$asset->enqueue_assets();

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
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent on Dashboard.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assert_channel_visibility_bundle_not_enqueued();
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_plugins_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context( array(), 'plugins' );
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent on Plugins list.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assert_channel_visibility_bundle_not_enqueued();
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_channel_bundle_enqueued_on_product_edit_order_bundle_absent(): void {
		$get_backup = $_GET;
		global $post;
		$post_backup = $post instanceof \WP_Post ? $post : null;

		$wc_product = \WC_Helper_Product::create_simple_product();
		$product_id = $wc_product->get_id();
		$post_obj   = get_post( $product_id );
		$this->assertNotNull( $post_obj );

		try {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $post_obj;

			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $product_id,
					'action' => 'edit',
				),
				'product'
			);

			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent when editing a product.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );

			$this->assertTrue(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_CHANNEL, 'enqueued' ),
				'Expected channel-visibility-meta-box on product edit.'
			);

			$decoded = $this->decode_registered_metabox_payload( self::SCRIPT_HANDLE_CHANNEL );
			$this->assertSame( Config::PLUGIN_SLUG, $decoded['slug'] ?? null );
			$this->assertArrayHasKey( 'channelVisibility', $decoded );
			$this->assertArrayNotHasKey( 'hasCampaign', $decoded );
			$this->assertArrayNotHasKey( 'orderAttributionSource', $decoded );
			$this->assertArrayNotHasKey( 'urls', $decoded );

			$cv = $decoded['channelVisibility'];
			$this->assertIsArray( $cv );
			$this->assertSame( Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM ), $cv['field_name'] ?? null );
			$this->assertSame( '1', $cv['product_catalog_item'] ?? null, 'Unset meta should default to catalog 1.' );
			$this->assert_metabox_payload_bool( $cv['product_is_visible'] ?? null, true );
			$this->assertSame( __( 'Sync and show', 'reddit-for-woocommerce' ), $cv['options']['1'] ?? null );
			$this->assertSame( __( "Don't sync and show", 'reddit-for-woocommerce' ), $cv['options']['0'] ?? null );

			$this->assertArrayNotHasKey( 'mode', $decoded );
			$this->assertArrayNotHasKey( 'cohabit_target', $decoded );
			$this->assertArrayNotHasKey( 'mode', $cv );
			$this->assertArrayNotHasKey( 'cohabit_target', $cv );
		} finally {
			if ( $post_backup instanceof \WP_Post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $post_backup;
			} else {
				unset( $GLOBALS['post'] );
			}
			$_GET = $get_backup;
		}
	}

	public function test_real_channel_bundle_not_enqueued_on_product_screen_without_global_post(): void {
		$get_backup = $_GET;
		global $post;
		$post_backup = $post instanceof \WP_Post ? $post : null;

		$wc_product = \WC_Helper_Product::create_simple_product();

		try {
			unset( $GLOBALS['post'] );

			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $wc_product->get_id(),
					'action' => 'edit',
				),
				'product'
			);

			$this->assert_channel_visibility_bundle_not_enqueued();
		} finally {
			if ( $post_backup instanceof \WP_Post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $post_backup;
			} else {
				unset( $GLOBALS['post'] );
			}
			$_GET = $get_backup;
		}
	}

	public function test_real_product_channel_payload_respects_catalog_meta_zero(): void {
		$get_backup = $_GET;
		global $post;
		$post_backup = $post instanceof \WP_Post ? $post : null;

		$wc_product = \WC_Helper_Product::create_simple_product();
		$field      = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
		update_post_meta( $wc_product->get_id(), $field, '0' );
		$post_obj = get_post( $wc_product->get_id() );
		$this->assertNotNull( $post_obj );

		try {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $post_obj;

			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $wc_product->get_id(),
					'action' => 'edit',
				),
				'product'
			);

			$decoded = $this->decode_registered_metabox_payload( self::SCRIPT_HANDLE_CHANNEL );
			$this->assertSame( '0', $decoded['channelVisibility']['product_catalog_item'] ?? null );
		} finally {
			if ( $post_backup instanceof \WP_Post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $post_backup;
			} else {
				unset( $GLOBALS['post'] );
			}
			$_GET = $get_backup;
		}
	}

	public function test_real_product_channel_payload_reflects_non_visible_product(): void {
		$get_backup = $_GET;
		global $post;
		$post_backup = $post instanceof \WP_Post ? $post : null;

		$wc_product = \WC_Helper_Product::create_simple_product();
		$wc_product->set_catalog_visibility( 'hidden' );
		$wc_product->save();
		$post_obj = get_post( $wc_product->get_id() );
		$this->assertNotNull( $post_obj );

		try {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $post_obj;

			$this->run_real_enqueue_in_admin_screen_context(
				array(
					'post'   => (string) $wc_product->get_id(),
					'action' => 'edit',
				),
				'product'
			);

			$decoded = $this->decode_registered_metabox_payload( self::SCRIPT_HANDLE_CHANNEL );
			$this->assert_metabox_payload_bool( $decoded['channelVisibility']['product_is_visible'] ?? null, false );
		} finally {
			if ( $post_backup instanceof \WP_Post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $post_backup;
			} else {
				unset( $GLOBALS['post'] );
			}
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
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent on wc-admin.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assert_channel_visibility_bundle_not_enqueued();
		} finally {
			$_GET = $get_backup;
		}
	}

	public function test_real_metabox_not_enqueued_on_general_settings_screen(): void {
		$get_backup = $_GET;

		try {
			$this->run_real_enqueue_in_admin_screen_context( array(), 'options-general' );
			$this->assertFalse(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent on Settings > General.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assertNotContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER,
				wp_scripts()->queue,
				'wp_scripts()->queue should not list order-attribution on core Settings.'
			);
			$this->assert_channel_visibility_bundle_not_enqueued();
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
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution absent on WooCommerce > Settings.'
			);
			$this->assertFalse( OrderAttributionData::is_wc_order_edit_screen() );
			$this->assertNotContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER,
				wp_scripts()->queue,
				'wp_scripts()->queue should not list order-attribution on WooCommerce settings.'
			);
			$this->assert_channel_visibility_bundle_not_enqueued();
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
			$this->run_legacy_shop_order_edit_enqueue( $order->get_id() );

			$this->assertTrue(
				OrderAttributionData::is_wc_order_edit_screen(),
				'Order edit helper should delegate to WooCommerce OrderUtil.'
			);
			$this->assertTrue(
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution on legacy Edit Order screen.'
			);
			$this->assertContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER,
				wp_scripts()->queue,
				'wp_scripts()->queue should list order-attribution on legacy Edit Order.'
			);
			$this->assert_channel_visibility_bundle_not_enqueued();
		} finally {
			unset( $GLOBALS['reddit_for_woocommerce_tests_filter_input_get_post_id'] );
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
			$this->purge_metabox_script_registration_state();

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
				wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER, 'enqueued' ),
				'Expected order-attribution on HPOS Edit Order screen.'
			);
			$this->assertContains(
				Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_ORDER,
				wp_scripts()->queue,
				'wp_scripts()->queue should list order-attribution on HPOS Edit Order.'
			);
			$this->assert_channel_visibility_bundle_not_enqueued();
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
	 * Legacy CPT order edit: PageController uses filter_input( INPUT_GET, 'post' ), which often does not
	 * see $_GET mutated at runtime in PHPUnit. Mirror WooCommerce PageControllerTest bootstrap.
	 *
	 * @param int $order_id Order post ID.
	 * @return void
	 */
	private function run_legacy_shop_order_edit_enqueue( int $order_id ): void {
		global $reddit_for_woocommerce_tests_filter_input_get_post_id;

		self::ensure_admin_request_context_for_tests();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->purge_metabox_script_registration_state();

		$_GET = array(
			'post'   => (string) $order_id,
			'action' => 'edit',
		);

		$reddit_for_woocommerce_tests_filter_input_get_post_id = $order_id;

		self::maybe_load_wp_screen_helpers();
		set_current_screen();
		$screen = get_current_screen();
		$this->assertNotNull( $screen );
		$screen->post_type = 'shop_order';
		$screen->base      = 'post';
		do_action( 'current_screen', $screen );

		( new MetaBoxAssets() )->enqueue_assets();
	}

	/**
	 * wp_localize_script JSON may decode booleans as 1/0 or "1"/"" depending on WP/WC pipeline — treat loosely.
	 *
	 * @param mixed $value   Raw decoded value.
	 * @param bool  $expected Expected truthiness.
	 * @return void
	 */
	private function assert_metabox_payload_bool( $value, bool $expected ): void {
		$actual = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( null === $actual ) {
			$actual = (bool) $value;
		}
		$this->assertSame( $expected, $actual, 'Localized boolean field should match expected truthiness.' );
	}

	/**
	 * Asserts a full admin URL is wc-admin with path= matching the expected Reddit route (encoded or not).
	 *
	 * @param string $url           Full URL from localized payload.
	 * @param string $expected_path Expected path query value, e.g. `/reddit/start`.
	 * @return void
	 */
	private function assert_wc_admin_url_has_reddit_path( string $url, string $expected_path ): void {
		$this->assertStringContainsString( 'page=wc-admin', $url );

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$this->assertIsString( $query );

		parse_str( $query, $parsed );
		$this->assertArrayHasKey( 'path', $parsed );

		$path = rawurldecode( (string) wp_unslash( $parsed['path'] ) );
		$this->assertSame( $expected_path, $path, 'path= query param should resolve to the expected Reddit route.' );
	}

	/**
	 * @param array<string,string> $get Query parameters (admin context).
	 * @param string               $screen_id WordPress {@see WP_Screen} id passed to {@see set_current_screen()}.
	 */
	private function run_real_enqueue_in_admin_screen_context( array $get, string $screen_id ): void {
		self::ensure_admin_request_context_for_tests();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->purge_metabox_script_registration_state();

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
	private function purge_metabox_script_registration_state(): void {
		foreach ( array( self::SCRIPT_HANDLE_ORDER, self::SCRIPT_HANDLE_CHANNEL ) as $basename ) {
			$prefixed_handle = Config::ASSET_HANDLE_PREFIX . $basename;
			wp_dequeue_script( $prefixed_handle );
			wp_deregister_script( $prefixed_handle );
		}
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

	/**
	 * @return void
	 */
	private function cleanup_metabox_build_scripts(): void {
		foreach ( array( self::SCRIPT_HANDLE_ORDER, self::SCRIPT_HANDLE_CHANNEL ) as $basename ) {
			$prefixed_handle = Config::ASSET_HANDLE_PREFIX . $basename;
			wp_dequeue_script( $prefixed_handle );
			wp_deregister_script( $prefixed_handle );
			@unlink( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH . $basename . '.js.asset.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * @param string $script_basename Unprefixed script basename (e.g. order-attribution).
	 * @return array<mixed,mixed>
	 */
	private function decode_registered_metabox_payload( string $script_basename = self::SCRIPT_HANDLE_ORDER ): array {
		global $wp_scripts;

		$handle     = Config::ASSET_HANDLE_PREFIX . $script_basename;
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

	/**
	 * @return void
	 */
	private function assert_channel_visibility_bundle_not_enqueued(): void {
		$this->assertFalse(
			wp_script_is( Config::ASSET_HANDLE_PREFIX . self::SCRIPT_HANDLE_CHANNEL, 'enqueued' ),
			'Expected channel-visibility-meta-box absent on this screen.'
		);
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

}

namespace Automattic\WooCommerce\Internal\Admin\Orders {

	if ( ! function_exists( __NAMESPACE__ . '\\filter_input' ) ) {

		/**
		 * PHPUnit often mutates $_GET after bootstrap; PHP's filter_input( INPUT_GET, ... ) may not reflect that.
		 * WooCommerce PageController uses filter_input for legacy CPT order edit detection (see PageControllerTest).
		 *
		 * @param int               $type    INPUT_* constant.
		 * @param string|null       $key     Variable name.
		 * @param int               $filter  Filter constant.
		 * @param array<int|string> $options Filter options.
		 * @return mixed
		 */
		function filter_input( $type, $key = null, $filter = FILTER_DEFAULT, $options = 0 ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.filter_inputFound
			if ( isset( $GLOBALS['reddit_for_woocommerce_tests_filter_input_get_post_id'] )
				&& INPUT_GET === $type
				&& 'post' === $key
				&& FILTER_VALIDATE_INT === $filter
			) {
				return (int) $GLOBALS['reddit_for_woocommerce_tests_filter_input_get_post_id'];
			}

			// Forward explicitly — func_get_args() after parameter use breaks PHPCompat / PHP 7+ inspection rules.
			return \filter_input( $type, (string) $key, $filter, $options );
		}
	}
}
