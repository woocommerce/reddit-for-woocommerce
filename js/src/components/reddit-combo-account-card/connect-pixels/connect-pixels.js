/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import ConnectExistingAccount from './connect-existing-account';
import UpsertingPixel from './upserting-pixel';

/**
 * ConnectPixels component renders an account card to connect to an existing Reddit Pixel ID.
 *
 * @param {Object} props Component props.
 * @param {string|null} props.upsertingAction The action the user is performing. Possible values are 'create', 'update', or null.
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectPixels = ( { upsertingAction } ) => {
	if ( upsertingAction ) {
		return <UpsertingPixel upsertingAction={ upsertingAction } />;
	}

	return <ConnectExistingAccount />;
};

export default ConnectPixels;
