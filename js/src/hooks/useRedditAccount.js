/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { REDDIT_ACCOUNT_STATUS } from '~/constants';

const selectorName = 'getRedditAccount';

/**
 * @typedef {Object} RedditAccountState
 * @property {'connected'|'disconnected'} status The status of the Reddit account.
 * @property {string} email The email of the Reddit account.
 * @property {boolean} isConnected Whether the Reddit account is connected and setup is complete.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Reddit account status and its resolution status.
 * @return {RedditAccountState} The Reddit account data and its state.
 */
const useRedditAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const account = selector[ selectorName ]();

		return {
			status: account?.status,
			email: account?.email,
			isConnected: account?.status === REDDIT_ACCOUNT_STATUS.CONNECTED,
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useRedditAccount;
