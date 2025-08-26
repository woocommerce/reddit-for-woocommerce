/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useRedditAccountDetails from '~/hooks/useRedditAccountDetails';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		business_name: businessName,
		ad_account_id: adsId,
		ad_account_name: adsName,
		pixel_id: pixelId,
	} = useRedditAccountDetails();

	return (
		<div className="rfw-reddit-account-details">
			{ businessName && (
				<p>
					{ __( 'Business:', 'reddit-for-woo' ) } { businessName }
				</p>
			) }

			{ adsId && adsName && (
				<p>
					{ __( 'Ads Account:', 'reddit-for-woo' ) } { adsName } (
					{ adsId })
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
