<?php
/**
 * Unit tests for the UserIdentifier class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use RedditForWooCommerce\Utils\UserIdentifier;

/**
 * @covers \RedditForWooCommerce\Utils\UserIdentifier
 */
final class UserIdentifierTest extends TestCase {

	/**
	 * Backup $_SERVER before each test.
	 *
	 * @var array<string,mixed>
	 */
	private $original_server;

	/**
	 * Backup superglobals.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->original_server = $_SERVER;
	}

	/**
	 * Restore superglobals.
	 */
	protected function tearDown(): void {
		$_SERVER = $this->original_server;

		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );
		parent::tearDown();
	}

	/**
	 * Test that IP and user agent are returned if present.
	 */
	public function test_get_user_data_returns_expected_keys(): void {
		$_SERVER['REMOTE_ADDR']     = '192.168.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'UnitTest UA';

		$data = UserIdentifier::get_user_data();

		$this->assertArrayHasKey( 'user', $data );
		$this->assertArrayHasKey( 'ip_address', $data['user'] );
		$this->assertArrayHasKey( 'user_agent', $data['user'] );
		$this->assertSame( '192.168.0.1', $data['user']['ip_address'] );
		$this->assertSame( 'UnitTest UA', $data['user']['user_agent'] );

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '192.168.1.40';
		$data                             = UserIdentifier::get_user_data();
		$this->assertSame( '192.168.1.40', $data['user']['ip_address'] );
	}

	/**
	 * Test that output is empty if no headers are present.
	 */
	public function test_get_user_data_returns_empty_array_when_headers_missing(): void {
		$data = UserIdentifier::get_user_data();

		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}
}
