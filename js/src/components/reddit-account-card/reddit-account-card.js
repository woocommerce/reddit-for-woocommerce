/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useRedditAccount from '~/hooks/useRedditAccount';
import ConnectedRedditAccountCard from './connected-reddit-account-card';
import ConnectRedditAccountCard from './connect-reddit-account-card';

const RedditAccountCard = ( { disabled = false } ) => {
	const { isConnected } = useRedditAccount();
	const { config_id: configId } = getQuery();

	if ( isConnected ) {
		return <ConnectedRedditAccountCard />;
	}

	return (
		<ConnectRedditAccountCard disabled={ disabled } configId={ configId } />
	);
};

export default RedditAccountCard;
