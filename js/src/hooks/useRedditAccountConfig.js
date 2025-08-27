/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getRedditAccountConfig';

/**
 * @typedef {import('../data/selectors').RedditAccountConfig} RedditAccountConfig
 */

/**
 * @typedef {Object} RedditAccountConfigState
 * @property {RedditAccountConfig} details The Reddit account details.
 * @property {Function} refetchRedditAccountConfig Function to refetch the Reddit account details.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Reddit account config and its resolution status.
 * @return {RedditAccountConfigState} The Reddit account config and its state.
 */
const useRedditAccountConfig = () => {
	const dispatcher = useAppDispatch();
	const refetchRedditAccountConfig = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const details = selector[ selectorName ]();

			return {
				...details,
				refetchRedditAccountConfig,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchRedditAccountConfig ]
	);
};

export default useRedditAccountConfig;
