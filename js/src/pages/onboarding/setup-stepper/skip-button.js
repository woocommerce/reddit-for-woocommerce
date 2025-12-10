/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';
import AppButton from '~/components/app-button';
import SkipPaidAdsConfirmationModal from './skip-paid-ads-confirmation-modal';
import { recordRfwEvent } from '~/utils/tracks';

/**
 * Clicking on the skip paid ads button to complete the onboarding flow.
 * The 'unknown' value of properties may means:
 * - the final status has not yet been resolved when recording this event
 * - the status is not available, for example, the billing status is unknown if Reddit Ads account is not yet connected
 *
 * @event rfw_onboarding_complete_button_click
 * @property {string} reddit_ads_account_status The connection status of merchant's Reddit Ads account, e.g. 'connected', 'disconnected', 'incomplete'
 * @property {string} billing_method_status The status of billing method of merchant's Reddit Ads account e.g. 'unknown', 'pending', 'approved', 'cancelled'
 * @property {string} campaign_form_validation Whether the entered paid campaign form data are valid, e.g. 'unknown', 'valid', 'invalid'
 */

export default function SkipButton( {
	isValidForm,
	onSkipCreatePaidAds = noop,
	loading,
	disabled,
} ) {
	const [
		showSkipPaidAdsConfirmationModal,
		setShowSkipPaidAdsConfirmationModal,
	] = useState( false );
	const { redditAdsAccount } = useRedditAdsAccount();

	const handleOnSkipClick = () => {
		setShowSkipPaidAdsConfirmationModal( true );
	};

	const handleCancelSkipPaidAdsClick = () => {
		setShowSkipPaidAdsConfirmationModal( false );
	};

	const handleSkipCreatePaidAds = () => {
		setShowSkipPaidAdsConfirmationModal( false );

		const eventProps = {
			reddit_ads_account_status: redditAdsAccount?.status,
			campaign_form_validation: isValidForm ? 'valid' : 'invalid',
		};
		recordRfwEvent( 'rfw_onboarding_complete_button_click', eventProps );

		onSkipCreatePaidAds();
	};

	return (
		<>
			<AppButton
				isTertiary
				text={ __( 'Skip ads creation', 'reddit-for-woocommerce' ) }
				loading={ loading }
				disabled={ disabled }
				onClick={ handleOnSkipClick }
			/>

			{ showSkipPaidAdsConfirmationModal && (
				<SkipPaidAdsConfirmationModal
					onRequestClose={ handleCancelSkipPaidAdsClick }
					onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				/>
			) }
		</>
	);
}
