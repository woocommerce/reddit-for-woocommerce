<?php
/**
 * Extracts and formats user identifiers for CAPI matching.
 *
 * Gathers client IP, user agent, Reddit click ID (rdtCid), and pixel UUID for
 * attribution in server-side events, and, when Collect Customer PII is enabled,
 * normalized and SHA-256 hashed customer match keys (email, phone number,
 * external ID).
 *
 * @package RedditForWooCommerce\Utils
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Utils;

use WC_Countries;
use WC_Order;

/**
 * Builds the user_data structure for CAPI event payloads.
 *
 * This includes device metadata (IP, UA), Reddit cookies, click ID data, and the
 * hashed customer match keys used for Advanced Matching.
 *
 * @since 0.1.0
 */
final class UserIdentifier {

	/**
	 * Returns a user_data array for CAPI payloads.
	 *
	 * Always includes the device identifiers available on the request: IP address,
	 * user agent and pixel UUID under `user`, plus the click ID.
	 *
	 * When Collect Customer PII is enabled, the hashed customer match keys are added
	 * under `user`:
	 *
	 * - `email`        — normalized, SHA-256 hashed email.
	 * - `phone_number` — E.164 canonicalized, SHA-256 hashed phone number.
	 * - `external_id`  — SHA-256 hashed stable per-customer identifier.
	 *
	 * The match keys are sourced from the order when one is passed (covering
	 * logged-in and guest purchases through billing details), and from the current
	 * user's billing meta for logged-in non-purchase events. Logged-out requests
	 * with no order carry no match keys.
	 *
	 * @since 0.1.0
	 * @since 1.0.7 Added the $order and $include_match_keys parameters for hashed customer match keys.
	 *
	 * @param WC_Order|null $order              Order to source purchase match keys from, when available.
	 * @param bool          $include_match_keys Whether to add the hashed customer match keys. Pass false for
	 *                                          synthetic events that must not carry a customer's identity, such as
	 *                                          the onboarding dummy purchase.
	 * @return array<string,mixed> Associative array of user identifiers.
	 */
	public static function get_user_data( ?WC_Order $order = null, bool $include_match_keys = true ): array {
		$data        = array();
		$ip_address  = self::get_user_ip_address();
		$user_agent  = self::get_user_agent();
		$reddit_uuid = self::get_reddit_pixel_uuid();
		$click_id    = self::get_reddit_click_id();

		if ( $ip_address ) {
			$data['user']['ip_address'] = $ip_address;
		}

		if ( $user_agent ) {
			$data['user']['user_agent'] = $user_agent;
		}

		if ( $reddit_uuid ) {
			$data['user']['uuid'] = $reddit_uuid;
		}

		if ( $click_id ) {
			$data['click_id'] = $click_id;
		}

		if ( ! $include_match_keys || ! Helper::is_collect_pii_enabled() ) {
			return $data;
		}

		return self::add_match_keys( $data, $order );
	}

	/**
	 * Adds the hashed customer match keys to a user_data array.
	 *
	 * Sources the raw values from the order for purchases, or from the current
	 * user's billing meta for logged-in non-purchase events, then normalizes and
	 * hashes each value before it is written into the array. Logged-out requests
	 * with no order add no keys.
	 *
	 * @since 1.0.7
	 *
	 * @param array<string,mixed> $data  The user_data array to extend.
	 * @param WC_Order|null       $order Order to source purchase match keys from, when available.
	 * @return array<string,mixed> The user_data array with match keys appended.
	 */
	private static function add_match_keys( array $data, ?WC_Order $order ): array {
		$email       = '';
		$phone       = '';
		$country     = '';
		$external_id = '';

		if ( $order instanceof WC_Order ) {
			$email   = (string) $order->get_billing_email();
			$phone   = (string) $order->get_billing_phone();
			$country = (string) $order->get_billing_country();

			$customer_id = $order->get_customer_id();

			if ( $customer_id > 0 ) {
				$external_id = self::hash( (string) $customer_id );
			} else {
				$normalized_email = self::normalize_email( $email );

				if ( '' !== $normalized_email ) {
					$external_id = self::hash( $normalized_email );
				}
			}
		} elseif ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$email   = (string) get_user_meta( $user_id, 'billing_email', true );

			if ( '' === $email ) {
				$user  = wp_get_current_user();
				$email = (string) $user->user_email;
			}

			$phone       = (string) get_user_meta( $user_id, 'billing_phone', true );
			$country     = (string) get_user_meta( $user_id, 'billing_country', true );
			$external_id = self::hash( (string) $user_id );
		} else {
			return $data;
		}

