/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useRedditAccount from '~/hooks/useRedditAccount';
import RedditAds from '~/pages/settings/reddit-ads';
import { getGetStartedUrl } from '~/utils/urls';
import AppSpinner from '~/components/app-spinner';
import './index.scss';

const CreateCampaign = () => {
	const { isConnected, hasFinishedResolution } = useRedditAccount();

	useEffect( () => {
		// Redirect to get started page if no reddit account is connected.
		if ( ! isConnected && hasFinishedResolution ) {
			getHistory().replace( getGetStartedUrl() );
		}
	}, [ isConnected, hasFinishedResolution ] );

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<div className="rfw-campaigns-create">
			<RedditAds />
		</div>
	);
};

export default CreateCampaign;
