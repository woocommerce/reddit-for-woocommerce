/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findRedditEvent, getThemes, switchTheme, setConsent } from '../../../utils';

const anyUuidRegex =
	/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

test.describe( 'PageVisit event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const PAGES = [
		{ url: '/', name: 'Home Page' },
		{ url: '/shop', name: 'Shop Page' },
		{ url: '/sample-page', name: 'Sample Page' },
		{ url: '/my-account', name: 'My Account Page' },
		{ url: '/cart', name: 'Cart Page' },
	];

	const themes = getThemes();

	for ( const theme in themes ) {
		for ( const PAGE of PAGES ) {
			test( `[${ theme } theme] ${ PAGE.name }`, async ( { page } ) => {
				await setConsent( page, true );
				await switchTheme( page, themes[ theme ] );
				await page.goto( PAGE.url );
				const events = await page.evaluate( () => window.rdt.queue );
				const PAGE_VISIT = findRedditEvent( events, 'PageVisit' );
				expect( PAGE_VISIT ).not.toBe( null );

				const [ , , payload ] = PAGE_VISIT;
				expect( payload.conversionId ).toMatch( anyUuidRegex );
			} );
		}

		test( `[${ theme } themme] Does not send on Single Product page`, async ( {
			page,
		} ) => {
			await setConsent( page, true );
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			const events = await page.evaluate( () => window.rdt.queue );
			const PAGE_VISIT = findRedditEvent( events, 'PageVisit' );
			expect( PAGE_VISIT ).toBe( null );
		} );
	}
} );
