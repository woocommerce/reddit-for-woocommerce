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

		test( 'Saving a product before onboarding does not reset its channel visibility', async () => {
			// Only the promo banner renders here — there is no channel visibility
			// toggle for the form to submit — so this reproduces the report:
			// an unrelated product save must not flip the (default opted-in) sync
			// preference to "Don't sync and show".
			await setPromoDismissed( false );
			await editorUtils.gotoEditProductPage( productId );

			// Sanity: no settings toggle is present before onboarding completes.
			await expect(
				editorUtils
					.getChannelVisibilityMetaBox()
					.getByRole( 'checkbox' )
			).toBeHidden();

			// Save the product for an unrelated reason (the form is submitted with
			// no channel visibility field present).
			await editorUtils.save();

			// Complete onboarding so the settings toggle renders, then confirm the
			// earlier save left the opt-in at its default (checked = Sync and show)
			// rather than resetting it to unchecked.
			await setOnboardingComplete( true );
			await editorUtils.gotoEditProductPage( productId );

			await expect(
				editorUtils
					.getChannelVisibilityMetaBox()
					.getByRole( 'checkbox' )
			).toBeChecked();

			// Restore onboarding state for the rest of this describe block.
			await setOnboardingComplete( false );
		} );
	} );

	test.describe( 'Onboarding completed', () => {
		test.beforeAll( async () => {
			await setOnboardingComplete( true );
		} );

		test.afterAll( async () => {
			await setOnboardingComplete( false );
		} );

		test( 'Shows channel visibility settings with a toggle', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();

			await expect( rfwBox.getByRole( 'checkbox' ) ).toBeVisible();

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

		test( 'Toggle defaults to checked (sync and show)', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const toggle = rfwBox.getByRole( 'checkbox' );

			await expect( toggle ).toBeVisible();
			await expect( toggle ).toBeChecked();
		} );

		test( 'Clicking the toggle updates the checked state', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const toggle = rfwBox.getByRole( 'checkbox' );

			await expect( toggle ).toBeVisible();

			await toggle.click();
			await expect( toggle ).not.toBeChecked();

			await toggle.click();
			await expect( toggle ).toBeChecked();
		} );

		test( 'Unchecking the toggle and saving persists the unchecked state', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const rfwBox = editorUtils.getChannelVisibilityMetaBox();
			const toggle = rfwBox.getByRole( 'checkbox' );

			await toggle.click();
			await expect( toggle ).not.toBeChecked();

			await editorUtils.save();

			const savedToggle = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'checkbox' );
			await expect( savedToggle ).not.toBeChecked();

			await savedToggle.click();
			await editorUtils.save();
		} );

		test( 'Checked visibility value persists after navigating away and back', async () => {
			await editorUtils.gotoEditProductPage( productId );

			const toggle = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'checkbox' );

			await toggle.click();
			await editorUtils.save();

			await editorUtils.gotoEditProductPage( productId );

			const toggleAfterRefresh = editorUtils
				.getChannelVisibilityMetaBox()
				.getByRole( 'checkbox' );

			await expect( toggleAfterRefresh ).not.toBeChecked();

			await toggleAfterRefresh.click();
			await editorUtils.save();
		} );
	} );
} );
