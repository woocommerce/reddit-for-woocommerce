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
 * @type {import('../utils/element-locators.js').default} onboardingPage
 */
let locator = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Merchant Onboarding', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setupPage = new SetupPage( page );
		locator = new ElementLocators( page );
	} );

	test.afterAll( async () => {
		await setupPage.closePage();
	} );

	test( 'Initial account card states', async () => {
		setupPage.goto();

		await expect( locator.getWPAccountCard() ).toBeVisible();
		await expect( locator.getWpConnectButton() ).toBeEnabled();

		await expect( locator.getRedditAccountCard() ).toBeVisible();
		await expect( locator.getRedditConnectButton() ).toBeDisabled();
	} );

	test( 'WP.com connection flow', async ( { baseURL } ) => {
		await setupPage.mockJetpackConnect( `${ baseURL }/auth_url` );
		await setupPage.goto();

		await locator.getWpConnectButton().click();
		await page.waitForLoadState( 'domcontentloaded' );
		await page.waitForURL( `${ baseURL }/auth_url` );
		expect( page.url() ).toMatch( `${ baseURL }/auth_url` );
	} );

	test( 'WP.com connected card state', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.goto();

		await expect( locator.getWpConnectedLabel() ).toBeVisible();
		await expect( locator.getRedditConnectButton() ).toBeEnabled();
	} );

	test( 'Reddit connection flow', async ( { baseURL } ) => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockRedditConnect( `${ baseURL }/reddit_auth_url` );
		await setupPage.goto();

		await locator.getRedditConnectButton().click();
		await page.waitForLoadState( 'domcontentloaded' );
		await page.waitForURL( `${ baseURL }/reddit_auth_url` );
		expect( page.url() ).toMatch( `${ baseURL }/reddit_auth_url` );
	} );

	test( 'Reddit display button to create business', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockRedditConnection( { status: 'connected' } );
		await setupPage.mockRedditAccount( {} );
		await setupPage.mockRedditBusiness( [] );
		await setupPage.mockRedditAdAccounts( [] );
		await setupPage.mockRedditPixels( [] );
		await setupPage.goto();
		await expect( locator.getCreateBusinessButton() ).toBeVisible();
		await expect( locator.getCreateBusinessButton() ).toContainText(
			'Create Business Account'
		);

		const newPagePromise = page.waitForEvent( 'popup' );
		await locator.getCreateBusinessButton().click();
		const newPage = await newPagePromise;
		await newPage.waitForLoadState();
		await expect( newPage.title() ).resolves.toContain( 'Reddit Ads' );
	} );

	test( 'Reddit connected card state', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockRedditConnection( { status: 'connected' } );
		await setupPage.mockOnboardingSetup( {
			status: 'connected',
			step: 'accounts',
		} );
		await setupPage.mockRedditBusiness();
		await setupPage.mockRedditAdAccounts();
		await setupPage.mockRedditPixels();
		await setupPage.mockRedditAccount();
		await setupPage.goto();

		await expect( locator.getRedditConnectedLabel() ).toBeVisible();
	} );

	test( 'Reddit card details', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockRedditConnection( { status: 'connected' } );
		await setupPage.mockOnboardingSetup( {
			status: 'connected',
			step: 'accounts',
		} );
		await setupPage.mockRedditBusiness();
		await setupPage.mockRedditAdAccounts();
		await setupPage.mockRedditPixels();
		await setupPage.mockRedditAccount();
		setupPage.goto();

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

	test( 'Reddit card edit state', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockRedditBusiness();
		await setupPage.mockRedditAdAccounts();
		await setupPage.mockRedditPixels();
		await setupPage.mockRedditConnection( { status: 'connected' } );
		await setupPage.mockOnboardingSetup( {
			status: 'connected',
			step: 'accounts',
		} );
		await setupPage.mockRedditAccount();
		setupPage.goto();

		await locator.getRedditCardEditButton().click();
		await expect(
			locator.getConnectToDifferentBusinessButton()
		).toBeVisible();
		await expect( locator.getRedditCardCancelButton() ).toBeVisible();
		await expect( locator.getRedditCardEditButton() ).not.toBeVisible();
		await expect( locator.getRedditBusinessCard() ).toBeVisible();
		await expect( locator.getRedditAdsAccountCard() ).toBeVisible();
		await expect( locator.getRedditPixelCard() ).toBeVisible();

		await locator.getRedditCardCancelButton().click();
		await expect( locator.getRedditCardEditButton() ).toBeVisible();
	} );
} );
