/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SettingPage from '../utils/pages/settings.js';
import ElementLocators from '../utils/element-locators.js';

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

test.describe.skip( 'Reddit Settings', () => {
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
			page.locator( '.sfw-section__header', {
				hasText: 'Product Catalog',
			} )
		).toBeVisible();
		await expect(
			page.locator( '.sfw-section__header', {
				hasText: 'Track Conversions',
			} )
		).toBeVisible();
		await expect(
			page.locator( '.sfw-section__header', {
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

	test( 'Reddit card details', async () => {
		const payload = {
			org_id: '244753a0-2021-482c-af9b-dd6e7677d562',
			org_name: 'RedditForWooV105',
			ad_acc_id: '89b3e14b-bac9-409e-857c-ab006cd1c96e',
			ad_acc_name: 'RedditForWooV105 Self Service',
			pixel_id: 'fd014a21-2e25-41a8-9e12-de8c9fe512b4',
		};

		await settingPage.mockRedditAccount( payload );
		settingPage.goto();

		await expect( locator.getRedditAccountCard() ).toContainText(
			'Organization: RedditForWooV105'
		);
		await expect( locator.getRedditAccountCard() ).toContainText(
			'Ads Account: RedditForWooV105 Self Service (89b3e14b-bac9-409e-857c-ab006cd1c96e)'
		);
		await expect( locator.getRedditAccountCard() ).toContainText(
			'Pixel ID: fd014a21-2e25-41a8-9e12-de8c9fe512b4'
		);
		await expect( locator.getRedditConnectedLabel() ).toBeVisible();
	} );

	test( 'Reddit account disconnection', async () => {
		const payload = {
			org_id: '244753a0-2021-482c-af9b-dd6e7677d562',
			org_name: 'RedditForWooV105',
			ad_acc_id: '89b3e14b-bac9-409e-857c-ab006cd1c96e',
			ad_acc_name: 'RedditForWooV105 Self Service',
			pixel_id: 'fd014a21-2e25-41a8-9e12-de8c9fe512b4',
		};

		await settingPage.mockRedditAccount( payload );
		await settingPage.mockRedditDisconnection();
		settingPage.goto();

		await locator.getRedditDisconnectButton().click();

		await expect( locator.getRedditAccountCard() ).toContainText(
			'Organization: RedditForWooV105'
		);

		await expect(
			locator.getRedditFinalDisconnectButton()
		).toBeDisabled();
		await expect(
			locator.getRedditDisconnectConfirmCheckbox()
		).not.toBeChecked();
		await locator.getRedditDisconnectConfirmCheckbox().click();
		await expect(
			locator.getRedditDisconnectConfirmCheckbox()
		).toBeChecked();
		await expect(
			locator.getRedditFinalDisconnectButton()
		).toBeEnabled();
		await locator.getRedditFinalDisconnectButton().click();

		await page.waitForURL(
			'**/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsetup'
		);
		expect( await page.url() ).toContain(
			`/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsetup`
		);
	} );
} );
