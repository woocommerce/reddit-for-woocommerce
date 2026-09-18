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

		return in_array( strtoupper( $currency ), self::SUPPORTED_CURRENCIES, true );
	}
}
