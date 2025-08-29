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
 * Reddit Pixel ID creation confirmation modal.
 * This modal is shown when the user tries to create a new Reddit Pixel ID.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onContinue Callback to continue with account creation.
 * @param {Function} props.onRequestClose Callback to close the modal.
 * @return {JSX.Element} Confirmation modal.
 */
const ConfirmCreateModal = ( { onContinue, onRequestClose } ) => {
	return (
		<AppModal
			className="rfw-create-pixel-id-warning-modal"
			title={ __( 'Create Pixel ID', 'reddit-for-woo' ) }
			buttons={ [
				<AppButton key="confirm" isSecondary onClick={ onContinue }>
					{ __( 'Yes, I want a new Pixel ID', 'reddit-for-woo' ) }
				</AppButton>,
				<AppButton key="cancel" isPrimary onClick={ onRequestClose }>
					{ __( 'Cancel', 'reddit-for-woo' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="rfw-create-pixel-id-warning-modal__warning-text">
				<WarningIcon />
				<span>
					{ __(
						'Are you sure you want to create a new Pixel ID?',
						'reddit-for-woo'
					) }
				</span>
			</p>
			<p>
				{ __(
					'You already have another Pixel ID associated with this Reddit account.',
					'reddit-for-woo'
				) }
			</p>
		</AppModal>
	);
};

export default ConfirmCreateModal;
