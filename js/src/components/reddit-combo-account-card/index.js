/**
 * Internal dependencies
 */
import useRedditAccountStatus from '~/hooks/useRedditAccountStatus';
import ConnectRedditComboAccountCard from './connect-reddit-combo-account-card';
import ConnectedRedditComboAccountCard from './connected-reddit-combo-account-card';

const RedditComboAccountCard = ( { disabled = false } ) => {
	const { isConnected } = useRedditAccountStatus();

	if ( isConnected ) {
		return <ConnectedRedditComboAccountCard />;
	}

	return <ConnectRedditComboAccountCard disabled={ disabled } />;
};

export default RedditComboAccountCard;
