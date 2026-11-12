/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from '../constants';
import {
	addToCartClick,
	retrievedVariation,
	singleAddToCartClick,
} from './utils';

jest.mock( '../constants', () => ( { TRACKING_DATA_VAR: {} } ) );

const setTrackingData = ( data ) => {
	Object.keys( TRACKING_DATA_VAR ).forEach(
		( key ) => delete TRACKING_DATA_VAR[ key ]
	);
	Object.assign( TRACKING_DATA_VAR, data );
};

const createCartForm = () => {
	document.body.innerHTML = `
		<form class="cart">
			<input name="quantity" value="2" />
			<button name="add-to-cart" value="10">Add to cart</button>
		</form>
	`;

	return document.querySelector( '[name=add-to-cart]' );
};

describe( 'retrievedVariation', () => {
	test( 'Records the variation with its price and the parent product name', () => {
		setTrackingData( {
			pixel_data: { products: { 10: { name: 'Hoodie', price: 20 } } },
			VIEW_CONTENT: { products: [ { id: 10, name: 'Hoodie' } ] },
		} );

		retrievedVariation( { variation_id: 11, display_price: 25 }, 10 );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: 'Hoodie',
		} );
	} );

	test( 'Takes the name from the parent product when the page has no VIEW_CONTENT', () => {
		setTrackingData( {
			pixel_data: { products: { 10: { name: 'Hoodie', price: 20 } } },
		} );

		retrievedVariation( { variation_id: 11, display_price: 25 }, 10 );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: 'Hoodie',
		} );
	} );

	test( 'Leaves the name unset when the parent product was not recorded', () => {
		setTrackingData( {
			pixel_data: { products: {} },
			VIEW_CONTENT: { products: [ { id: 99, name: 'Other product' } ] },
		} );

		retrievedVariation( { variation_id: 11, display_price: 25 }, 10 );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
			name: undefined,
		} );
	} );

	test( 'Does nothing when the page has no pixel data', () => {
		setTrackingData( {} );

		expect( () =>
			retrievedVariation( { variation_id: 11, display_price: 25 }, 10 )
		).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data ).toBeUndefined();
	} );

	test( 'Does nothing when the pixel data has no products', () => {
		setTrackingData( { pixel_data: { currency_minor_unit: 2 } } );

		expect( () =>
			retrievedVariation( { variation_id: 11, display_price: 25 }, 10 )
		).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data.products ).toBeUndefined();
	} );

	test( 'Does nothing when the variation has no ID', () => {
		setTrackingData( { pixel_data: { products: {} } } );

		retrievedVariation( { display_price: 25 }, 10 );

		expect( TRACKING_DATA_VAR.pixel_data.products ).toEqual( {} );
	} );
} );

describe( 'Add to cart clicks without pixel data', () => {
	beforeEach( () => {
		setTrackingData( { currency: 'EUR' } );
		window.rdt = jest.fn();
	} );

	afterEach( () => {
		delete window.rdt;
	} );

	test( 'singleAddToCartClick does not throw or send an event', () => {
		const button = createCartForm();

		expect( () =>
			singleAddToCartClick( { target: button }, 'event-id' )
		).not.toThrow();
		expect( window.rdt ).not.toHaveBeenCalled();
	} );

	test( 'addToCartClick does not throw or send an event', () => {
		const event = {
			currentTarget: { dataset: { product_id: '10', quantity: '1' } },
		};

		expect( () => addToCartClick( event, 'event-id' ) ).not.toThrow();
		expect( window.rdt ).not.toHaveBeenCalled();
	} );
} );

describe( 'singleAddToCartClick with pixel data', () => {
	afterEach( () => {
		delete window.rdt;
	} );

	test( 'Sends AddToCart with the product ID, name, price, and quantity', () => {
		setTrackingData( {
			currency: 'EUR',
			pixel_data: {
				currency_minor_unit: 2,
				products: { 10: { name: 'Hoodie', price: 25 } },
			},
		} );
		window.rdt = jest.fn();
		const button = createCartForm();

		singleAddToCartClick( { target: button }, 'event-id' );

		expect( window.rdt ).toHaveBeenCalledWith( 'track', 'AddToCart', {
			itemCount: 2,
			value: 50,
			currency: 'EUR',
			products: [ { id: 10, name: 'Hoodie' } ],
			conversionId: 'event-id',
		} );
	} );
} );
