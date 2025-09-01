/**
 * Internal dependencies
 */
import {
	connectedConfigPayload,
	businesses,
	adAccounts,
	pixels,
} from '../utils/mockPayloads.js';

const proxyFulfill = ( instance, options ) => {
	return new Proxy( instance.originalTarget || instance, {
		get( target, property ) {
			if ( property === 'originalTarget' ) {
				return target;
			}

			if ( property === 'previousOptions' ) {
				return options;
			}

			const value = Reflect.get( ...arguments );

			if ( property === 'fulfillRequest' ) {
				return function ( url, payload, status, methods ) {
					const mergedOpts = {
						...instance.previousOptions,
						...options,
					};
					const args = [ url, payload, status, methods, mergedOpts ];
					return value.apply( target, args );
				};
			}

			return value;
		},
	} );
};

/**
 * MockRequests - A general-purpose utility to mock HTTP requests via Playwright.
 */
export default class MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Intercepts a request and fulfills it with a given payload and optional options.
	 *
	 * @param {RegExp|string} url URL pattern or string to intercept.
	 * @param {Object} payload The response payload.
	 * @param {number} [status=200] HTTP status code.
	 * @param {Array<string>} [methods=[]] HTTP methods to intercept.
	 * @param {Object} [options={}] Additional options like `times` or `beforeFulfill`.
	 * @return {Promise<void>}
	 */
	async fulfillRequest(
		url,
		payload,
		status = 200,
		methods = [],
		options = {}
	) {
		const handler = async ( route ) => {
			if (
				methods.length === 0 ||
				methods.includes( route.request().method() )
			) {
				const fulfillOptions = {
					status,
					contentType: 'application/json',
					headers: { 'Access-Control-Allow-Origin': '*' },
					body: JSON.stringify( payload ),
				};

				const { beforeFulfill = Promise.resolve() } = options;

				return beforeFulfill.then( () =>
					route.fulfill( fulfillOptions )
				);
			}
			return route.fallback();
		};

		await this.page.route( url, handler, { times: options.times } );
	}

	/**
	 * Chainable method to fulfill a request multiple times.
	 *
	 * @param {number} times
	 * @return {this} A proxied instance with the fulfill count set.
	 */
	withFulfillTimes( times ) {
		return proxyFulfill( this, { times } );
	}

	/**
	 * Defer fulfillment of intercepted requests until manually triggered.
	 *
	 * @return {this} Proxied instance with `continueFulfill` callback.
	 */
	withFulfillDeferred() {
		let continueFulfill;
		const beforeFulfill = new Promise( ( resolve ) => {
			continueFulfill = resolve;
		} );

		const proxiedInstance = proxyFulfill( this, { beforeFulfill } );
		proxiedInstance.continueFulfill = continueFulfill;

		return proxiedInstance;
	}

	/**
	 * Mock the request to connect Jetpack
	 *
	 * @param {string} url
	 */
	async mockJetpackConnect( url ) {
		await this.fulfillRequest( /\/wc\/rfw\/jetpack\/connect\b/, { url } );
	}

	/**
	 * Mock Jetpack as connected.
	 *
	 * @param {string} displayName
	 * @param {string} email
	 */
	async mockJetpackConnected(
		displayName = 'Test user',
		email = 'jetpack@example.com'
	) {
		await this.fulfillRequest( /\/wc\/rfw\/jetpack\/connected\b/, {
			active: 'yes',
			owner: 'yes',
			displayName,
			email,
		} );
	}

	/**
	 * Mock Jetpack as not connected.
	 */
	async mockJetpackNotConnected() {
		await this.fulfillRequest( /\/wc\/rfw\/jetpack\/connected\b/, {
			active: 'no',
			displayName: '',
			email: '',
		} );
	}

	/**
	 * Mock the request to connect Reddit.
	 *
	 * @param {string} url The redirect URL returned by the Reddit connect endpoint.
	 * @return {Promise<void>}
	 */
	async mockRedditConnect( url ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/connect\b/, { url } );
	}

	/**
	 * Mock the current Reddit connection status.
	 *
	 * @param {Object} payload The response payload to return for the connection status.
	 * @return {Promise<void>}
	 */
	async mockRedditConnection( payload ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/connection\b/, payload );
	}

	/**
	 * Mock the onboarding setup endpoint response.
	 *
	 * @param {Object} payload The setup state or payload returned from the API.
	 * @return {Promise<void>}
	 */
	async mockOnboardingSetup( payload ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/setup\b/, payload );
	}

	/**
	 * Mock the Reddit Ad Account or Business data endpoint.
	 *
	 * @param {Object} payload The account or organization data to return.
	 * @return {Promise<void>}
	 */
	async mockRedditAccount( payload = connectedConfigPayload ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/config\b/, payload );
	}

	/**
	 * Mock the Reddit Businesses data endpoint.
	 *
	 * @param {Object} payload The list of Business data to return.
	 * @return {Promise<void>}
	 */
	async mockRedditBusiness( payload = businesses ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/businesses\b/, payload );
	}

	/**
	 * Mock the Reddit Ad Accounts data endpoint.
	 *
	 * @param {Object} payload The list of Ad Accounts data to return.
	 * @return {Promise<void>}
	 */
	async mockRedditAdAccounts( payload = adAccounts ) {
		await this.fulfillRequest(
			/\/wc\/rfw\/reddit\/ad_accounts\b/,
			payload
		);
	}

	/**
	 * Mock the Reddit Pixels data endpoint.
	 *
	 * @param {Object} payload The list of Pixel data to return.
	 * @return {Promise<void>}
	 */
	async mockRedditPixels( payload = pixels ) {
		await this.fulfillRequest( /\/wc\/rfw\/reddit\/pixels\b/, payload );
	}

	/**
	 * Mock the DELETE request to disconnect Reddit.
	 *
	 * @param {number} [status=200] The HTTP status code to return.
	 * @return {Promise<void>}
	 */
	async mockRedditDisconnection( status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/rfw\/reddit\/connection\b/,
			{ status: 'disconnected' },
			status,
			[ 'DELETE' ]
		);
	}
}
