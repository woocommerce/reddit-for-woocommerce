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
	 * Restores superglobals after each test.
	 */
	public function tear_down(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = array();
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// save_meta
	// -------------------------------------------------------------------------

	/**
	 * save_meta writes '1' when the form field is '1'.
	 */
	public function test_save_meta_writes_one_when_field_is_one(): void {
		$post_id                 = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		$_POST[ $this->meta_key ] = '1';

		$this->sut->save_meta( $post_id );

		$this->assertSame( '1', get_post_meta( $post_id, $this->meta_key, true ) );
	}

	/**
	 * save_meta writes '0' when the form field is absent.
	 */
	public function test_save_meta_writes_zero_when_field_absent(): void {
		$post_id = $this->factory()->post->create( array( 'post_type' => 'product' ) );
		unset( $_POST[ $this->meta_key ] );

		$this->sut->save_meta( $post_id );

		$this->assertSame( '0', get_post_meta( $post_id, $this->meta_key, true ) );
	}
}
