/**
 * Internal dependencies
 */
import DisconnectAccountButton from './disconnect-account-button';
import useExistingPixels from '~/hooks/useExistingPixels';
import useRedditPixelId from '~/hooks/useRedditPixelId';

/**
 * ConnectExistingAccountActions component.
 *
 * @param {Object} props Props.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 * @return {JSX.Element} Footer component.
 */
const ConnectExistingAccountActions = ( { onDisconnected } ) => {
	const { existingPixels } = useExistingPixels();
	const { hasConnection } = useRedditPixelId();

	if ( ! hasConnection || existingPixels?.length <= 1 ) {
		return null;
	}

	return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
};

export default ConnectExistingAccountActions;
