/**
 * Internal dependencies
 */
import useRedditAdsAccount from './useRedditAdsAccount';
import useExistingAdsAccounts from './useExistingAdsAccounts';

/**
 * Custom hook to determine if the user should connect an ads account.
 *
 * Returns `null` if the Reddit ads account or existing ads accounts have not finished resolving.
 * Otherwise, returns `true` if there is no current connection and a single existing account,
 * indicating that the user should connect an ads account.
 *
 * @return {boolean|null} `true` if the user should connect an ads account, `false` otherwise, or `null` if still resolving.
 */
const useShouldConnectAdsAccount = () => {
	const { hasFinishedResolution, hasConnection } = useRedditAdsAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		existingAccounts,
	} = useExistingAdsAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasFinishedResolution || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && existingAccounts?.length === 1;
};

export default useShouldConnectAdsAccount;
