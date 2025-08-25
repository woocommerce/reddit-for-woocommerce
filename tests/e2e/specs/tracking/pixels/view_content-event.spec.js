/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findRedditEvent, getThemes, switchTheme } from '../../../utils';

test.describe( 'ViewContent event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

	for ( const theme in themes ) {
		test( `[${ theme } theme] Direct access to Single Product Page sends events`, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			const events = await page.evaluate( () => window.rdt.queue );
			const VIEW_CONTENT = findRedditEvent( events, 'ViewContent' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.products ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { id: expect.any( Number ) } ),
					expect.objectContaining( { name: 'Product Two' } ),
				] )
			);
		} );

		test( `[${ theme } theme] Backward navigation sends event `, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			await page
				.getByRole( 'link', { name: 'Sample Page' } )
				.first()
				.click();
			await page.goBack();

			const events = await page.evaluate( () => window.rdt.queue );
			const VIEW_CONTENT = findRedditEvent( events, 'ViewContent' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.products ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { id: expect.any( Number ) } ),
					expect.objectContaining( { name: 'Product Two' } ),
				] )
			);
		} );

		test( `[${ theme } theme] Navigate to Single Product Page event sends event `, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/shop' );
			await page
				.locator( '.woocommerce-loop-product__title', {
					hasText: 'Product Two',
				} )
				.or(
					page.locator( '.wp-block-post-title', {
						hasText: 'Product Two',
					} )
				)
				.click();

			await expect( page.url() ).toContain( '/product/product-two' );
			await page.waitForLoadState( 'domcontentloaded' );

			const events = await page.evaluate( () => window.rdt.queue );
			const VIEW_CONTENT = findRedditEvent( events, 'ViewContent' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.products ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { id: expect.any( Number ) } ),
					expect.objectContaining( { name: 'Product Two' } ),
				] )
			);
		} );

		test( `[${ theme } theme] No event is sent on reload`, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			await page.reload();

			const events = await page.evaluate( () => window.rdt.queue );
			const VIEW_CONTENT = findRedditEvent( events, 'ViewContent' );
			expect( VIEW_CONTENT ).toBe( null );
		} );
	}
} );
