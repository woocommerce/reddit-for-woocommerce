/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getExistingBusinessAccounts';

const useExistingBusinessAccounts = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			existingAccounts: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useExistingBusinessAccounts;
