/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getGetStartedUrl } from '~/utils/urls';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';

/**
 * Reddit Ads Promo "Get started" button is clicked.
 *
 * @event rfw_reddit_ads_promo_get_started_click
 * @property {string} context Context of the Reddit Ads Promo.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Get Started CTA component.
 *
 * @fires rfw_reddit_ads_promo_get_started_click with `{ context: channel-visibility-meta-box, href: 'admin.php?page=wc-admin&path=%2Freddit%2Fstart' }`.
 *
 * @return {JSX.Element} The Get Started CTA component.
 */
const GetStartedCTA = () => {
	const setupUrl = getGetStartedUrl();

	return (
		<AppButton
			href={ setupUrl }
			eventName="rfw_reddit_ads_promo_get_started_click"
			eventProps={ {
				href: setupUrl,
				context: CHANNEL_VISIBILITY_CONTEXT,
			} }
			isSecondary
		>
			{ __( 'Get started', 'reddit-for-woocommerce' ) }
		</AppButton>
	);
};

export default GetStartedCTA;
