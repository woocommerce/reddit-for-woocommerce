/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from './constants';
import { onFoundVariation } from './utils';

jest.mock( './constants', () => ( { TRACKING_DATA_VAR: {} } ) );

const setTrackingData = ( data ) => {
	Object.keys( TRACKING_DATA_VAR ).forEach(
		( key ) => delete TRACKING_DATA_VAR[ key ]
	);
	Object.assign( TRACKING_DATA_VAR, data );
};

const createVariationForm = () => {
	const form = document.createElement( 'form' );
	form.className = 'variations_form cart';
	form.dataset.product_id = '10';
	return form;
};

const variation = { variation_id: 11, display_price: 25 };

describe( 'onFoundVariation', () => {
	beforeEach( () => {
		setTrackingData( {
			is_pixel_enabled: true,
			pixel_data: { products: { 10: { name: 'Hoodie', price: 20 } } },
		} );
	} );

	afterEach( () => {
		delete window.wp_has_consent;
	} );

	test( 'Records nothing when the pixel is off', () => {
		TRACKING_DATA_VAR.is_pixel_enabled = false;

		onFoundVariation( createVariationForm(), variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toBeUndefined();
	} );

	test( 'Records nothing when the consent tool reports no marketing consent', () => {
		window.wp_has_consent = jest.fn( () => false );

		onFoundVariation( createVariationForm(), variation );

		expect( window.wp_has_consent ).toHaveBeenCalledWith( 'marketing' );
		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toBeUndefined();
	} );

	test( 'Records the variation with the parent product name when no consent tool is installed', () => {
		onFoundVariation( createVariationForm(), variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: 'Hoodie',
		} );
	} );

	test( 'Records the variation when the consent tool reports consent', () => {
		window.wp_has_consent = jest.fn( () => true );

		onFoundVariation( createVariationForm(), variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: 'Hoodie',
		} );
	} );

	test( 'Logs instead of throwing when recording the variation fails', () => {
		const pixelData = {};
		Object.defineProperty( pixelData, 'products', {
			get() {
				throw new Error( 'Unexpected tracking data' );
			},
		} );
		TRACKING_DATA_VAR.pixel_data = pixelData;

		expect( () =>
			onFoundVariation( createVariationForm(), variation )
		).not.toThrow();
		expect( console ).toHaveErrored();
	} );
} );
