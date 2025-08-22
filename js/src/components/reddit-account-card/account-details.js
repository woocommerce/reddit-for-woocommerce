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
		org_name: organizationName,
		ad_acc_id: adsId,
		ad_acc_name: adsName,
		pixel_id: pixelId,
	} = useRedditAccountDetails();

	return (
		<div className="rfw-reddit-account-details">
			{ organizationName && (
				<p>
					{ __( 'Organization:', 'reddit-for-woo' ) }{ ' ' }
					{ organizationName }
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
