<?php
/**
 * Unit tests for the ChannelVisibilityMetaBox class.
 */

namespace RedditForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use RedditForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
use RedditForWooCommerce\Utils\Helper;

/**
 * @covers \RedditForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox
 */
class ChannelVisibilityMetaBoxTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var ChannelVisibilityMetaBox
	 */
	private ChannelVisibilityMetaBox $sut;

	/**
	 * Meta key used across tests.
	 *
	 * @var string
	 */
	private string $meta_key;

	/**
	 * Sets up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->sut      = new ChannelVisibilityMetaBox();
		$this->meta_key = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
	}

	/**
	 * Restores globals and superglobals after each test.
	 */
	public function tear_down(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = array();
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Registration — standalone mode
	// -------------------------------------------------------------------------

	/**
	 * In standalone mode the reddit-channel-visibility meta box is registered
	 * with the expected id, title, screen, and context.
	 */
	public function test_standalone_mode_registers_meta_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'product', null );

		$this->assertArrayHasKey( 'reddit-channel-visibility', $wp_meta_boxes['product']['side']['default'] );

		$box = $wp_meta_boxes['product']['side']['default']['reddit-channel-visibility'];
		$this->assertSame( 'reddit-channel-visibility', $box['id'] );
		$this->assertSame( 'Channel visibility', $box['title'] );
	}

	/**
	 * maybe_register returns early for non-product post types.
	 */
	public function test_standalone_mode_ignores_non_product_post_type(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'page', null );

		$this->assertEmpty( $wp_meta_boxes );
	}

	// -------------------------------------------------------------------------
	// Registration — cohabit mode
	// -------------------------------------------------------------------------

	/**
	 * In cohabit mode GLA's channel_visibility box is already present; no Reddit
	 * meta box should be registered.
	 */
	public function test_cohabit_mode_does_not_register_meta_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(
			'product' => array(
				'side' => array(
					'default' => array(
						'channel_visibility' => array(
							'id'       => 'channel_visibility',
							'title'    => 'Channel visibility',
							'callback' => '__return_false',
							'args'     => null,
						),
					),
				),
			),
		);

		$this->sut->maybe_register( 'product', null );

		foreach ( $wp_meta_boxes['product'] as $contexts ) {
			foreach ( $contexts as $priorities ) {
				$this->assertArrayNotHasKey( 'reddit-channel-visibility', $priorities );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * render() emits exactly one mount-point div and nothing else.
	 */
	public function test_render_emits_mount_point_div(): void {
		ob_start();
		$this->sut->render();
		$output = ob_get_clean();

		$this->assertSame( '<div id="reddit-channel-visibility-box"></div>', $output );
	}

	// -------------------------------------------------------------------------
	// save_meta — standalone mode
	// -------------------------------------------------------------------------

	/**
	 * save_meta writes '1' when the form field is '1' (standalone mode).
	 */
	public function test_save_meta_writes_one_in_standalone_mode(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$post_id                    = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		$_POST[ $this->meta_key ] = '1';

		$this->sut->save_meta( $post_id );

		$this->assertSame( '1', get_post_meta( $post_id, $this->meta_key, true ) );
	}

	/**
	 * save_meta writes '0' when the form field is absent (standalone mode).
	 */
	public function test_save_meta_writes_zero_when_field_absent_in_standalone_mode(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$post_id = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		unset( $_POST[ $this->meta_key ] );

		$this->sut->save_meta( $post_id );

		$this->assertSame( '0', get_post_meta( $post_id, $this->meta_key, true ) );
	}

	// -------------------------------------------------------------------------
	// save_meta — cohabit mode
	// -------------------------------------------------------------------------

	/**
	 * save_meta writes '1' when the form field is '1' (cohabit mode).
	 */
	public function test_save_meta_writes_one_in_cohabit_mode(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(
			'product' => array(
				'side' => array(
					'default' => array(
						'channel_visibility' => array(),
					),
				),
			),
		);

		$post_id                    = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		$_POST[ $this->meta_key ] = '1';

		$this->sut->save_meta( $post_id );

		$this->assertSame( '1', get_post_meta( $post_id, $this->meta_key, true ) );
	}

	/**
	 * save_meta writes '0' when the form field is absent (cohabit mode).
	 */
	public function test_save_meta_writes_zero_when_field_absent_in_cohabit_mode(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(
			'product' => array(
				'side' => array(
					'default' => array(
						'channel_visibility' => array(),
					),
				),
			),
		);

		$post_id = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		unset( $_POST[ $this->meta_key ] );

		$this->sut->save_meta( $post_id );

		$this->assertSame( '0', get_post_meta( $post_id, $this->meta_key, true ) );
	}
}
