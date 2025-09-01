/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import './account-details.scss';
import AppNotice from '../app-notice';
import AppButton from '../app-button';

const AccountDetails = () => {
	const {
		business_id: businessId,
		business_name: businessName,
		ad_account_id: adsAccountId,
		ad_account_name: adsAccountName,
		pixel_id: pixelId,
	} = useRedditAccountConfig();

	if ( ! businessId && ! adsAccountId && ! pixelId ) {
		return (
			<AppNotice
				status="warning"
				isDismissible={ false }
				className="rfw-reddit-account-details__notice"
			>
				<p>
					{ __(
						"We couldn't find a Reddit Business Account connected to your user.",
						'reddit-for-woo'
					) }
				</p>
				<AppButton
					isPrimary
					text={ __( 'Create Business Account', 'reddit-for-woo' ) }
					onClick={ () => {
						window.open(
							'https://accounts.reddit.com/adsregister',
							'_blank',
							'noopener,noreferrer'
						);
					} }
				/>
			</AppNotice>
		);
	}

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
