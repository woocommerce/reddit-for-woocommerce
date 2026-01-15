/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Icon, info } from '@wordpress/icons';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders the Billing Notice component.
 */
const BillingNotice = () => {
	return (
		<div className="rfw-billing-notice">
			<Icon icon={ info } />

			<p>
				{ createInterpolateElement(
					__(
						'To run ads on Reddit, you need to have billing information set up in your Reddit Ads account. If you haven\'t set up billing yet, please set it up from <link>here</link> before continuing.',
						'reddit-for-woocommerce'
					),
					{
						link: (
							<Link
								href="https://ads.reddit.com/billing"
								target="_blank"
								rel="noopener noreferrer"
								external
							/>
						),
					}
				) }
			</p>
		</div>
	);
};

export default BillingNotice;
