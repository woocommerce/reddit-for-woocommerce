/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import './confirm-create-modal.scss';

/**
 * Reddit Ads account creation confirmation modal.
 * This modal is shown when the user tries to create a new Reddit Ads account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onContinue Callback to continue with account creation.
 * @param {Function} props.onRequestClose Callback to close the modal.
 * @return {JSX.Element} Confirmation modal.
 */
const ConfirmCreateModal = ( { onContinue, onRequestClose } ) => {
	return (
		<AppModal
			className="rfw-create-ads-account-warning-modal"
			title={ __( 'Create Ads Account', 'reddit-for-woocommerce' ) }
			buttons={ [
				<AppButton key="confirm" isSecondary onClick={ onContinue }>
					{ __(
						'Yes, I want a new account',
						'reddit-for-woocommerce'
					) }
				</AppButton>,
				<AppButton key="cancel" isPrimary onClick={ onRequestClose }>
					{ __( 'Cancel', 'reddit-for-woocommerce' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="rfw-create-ads-account-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Ads account?',
						'reddit-for-woocommerce'
					) }
				</span>
			</p>
			<p>
				{ __(
					'You already have another Ads account associated with this Reddit account.',
					'reddit-for-woocommerce'
				) }
			</p>
		</AppModal>
	);
};

export default ConfirmCreateModal;
