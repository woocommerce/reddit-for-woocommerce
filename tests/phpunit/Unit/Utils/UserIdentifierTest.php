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
	 * Option holding the per-site secret that keys external IDs.
	 */
	private const EXTERNAL_ID_SECRET_OPTION = 'reddit_external_id_secret';

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
		delete_option( self::EXTERNAL_ID_SECRET_OPTION );
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
			'US country code without +'           => array( '1-555-867-5309', 'US' ),
			'CA country code without +'           => array( '1 416 555 0199', 'CA' ),
			'US 011 dialing prefix'               => array( '011 44 20 7946 0018', 'US' ),
			'GB 00 dialing prefix'                => array( '0044 20 7946 0018', 'GB' ),
			'DE 00 dialing prefix'                => array( '0049 30 1234567', 'DE' ),
			'GB international with (0)'           => array( '+44 (0)20 7946 0018', 'GB' ),
			'DE international with (0)'           => array( '+49 (0)30 1234567', 'DE' ),
			'IN country code without +'           => array( '91 98765 43210', 'IN' ),
			'VA national without leading 0'       => array( '698 12345', 'VA' ),
			'VA international with +379'          => array( '+379 06 698 12345', 'VA' ),
			'VA international with +379 and no 0' => array( '+379 698 12345', 'VA' ),
			'RU 8 10 dialing prefix'              => array( '8 10 1234 5678', 'RU' ),
			'KZ 8 10 dialing prefix'              => array( '8 10 44 20 7946 0018', 'KZ' ),
			'RU international with trunk 8'       => array( '+7 8 (812) 123-45-67', 'RU' ),
			'BY international with trunk 8 0'     => array( '+375 8 029 123 45 67', 'BY' ),
			'RU trunk prefix 8 too short'         => array( '8 912 345 67', 'RU' ),
			'RU trunk prefix 8 too long'          => array( '8 912 345 67890', 'RU' ),
			'IT country code then landline'       => array( '39 0471 1234', 'IT' ),
			'IT country code then mobile'         => array( '39 333 123 4567', 'IT' ),
			'SG country code without +'           => array( '65 6123 4567', 'SG' ),
			'PL country code without +'           => array( '48 512 345 678', 'PL' ),
			'GB 00 dialing prefix with extension' => array( '0044 20 7946 0018 ext. 5', 'GB' ),
			'GB international with trunk 0'       => array( '+44 020 7946 0018', 'GB' ),
			'BY trunk prefix 8 too long'          => array( '8 029 123 45 678', 'BY' ),
			'KZ country code without +'           => array( '7 701 123 4567', 'KZ' ),
			'JM country code without +'           => array( '1 876 555 1234', 'JM' ),
			'JM area code of another country'     => array( '809-555-1234', 'JM' ),
			'PR area code of another country'     => array( '876 555 1234', 'PR' ),
			'PR local number without area code'   => array( '555-1234', 'PR' ),
			'JE country code without +'           => array( '44 1534 123456', 'JE' ),
			'JM local number without area code'   => array( '555-1234', 'JM' ),
			'BR country code then mobile'         => array( '55 11 99123 4567', 'BR' ),
			'BR country code then landline'       => array( '55 11 3123 4567', 'BR' ),
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
			'international without (0)'          => array( '+44 20 7946 0018', 'GB', '+442079460018' ),
			'GB national landline'               => array( '020 7946 0018', 'GB', '+442079460018' ),
			'GB national 011 area code'          => array( '0113 496 0000', 'GB', '+441134960000' ),
			'US national'                        => array( '(555) 867-5309', 'US', '+15558675309' ),
			'US national with extension'         => array( '555-867-5309 x12', 'US', '+15558675309' ),
			'IT mobile'                          => array( '333 123 4567', 'IT', '+393331234567' ),
			'RU without trunk prefix'            => array( '912 345 6789', 'RU', '+79123456789' ),
			'CH area code matching country code' => array( '041 123 45 67', 'CH', '+41411234567' ),
			'US national with # extension'       => array( '555-867-5309 #12', 'US', '+15558675309' ),
			'international with # extension'     => array( '+1 415 555 0100 #22', 'US', '+14155550100' ),
			'IT international with kept 0'       => array( '+39 06 1234 5678', 'IT', '+390612345678' ),
			'HU without trunk prefix'            => array( '1 234 5678', 'HU', '+3612345678' ),
			'KZ local number'                    => array( '701 123 4567', 'KZ', '+77011234567' ),
			'JM full number'                     => array( '876-555-1234', 'JM', '+18765551234' ),
			'JM overlay area code'               => array( '658-555-1234', 'JM', '+16585551234' ),
			'IN mobile starting with 91'         => array( '91234 56789', 'IN', '+919123456789' ),
			'BR mobile starting with 55'         => array( '55 99123 4567', 'BR', '+5555991234567' ),
			'BR landline starting with 55'       => array( '55 3123 4567', 'BR', '+555531234567' ),
			'PR first area code'                 => array( '787 555 1234', 'PR', '+17875551234' ),
			'PR second area code'                => array( '(939) 555-1234', 'PR', '+19395551234' ),
			'CI international with kept 0'       => array( '+225 07 07 12 34 56', 'CI', '+2250707123456' ),
			'BJ international with kept 0'       => array( '+229 01 97 12 34 56', 'BJ', '+2290197123456' ),
			'CG international with kept 0'       => array( '+242 06 123 4567', 'CG', '+242061234567' ),
			'GA international with kept 0'       => array( '+241 06 03 12 34', 'GA', '+24106031234' ),
			'CI national with kept 0'            => array( '07 07 12 34 56', 'CI', '+2250707123456' ),
			'BJ national with kept 0'            => array( '01 97 12 34 56', 'BJ', '+2290197123456' ),
			'IT national landline'               => array( '06 1234 5678', 'IT', '+390612345678' ),
			'RU trunk prefix 8'                  => array( '8 912 345 6789', 'RU', '+79123456789' ),
			'RU 8xx area code'                   => array( '812 123 4567', 'RU', '+78121234567' ),
			'RU number in the KZ range'          => array( '701 234 5678', 'RU', '+77012345678' ),
			'RU international 8xx area code'     => array( '+7 812 123 45 67', 'RU', '+78121234567' ),
			'RU trunk 8 and 8xx area code'       => array( '8 (812) 123-45-67', 'RU', '+78121234567' ),
			'KZ trunk prefix 8'                  => array( '8 701 123 4567', 'KZ', '+77011234567' ),
			'BY trunk prefix 8 0'                => array( '8 029 123 45 67', 'BY', '+375291234567' ),
			'BY national with trunk 0'           => array( '029 123 45 67', 'BY', '+375291234567' ),
			'HU trunk prefix 06'                 => array( '06 1 234 5678', 'HU', '+3612345678' ),
			'HU mobile with trunk prefix 06'     => array( '06 30 123 4567', 'HU', '+36301234567' ),
			'VA national with leading 0'         => array( '06 698 12345', 'VA', '+390669812345' ),
			'SG landline starting with 65'       => array( '6512 3456', 'SG', '+6565123456' ),
			'NO mobile starting with 47'         => array( '471 23 456', 'NO', '+4747123456' ),
			'DK number starting with 45'         => array( '45 12 34 56', 'DK', '+4545123456' ),
			'PL number starting with 48'         => array( '48 123 45 67', 'PL', '+48481234567' ),
			'IT mobile starting with 39'         => array( '391 123 4567', 'IT', '+393911234567' ),
			'JE national landline'               => array( '01534 123456', 'JE', '+441534123456' ),
			'JE national mobile'                 => array( '07797 123456', 'JE', '+447797123456' ),
			'JE landline without trunk 0'        => array( '1534 123456', 'JE', '+441534123456' ),
			'GG national landline'               => array( '01481 123456', 'GG', '+441481123456' ),
			'IM national landline'               => array( '01624 123456', 'IM', '+441624123456' ),
			'AX national landline'               => array( '018 12345', 'AX', '+3581812345' ),
			'AX national mobile'                 => array( '040 123 4567', 'AX', '+358401234567' ),
			'JE international'                   => array( '+44 1534 123456', 'JE', '+441534123456' ),
		);
	}

	/**
	 * A purchase by a registered customer keys the customer ID as external ID, so
	 * the plain SHA-256 of the ID is never sent.
	 */
	public function test_external_id_from_order_customer_id(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 42 ) );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( $this->expected_external_id( 'user', '42' ), $data['user']['external_id'] );
		$this->assertNotSame( hash( 'sha256', '42' ), $data['user']['external_id'] );
	}

	/**
	 * A guest purchase keys the normalised billing email as external ID, so it is
	 * not a copy of the email match key.
	 */
	public function test_external_id_from_guest_billing_email(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 0 ) );
		$order->set_billing_email( 'Guest.Buyer@Example.com' );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( $this->expected_external_id( 'email', 'guestbuyer@example.com' ), $data['user']['external_id'] );
		$this->assertNotSame( $data['user']['email'], $data['user']['external_id'] );
	}

	/**
	 * A customer's browsing events and purchases carry the same external ID, so
	 * Reddit can link them.
	 */
	public function test_external_id_matches_between_browsing_and_purchase(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$browsing = UserIdentifier::get_user_data();

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => $user_id ) );
		$order->save();
		$purchase = UserIdentifier::get_user_data( $order );

		$this->assertSame( $browsing['user']['external_id'], $purchase['user']['external_id'] );
	}

	/**
	 * The secret is created on first use and reused, so the external ID stays stable.
	 */
	public function test_external_id_secret_is_created_once_and_reused(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 42 ) );
		$order->save();

		$first  = UserIdentifier::get_user_data( $order );
		$secret = get_option( self::EXTERNAL_ID_SECRET_OPTION );
		$second = UserIdentifier::get_user_data( $order );

		$this->assertIsString( $secret );
		$this->assertSame( 64, strlen( $secret ) );
		$this->assertSame( $secret, get_option( self::EXTERNAL_ID_SECRET_OPTION ) );
		$this->assertSame( $first['user']['external_id'], $second['user']['external_id'] );
	}

	/**
	 * The secret is not autoloaded on every request.
	 */
	public function test_external_id_secret_is_not_autoloaded(): void {
		UserIdentifier::ensure_external_id_secret();
		wp_cache_delete( 'alloptions', 'options' );

		$this->assertNotEmpty( get_option( self::EXTERNAL_ID_SECRET_OPTION ) );
		$this->assertArrayNotHasKey( self::EXTERNAL_ID_SECRET_OPTION, wp_load_alloptions() );
	}

	/**
	 * A stored value too short to be a safe key is replaced rather than used.
	 */
	public function test_short_external_id_secret_is_replaced(): void {
		update_option( self::EXTERNAL_ID_SECRET_OPTION, '', false );
		UserIdentifier::ensure_external_id_secret();
		$this->assertSame( 64, strlen( get_option( self::EXTERNAL_ID_SECRET_OPTION ) ) );

		update_option( self::EXTERNAL_ID_SECRET_OPTION, 'short', false );
		UserIdentifier::ensure_external_id_secret();
		$this->assertSame( 64, strlen( get_option( self::EXTERNAL_ID_SECRET_OPTION ) ) );
	}

	/**
	 * When the secret cannot be stored, the external ID is left out instead of being
	 * keyed with a value no later event will reproduce.
	 */
	public function test_external_id_is_omitted_when_secret_cannot_be_stored(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$block_write = static function ( $value, $old_value ) {
			return $old_value;
		};
		add_filter( 'pre_update_option_' . self::EXTERNAL_ID_SECRET_OPTION, $block_write, 10, 2 );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 42 ) );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		remove_filter( 'pre_update_option_' . self::EXTERNAL_ID_SECRET_OPTION, $block_write, 10 );

		$this->assertArrayNotHasKey( 'external_id', $data['user'] );
	}

	/**
	 * Pins the external ID formula against a precomputed value, so a change to the
	 * `user:` / `email:` prefixes or the hash cannot pass by changing the test helper too.
	 */
	public function test_external_id_matches_known_answer(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );
		update_option( self::EXTERNAL_ID_SECRET_OPTION, str_repeat( 'k', 64 ), false );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 42 ) );
		$order->save();

		$data = UserIdentifier::get_user_data( $order );

		$this->assertSame( 'a9c86ed89d425e74a757ab695c0cef7fa8a9b6f2ca9b0cdd3afbd20135b7092b', $data['user']['external_id'] );
	}

	/**
	 * Two stores with different secrets send different external IDs for the same
	 * customer ID.
	 */
	public function test_external_id_differs_between_stores(): void {
		Options::set( OptionDefaults::COLLECT_PII, 'yes' );

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 1 ) );
		$order->save();

		update_option( self::EXTERNAL_ID_SECRET_OPTION, 'store-a-secret', false );
		$store_a = UserIdentifier::get_user_data( $order );

		update_option( self::EXTERNAL_ID_SECRET_OPTION, 'store-b-secret', false );
		$store_b = UserIdentifier::get_user_data( $order );

		$this->assertNotSame( $store_a['user']['external_id'], $store_b['user']['external_id'] );
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
		$this->assertSame( $this->expected_external_id( 'user', (string) $user_id ), $data['user']['external_id'] );
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

	/**
	 * Builds the external ID the identifier is expected to send, using the stored secret.
	 *
	 * @param string $type  Identifier type, `user` or `email`.
	 * @param string $value Customer ID or normalised email.
	 * @return string Expected external ID.
	 */
	private function expected_external_id( string $type, string $value ): string {
		return hash_hmac( 'sha256', $type . ':' . $value, (string) get_option( self::EXTERNAL_ID_SECRET_OPTION ) );
	}
}
