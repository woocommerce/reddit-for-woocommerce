/**
 * Internal dependencies
 */
import useRedditBusinessAccount from './useRedditBusinessAccount';
import useExistingBusinessAccounts from './useExistingBusinessAccounts';

/**
 * Custom hook to determine if the user should connect a business account.
 *
 * Returns `null` if the Reddit business account or existing business accounts have not finished resolving.
 * Otherwise, returns `true` if there is no current connection and a single existing account,
 * indicating that the user should connect a business account.
 *
 * @return {boolean|null} `true` if the user should connect a business account, `false` otherwise, or `null` if still resolving.
 */
const useShouldConnectBusinessAccount = () => {
	const { hasFinishedResolution, hasConnection } = useRedditBusinessAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		existingAccounts,
	} = useExistingBusinessAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasFinishedResolution || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && existingAccounts?.length === 1;
};

export default useShouldConnectBusinessAccount;
