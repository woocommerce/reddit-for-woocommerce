/**
 * External dependencies
 */
import { Flex, FlexBlock } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { CHANNEL_VISIBILITY_CONTEXT } from './constants';
import GetStartedCTA from './get-started-cta';

/**
 * Reddit Ads Promo "Dismiss" button is clicked.
 *
 * @event rfw_reddit_ads_promo_dismiss_click
 * @property {string} context Context of the Reddit Ads Promo.
 */

/**
 * Reddit Ads Promo CTA component.
 *
 * @fires rfw_reddit_ads_promo_dismiss_click with `{ context: channel-visibility-meta-box }`.
 * @param {Function} onDismiss The function to call when the dismiss button is clicked.
 *
 * @return {JSX.Element} The Reddit Ads Promo CTA component.
 */
const PromoCTA = ( { onDismiss } ) => {
	return (
		<Flex gap={ 3 } align="flex-start">
			<FlexBlock>
				<GetStartedCTA />
			</FlexBlock>

			<FlexBlock>
				<AppButton
					eventName="rfw_reddit_ads_promo_dismiss_click"
					eventProps={ {
						context: CHANNEL_VISIBILITY_CONTEXT,
					} }
					onClick={ onDismiss }
					isTertiary
				>
					{ __( 'Dismiss', 'reddit-for-woocommerce' ) }
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default PromoCTA;
