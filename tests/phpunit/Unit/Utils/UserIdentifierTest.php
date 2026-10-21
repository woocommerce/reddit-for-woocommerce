<?php
/**
 * Unit tests for the UserIdentifier class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Utils;

use RedditForWooCommerce\Utils\UserIdentifier;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use WP_UnitTestCase;

/**
 * @covers \RedditForWooCommerce\Utils\UserIdentifier
 */
final class UserIdentifierTest extends WP_UnitTestCase {

	/**
	 * Set up superglobals shared by the request-derived identifiers.
	 */
	public function set_up(): void {
		parent::set_up();
		$_SERVER['REMOTE_ADDR']     = '192.168.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'UnitTest UA';
		$_COOKIE['_rdt_uuid']       = 'test-uuid';
		$_COOKIE['rdtCid']          = 'rdt-cid';
	}

	/**
	 * Restore state.
	 */
	public function tear_down(): void {
		unset(
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['HTTP_USER_AGENT'],
			$_SERVER['HTTP_CF_CONNECTING_IP'],
			$_COOKIE['_rdt_uuid'],
			$_COOKIE['rdtCid']
		);
		Options::set( OptionDefaults::COLLECT_PII, 'no' );
		parent::tear_down();
	}

	/**
	 * The request-derived identifiers are always returned.
	 */
	public function test_get_user_data_returns_device_identifiers(): void {
		$data = UserIdentifier::get_user_data();

		$this->assertSame( '192.168.0.1', $data['user']['ip_address'] );
		$this->assertSame( 'UnitTest UA', $data['user']['user_agent'] );
		$this->assertSame( 'test-uuid', $data['user']['uuid'] );
		$this->assertSame( 'rdt-cid', $data['click_id'] );

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '192.168.1.40';
		$data                             = UserIdentifier::get_user_data();
		$this->assertSame( '192.168.1.40', $data['user']['ip_address'] );
	}

	/**
	 * With the setting disabled, no match keys are added and the array matches the
	 * device-only identifiers.
	 */
	public function test_no_match_keys_when_setting_disabled(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'no' );

		$order = wc_create_order( array( 'status' => 'pending' ) );
		$order->set_billing_email( 'jane@example.com' );
		$order->set_billing_phone( '+14155550100' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertArrayNotHasKey( 'email', $data['user'] );
		$this->assertArrayNotHasKey( 'phone_number', $data['user'] );
		$this->assertArrayNotHasKey( 'external_id', $data['user'] );
	}

	/**
	 * Logged out with no order produces only the device identifiers even when the
	 * setting is enabled.
	 */
	public function test_no_match_keys_when_logged_out_without_order(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );
		wp_set_current_user( 0 );

		$data = UserIdentifier::get_user_data();

