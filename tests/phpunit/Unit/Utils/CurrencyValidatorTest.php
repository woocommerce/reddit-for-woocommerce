<?php
/**
 * Unit tests for the CurrencyValidator class.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

declare( strict_types=1 );

namespace RedditForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use RedditForWooCommerce\Utils\CurrencyValidator;

/**
 * @covers \RedditForWooCommerce\Utils\CurrencyValidator
 */
final class CurrencyValidatorTest extends TestCase {

	/**
	 * Backup the store currency option before each test.
	 *
	 * @var string
	 */
	private $original_currency;

	/**
	 * Backup the store currency.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->original_currency = get_woocommerce_currency();
	}

	/**
	 * Restore the store currency.
	 */
	protected function tearDown(): void {
		update_option( 'woocommerce_currency', $this->original_currency );
		remove_all_filters( 'reddit_for_woocommerce_supported_currencies' );
		parent::tearDown();
	}

	/**
	 * Test that every currency in the supported list passes when explicitly provided.
	 *
	 * @dataProvider supported_currency_provider
	 */
	public function test_is_supported_returns_true_for_supported_currencies( string $currency ): void {
		$this->assertTrue( CurrencyValidator::is_supported( $currency ) );
	}

	/**
	 * Data provider of Reddit-supported currency codes.
	 *
	 * @return array<string,array<string>>
	 */
	public function supported_currency_provider(): array {
		return array(
			'USD' => array( 'USD' ),
			'GBP' => array( 'GBP' ),
			'CAD' => array( 'CAD' ),
			'EUR' => array( 'EUR' ),
			'AUD' => array( 'AUD' ),
			'JPY' => array( 'JPY' ),
			'CHF' => array( 'CHF' ),
			'NZD' => array( 'NZD' ),
			'SEK' => array( 'SEK' ),
			'NOK' => array( 'NOK' ),
		);
	}

	/**
	 * Test that a representative set of unsupported currencies fail, including INR from the ticket.
	 *
	 * @dataProvider unsupported_currency_provider
	 */
	public function test_is_supported_returns_false_for_unsupported_currencies( string $currency ): void {
		$this->assertFalse( CurrencyValidator::is_supported( $currency ) );
	}

	/**
	 * Data provider of currency codes Reddit does not support.
	 *
	 * @return array<string,array<string>>
	 */
	public function unsupported_currency_provider(): array {
		return array(
			'INR' => array( 'INR' ),
			'BRL' => array( 'BRL' ),
			'ZAR' => array( 'ZAR' ),
			'PLN' => array( 'PLN' ),
		);
	}

	/**
	 * Test that the check is case-insensitive.
	 */
	public function test_is_supported_is_case_insensitive(): void {
		$this->assertTrue( CurrencyValidator::is_supported( 'usd' ) );
	}

	/**
	 * Test that omitting the currency falls back to the store currency.
	 */
	public function test_is_supported_defaults_to_store_currency(): void {
		update_option( 'woocommerce_currency', 'INR' );
		$this->assertFalse( CurrencyValidator::is_supported() );

		update_option( 'woocommerce_currency', 'USD' );
		$this->assertTrue( CurrencyValidator::is_supported() );
	}

	/**
	 * Test that the supported currencies can be extended via a filter.
	 */
	public function test_supported_currencies_can_be_filtered(): void {
		add_filter(
			'reddit_for_woocommerce_supported_currencies',
			function ( $currencies ) {
				$currencies[] = 'inr';
				return $currencies;
			}
		);

		$this->assertContains( 'INR', CurrencyValidator::get_supported_currencies() );
		$this->assertTrue( CurrencyValidator::is_supported( 'INR' ) );
	}

	/**
	 * Test that non-string entries returned by a filter are discarded.
	 */
	public function test_supported_currencies_filter_discards_invalid_entries(): void {
		add_filter(
			'reddit_for_woocommerce_supported_currencies',
			function () {
				return array( 'USD', 123, null, array( 'GBP' ) );
			}
		);

		$this->assertSame( array( 'USD' ), CurrencyValidator::get_supported_currencies() );
	}

	/**
	 * Test that a filter returning a non-array falls back to the default list.
	 */
	public function test_supported_currencies_filter_falls_back_on_invalid_return(): void {
		add_filter( 'reddit_for_woocommerce_supported_currencies', '__return_false' );

		$this->assertSame( CurrencyValidator::SUPPORTED_CURRENCIES, CurrencyValidator::get_supported_currencies() );
	}
}
