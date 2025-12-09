/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconGift from 'gridicons/dist/gift';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders the Free Ad Credit component.
 */
const FreeAdCredit = () => {
	return (
		<div className="rfw-free-ad-credit">
			<GridiconGift />

			<div>
				<div className="rfw-free-ad-credit__description">
					{ __(
						'New to Reddit Ads? You might be eligible for ad credits when you spend a certain amount in your first 60 days.',
						'reddit-for-woocommerce'
					) }
				</div>
			</div>
		</div>
	);
};

export default FreeAdCredit;
