/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import redditLogoURL from '~/images/logo/reddit.svg';
import { recordRfwEvent } from '~/utils/tracks';
import { getCampaignCreateUrl, getGetStartedUrl } from '~/utils/urls';
import { ORDER_ATTRIBUTION_CONTEXT } from './constants';
import './reddit-ads-promo.scss';

/**
 * Reddit Ads Promo component is shown.
 *
 * @event rfw_reddit_ads_promo_shown
 * @property {string} context Context of the Reddit Ads Promo.
 */

/**
 * Reddit Ads Promo "Get started" button is clicked.
 *
 * @event rfw_reddit_ads_promo_get_started_click
 * @property {string} context Context of the Reddit Ads Promo.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Reddit Ads Promo "Create campaign" button is clicked.
 *
 * @event rfw_reddit_ads_promo_create_campaign_click
 * @property {string} context Context of the Reddit Ads Promo.
 * @property {string} href URL of the "Create campaign" button.
 */

/**
 * Reddit Ads Promo component.
 *
 * Renders one of three states:
 *  - Not onboarded: nudge to get started.
 *  - Onboarded, no campaign: nudge to create a campaign.
 *  - Onboarded with campaign: returns null (no banner).
 *
 * @fires rfw_reddit_ads_promo_shown with `{ context: 'order-attribution-meta-box' }`.
 * @fires rfw_reddit_ads_promo_get_started_click with `{ context: 'order-attribution-meta-box', href: 'admin.php?page=wc-admin&path=%2Freddit%2Fstart' }`.
 * @fires rfw_reddit_ads_promo_create_campaign_click with `{ context: 'order-attribution-meta-box', href: 'admin.php?page=wc-admin&path=%2Freddit%2Fcampaigns%2Fcreate' }`.
 *
 * @return {JSX.Element|null} The Reddit Ads Promo component or null.
 */
const RedditAdsPromo = () => {
	const { onboardingComplete = false, hasCampaign = false } =
		window.redditAdsMetaBoxData || {};

	const hasTrackedRef = useRef( false );
	const shouldShowPromo = ! ( onboardingComplete && hasCampaign );

	useEffect( () => {
		if ( ! hasTrackedRef.current && shouldShowPromo ) {
			recordRfwEvent( 'rfw_reddit_ads_promo_shown', {
				context: ORDER_ATTRIBUTION_CONTEXT,
			} );
			hasTrackedRef.current = true;
		}
	}, [ shouldShowPromo ] );

	if ( ! shouldShowPromo ) {
		return null;
	}

	const campaignCreateUrl = getCampaignCreateUrl();
	const startUrl = getGetStartedUrl();
	let content;

	if ( onboardingComplete && ! hasCampaign ) {
		content = {
			title: __(
				'Get more sales with Reddit Ads',
				'reddit-for-woocommerce'
			),
			description: __(
				'Launch a Reddit Ads campaign and get your products discovered by highly engaged communities actively looking for what to buy next.',
				'reddit-for-woocommerce'
			),
			cta: (
				<AppButton
					href={ campaignCreateUrl }
					eventName="rfw_reddit_ads_promo_create_campaign_click"
					eventProps={ {
						href: campaignCreateUrl,
						context: ORDER_ATTRIBUTION_CONTEXT,
					} }
					isSecondary
				>
					{ __( 'Create campaign', 'reddit-for-woocommerce' ) }
				</AppButton>
			),
		};
	} else {
		content = {
			title: __(
				'Your next customers are on Reddit',
				'reddit-for-woocommerce'
			),
			description: __(
				'Sync your catalog to reach shoppers actively discovering new brands and products on Reddit.',
				'reddit-for-woocommerce'
			),
			cta: (
				<AppButton
					href={ startUrl }
					eventName="rfw_reddit_ads_promo_get_started_click"
					eventProps={ {
						href: startUrl,
						context: ORDER_ATTRIBUTION_CONTEXT,
					} }
					isSecondary
				>
					{ __( 'Get started', 'reddit-for-woocommerce' ) }
				</AppButton>
			),
		};
	}

	const { title, description, cta } = content;

	return (
		<Flex
			className="rfw-order-attribution-promo"
			direction="column"
			gap={ 3 }
		>
			<FlexBlock>
				<Flex gap={ 2 } align="center">
					<FlexItem>
						<img
							className="rfw-order-attribution-promo__logo"
							src={ redditLogoURL }
							alt={ __(
								'Reddit Logo',
								'reddit-for-woocommerce'
							) }
							width={ 24 }
							height={ 24 }
						/>
					</FlexItem>
					<FlexBlock>
						<h3 className="rfw-order-attribution-promo__title">
							{ title }
						</h3>
					</FlexBlock>
				</Flex>
			</FlexBlock>
			<FlexBlock>
				<p>{ description }</p>
			</FlexBlock>
			<FlexBlock>{ cta }</FlexBlock>
		</Flex>
	);
};

export default RedditAdsPromo;
