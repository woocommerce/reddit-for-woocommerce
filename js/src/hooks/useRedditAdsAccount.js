/**
 * Internal dependencies
 */
import useRedditAccountConfig from './useRedditAccountConfig';

/**
 * @typedef {Object} RedditAdsAccount
 * @property {string} ad_account_id The Reddit ad account ID.
 * @property {string} ad_account_name The name of the Reddit ad account.
 */

/**
 * Hook to retrieve the Reddit Ads account status and connection information.
 * @return {RedditAdsAccount} The Reddit Ads account.
 */
const useRedditAdsAccount = () => {
	const {
		ad_account_id: adAccountId,
		ad_account_name: adAccountName,
		currency,
		symbol,
		hasFinishedResolution,
	} = useRedditAccountConfig();

	return {
		adAccountId,
		adAccountName,
		hasConnection: !! adAccountId,
		currency,
		symbol,
		hasFinishedResolution,
	};
};

export default useRedditAdsAccount;