		$hashed_email = self::hash_email( $email );

		if ( '' !== $hashed_email ) {
			$data['user']['email'] = $hashed_email;
		}

		$hashed_phone = self::hash_phone( $phone, $country );

		if ( '' !== $hashed_phone ) {
			$data['user']['phone_number'] = $hashed_phone;
		}

		if ( '' !== $external_id ) {
			$data['user']['external_id'] = $external_id;
		}

		return $data;
	}

	/**
	 * Retrieves the user's browser user agent string.
	 *
	 * The user agent string provides information about the client's browser, operating system,
	 * and device. This information is commonly used for device identification and analytics.
	 *
	 * This method accesses the 'HTTP_USER_AGENT' server variable and sanitizes the input to
	 * ensure it is safe for further processing or storage.
	 *
	 * @since 0.1.0
	 *
	 * @return string The sanitized user agent string, or an empty string if not available.
	 */
	private static function get_user_agent(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
	}

	/**
	 * Retrieves the user's IP address from server variables.
	 *
	 * This method attempts to determine the client's real IP address by checking common HTTP headers
	 * that may be set by proxies or CDNs such as Cloudflare.
	 *
	 * The order of precedence is:
	 * 1. 'HTTP_CF_CONNECTING_IP' - Used by Cloudflare to pass the original client IP.
	 * 2. 'HTTP_X_FORWARDED_FOR' - A comma-separated list of IP addresses sent by proxies; the first IP is assumed to be the client IP.
	 * 3. 'REMOTE_ADDR' - The IP address reported by the web server as the remote client.
	 *
	 * @since 0.1.0
	 *
	 * @return string The client's IP address if available, otherwise an empty string.
	 */
	private static function get_user_ip_address(): string {
		$ip_address = '';

		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$raw        = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$list       = explode( ',', $raw );
			$ip_address = trim( $list[0] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip_address;
	}

	/**
	 * Retrieves the Reddit Pixel UUID from the user's browser cookies.
	 *
	 * The Reddit Pixel UUID is a unique user identifier set by the Reddit Pixel in the '_rdt_uuid' cookie
	 * whenever the pixel is installed on the website. Sending this identifier with server-side conversion events
	 * significantly improves Reddit's ability to match conversions with user engagements and ad impressions,
	 * enhancing attribution accuracy and deduplication between pixel and server events.
	 *
	 * @since 0.1.0
	 *
	 * @return string The Reddit Pixel UUID from the '_rdt_uuid' cookie, or an empty string if not set.
	 */
	private static function get_reddit_pixel_uuid(): ?string {
		$cookie_name = '_rdt_uuid';

		if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		}

		return '';
	}

	/**
	 * Retrieves the Reddit Click ID (rdtCid) from the user's browser cookies.
	 *
	 * The Reddit Click ID represents a unique identifier passed as a query parameter (rdt_cid)
	 * on product links when a user clicks a Reddit ad. After redirecting to the merchant site,
	 * this value is stored in a cookie named 'rdtCid'.
	 *
	 * Including this identifier in server-side conversion events helps attribute the conversion
	 * to the specific Reddit ad click, improving attribution accuracy and deduplication.
	 *
	 * @since 0.1.0
	 *
	 * @return string The Reddit Click ID stored in the 'rdtCid' cookie, or an empty string if not set.
	 */
	private static function get_reddit_click_id(): string {
		$cookie_name = 'rdtCid';

		if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		}

		return '';
	}

	/**
	 * Normalizes and SHA-256 hashes an email address for Advanced Matching.
	 *
	 * @since 1.0.7
	 *
	 * @param string $email Raw email address.
	 * @return string SHA-256 hash of the normalized email, or an empty string when the email is invalid.
	 */
	private static function hash_email( string $email ): string {
		$normalized = self::normalize_email( $email );

		if ( '' === $normalized ) {
			return '';
		}

		return self::hash( $normalized );
	}

	/**
	 * Normalizes an email address for Advanced Matching.
	 *
	 * Lowercases the address, removes any `+alias` from the local part, and removes
	 * dots from the local part while preserving dots in the domain.
	 *
	 * @since 1.0.7
	 *
	 * @param string $email Raw email address.
	 * @return string The normalized email, or an empty string when the input is not a valid address.
	 */
	private static function normalize_email( string $email ): string {
		$email = strtolower( trim( $email ) );
		$at    = strrpos( $email, '@' );

		if ( false === $at || 0 === $at ) {
			return '';
		}

		$local  = substr( $email, 0, $at );
		$domain = substr( $email, $at + 1 );
		$plus   = strpos( $local, '+' );

		if ( false !== $plus ) {
			$local = substr( $local, 0, $plus );
		}

		$local = str_replace( '.', '', $local );

		if ( '' === $local || '' === $domain ) {
			return '';
		}

		return $local . '@' . $domain;
	}

	/**
	 * Canonicalizes a phone number to E.164 and SHA-256 hashes it for Advanced Matching.
	 *
	 * @since 1.0.7
	 *
	 * @param string $phone   Raw phone number.
	 * @param string $country Billing country code used to derive the calling code.
	 * @return string SHA-256 hash of the canonicalized number, or an empty string when it cannot be canonicalized.
	 */
	private static function hash_phone( string $phone, string $country ): string {
		$normalized = self::normalize_phone( $phone, $country );

		if ( '' === $normalized ) {
			return '';
		}

		return self::hash( $normalized );
	}

	/**
	 * Canonicalizes a phone number to E.164 format.
	 *
	 * A number already in international form (leading `+`) keeps its digits with any
	 * extension and formatting removed. A national number is prefixed with the
	 * calling code derived from the billing country after its leading zero is
	 * dropped. When no calling code can be resolved for a national number, an empty
	 * string is returned so a mis-canonicalized value is never hashed and sent.
	 *
	 * @since 1.0.7
	 *
	 * @param string $phone   Raw phone number.
	 * @param string $country Billing country code used to derive the calling code.
	 * @return string The `+`-prefixed E.164 number, or an empty string when it cannot be resolved.
	 */
	private static function normalize_phone( string $phone, string $country ): string {
		$phone = trim( $phone );

		if ( '' === $phone ) {
			return '';
		}

		// Drop a trailing extension (e.g. "x123", "ext. 123", "#123") before parsing digits, for both international and national numbers.
		$phone = (string) preg_replace( '/\s*(?:ext|extension|x|#)\.?\s*\d+\s*$/i', '', $phone );

		if ( 0 === strpos( $phone, '+' ) ) {
			$digits = preg_replace( '/\D/', '', substr( $phone, 1 ) );

			return '' === $digits ? '' : '+' . $digits;
		}

		$calling_code = self::get_country_calling_code( $country );

		if ( '' === $calling_code ) {
			return '';
		}

		$digits = ltrim( (string) preg_replace( '/\D/', '', $phone ), '0' );

		if ( '' === $digits ) {
			return '';
		}

		return $calling_code . $digits;
	}

	/**
	 * Resolves the E.164 calling code for a country, including the leading `+`.
	 *
	 * @since 1.0.7
	 *
	 * @param string $country Two-letter country code.
	 * @return string The calling code (e.g. `+44`), or an empty string when it cannot be resolved.
	 */
	private static function get_country_calling_code( string $country ): string {
		if ( '' === $country || ! class_exists( 'WC_Countries' ) ) {
			return '';
		}

		$countries    = new WC_Countries();
		$calling_code = $countries->get_country_calling_code( strtoupper( $country ) );

		if ( is_array( $calling_code ) ) {
			$calling_code = $calling_code[0] ?? '';
		}

		return (string) $calling_code;
	}

	/**
	 * SHA-256 hashes a value.
	 *
	 * @since 1.0.7
	 *
	 * @param string $value Value to hash.
	 * @return string Lowercase hexadecimal SHA-256 digest.
	 */
	private static function hash( string $value ): string {
		return hash( 'sha256', $value );
	}
}
