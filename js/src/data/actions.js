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
 * Creates an action to receive Reddit account details.
 *
 * @param {Object} redditAccountDetails - The Reddit account details to be received.
 * @return {Object} Action object with type RECEIVE_REDDIT_ACCOUNT_DETAILS and the Reddit account details.
 */
export function receiveRedditAccountDetails( redditAccountDetails ) {
	return {
		type: TYPES.RECEIVE_REDDIT_ACCOUNT_DETAILS,
		redditAccountDetails,
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
