/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from '../constants';
import { RedditEvent } from './events';

/**
 * Tracks a Reddit event using the global `rdt` function.
 *
 * @since 0.1.0
 *
 * @param {string} eventName Name of the event to track.
 * @param {Object} eventParams Additional event parameters to send.
 * @throws Will throw an error if `rdt` is not available globally.
 */
export const sendPixelEvent = ( eventName, eventParams ) => {
	if ( typeof rdt !== 'function' ) {
		throw new Error( 'Function rdt not implemented.' );
	}

	window.rdt( 'track', eventName, {
		...eventParams,
	} );
};

/**
 * Converts a numeric price to an object formatted with currency minor units.
 *
 * @since 0.1.0
 *
 * @param {number} price The product price.
 * @return {Object} Formatted price object with `price` and `currency_minor_unit`.
 */
const getPriceObject = ( price ) => {
	return {
		price: Math.round(
			price * 10 ** TRACKING_DATA_VAR.pixel_data.currency_minor_unit
		),
		currency_minor_unit: TRACKING_DATA_VAR.pixel_data.currency_minor_unit,
	};
};

/**
 * Enhances a product object by attaching pricing data from global tracking.
 *
 * @since 0.1.0
 *
 * @param {Object} product Product object containing at least an `id`.
 * @return {Object} The updated product object with price data included if available.
 */
const getProductObject = ( product ) => {
	if ( TRACKING_DATA_VAR.pixel_data.products[ product.id ] ) {
		product.name = TRACKING_DATA_VAR.pixel_data.products[ product.id ].name;
		product.prices = getPriceObject(
			TRACKING_DATA_VAR.pixel_data.products[ product.id ].price
		);
	}
	return product;
};

/**
 * Constructs a cart item object with ID, quantity, and calculated price.
 *
 * @since 0.1.0
 *
 * @param {Object} product Product object that may include price data.
 * @param {number} quantity Number of items added to the cart.
 * @return {Object} Cart item object.
 */
export const getCartItemObject = ( product, quantity ) => {
	const item = {
		id: product.id,
		name: product.name,
		quantity,
	};

	if ( product?.prices?.price ) {
		item.price =
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit;
	}

	return item;
};

/**
 * Tracks an `add_to_cart` event for Reddit.
 *
 * @since 0.1.0
 *
 * @param {Object} product Product object.
 * @param {number} [quantity=1] Quantity of product added.
 * @param {string|null} [eventId=null] Optional unique event identifier for deduplication.
 */
const trackAddToCartEvent = ( product, quantity = 1, eventId = null ) => {
	const { id, name, price } = getCartItemObject( product, quantity );

	const data = {
		itemCount: parseInt( quantity, 10 ),
		value: price * parseInt( quantity, 10 ),
		currency: TRACKING_DATA_VAR.currency,
		products: [
			{
				id,
				name,
			},
		],
	};

	if ( eventId ) {
		data.conversionId = eventId;
	}

	sendPixelEvent( RedditEvent.ADD_TO_CART, data );
};

/**
 * Handles "Add to Cart" click events on single product pages.
 *
 * @since 0.1.0
 *
 * @param {Event} event DOM event triggered by the user.
 * @param {string} [eventId=''] Optional unique event ID.
 */
export const singleAddToCartClick = function ( event, eventId = '' ) {
	const cartForm = event.target.closest( 'form.cart' );

	if ( ! cartForm ) {
		return;
	}

	const addToCart = cartForm.querySelector( '[name=add-to-cart]' );
	if ( ! addToCart ) {
		return;
	}

	const variationId = cartForm.querySelector( '[name=variation_id]' );
	const quantity = cartForm.querySelector( '[name=quantity]' );

	const product = getProductObject( {
		id: parseInt( variationId ? variationId.value : addToCart.value, 10 ),
	} );

	trackAddToCartEvent(
		product,
		quantity ? parseInt( quantity.value, 10 ) : 1,
		eventId
	);
};

/**
 * Handles "Add to Cart" clicks from archive or loop pages.
 *
 * @since 0.1.0
 *
 * @param {Event} event DOM event triggered by the user.
 * @param {string} [eventId=''] Optional unique event ID.
 */
export const addToCartClick = function ( event, eventId = '' ) {
	const data = event.currentTarget.dataset;
	const product = getProductObject( { id: data.product_id } );

	trackAddToCartEvent( product, data.quantity || 1, eventId );
};

/**
 * Updates the global product data with pricing from a variation.
 *
 * @since 0.1.0
 *
 * @param {Object} variation Variation object containing a `variation_id` and `display_price`.
 */
export const retrievedVariation = ( variation ) => {
	if ( ! variation?.variation_id ) {
		return;
	}

	TRACKING_DATA_VAR.pixel_data.products[ variation.variation_id ] = {
		price: variation.display_price,
		name: TRACKING_DATA_VAR.VIEW_CONTENT.products.name,
	};
};
