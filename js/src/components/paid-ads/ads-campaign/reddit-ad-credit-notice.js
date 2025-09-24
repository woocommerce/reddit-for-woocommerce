/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import giftUrl from '~/images/logo/gift.svg';
import './reddit-ad-credit-notice.scss';

export default function RedditAdCreditNotice() {
	return (
		<div className="rfw-paid-ads-claim-credit-notice">
			<div>
				{ /* eslint-disable-next-line jsx-a11y/alt-text */ }
				<img src={ giftUrl } />
			</div>
			<div>
				{ __(
					'New to Reddit Ads? You might be eligible for ad credits when you spend a certain amount in your first 60 days.',
					'reddit-for-woocommerce'
				) }
				<br />
				<a
					target="_blank"
					href="https://ads.reddit.com/billing"
					rel="noreferrer"
				>
					{ __( 'Check your eligibility', 'reddit-for-woocommerce' ) }
				</a>
			</div>
		</div>
	);
}
