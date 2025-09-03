/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SettingPage from '../utils/pages/settings.js';
import ElementLocators from '../utils/element-locators.js';
import { adAccounts, businesses, connectedConfigPayload, pixels } from '../utils/mockPayloads.js';

/**
 * @type {import('../utils/pages/settings.js').default} settingPage
 */
let settingPage = null;

/**
 * @type {import('../utils/element-locators.js').default} onboardingPage
 */
let locator = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Reddit Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingPage = new SettingPage( page );
		locator = new ElementLocators( page );

		await settingPage.mockJetpackConnected();
		await settingPage.mockRedditConnection( { status: 'connected' } );
		await settingPage.mockOnboardingSetup( {
			status: 'connected',
			step: 'accounts',
		} );
	} );

	test.afterAll( async () => {
		await settingPage.closePage();
	} );

	test( 'Can see onboarding success modal', async () => {
		await settingPage.mockJetpackConnected();
		await settingPage.mockRedditConnection( { status: 'connected' } );
		await settingPage.mockRedditBusiness([businesses[0]]);
		await settingPage.mockRedditAdAccounts([adAccounts[0]]);
		await settingPage.mockRedditPixels([pixels[0]]);
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsetup',
			{ waitUntil: 'domcontentloaded' }
		);

		await locator.getContinueToSetupButton().click();

		await page.waitForURL(
			'**/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsettings&onboarding=success'
		);
		expect( await page.url() ).toContain(
			'/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsettings&onboarding=success'
		);

		await expect( locator.getOnboardingSuccessfulModal() ).toBeVisible();

		await locator.getOnboardingSuccessfulCloseModalButton().first().click();
		await expect(
			locator.getOnboardingSuccessfulModal()
		).not.toBeVisible();
		await page.waitForURL(
			'**/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsettings'
		);
		expect( await page.url() ).toContain(
			'/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsettings'
		);
	} );

	test( 'Shows all sections', async () => {
		settingPage.goto();

		await expect(
			page.locator( '.rfw-section__header', {
				hasText: 'Product Catalog',
			} )
		).toBeVisible();
		await expect(
			page.locator( '.rfw-section__header', {
				hasText: 'Track Conversions',
			} )
		).toBeVisible();
		await expect(
			page.locator( '.rfw-section__header', {
				hasText: 'Manage Reddit Connection',
			} )
		).toBeVisible();
	} );

	test( 'Toggle conversion tracking', async () => {
		settingPage.goto();

		await locator.getCapiCheckbox().click();
		await expect( locator.getCapiCheckbox() ).toBeEnabled();
		await expect(
			page
				.getByText(
					'Conversions API Tracking status updated successfully.'
				)
				.first()
		).toBeVisible();

		await locator.getCapiCheckbox().click();
		await expect( locator.getCapiCheckbox() ).toBeDisabled();
		await expect(
			page
				.getByText(
					'Conversions API Tracking status updated successfully.'
				)
				.first()
		).toBeVisible();
	} );

	test( 'Save Conversion Access Token', async () => {
		await settingPage.mockJetpackConnected();
		await settingPage.mockRedditConnection( { status: 'connected' } );
		await settingPage.mockRedditBusiness([businesses[0]]);
		await settingPage.mockRedditAdAccounts([adAccounts[0]]);
		await settingPage.mockRedditPixels([pixels[0]]);
		await settingPage.mockRedditAccount( connectedConfigPayload );
		settingPage.goto();

		// Save the token
		const capiToken = `capi-token-${Date.now()}`;
		await locator.getCapiTokenInput().fill( capiToken );
		await expect( locator.getCapiTokenInput() ).toHaveValue( capiToken );
		await expect(
			page
				.getByText( 'Conversions API Access Token updated successfully.' )
				.first()
		).toBeVisible();

		await page.reload();
		await page.waitForTimeout( 1000 );
		await expect( locator.getCapiTokenInput() ).toHaveValue( capiToken );

		// Clear the token
		await locator.getCapiTokenInput().fill( '' );
		await expect( locator.getCapiTokenInput() ).toHaveValue( '' );
		await expect(
			page
				.locator( '.components-snackbar-list .components-snackbar-list__notice-container .components-snackbar' )
				.first()
		).toBeVisible();
		await page.reload();
		await expect( locator.getCapiTokenInput() ).toHaveValue( '' );
	} );

	test( 'Remove Conversion Access Token should disable conversions tracking', async () => {
		await settingPage.mockRedditAccount( connectedConfigPayload );
		settingPage.goto();
		const capiCheckbox = locator.getCapiCheckbox().locator('..').locator('input');
		await capiCheckbox.check();
		const capiToken = `capi-token-${Date.now()}`;
		await locator.getCapiTokenInput().fill( capiToken );
		await expect(
			page
				.getByText( 'Conversions API Access Token updated successfully.' )
				.first()
		).toBeVisible();
		await expect( capiCheckbox ).toBeChecked();

		await page.reload();
		await page.waitForTimeout( 1500 );
		await expect( capiCheckbox ).toBeChecked();
		await locator.getCapiTokenInput().fill( '' );
		await expect( locator.getCapiTokenInput() ).toHaveValue( '' );
		await expect(
			page
				.getByText( 'Conversions API Access Token updated successfully.' )
				.first()
		).toBeVisible();
		await expect( capiCheckbox ).not.toBeChecked();
	} );

	test( 'Reddit card details', async () => {
		await settingPage.mockRedditAccount( connectedConfigPayload );
		settingPage.goto();

		await expect( locator.getRedditAccountCard() ).toContainText(
			'Business: R4W Business'
		);
		await expect( locator.getRedditAccountCard() ).toContainText(
			'Ads Account: R4W Ad Account (ad-account-def123)'
		);
		await expect( locator.getRedditAccountCard() ).toContainText(
			'Pixel ID: pixel-def123'
		);
		await expect( locator.getRedditConnectedLabel() ).toBeVisible();
	} );

	test( 'Reddit account disconnection', async () => {
		await settingPage.mockRedditAccount( connectedConfigPayload );
		await settingPage.mockRedditDisconnection();
		settingPage.goto();

		await locator.getRedditDisconnectButton().click();

		await expect( locator.getRedditAccountCard() ).toContainText(
			'Business: R4W Business'
		);

		await expect( locator.getRedditFinalDisconnectButton() ).toBeDisabled();
		await expect(
			locator.getRedditDisconnectConfirmCheckbox()
		).not.toBeChecked();
		await locator.getRedditDisconnectConfirmCheckbox().click();
		await expect(
			locator.getRedditDisconnectConfirmCheckbox()
		).toBeChecked();
		await expect( locator.getRedditFinalDisconnectButton() ).toBeEnabled();
		await locator.getRedditFinalDisconnectButton().click();

		await page.waitForURL(
			'**/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsetup'
		);
		expect( await page.url() ).toContain(
			`/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsetup`
		);
	} );
} );
