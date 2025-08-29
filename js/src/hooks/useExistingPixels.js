/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useRedditAdsAccount from './useRedditAdsAccount';
import useRedditBusinessAccount from './useRedditBusinessAccount';

const selectorName = 'getExistingPixels';

const useExistingPixels = () => {
	const {
		hasConnection: hasBusinessConnection,
		hasFinishedResolution: hasResolvedBusinessAccount,
	} = useRedditBusinessAccount();
	const {
		hasConnection: hasAdsConnection,
		hasFinishedResolution: hasResolvedAdsAccount,
	} = useRedditAdsAccount();

	return useSelect(
		( select ) => {
			if (
				! hasBusinessConnection ||
				! hasResolvedBusinessAccount ||
				! hasAdsConnection ||
				! hasResolvedAdsAccount
			) {
				return {
					existingPixels: null,
					hasFinishedResolution:
						hasResolvedBusinessAccount && hasResolvedAdsAccount,
				};
			}

			const selector = select( STORE_KEY );
			return {
				existingPixels: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[
			hasBusinessConnection,
			hasResolvedBusinessAccount,
			hasAdsConnection,
			hasResolvedAdsAccount,
		]
	);
};

export default useExistingPixels;
