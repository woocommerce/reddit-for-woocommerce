/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import { rfwData } from '~/constants';

/**
 * Renders the message shown when the store currency isn't supported by Reddit's Catalog API,
 * with a link to the WooCommerce currency settings and the list of supported currencies.
 *
 * @return {JSX.Element} The unsupported currency message.
 */
const UnsupportedCurrencyMessage = () => {
	const adminUrl = useAdminUrl();
	const supportedCurrencies = rfwData?.supportedCurrencies || [];

	return (
		<p>
			{ createInterpolateElement(
				sprintf(
					/* translators: %s: comma-separated list of supported currency codes */
					__(
						'Store currency is not supported by Reddit for WooCommerce. Please <link>change your store currency</link> to one of the supported options: %s.',
						'reddit-for-woocommerce'
					),
					supportedCurrencies.join( ', ' )
				),
				{
					link: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href={ `${ adminUrl }admin.php?page=wc-settings&tab=general` }
						/>
					),
				}
			) }
		</p>
	);
};

export default UnsupportedCurrencyMessage;
