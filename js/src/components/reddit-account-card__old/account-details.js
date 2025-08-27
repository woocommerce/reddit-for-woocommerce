/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		business_name: businessName,
		ad_account_id: adsAccountId,
		ad_account_name: adsAccountName,
		pixel_id: pixelId,
	} = useRedditAccountConfig();

	return (
		<div className="rfw-reddit-account-details">
			{ businessName && (
				<p>
					{ __( 'Business:', 'reddit-for-woo' ) } { businessName }
				</p>
			) }

			{ adsAccountId && adsAccountName && (
				<p>
					{ __( 'Ads Account:', 'reddit-for-woo' ) }{ ' ' }
					{ adsAccountName } ({ adsAccountId })
				</p>
			) }

			{ pixelId && (
				<p>
					{ __( 'Pixel ID:', 'reddit-for-woo' ) } { pixelId }
				</p>
			) }
		</div>
	);
};

export default AccountDetails;
