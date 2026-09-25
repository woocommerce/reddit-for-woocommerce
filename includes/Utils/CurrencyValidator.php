<?php
/**
 * Validates the store currency against Reddit's supported catalog currencies.
 *
 * @package RedditForWooCommerce\Utils
 * @since 1.0.7
 */

namespace RedditForWooCommerce\Utils;

/**
 * Single source of truth for which currencies Reddit's Catalog API accepts.
 *
 * @since 1.0.7
 */
final class CurrencyValidator {

	/**
	 * Currencies accepted by Reddit's Catalog API `default_currency` field.
	 *
	 * @see https://ads-api.reddit.com/docs/v3/api/create-product-catalog#request
	 *
	 * @since 1.0.7
	 *
	 * @var string[]
	 */
	public const SUPPORTED_CURRENCIES = array(
		'USD',
		'GBP',
		'CAD',
		'EUR',
		'AUD',
		'JPY',
		'CHF',
		'NZD',
		'SEK',
		'NOK',
	);

	/**
	 * Checks whether a currency is supported by Reddit's Catalog API.
	 *
	 * @since 1.0.7
	 *
	 * @param string|null $currency Currency code to check. Defaults to the store's currency.
	 * @return bool True if the currency is supported.
	 */
	public static function is_supported( ?string $currency = null ): bool {
		$currency = $currency ?? get_woocommerce_currency();

		return in_array( strtoupper( $currency ), self::get_supported_currencies(), true );
	}

	/**
	 * Returns the currencies Reddit's Catalog API accepts, after filtering.
	 *
	 * Falls back to {@see self::SUPPORTED_CURRENCIES} if a filter callback
	 * returns anything other than a list of currency codes.
	 *
	 * @since 1.0.7
	 *
	 * @return string[] Uppercase currency codes.
	 */
	public static function get_supported_currencies(): array {
		/**
		 * Filters the currencies Reddit's Catalog API accepts.
		 *
		 * @since 1.0.7
		 *
		 * @param string[] $currencies Uppercase ISO 4217 currency codes.
		 */
		$currencies = apply_filters( Helper::with_prefix( 'supported_currencies' ), self::SUPPORTED_CURRENCIES );

		if ( ! is_array( $currencies ) ) {
			return self::SUPPORTED_CURRENCIES;
		}

		$currencies = array_filter( $currencies, 'is_string' );

		return array_values( array_unique( array_map( 'strtoupper', $currencies ) ) );
	}
}
