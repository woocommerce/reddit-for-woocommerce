/**
 * REST API utilities for E2E tests.
 *
 * Uses axios with HTTP Basic Auth (enabled in the test environment via the
 * WP-API/Basic-Auth plugin) so API calls work without a browser session.
 */

/**
 * External dependencies
 */
const axios = require( 'axios' ).default;

/**
 * Internal dependencies
 */
const config = require( '../config/default.json' );

const BASE_URL = `${ config.url }wp-json/`;

const PROMO_NAMESPACE = 'woocommerce/reddit-for-woocommerce';
const PROMO_KEY = 'channel-visibility-reddit-promo-dismissed';

function makeClient( apiVersion ) {
	const token = Buffer.from(
		`${ config.users.admin.username }:${ config.users.admin.password }`,
		'utf8'
	).toString( 'base64' );

	return axios.create( {
		baseURL: `${ BASE_URL }${ apiVersion }/`,
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Basic ${ token }`,
		},
	} );
}

function wcApi() {
	return makeClient( 'wc/v3' );
}

function wpApi() {
	return makeClient( 'wp/v2' );
}

/**
 * Creates a simple WooCommerce product via the WC REST API.
 *
 * @param {Object} [opts]
 * @param {string} [opts.name]  Product name.
 * @param {string} [opts.price] Regular price.
 * @return {Promise<number>} Product ID.
 */
export async function createSimpleProduct( opts = {} ) {
	const product = config.products.simple;

	const response = await wcApi().post( 'products', {
		name: opts.name || product.name,
		regular_price: opts.price || product.regular_price,
		type: product.type,
		status: product.status,
	} );

	return response.data.id;
}

/**
 * Permanently deletes a WooCommerce product via the WC REST API.
 *
 * @param {number} id Product ID.
 * @return {Promise<void>}
 */
export async function deleteProduct( id ) {
	await wcApi().delete( `products/${ id }`, { params: { force: true } } );
}

/**
 * Sets or clears the promo-dismissed preference for the admin user.
 *
 * Uses the standard WP REST API (`wp/v2/users/me`). WooCommerce registers
 * `persisted_preferences` as a writable user meta field.
 *
 * @param {boolean} dismissed
 * @return {Promise<void>}
 */
export async function setPromoDismissed( dismissed ) {
	const persistedPreferences = dismissed
		? { [ PROMO_NAMESPACE ]: { [ PROMO_KEY ]: true } }
		: {};

	await wpApi().put( 'users/me', {
		meta: { persisted_preferences: persistedPreferences },
	} );
}

/**
 * Sets or clears the Reddit onboarding completion status via the E2E test
 * endpoint (no standard WP REST API equivalent exists for plugin options).
 *
 * @param {boolean} complete
 * @return {Promise<void>}
 */
export async function setOnboardingComplete( complete ) {
	const client = axios.create( {
		baseURL: `${ BASE_URL }reddit-e2e/v1/`,
		headers: { 'Content-Type': 'application/json' },
	} );

	if ( complete ) {
		await client.post( 'onboarding-status', { status: 'connected' } );
	} else {
		await client.delete( 'onboarding-status' );
	}
}
