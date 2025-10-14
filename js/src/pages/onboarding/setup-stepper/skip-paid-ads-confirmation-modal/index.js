/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import SurveyModal from './survey-modal';
import isWCTracksEnabled from '~/utils/isWCTracksEnabled';

/**
 * @fires rfw_documentation_link_click with `{ context: 'skip-paid-ads-modal', link_id: 'paid-ads-learn-more', href: 'https://www.business.reddit.com/learn' }`
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 * If WC tracking is disabled, it will show a simple confirmation modal.
 * If WC tracking is enabled, it will show a survey modal to gather user feedback.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed.
 * @param {Function} props.onSkipCreatePaidAds Function to be called when the user confirms skipping the paid ads setup.
 */
const SkipPaidAdsConfirmationModal = ( {
	onRequestClose,
	onSkipCreatePaidAds,
} ) => {
	const wcTracksEnabled = isWCTracksEnabled();

	if ( wcTracksEnabled ) {
		return (
			<SurveyModal
				onRequestClose={ onRequestClose }
				onSkipCreatePaidAds={ onSkipCreatePaidAds }
			/>
		);
	}

	return (
		<AppModal
			title={ __( 'Skip setting up ads?', 'reddit-for-woocommerce' ) }
			buttons={ [
				<AppButton key="cancel" isSecondary onClick={ onRequestClose }>
					{ __( 'Cancel', 'reddit-for-woocommerce' ) }
				</AppButton>,
				<AppButton
					key="complete-setup"
					onClick={ onSkipCreatePaidAds }
					isPrimary
				>
					{ __(
						'Complete setup without setting up ads',
						'reddit-for-woocommerce'
					) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Enable Dynamic Ads to drive more sales and reach new shoppers across Reddit.',
					'reddit-for-woocommerce'
				) }
			</p>
			<p>
				{ __(
					'With Pixel and Conversion Tracking, Reddit can use your product data to automatically generate ads and show them to the right people at the right time — helping you grow your business more efficiently.',
					'reddit-for-woocommerce'
				) }
			</p>
			<p>
				<AppDocumentationLink
					href="https://www.business.reddit.com/learn"
					context="skip-paid-ads-modal"
					linkId="paid-ads-learn-more"
				>
					{ __(
						'Learn more about Reddit Ads.',
						'reddit-for-woocommerce'
					) }
				</AppDocumentationLink>
			</p>
		</AppModal>
	);
};

export default SkipPaidAdsConfirmationModal;
