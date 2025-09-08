/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import ConnectExistingAccount from './connect-existing-account';
import UpsertingAccount from './upserting-account';

/**
 * ConnectAds component renders an account card to connect to an existing Reddit Ads account.
 *
 * @param {Object} props Component props.
 * @param {string|null} props.upsertingAction The action the user is performing. Possible values are 'create', 'update', or null.
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectAds = ( { upsertingAction } ) => {
	if ( upsertingAction ) {
		return <UpsertingAccount upsertingAction={ upsertingAction } />;
	}

	return <ConnectExistingAccount />;
};

export default ConnectAds;
