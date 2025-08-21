<?php
/**
 * Extracts and formats user identifiers for CAPI matching.
 *
 * Gathers client IP, user agent, Reddit click ID (rdtCid),
 * and sc_cookie1 for better attribution in server-side events.
 *
 * @package RedditForWooCommerce\Utils
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Utils;

/**
 * Builds the user_data structure for CAPI event payloads.
 *
 * This includes device metadata (IP, UA), Reddit cookies, and click ID data.
 * Intended to improve event match rate and attribution accuracy.
 *
 * @since 0.1.0
 */
final class UserIdentifier {

	/**
	 * Returns a user_data array for CAPI payloads.
	 *
	 * Includes:
	 * - IP address
	 * - user agent (from server)
	 * - unique user ID (UUID)
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> Associative array of user identifiers.
	 */
	public static function get_user_data(): array {
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
}
