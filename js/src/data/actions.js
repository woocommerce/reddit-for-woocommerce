/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE, STORE_KEY } from './constants';
import { handleApiError } from '~/utils/handleError';
import TYPES from './action-types';

/**
 * @typedef {import('../data/selectors').RedditAdsAccount} RedditAdsAccount
 */

/**
 * @typedef {import('../data/selectors').RedditBusinessAccount} RedditBusinessAccount
 */

/**
 * Creates an action to receive a Jetpack account.
 *
 * @param {Object} account - The Jetpack account object to be received.
 * @return {Object} Action object with type `TYPES.RECEIVE_ACCOUNTS_JETPACK` and the account payload.
 */
export function receiveJetpackAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
		account,
	};
}

/**
 * Creates an action to receive a Reddit account.
 *
 * @param {Object} redditAccount - The Reddit account data to be received.
 * @return {Object} Action object with type RECEIVE_REDDIT_ACCOUNT and the Reddit account payload.
 */
export function receiveRedditAccount( redditAccount ) {
	return {
		type: TYPES.RECEIVE_REDDIT_ACCOUNT,
		redditAccount,
	};
}

/**
 * Creates an action to receive the status of conversions tracking.
 *
 * @param {boolean} status - The status of conversions tracking, true if enabled, false otherwise.
 * @return {Object} Action object with type RECEIVE_TRACK_CONVERSIONS_STATUS.
 */
export function receiveTrackConversionsStatus( status ) {
	return {
		type: TYPES.RECEIVE_TRACK_CONVERSIONS_STATUS,
		status,
	};
}

/**
 * Creates an action to receive the setup data.
 *
 * @param {Object} setup - The setup data to be received.
 * @return {Object} Action object with type RECEIVE_SETUP and the setup data.
 */
export function receiveSetup( setup ) {
	return {
		type: TYPES.RECEIVE_SETUP,
		setup,
	};
}

/**
 * Creates an action to receive the settings data from the API.
 *
 * @param {Object} settings - Settings object, e.g., { trackConversions: boolean, triggerExport: boolean }.
 * @return {Object} Action object.
 */
export function receiveSettings( settings ) {
	return {
		type: TYPES.RECEIVE_SETTINGS,
		settings,
	};
}

/**
 * Creates an action to receive Reddit account config.
 *
 * @param {Object} redditAccountConfig - The Reddit account config to be received.
 * @return {Object} Action object with type RECEIVE_REDDIT_ACCOUNT_CONFIG and the Reddit account config.
 */
export function receiveRedditAccountConfig( redditAccountConfig ) {
	return {
		type: TYPES.RECEIVE_REDDIT_ACCOUNT_CONFIG,
		redditAccountConfig,
	};
}

/**
 * Creates an action to receive existing ads accounts.
 *
 * @param {Array<RedditAdsAccount>} accounts - The list or object of existing ads accounts.
 * @param {string} businessId - The Reddit business ID associated with the ads accounts.
 * @return {Object} Redux action with type RECEIVE_EXISTING_ADS_ACCOUNTS and accounts payload.
 */
export function receiveExistingAdsAccounts( accounts, businessId ) {
	return {
		type: TYPES.RECEIVE_EXISTING_ADS_ACCOUNTS,
		accounts,
		businessId,
	};
}

/**
 * Creates an action to receive existing business accounts.
 *
 * @param {Array<RedditBusinessAccount>} accounts - The list or object of business accounts to be received.
 * @return {Object} Action object with type and accounts payload.
 */
export function receiveExistingBusinessAccounts( accounts ) {
	return {
		type: TYPES.RECEIVE_EXISTING_BUSINESS_ACCOUNTS,
		accounts,
	};
}

/**
 * Update the conversions tracking status.
 *
 * @param {boolean} status The status of the conversions tracking.
 * @return {Object} Action object to update the conversions tracking status.
 */
