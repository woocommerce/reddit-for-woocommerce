/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { getThemes, switchTheme, setConsent } from '../../../utils';

test.describe( 'Pixel consent', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

	test.afterEach( async ( { page } ) => {
		await setConsent( page, true );
	} );

	for ( const theme in themes ) {
		test( `[${ theme } theme] Pixel is not loaded when consent is denied`, async ( {
			page,
		} ) => {
			await setConsent( page, false );
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/' );

			const pixelLoaded = await page.evaluate(
				() => typeof window.rdt !== 'undefined'
			);
			expect( pixelLoaded ).toBe( false );
		} );

		test( `[${ theme } theme] Pixel is loaded when consent is granted`, async ( {
			page,
		} ) => {
			await setConsent( page, true );
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/' );

			const pixelLoaded = await page.evaluate(
				() => typeof window.rdt !== 'undefined'
			);
			expect( pixelLoaded ).toBe( true );
		} );
	}
} );
