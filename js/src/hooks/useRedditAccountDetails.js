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

const selectorName = 'getRedditAccountDetails';

/**
 * @typedef {import('../data/selectors').RedditAccountDetails} RedditAccountDetailsObject
 */

/**
 * @typedef {Object} RedditAccountDetailsState
 * @property {RedditAccountDetailsObject} details The Reddit account details.
 * @property {Function} refetchRedditAccountDetails Function to refetch the Reddit account details.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Reddit account details and its resolution status.
 * @return {RedditAccountDetailsState} The Reddit account details and its state.
 */
const useRedditAccountDetails = () => {
	const dispatcher = useAppDispatch();
	const refetchRedditAccountDetails = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const details = selector[ selectorName ]();

			return {
				...details,
				refetchRedditAccountDetails,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchRedditAccountDetails ]
	);
};

export default useRedditAccountDetails;
