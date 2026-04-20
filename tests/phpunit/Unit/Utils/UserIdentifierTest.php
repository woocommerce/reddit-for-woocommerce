<?php
/**
 * Unit tests for the UserIdentifier class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Utils;

use RedditForWooCommerce\Utils\UserIdentifier;
use WP_UnitTestCase;

/**
 * @covers \RedditForWooCommerce\Utils\UserIdentifier
 */
final class UserIdentifierTest extends WP_UnitTestCase {

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
		set_query_var( 'order-received', '' );
		remove_all_filters( 'woocommerce_geolocate_ip' );
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
	 * Test that a basic email is normalised and SHA-256 hashed.
	 */
	public function test_add_user_data_adds_hashed_email(): void {
		$order = $this->create_order_with_billing( 'customer@example.com', '' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$expected = hash( 'sha256', 'customer@example.com' );
		$this->assertSame( $expected, $data['user']['email'] );
	}

	/**
	 * Test that the email alias (+ segment) is stripped before hashing.
	 */
	public function test_add_user_data_strips_email_alias(): void {
		$order = $this->create_order_with_billing( 'customer+promo@example.com', '' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$expected = hash( 'sha256', 'customer@example.com' );
		$this->assertSame( $expected, $data['user']['email'] );
	}

	/**
	 * Test that non-alphanumeric characters are removed from the email username.
	 *
	 * Uses the example from Reddit's Advanced Matching documentation:
	 * "Al.ice$+Apple@Example.Com" normalises to "alice@example.com".
	 *
	 * @see https://business.reddithelp.com/s/article/advanced-matching-for-developers
	 */
	public function test_add_user_data_removes_non_alphanumeric_from_email_username(): void {
		$order = $this->create_order_with_billing( 'Al.ice$+Apple@Example.Com', '' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$expected = hash( 'sha256', 'alice@example.com' );
		$this->assertSame( $expected, $data['user']['email'] );
	}

	/**
	 * Test that a formatted phone number is normalised and hashed correctly.
	 *
	 * Uses the example from Reddit's Advanced Matching documentation:
	 * "+1 (555) 444-1234" and "+15554441234" should both hash to
	 * e5b124c58580eb16bd959b8d0cac12b12c952e2ceae0203d416cff94f10b994a.
	 *
	 * @see https://business.reddithelp.com/s/article/advanced-matching-for-developers
	 */
	public function test_add_user_data_adds_hashed_phone(): void {
		$expected = 'e5b124c58580eb16bd959b8d0cac12b12c952e2ceae0203d416cff94f10b994a';

		$order = $this->create_order_with_billing( '', '+1 (555) 444-1234' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertSame( $expected, $data['user']['phone_number'] );
	}

	/**
	 * Test that a phone number without a + prefix produces the same hash.
	 *
	 * @see https://business.reddithelp.com/s/article/advanced-matching-for-developers
	 */
	public function test_add_user_data_adds_plus_prefix_to_phone_without_one(): void {
		$expected = 'e5b124c58580eb16bd959b8d0cac12b12c952e2ceae0203d416cff94f10b994a';

		$order = $this->create_order_with_billing( '', '15554441234' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertSame( $expected, $data['user']['phone_number'] );
	}

	/**
	 * Test that an already-normalised phone number produces the same hash.
	 *
	 * @see https://business.reddithelp.com/s/article/advanced-matching-for-developers
	 */
	public function test_add_user_data_hashes_clean_phone_identically(): void {
		$expected = 'e5b124c58580eb16bd959b8d0cac12b12c952e2ceae0203d416cff94f10b994a';

		$order = $this->create_order_with_billing( '', '+15554441234' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertSame( $expected, $data['user']['phone_number'] );
	}

	/**
	 * Test that a phone extension is stripped before normalization.
	 *
	 * Uses the example from Reddit's Advanced Matching documentation:
	 * "+1 (555) 444-1234 ext. 789" normalises to "+15554441234".
	 *
	 * @see https://business.reddithelp.com/s/article/advanced-matching-for-developers
	 */
	public function test_add_user_data_strips_phone_extension(): void {
		$expected = 'e5b124c58580eb16bd959b8d0cac12b12c952e2ceae0203d416cff94f10b994a';

		$order = $this->create_order_with_billing( '', '+1 (555) 444-1234 ext. 789' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertSame( $expected, $data['user']['phone_number'] );
	}

	/**
	 * Test that an empty email is not added to the data array.
	 */
	public function test_add_user_data_skips_empty_email(): void {
		$order = $this->create_order_with_billing( '', '+15551234567' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertArrayNotHasKey( 'email', $data['user'] ?? array() );
		$this->assertArrayHasKey( 'phone_number', $data['user'] );
	}

	/**
	 * Test that an empty phone is not added to the data array.
	 */
	public function test_add_user_data_skips_empty_phone(): void {
		$order = $this->create_order_with_billing( 'test@example.com', '' );
		set_query_var( 'order-received', $order->get_id() );

		$data = array();
		UserIdentifier::add_user_data( $data );

		$this->assertArrayHasKey( 'email', $data['user'] );
		$this->assertArrayNotHasKey( 'phone_number', $data['user'] ?? array() );
	}

	/**
	 * Test that data is unchanged when no valid order is found.
	 */
	public function test_add_user_data_skips_invalid_order(): void {
		set_query_var( 'order-received', 0 );

		$data = array( 'user' => array( 'ip_address' => '1.2.3.4' ) );
		UserIdentifier::add_user_data( $data );

		$this->assertSame( array( 'user' => array( 'ip_address' => '1.2.3.4' ) ), $data );
	}

	/**
	 * Test that get_user_data includes email and phone on the order-received page.
	 */
	public function test_get_user_data_includes_email_and_phone_on_order_received_page(): void {
		add_filter( 'woocommerce_geolocate_ip', fn() => 'US' );

		$order = $this->create_order_with_billing( 'buyer@shop.org', '+15559876543' );
		set_query_var( 'order-received', $order->get_id() );

		$_SERVER['REMOTE_ADDR']     = '10.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'TestAgent';

		$data = UserIdentifier::get_user_data();

		$this->assertSame( hash( 'sha256', 'buyer@shop.org' ), $data['user']['email'] );
		$this->assertSame( hash( 'sha256', '+15559876543' ), $data['user']['phone_number'] );
		$this->assertSame( '10.0.0.1', $data['user']['ip_address'] );
	}

	/**
	 * Creates a WooCommerce order with the given billing email and phone.
	 *
	 * @param string $email Billing email address.
	 * @param string $phone Billing phone number.
	 * @return \WC_Order
	 */
	private function create_order_with_billing( string $email, string $phone ): \WC_Order {
		$order = wc_create_order( array( 'status' => 'pending' ) );

		if ( $email ) {
			$order->set_billing_email( $email );
		}

		if ( $phone ) {
			$order->set_billing_phone( $phone );
		}

		$order->save();

		return $order;
	}
}
