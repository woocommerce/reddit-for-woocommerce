/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { REDDIT_ACCOUNT_STATUS, SETUP_STATUS } from '~/constants';
import useSetup from './useSetup';

const selectorName = 'getRedditAccount';

/**
 * @typedef {import('../data/selectors').RedditAccount} RedditAccountObject
 */

/**
 * @typedef {Object} RedditAccountState
 * @property {RedditAccountObject} status The status of the Reddit account.
 * @property {boolean} isConnected Whether the Reddit account is connected and setup is complete.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Reddit account data and its resolution status.
 * @return {RedditAccountState} The Reddit account data and its state.
 */
const useRedditAccount = () => {
	const { data } = useSetup();
	const setupComplete = data?.status === SETUP_STATUS.CONNECTED;

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const account = selector[ selectorName ]();

			return {
				status: account?.status,
				isConnected:
					account?.status === REDDIT_ACCOUNT_STATUS.CONNECTED &&
					setupComplete,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ setupComplete ]
	);
};

export default useRedditAccount;
