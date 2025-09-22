/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AdsCampaign from '~/components/paid-ads/ads-campaign';

const SetupPaidAds = ( { onContinue } ) => {
	return (
		<AdsCampaign
			headerTitle={ __(
				'Create a Reddit campaign to promote your products',
				'reddit-for-woocommerce'
			) }
			onContinue={ onContinue }
		/>
	);
};

export default SetupPaidAds;
