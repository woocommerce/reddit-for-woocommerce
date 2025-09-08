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
import CreateBusinessAccountButton from '~/components/create-business-account-button';
import './confirm-create-modal.scss';

/**
 * Reddit Business account creation confirmation modal.
 * This modal is shown when the user tries to create a new Reddit Business account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onContinue Callback to continue with account creation.
 * @param {Function} props.onRequestClose Callback to close the modal.
 * @return {JSX.Element} Confirmation modal.
 */
const ConfirmCreateModal = ( { onContinue, onRequestClose } ) => {
	return (
		<AppModal
			className="rfw-create-business-account-warning-modal"
			title={ __( 'Create Business Account', 'reddit-for-woocommerce' ) }
			buttons={ [
				<CreateBusinessAccountButton
					key="confirm"
					isSecondary
					text={ __(
						'Yes, I want a new account',
						'reddit-for-woocommerce'
					) }
					onClick={ onContinue }
				/>,
				<AppButton key="cancel" isPrimary onClick={ onRequestClose }>
					{ __( 'Cancel', 'reddit-for-woocommerce' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="rfw-create-business-account-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Business account?',
						'reddit-for-woocommerce'
					) }
				</span>
			</p>
			<p>
				{ __(
					'You already have another Business account associated with this Reddit account.',
					'reddit-for-woocommerce'
				) }
			</p>
		</AppModal>
	);
};

export default ConfirmCreateModal;
