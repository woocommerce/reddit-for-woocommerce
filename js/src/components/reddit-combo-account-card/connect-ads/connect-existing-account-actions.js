/**
 * Internal dependencies
 */
import DisconnectAccountButton from './disconnect-account-button';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';

/**
 * Footer component.
 *
 * @param {Object} props Props.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 * @return {JSX.Element} Footer component.
 */
const ConnectExistingAccountActions = ( { onDisconnected } ) => {
	const { existingAccounts } = useExistingAdsAccounts();
	const { hasConnection } = useRedditAdsAccount();

	if ( ! hasConnection || existingAccounts?.length <= 1 ) {
		return null;
	}

	return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
};

export default ConnectExistingAccountActions;
