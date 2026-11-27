/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from './constants';
import { onFoundVariation } from './utils';
import { singleAddToCartClick } from './pixel/utils';

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

	test( 'Records the variation without a name when the form has no product ID', () => {
		const form = createVariationForm();
		delete form.dataset.product_id;

		expect( () => onFoundVariation( form, variation ) ).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: undefined,
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

describe( 'Adding a selected variation to the cart', () => {
	afterEach( () => {
		delete window.rdt;
	} );

	test( 'Sends AddToCart with the variation price and the parent product name', () => {
		setTrackingData( {
			currency: 'EUR',
			is_pixel_enabled: true,
			pixel_data: {
				currency_minor_unit: 2,
				products: { 10: { name: 'Hoodie', price: 20 } },
			},
		} );
		window.rdt = jest.fn();
		document.body.innerHTML = `
			<form class="variations_form cart" data-product_id="10">
				<input name="quantity" value="2" />
				<input name="variation_id" value="11" />
				<input name="add-to-cart" value="10" />
				<button type="submit">Add to cart</button>
			</form>
		`;
		const form = document.querySelector( 'form' );

		onFoundVariation( form, variation );
		singleAddToCartClick(
			{ target: form.querySelector( 'button' ) },
			'event-id'
		);

		expect( window.rdt ).toHaveBeenCalledWith( 'track', 'AddToCart', {
			itemCount: 2,
			value: 50,
			currency: 'EUR',
			products: [ { id: 11, name: 'Hoodie' } ],
			conversionId: 'event-id',
		} );
	} );
} );
