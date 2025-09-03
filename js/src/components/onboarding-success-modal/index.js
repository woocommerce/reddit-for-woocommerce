/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import wooLogoURL from '~/images/logo/woocommerce.svg';
import redditLogoURL from '~/images/logo/reddit-wide.svg';
import { getSettingsUrl } from '~/utils/urls';
import './index.scss';

/**
 * OnboardingSuccessModal component displays a modal dialog indicating successful setup of Reddit for WooCommerce.
 *
 * The modal includes:
 * - Logos for WooCommerce and Reddit.
 * - A success message and description.
 * - A close button that redirects the user to the settings page.
 *
 * @return {JSX.Element} The onboarding success modal UI.
 */
const OnboardingSuccessModal = () => {
	const handleCloseModal = () => {
		getHistory().replace( getSettingsUrl() );
	};

	return (
		<AppModal
			className="rfw-onboarding-success-modal"
			onRequestClose={ handleCloseModal }
			buttons={ [
				<AppButton
					key="close"
					variant="secondary"
					onClick={ handleCloseModal }
				>
					{ __( 'Close', 'reddit-for-woocommerce' ) }
				</AppButton>,
			] }
		>
			<div className="rfw-onboarding-success-modal__logos">
				<Flex gap={ 6 } align="center" justify="center">
					<FlexItem>
						<img
							src={ wooLogoURL }
							alt={ __(
								'WooCommerce Logo',
								'reddit-for-woocommerce'
							) }
							width="187.5"
						/>
					</FlexItem>
					<FlexItem className="rfw-onboarding-success-modal__logo-separator-line" />
					<FlexItem>
						<img
							src={ redditLogoURL }
							alt={ __(
								'Reddit Logo',
								'reddit-for-woocommerce'
							) }
							width="123"
						/>
					</FlexItem>
				</Flex>
			</div>

			<div className="rfw-onboarding-success-modal__content">
				<h2 className="rfw-onboarding-success-modal__title">
					{ __(
						'You’ve successfully set up Reddit for WooCommerce! 🎉',
						'reddit-for-woocommerce'
					) }
				</h2>
				<div className="rfw-onboarding-success-modal__description">
					{ __(
						'Your store is now connected to Reddit. You can start running ads, track performance, and reach Reddit users with your WooCommerce products.',
						'reddit-for-woocommerce'
					) }
				</div>
			</div>
		</AppModal>
	);
};

export default OnboardingSuccessModal;
