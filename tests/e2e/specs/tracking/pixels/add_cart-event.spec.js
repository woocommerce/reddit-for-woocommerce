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

test.describe( 'AddToCart event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

	for ( const theme in themes ) {
		test( `[${ theme } theme] Shop page`, async ( { page } ) => {
			await setConsent( page, true );
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/shop' );

			await page
				.getByRole( 'link', { name: 'Add to cart: “Product Five”' } )
				.or(
					page.getByRole( 'button', {
						name: 'Add to cart: “Product Five”',
					} )
				)
				.click();
			const events = await page.evaluate( () => window.rdt.queue );
			const ADD_CART = findRedditEvent( events, 'AddToCart' );
			expect( ADD_CART ).not.toBe( null );

			const [ , , payload ] = ADD_CART;

			expect( payload.itemCount ).toBe( 1 );
			expect( payload.value ).toBe( 10 );
			expect( payload.currency ).toMatch( 'USD' );
			expect( payload.conversionId ).toMatch( anyUuidRegex );
			expect( payload.products ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: expect.stringMatching( /^\d+$/ ),
					} ),
					expect.objectContaining( { name: 'Product Five' } ),
				] )
			);
		} );
	}
} );
