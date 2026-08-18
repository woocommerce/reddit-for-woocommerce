/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import usePreference from '~/hooks/usePreference';
import redditLogoURL from '~/images/logo/reddit.svg';
import { recordRfwEvent } from '~/utils/tracks';
import {
	CHANNEL_VISIBILITY_CONTEXT,
	CHANNEL_VISIBILITY_PROMO_KEY,
} from './constants';
import ChannelVisibilitySettings from './channel-visibility-settings';
import GetStartedCTA from './get-started-cta';
import PromoCTA from './promo-cta';
import './reddit-ads-promo.scss';

/**
 * Reddit Ads Promo banner is shown.
 *
 * Fires on every meta box load, including after onboarding when the settings
 * panel is shown instead of the promo, and when the promo has been dismissed.
 *
 * @event rfw_reddit_ads_promo_shown
 * @property {string} context Context of the Reddit Ads Promo.
 */

/**
 * Reddit Ads Promo component.
 *
 * @fires rfw_reddit_ads_promo_shown with `{ context: channel-visibility-meta-box }`.
 *
 * @return {JSX.Element} The Reddit Ads Promo component.
 */
const RedditAdsPromo = () => {
	const { onboardingComplete = false } = window.redditAdsMetaBoxData || {};
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( CHANNEL_VISIBILITY_PROMO_KEY );
	const hasTrackedRef = useRef( false );

	useEffect( () => {
		if ( ! hasTrackedRef.current ) {
			recordRfwEvent( 'rfw_reddit_ads_promo_shown', {
				context: CHANNEL_VISIBILITY_CONTEXT,
			} );
			hasTrackedRef.current = true;
		}
	}, [] );

	const handleDismiss = () => {
		set( PREFERENCES_STORE_NAMESPACE, CHANNEL_VISIBILITY_PROMO_KEY, true );
	};

	if ( onboardingComplete ) {
		return <ChannelVisibilitySettings />;
	}

	return (
		<Flex className="rfw-channel-visibility" direction="column" gap={ 4 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<img
							className="rfw-channel-visibility__logo"
							src={ redditLogoURL }
							alt={ __(
								'Reddit Logo',
								'reddit-for-woocommerce'
							) }
							width={ 16 }
							height={ 16 }
						/>
					</FlexItem>
					<FlexItem>
						{ __( 'Reddit', 'reddit-for-woocommerce' ) }
					</FlexItem>
					{ isDismissed && (
						<FlexItem className="rfw-channel-visibility__get-started--is-dismissed">
							<GetStartedCTA />
						</FlexItem>
					) }
				</Flex>
			</FlexBlock>

			{ ! isDismissed && (
				<Flex
					className="rfw-channel-visibility__content"
					direction="column"
					gap={ 3 }
				>
					<FlexBlock>
						<h3 className="rfw-channel-visibility__title">
							{ __(
								'Get your products on Reddit',
								'reddit-for-woocommerce'
							) }
						</h3>
					</FlexBlock>
					<FlexBlock>
						<p>
							{ __(
								'Sync your catalog to reach shoppers actively discovering new brands and products on Reddit.',
								'reddit-for-woocommerce'
							) }
						</p>
					</FlexBlock>
					<FlexBlock>
						<PromoCTA onDismiss={ handleDismiss } />
					</FlexBlock>
				</Flex>
			) }
		</Flex>
	);
};

export default RedditAdsPromo;
