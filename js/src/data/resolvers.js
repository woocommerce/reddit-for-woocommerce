/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import { handleApiError } from '~/utils/handleError';
import {
	fetchExistingAdsAccounts,
	fetchSetup,
	fetchRedditAccount,
	receiveExistingBusinessAccounts,
	receiveJetpackAccount,
	receiveRedditAccountConfig,
	receiveTrackConversionsStatus,
	receiveSettings,
	receiveExistingPixels,
} from './actions';

/**
 * Asynchronous thunk action creator to fetch Jetpack account connection status.
 *
 * Dispatches the received Jetpack account information to the store.
 * Handles API errors gracefully and displays a localized error message if needed.
 *
 * @return {Function} Thunk function that accepts Redux's dispatch.
 */
export function getJetpackAccount() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/jetpack/connected`,
			} );

			dispatch( receiveJetpackAccount( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Jetpack account info.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}

/**
 * Retrieves the Reddit account fetch function.
 *
 * @return {Function} The function to fetch the Reddit account.
 */
export function getRedditAccount() {
	return fetchRedditAccount;
}

/**
 * Fetches the Reddit account config information from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getRedditAccountConfig() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/config`,
			} );
			dispatch( receiveRedditAccountConfig( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Reddit account config info.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}

/**
 * Fetches the status of conversions tracking from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getTrackConversions() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/settings`,
			} );

			dispatch(
				receiveTrackConversionsStatus(
					Boolean( response.capi_enabled )
				)
			);
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error getting the conversions tracking status.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}

/**
 * Fetches the settings data from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSettings() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/settings`,
			} );

			dispatch(
				receiveSettings( {
					exportFileUrl: response.export_file_url,
					lastExportTimeStamp: response.last_export_timestamp,
					productsToken: response.products_token,
					trackConversions: Boolean( response.capi_enabled ),
					campaignCreated: Boolean( response.campaign_created ),
					triggerExport: Boolean( response.trigger_export ),
				} )
			);
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error fetching settings.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}

/**
 * Fetches the Reddit setup information from the API and dispatches the result.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSetup() {
	return fetchSetup;
}

/**
 * Asynchronous resolver to fetch existing Reddit ads accounts.
 *
 * Dispatches the received accounts to the store or handles API errors.
 *
 * @return {Function} Thunk function that performs the API call and dispatches actions.
 */
export function getExistingAdsAccounts() {
	return fetchExistingAdsAccounts;
}

/**
 * Asynchronous resolver to fetch existing business accounts from the API.
 *
 * Dispatches the received business accounts to the store.
 * Handles API errors and displays a localized error message if the request fails.
 *
 * @return {Function} Thunk function for Redux dispatch.
 */
export function getExistingBusinessAccounts() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/businesses`,
			} );

			dispatch( receiveExistingBusinessAccounts( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading existing business accounts.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}

/**
 * Asynchronous resolver to fetch existing pixel IDs from the API.
 *
 * Dispatches the received pixel IDs to the store.
 * Handles API errors and displays a localized error message if the request fails.
 *
 * @return {Function} Thunk function for Redux dispatch.
 */
export function getExistingPixels() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/pixels`,
			} );

			dispatch( receiveExistingPixels( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading existing pixel IDs.',
					'reddit-for-woocommerce'
				)
			);
		}
	};
}
