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
}
