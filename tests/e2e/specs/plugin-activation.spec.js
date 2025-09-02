/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

const PLUGINS_PAGE_URL = '/wp-admin/plugins.php';

test.describe( 'Reddit for WooCommerce', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'Woo Dependency', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await expect(
			await page
				.locator( '[data-slug="woocommerce"]' )
				.locator( '.required-by' )
		).toContainText( 'Required by: Reddit for WooCommerce', {
			exact: false,
		} );
	} );

	test( 'Deactivation', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await page
			.getByRole( 'link', {
				name: 'Deactivate Reddit for Woocommerce',
			} )
			.click();

		await expect( page.getByText( 'Plugin deactivated.' ) ).toBeVisible();
	} );

	test( 'Activation', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await page
			.getByRole( 'link', { name: 'Activate Reddit for Woocommerce' } )
			.click();

		await expect( page.getByText( 'Plugin activated.' ) ).toBeVisible();
	} );

	test( '"Reddit" menu option', async ( { page } ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing'
		);
		await expect(
			page
				.locator( '.wp-submenu' )
				.getByRole( 'link', { name: 'Reddit' } )
		).toBeVisible();
	} );
} );