		$this->assertArrayNotHasKey( 'email', $data['user'] );
		$this->assertArrayNotHasKey( 'phone_number', $data['user'] );
		$this->assertArrayNotHasKey( 'external_id', $data['user'] );
	}

	/**
	 * A disabled setting is honoured even when the include flag is set; the synthetic
	 * onboarding purchase passes the flag as false to freeze its payload.
	 */
	public function test_no_match_keys_when_include_flag_false(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending' ) );
		$order->set_billing_email( 'jane@example.com' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order, false );

		$this->assertArrayNotHasKey( 'email', $data['user'] );
		$this->assertArrayNotHasKey( 'external_id', $data['user'] );
	}

	/**
	 * Email is lowercased, the +alias and local-part dots are removed, domain dots
	 * are preserved, and the result is SHA-256 hashed.
	 */
	public function test_email_normalisation_and_hash(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_email( 'Jane.Doe+promo@Example.COM' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', 'janedoe@example.com' ), $data['user']['email'] );
	}

	/**
	 * An international number keeps its digits with spaces and extension removed.
	 */
	public function test_phone_international_normalisation(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( '+1 (415) 555-0100 ext. 22' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', '+14155550100' ), $data['user']['phone_number'] );
	}

	/**
	 * A national number is prefixed with the calling code derived from the billing
	 * country after its leading zero is dropped.
	 */
	public function test_phone_national_with_country_calling_code(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( '07911 123456' );
		$order->set_billing_country( 'GB' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', '+447911123456' ), $data['user']['phone_number'] );
	}

	/**
	 * A national number with a trailing extension drops the extension before the calling
	 * code is prefixed, so the extension digits never leak into the E.164 number.
	 */
	public function test_phone_national_with_extension_strips_extension(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( '07911 123456 ext. 22' );
		$order->set_billing_country( 'GB' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', '+447911123456' ), $data['user']['phone_number'] );
	}

	/**
	 * A lowercase billing country still resolves its calling code, since the lookup is
	 * case-sensitive on the uppercase ISO code.
	 */
	public function test_phone_national_with_lowercase_country(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( '07911 123456' );
		$order->set_billing_country( 'gb' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', '+447911123456' ), $data['user']['phone_number'] );
	}

	/**
	 * A national number with no resolvable calling code is omitted rather than hashed
	 * in a form that can never match.
	 */
	public function test_phone_omitted_when_no_calling_code(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( '5551234' );
		$order->set_billing_country( '' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertArrayNotHasKey( 'phone_number', $data['user'] );
	}

	/**
	 * Numbers typed in a form that cannot be converted to E.164 with confidence are
	 * omitted, while the other match keys are still sent.
	 *
	 * @dataProvider ambiguous_phone_provider
	 *
	 * @param string $phone   Phone number as typed by the shopper.
	 * @param string $country Billing country.
	 */
	public function test_phone_omitted_when_ambiguous( string $phone, string $country ): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_email( 'jane@example.com' );
		$order->set_billing_phone( $phone );
		$order->set_billing_country( $country );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertArrayNotHasKey( 'phone_number', $data['user'] );
		$this->assertArrayHasKey( 'email', $data['user'] );
		$this->assertArrayHasKey( 'external_id', $data['user'] );
	}

	/**
	 * Phone numbers that cannot be converted with confidence.
	 *
	 * @return array<string,array{string,string}>
	 */
	public function ambiguous_phone_provider(): array {
		return array(
			'US country code without +'        => array( '1-555-867-5309', 'US' ),
			'CA country code without +'        => array( '1 416 555 0199', 'CA' ),
			'US 011 dialing prefix'            => array( '011 44 20 7946 0018', 'US' ),
			'GB 00 dialing prefix'             => array( '0044 20 7946 0018', 'GB' ),
			'DE 00 dialing prefix'             => array( '0049 30 1234567', 'DE' ),
			'GB international with (0)'        => array( '+44 (0)20 7946 0018', 'GB' ),
			'DE international with (0)'        => array( '+49 (0)30 1234567', 'DE' ),
			'IN country code without +'        => array( '91 98765 43210', 'IN' ),
			'IT landline with kept leading 0'  => array( '06 1234 5678', 'IT' ),
			'RU trunk prefix 8'                => array( '8 912 345 6789', 'RU' ),
			'GB 00 dialing prefix with extension' => array( '0044 20 7946 0018 ext. 5', 'GB' ),
		);
	}

	/**
	 * Numbers that convert with confidence keep their E.164 form.
	 *
	 * @dataProvider convertible_phone_provider
	 *
	 * @param string $phone    Phone number as typed by the shopper.
	 * @param string $country  Billing country.
	 * @param string $expected Expected E.164 number before hashing.
	 */
	public function test_phone_converted_when_unambiguous( string $phone, string $country, string $expected ): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 7 ) );
		$order->set_billing_phone( $phone );
		$order->set_billing_country( $country );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', $expected ), $data['user']['phone_number'] );
	}

	/**
	 * Phone numbers that convert with confidence.
	 *
	 * @return array<string,array{string,string,string}>
	 */
	public function convertible_phone_provider(): array {
		return array(
			'international without (0)'  => array( '+44 20 7946 0018', 'GB', '+442079460018' ),
			'GB national landline'       => array( '020 7946 0018', 'GB', '+442079460018' ),
			'GB national 011 area code'  => array( '0113 496 0000', 'GB', '+441134960000' ),
			'US national'                => array( '(555) 867-5309', 'US', '+15558675309' ),
			'US national with extension' => array( '555-867-5309 x12', 'US', '+15558675309' ),
			'IT mobile'                  => array( '333 123 4567', 'IT', '+393331234567' ),
			'RU without trunk prefix'    => array( '912 345 6789', 'RU', '+79123456789' ),
			'CH area code matching country code' => array( '041 123 45 67', 'CH', '+41411234567' ),
		);
	}

	/**
	 * A purchase by a registered customer uses the hashed customer ID as external ID.
	 */
	public function test_external_id_from_order_customer_id(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 42 ) );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', '42' ), $data['user']['external_id'] );
	}

	/**
	 * A guest purchase uses the hashed normalised billing email as external ID.
	 */
	public function test_external_id_from_guest_billing_email(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 0 ) );
		$order->set_billing_email( 'Guest.Buyer@Example.com' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( hash( 'sha256', 'guestbuyer@example.com' ), $data['user']['external_id'] );
	}

	/**
	 * A logged-in non-purchase event sources match keys from the current user's
	 * billing meta and uses the hashed current user ID as external ID.
	 */
	public function test_logged_in_non_purchase_match_keys(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'billing_email', 'member@example.com' );
		update_user_meta( $user_id, 'billing_phone', '+14155550111' );
		update_user_meta( $user_id, 'billing_country', 'US' );
		wp_set_current_user( $user_id );

		$data = UserIdentifier::get_user_data();

		$this->assertSame( hash( 'sha256', 'member@example.com' ), $data['user']['email'] );
		$this->assertSame( hash( 'sha256', '+14155550111' ), $data['user']['phone_number'] );
		$this->assertSame( hash( 'sha256', (string) $user_id ), $data['user']['external_id'] );
	}

	/**
	 * A logged-in non-purchase event falls back to the account email when no billing
	 * email meta is set.
	 */
	public function test_logged_in_email_falls_back_to_account_email(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$user_id = self::factory()->user->create( array( 'user_email' => 'account@example.com' ) );
		wp_set_current_user( $user_id );

		$data = UserIdentifier::get_user_data();

		$this->assertSame( hash( 'sha256', 'account@example.com' ), $data['user']['email'] );
	}
}
