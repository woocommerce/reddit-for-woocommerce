/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import './index.scss';

/**
 * @fires rfw_documentation_link_click with `{ context: 'setup-ads', link_id: 'free-ad-credit-terms', href: 'https://www.google.com/ads/coupons/terms/' }`
 */
const FreeAdCredit = () => {
	return (
		<div className="rfw-free-ad-credit">
			<GridiconGift />

			<div>
				<div className="rfw-free-ad-credit__description">
					{ createInterpolateElement(
						__(
							'New to Reddit Ads? You might be eligible for ad credits when you spend a certain amount in your first 60 days. <termLink>Check your eligibility TBD</termLink>',
							'reddit-for-woocommerce'
						),
						{
							termLink: (
								<AppDocumentationLink
									context="setup-ads"
									linkId="free-ad-credit-terms"
									href="https://ads.reddit.com/billing" // @TODO: review the link
								/>
							),
						}
					) }
				</div>
			</div>
		</div>
	);
};

export default FreeAdCredit;
