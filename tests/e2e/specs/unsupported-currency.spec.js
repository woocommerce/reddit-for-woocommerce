/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupPage from '../utils/pages/setup.js';
import ElementLocators from '../utils/element-locators.js';

/**
 * @type {import('../utils/pages/setup.js').default} setupPage
 */
let setupPage = null;

/**
 * @type {import('../utils/element-locators.js').default} locator
 */
let locator = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

const UNSUPPORTED_CURRENCY_MESSAGE =
	'Store currency is not supported by Reddit for WooCommerce';

/**
 * Updates the store currency through the WooCommerce settings REST API.
 *
 * @param {string} currency ISO 4217 currency code.
 * @return {Promise<void>}
 */
const setStoreCurrency = async ( currency ) => {
	await setupPage.goto();
	await page.waitForFunction( () => window.wp?.apiFetch );
	await page.evaluate(
		( value ) =>
			window.wp.apiFetch( {
				path: '/wc/v3/settings/general/woocommerce_currency',
				method: 'PUT',
				data: { value },
			} ),
		currency
	);
};

test.describe( 'Unsupported store currency', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setupPage = new SetupPage( page );
		locator = new ElementLocators( page );
	} );

	test.afterAll( async () => {
		await setStoreCurrency( 'USD' );
		await setupPage.closePage();
	} );

	test.describe( 'with an unsupported currency', () => {
		test.beforeAll( async () => {
			await setStoreCurrency( 'INR' );
		} );

		test( 'shows a site-wide admin notice', async () => {
			await page.goto( '/wp-admin/index.php' );

			const notice = page.locator( '.notice-warning', {
				hasText: UNSUPPORTED_CURRENCY_MESSAGE,
			} );

			await expect( notice ).toBeVisible();
			await expect(
				notice.getByRole( 'link', {
					name: 'change your store currency',
				} )
			).toHaveAttribute( 'href', /page=wc-settings&tab=general/ );
		} );

		test( 'blocks the onboarding stepper', async () => {
			await setupPage.mockJetpackConnected();
			await setupPage.goto();

			const notice = page.locator( '.rfw-app-notice', {
				hasText: UNSUPPORTED_CURRENCY_MESSAGE,
			} );

			await expect( notice ).toBeVisible();
			await expect( notice ).toContainText( 'USD, GBP, CAD, EUR' );
			await expect( locator.getWPAccountCard() ).not.toBeVisible();
		} );
	} );

	test.describe( 'with a supported currency', () => {
		test.beforeAll( async () => {
			await setStoreCurrency( 'USD' );
		} );

		test( 'does not show the admin notice', async () => {
			await page.goto( '/wp-admin/index.php' );

			await expect(
				page.getByText( UNSUPPORTED_CURRENCY_MESSAGE )
			).toHaveCount( 0 );
		} );

		test( 'renders the onboarding stepper', async () => {
			await setupPage.goto();

			await expect(
				page.getByText( UNSUPPORTED_CURRENCY_MESSAGE )
			).toHaveCount( 0 );
			await expect( locator.getWPAccountCard() ).toBeVisible();
		} );
	} );
} );