export async function updateTrackConversionsStatus( status ) {
	try {
		await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/settings`,
			method: 'POST',
			data: {
				capi_enabled: status,
			},
		} );

		return receiveTrackConversionsStatus( status );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the conversions tracking status.',
				'reddit-for-woo'
			)
		);
		throw error;
	}
}

/**
 * Updates one or more settings on the server.
 *
 * @param {Object} updatedSettings - Partial settings to update, e.g. { trackConversions: true }.
 * @return {Function} Action object to update settings locally.
 */
export async function updateSettings( updatedSettings ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/settings`,
			method: 'POST',
			data: {
				// Convert settings keys to match REST keys
				capi_enabled: updatedSettings.trackConversions,
			},
		} );

		return receiveSettings( {
			trackConversions: Boolean( response.capi_enabled ),
		} );
	} catch ( error ) {
		handleApiError(
			error,
			__( 'There was an error updating the settings.', 'reddit-for-woo' )
		);
		throw error;
	}
}

/**
 * Fetches the Reddit account information from the API and dispatches the result.
 *
 * @function fetchRedditAccount
 * @return {Promise<void>} Resolves when the account information has been fetched and dispatched.
 */
export async function fetchRedditAccount() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/connection`,
		} );

		dispatch( STORE_KEY ).receiveRedditAccount( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Reddit account info.',
				'reddit-for-woo'
			)
		);
	}
}

/**
 * Fetches the Reddit setup information from the API and dispatches the result.
 *
 * @function fetchSetup
 * @return {Promise<void>} Resolves when the setup information has been fetched and dispatched.
 */
export async function fetchSetup() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/setup`,
		} );

		dispatch( STORE_KEY ).receiveSetup( response );
	} catch ( error ) {
		handleApiError(
			error,
			__( 'There was an error loading Reddit setup.', 'reddit-for-woo' )
		);
	}
}

/**
 * Disconnect the connected Reddit account.
 *
 * @param {boolean} [invalidateRelatedState=false] Whether to invalidate related state in wp-data store.
 * @throws Will throw an error if the request failed.
 */
export async function disconnectRedditAccount(
	invalidateRelatedState = false
) {
	try {
		await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/connection`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_REDDIT,
			invalidateRelatedState,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__( 'Unable to disconnect your Reddit account.', 'reddit-for-woo' )
		);
		throw error;
	}
}

/**
 * Upserts (creates or updates) a business account configuration for Reddit integration.
 *
 * Sends a POST request to the Reddit config API endpoint with the provided business ID.
 * On success, dispatches the received Reddit account configuration.
 * On failure, handles the API error and throws it.
 *
 * @async
 * @param {string} businessAccountId - The unique identifier for the business.
 * @return {Object} The updated Reddit account configuration.
 * @throws Will throw an error if the API request fails.
 */
export async function upsertBusinessAccount( businessAccountId ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/config`,
			method: 'POST',
			data: {
				business_id: businessAccountId,
			},
		} );

		return receiveRedditAccountConfig( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error connecting your business account.',
				'reddit-for-woo'
			)
		);
		throw error;
	}
}

/**
 * Upserts (creates or updates) a Reddit ads account configuration.
 *
 * Sends a POST request to the Reddit config API endpoint with the provided
 * ads account ID, then dispatches the received configuration.
 * Handles API errors and throws them after displaying an error message.
 *
 * @async
 * @param {string} adsAccountId - The ID of the Reddit ads account.
 * @return {Object} The received Reddit account configuration.
 * @throws {Error} If there is an error connecting the ad account.
 */
export async function upsertAdsAccount( adsAccountId ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/config`,
			method: 'POST',
			data: {
				ad_account_id: adsAccountId,
			},
		} );

		return receiveRedditAccountConfig( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error connecting your Ad account.',
				'reddit-for-woo'
			)
		);
		throw error;
	}
}

export async function disconnectBusinessAccount() {
	return dispatch( STORE_KEY ).upsertBusinessAccount( '' );
}
