/**
 * Internal dependencies
 */
import { onFoundVariation } from './utils';

jest.mock( './constants', () => ( { TRACKING_DATA_VAR: {} } ) );
jest.mock( './utils', () => ( {
	onSingleAddToCartClick: jest.fn(),
	onLoopAddToCartClick: jest.fn(),
	hasUserConsent: jest.fn( () => false ),
	setRedditClickId: jest.fn(),
	onSingleProductPageVisit: jest.fn(),
	onPageVisit: jest.fn(),
	onFoundVariation: jest.fn(),
} ) );

describe( 'found_variation listener', () => {
	afterEach( () => {
		delete window.jQuery;
	} );

	test( 'Passes the variation form and the variation to onFoundVariation', () => {
		const on = jest.fn();
		window.jQuery = jest.fn( () => ( { on } ) );

		jest.isolateModules( () => {
			require( './index' );
		} );

		expect( on ).toHaveBeenCalledWith(
			'found_variation',
			'form.cart',
			expect.any( Function )
		);

		const handler = on.mock.calls[ 0 ][ 2 ];
		const form = document.createElement( 'form' );
		const variation = { variation_id: 11, display_price: 25 };

		handler.call( form, {}, variation );

		expect( onFoundVariation ).toHaveBeenCalledWith( form, variation );
	} );
} );
