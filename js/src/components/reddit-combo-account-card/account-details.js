/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		business_id: businessId,
		ad_account_id: adsAccountId,
		pixel_id: pixelId,
	} = useRedditAccountConfig();
	const { existingAccounts: existingAdsAccounts } = useExistingAdsAccounts();
	const adsAccount = existingAdsAccounts?.find(
		( acc ) => acc.ad_account_id === adsAccountId
	);
	const adsAccountName = adsAccount?.ad_account_name;

	const { existingAccounts: existingBusinessAccounts } =
		useExistingBusinessAccounts();
	const businessAccount = existingBusinessAccounts?.find(
		( acc ) => acc.business_id === businessId
	);
	const businessName = businessAccount?.business_name;

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
