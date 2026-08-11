/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import {
	createSimpleProduct,
	deleteProduct,
	setPromoDismissed,
	setOnboardingComplete,
} from '../../utils/api';
import { getClassicProductEditorUtils } from '../../utils/product-editor';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const GET_STARTED_URL_PATTERN = /page=wc-admin&path=%2Freddit%2Fstart/;

/**
 * @type {import('@playwright/test').Page}
 */
let page = null;

/**
 * @type {ReturnType<typeof getClassicProductEditorUtils>}
 */
let editorUtils = null;

test.describe( 'Channel Visibility Meta Box', () => {
	let productId = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage( {
			storageState: process.env.ADMINSTATE,
		} );
		editorUtils = getClassicProductEditorUtils( page );
		productId = await createSimpleProduct();
	} );

	test.afterAll( async () => {
		await deleteProduct( productId );
		await page.close();
	} );

	test.describe( 'Onboarding not completed', () => {
		test.beforeEach( async () => {
			await setOnboardingComplete( false );
		} );

		test( 'Shows full promo banner when not dismissed', async () => {
			await setPromoDismissed( false );
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();

			await expect(
				rfwBox.getByRole( 'heading', {
					level: 3,
					name: 'Get your products on Reddit',
				} )
			).toBeVisible();

			await expect(
				rfwBox.getByText( /Sync your catalog to reach shoppers/ )
			).toBeVisible();

			const getStartedLink = rfwBox.getByRole( 'link', {
				name: 'Get started',
			} );
			await expect( getStartedLink ).toBeVisible();
			await expect( getStartedLink ).toHaveAttribute(
				'href',
				GET_STARTED_URL_PATTERN
			);

			await expect(
				rfwBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeVisible();

			await expect(
				rfwBox.locator(
					'.rfw-channel-visibility__get-started--is-dismissed'
				)
			).toBeHidden();
		} );

		test( 'Shows compact header with Get started only when dismissed', async () => {
			await setPromoDismissed( true );
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();

			const compactGetStarted = rfwBox
				.locator( '.rfw-channel-visibility__get-started--is-dismissed' )
				.getByRole( 'link', { name: 'Get started' } );

			await expect( compactGetStarted ).toBeVisible();
			await expect( compactGetStarted ).toHaveAttribute(
				'href',
				GET_STARTED_URL_PATTERN
			);

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeHidden();

			await expect(
				rfwBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();

			await expect(
				rfwBox.getByText( 'Get your products on Reddit' )
			).toBeHidden();
		} );

		test( 'Clicking on dismiss shows compact layout and settings are persisted on page refresh', async () => {
			await setPromoDismissed( false );
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeVisible();

			await rfwBox.getByRole( 'button', { name: 'Dismiss' } ).click();

			await expect(
				editorUtils.getChannelVisibilityMetaBoxContent()
			).toBeHidden();

			await editorUtils.gotoEditProductPage( productId );

			const rfwBoxAfterRefresh =
				editorUtils.getChannelVisibilityMetaBox();

			await expect(
				rfwBoxAfterRefresh.locator(
					'.rfw-channel-visibility__get-started--is-dismissed'
				)
			).toBeVisible();

			await expect(
				rfwBoxAfterRefresh.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();
		} );
	} );

	test.describe( 'Onboarding completed', () => {
		test.beforeAll( async () => {
			await setOnboardingComplete( true );
		} );

		test.afterAll( async () => {
			await setOnboardingComplete( false );
		} );

		test( 'Shows channel visibility settings with dropdown', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();

			await expect( rfwBox.getByRole( 'combobox' ) ).toBeVisible();

			await expect(
				rfwBox.getByText( 'Get your products on Reddit' )
			).toBeHidden();

			await expect(
				rfwBox.getByRole( 'button', { name: 'Dismiss' } )
			).toBeHidden();

			await expect(
				rfwBox.locator(
					'.rfw-channel-visibility__get-started--is-dismissed'
				)
			).toBeHidden();
		} );

		test( "Dropdown contains 'Sync and show' and 'Don't sync and show' options", async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const select = rfwBox.getByRole( 'combobox' );
			const options = select.locator( 'option' );

			await expect( select ).toBeVisible();
			await expect( options ).toHaveCount( 2 );
			await expect( select ).toHaveValue( '1' );
		} );

		test( 'Changing the dropdown updates the selected value', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const select = rfwBox.getByRole( 'combobox' );

			await expect( select ).toBeVisible();

			await select.selectOption( '0' );
			await expect( select ).toHaveValue( '0' );

			await select.selectOption( '1' );
			await expect( select ).toHaveValue( '1' );
		} );

		test( 'Selected visibility value is saved when the product form is submitted', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const select = rfwBox.getByRole( 'combobox' );

			await select.selectOption( '0' );
			await expect( select ).toHaveValue( '0' );

			await editorUtils.save();

			const savedSelect = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );
			await expect( savedSelect ).toHaveValue( '0' );

			await savedSelect.selectOption( '1' );
			await editorUtils.save();
		} );

		test( 'Changed visibility value persists after navigating away and back', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const select = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );

			await select.selectOption( '0' );
			await editorUtils.save();

			await editorUtils.gotoEditProductPage( productId );

			const selectAfterRefresh = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'combobox' );

			await expect( selectAfterRefresh ).toHaveValue( '0' );

			await selectAfterRefresh.selectOption( '1' );
			await editorUtils.save();
		} );
	} );
} );
