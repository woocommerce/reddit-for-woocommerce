/**
 * Internal dependencies
 */
import useRedditAccountConfig from './useRedditAccountConfig';

/**
 * @typedef {Object} RedditBusinessAccount
 * @property {string} business_id The Reddit business ID.
 * @property {string} business_name The name of the Reddit business.
 */

/**
 * Hook to retrieve the Reddit Business account status and connection information.
 * @return {RedditBusinessAccount} The Reddit Business account.
 */
const useRedditBusinessAccount = () => {
	const {
		business_id: businessId,
		business_name: businessName,
		hasFinishedResolution,
	} = useRedditAccountConfig();

	return {
		businessId,
		businessName,
		hasConnection: !! businessId,
		hasFinishedResolution,
	};
};

export default useRedditBusinessAccount;
