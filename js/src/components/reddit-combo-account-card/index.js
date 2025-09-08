/**
 * Internal dependencies
 */
import useRedditAccount from '~/hooks/useRedditAccount';
import ConnectRedditComboAccountCard from './connect-reddit-combo-account-card';
import ConnectedRedditComboAccountCard from './connected-reddit-combo-account-card';

const RedditComboAccountCard = ( { disabled = false } ) => {
	const { isConnected } = useRedditAccount();

	if ( isConnected ) {
		return <ConnectedRedditComboAccountCard />;
	}

	return <ConnectRedditComboAccountCard disabled={ disabled } />;
};

export default RedditComboAccountCard;
