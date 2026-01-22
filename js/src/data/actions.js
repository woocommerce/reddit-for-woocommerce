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
import { ACCOUNT_TYPE } from '~/constants';
import { isWCIos, isWCAndroid } from '~/utils/isMobileApp';
import TYPES from './action-types';

/**
 * @typedef {import('../data/selectors').RedditPixel} RedditPixel
 */

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
 * @return {Object} Redux action with type RECEIVE_EXISTING_ADS_ACCOUNTS and accounts payload.
 */
export function receiveExistingAdsAccounts( accounts ) {
	return {
		type: TYPES.RECEIVE_EXISTING_ADS_ACCOUNTS,
		accounts,
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
 * Creates an action to receive existing pixel IDs.
 *
 * @param {Array<RedditPixel>} pixels - The list or object of pixel IDs to be received.
 * @return {Object} Action object with type and accounts payload.
 */
export function receiveExistingPixels( pixels ) {
	return {
		type: TYPES.RECEIVE_EXISTING_PIXELS,
		pixels,
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
				'reddit-for-woocommerce'
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
				products_token: updatedSettings.productsToken,
			},
		} );

		return receiveSettings( {
			exportFileUrl: response.export_file_url,
			lastExportTimeStamp: response.last_export_timestamp,
			productsToken: response.products_token,
			trackConversions: Boolean( response.capi_enabled ),
			campaignCreated: Boolean( response.campaign_created ),
			triggerExport: Boolean( response.trigger_export ),
		} );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the settings.',
				'reddit-for-woocommerce'
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
				'reddit-for-woocommerce'
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
			__(
				'There was an error loading Reddit setup.',
				'reddit-for-woocommerce'
			)
		);
	}
}

/**
 * Fetches existing Reddit ads accounts via API and dispatches them to the store.
 * Handles API errors gracefully.
 *
 * @function fetchExistingAdsAccounts
 * @return {Promise<void>} Resolves when the accounts are fetched and dispatched.
 */
export async function fetchExistingAdsAccounts() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/ad_accounts`,
		} );

		dispatch( STORE_KEY ).receiveExistingAdsAccounts( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading existing ads accounts.',
				'reddit-for-woocommerce'
			)
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
			__(
				'Unable to disconnect your Reddit account.',
				'reddit-for-woocommerce'
			)
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
 * @param {string} businessAccountName - The name of the business account.
 * @return {Object} The updated Reddit account configuration.
 * @throws Will throw an error if the API request fails.
 */
export async function upsertBusinessAccount(
	businessAccountId,
	businessAccountName
) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/config`,
			method: 'POST',
			data: {
				business_id: businessAccountId,
				business_name: businessAccountName,
			},
		} );

		await fetchExistingAdsAccounts();
		return receiveRedditAccountConfig( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error connecting your business account.',
				'reddit-for-woocommerce'
			)
		);
		throw error;
	}
}

/**
 * Resets the Reddit account configuration by sending empty values to the API.
 *
 * Sends a POST request to the Reddit config API endpoint with empty values for
 * business ID, business name, ad account ID, ad account name, and pixel ID.
 * On success, dispatches the received Reddit account configuration.
 * On failure, handles the API error and throws it.
 *
 * @async
 * @function resetRedditAccountConfig
 * @return {Promise<Object>} The updated Reddit account configuration.
 * @throws {Error} If the API request fails.
 */
export async function resetRedditAccountConfig(
	accountType = ACCOUNT_TYPE.BUSINESS
) {
	try {
		let data = {};
		switch ( accountType ) {
			case ACCOUNT_TYPE.BUSINESS:
				data = {
					business_id: '',
					business_name: '',
					ad_account_id: '',
					ad_account_name: '',
					pixel_id: '',
				};
				break;
			case ACCOUNT_TYPE.ADS:
				data = {
					ad_account_id: '',
					ad_account_name: '',
					pixel_id: '',
				};
				break;
			case ACCOUNT_TYPE.PIXEL:
				data = {
					pixel_id: '',
				};
				break;
		}

		if ( Object.keys( data ).length > 0 ) {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/reddit/config`,
				method: 'POST',
				data,
			} );

			return receiveRedditAccountConfig( response );
		}
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error disconnecting your account.',
				'reddit-for-woocommerce'
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
 * The pixel ID is set to be the same as the ads account ID.
 *
 * @async
 * @param {string} adsAccountId - The ID of the Reddit ads account.
 * @param {string} adsAccountName - The name of the Reddit ads account.
 * @return {Object} The received Reddit account configuration.
 * @throws {Error} If there is an error connecting the ad account.
 */
export async function upsertAdsAccount( adsAccountId, adsAccountName ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/config`,
			method: 'POST',
			data: {
				ad_account_id: adsAccountId,
				ad_account_name: adsAccountName,

				// pixel ID is same as the adsAccountID
				pixel_id: adsAccountId,
			},
		} );

		return receiveRedditAccountConfig( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error connecting your Ad account.',
				'reddit-for-woocommerce'
			)
		);
		throw error;
	}
}

/**
 * Upserts (creates or updates) a Reddit pixel ID configuration.
 *
 * Sends a POST request to the Reddit config API endpoint with the provided
 * pixel ID, then dispatches the received configuration.
 * Handles API errors and throws them after displaying an error message.
 *
 * @async
 * @param {string} pixelId - The ID of the Reddit pixel ID.
 * @return {Object} The received Reddit account configuration.
 * @throws {Error} If there is an error connecting the pixel ID.
 */
export async function upsertPixelId( pixelId ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/config`,
			method: 'POST',
			data: {
				pixel_id: pixelId,
			},
		} );

		return receiveRedditAccountConfig( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error connecting your Pixel ID.',
				'reddit-for-woocommerce'
			)
		);
		throw error;
	}
}

/**
 * Completes the setup accounts.
 *
 * @return {Promise<Object>} The updated setup data.
 * @throws {Error} If the API request fails.
 */
export async function completeSetupAccounts() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/reddit/setup/complete`,
			method: 'POST',
		} );

		return receiveSetup( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error completing your setup. Please try again.',
				'reddit-for-woocommerce'
			)
		);
		throw error;
	}
}

/**
 * Create a new ads campaign.
 *
 * @param {number} amount Daily average cost of the paid ads campaign.
 *
 * @throws { { message: string } } Will throw an error if the campaign creation fails.
 */
export function* createAdsCampaign( amount ) {
	let label = 'wc-web';

	if ( isWCIos() ) {
		label = 'wc-ios';
	} else if ( isWCAndroid() ) {
		label = 'wc-android';
	}

	try {
		const createdCampaign = yield apiFetch( {
			path: `${ API_NAMESPACE }/reddit/campaigns`,
			method: 'POST',
			data: {
				amount,
				label,
			},
		} );

		return {
			type: TYPES.CREATE_ADS_CAMPAIGN,
			createdCampaign,
		};
	} catch ( error ) {
		handleApiError( error );

		throw error;
	}
}
