/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useRedditBusinessAccount from './useRedditBusinessAccount';

const selectorName = 'getExistingAdsAccounts';

const useExistingAdsAccounts = () => {
	const {
		hasConnection: hasBusinessConnection,
		hasFinishedResolution: hasResolvedBusinessAccount,
	} = useRedditBusinessAccount();

	return useSelect(
		( select ) => {
			if ( ! hasBusinessConnection || ! hasResolvedBusinessAccount ) {
				return {
					existingAccounts: null,
					hasFinishedResolution: hasResolvedBusinessAccount,
				};
			}

			const selector = select( STORE_KEY );
			return {
				existingAccounts: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ hasBusinessConnection, hasResolvedBusinessAccount ]
	);
};

export default useExistingAdsAccounts;
