/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import LinkedAccounts from './linked-accounts';
import Section from '~/components/section';
import ProductCatalog from './product-catalog';
import ConversionsAPI from './conversions-api';
import RedditAds from './reddit-ads';
import useRedditAccount from '~/hooks/useRedditAccount';
import OnboardingSuccessModal from '~/components/onboarding-success-modal';
import { getOnboardingUrl } from '~/utils/urls';
import useMenuLinkUpdate from '~/hooks/useMenuLinkUpdate';
import './index.scss';
import Faqs from '~/components/paid-ads/ads-campaign/faqs';

const Settings = () => {
	// Make the component highlight SFW entry in the WC legacy menu.
	useMenuEffect();
	useMenuLinkUpdate();
	const { isConnected, hasFinishedResolution } = useRedditAccount();

	// Show onboarding success guide modal by visiting the path with a specific query `onboarding=success`.
	// For example: `/wp-admin/admin.php?page=wc-admin&path=%2Freddit%2Fsettings&onboarding=success`.
	const isOnboardingSuccessModalOpen = getQuery()?.onboarding === 'success';

	useEffect( () => {
		if ( ! isConnected && hasFinishedResolution ) {
			getHistory().replace( getOnboardingUrl() );
		}
	}, [ isConnected, hasFinishedResolution ] );

	return (
		<div className="rfw-settings">
			{ isOnboardingSuccessModalOpen && <OnboardingSuccessModal /> }

			<Section
				title={ __( 'Product Catalog', 'reddit-for-woocommerce' ) }
			>
				<ProductCatalog />
			</Section>

			<Section title={ __( 'Reddit Ads', 'reddit-for-woocommerce' ) }>
				<RedditAds />
			</Section>

			<Section
				title={ __( 'Track Conversions', 'reddit-for-woocommerce' ) }
				description={ __(
					'Manage how conversions are tracked on your site.',
					'reddit-for-woocommerce'
				) }
			>
				<ConversionsAPI />
			</Section>

			<Section
				title={ __(
					'Manage Reddit Connection',
					'reddit-for-woocommerce'
				) }
				description={ __(
					'See your currently connected account or disconnect.',
					'reddit-for-woocommerce'
				) }
			>
				<LinkedAccounts />
			</Section>

			<Section>
				<Faqs />
			</Section>
		</div>
	);
};

export default Settings;
