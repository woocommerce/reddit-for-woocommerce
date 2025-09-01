/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import DisconnectPixelIdButton from './disconnect-pixel-id-button';
import useExistingPixels from '~/hooks/useExistingPixels';
import useRedditPixelId from '~/hooks/useRedditPixelId';

/**
 * Footer component.
 *
 * @param {Object} props Props.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.onCreateNewClick Callback when clicking on the button to create a new account.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 * @param {boolean} props.disabled Whether to disable the create account button.
 * @param {Object} props.restProps Rest props. Passed to AppButton.
 * @return {JSX.Element} Footer component.
 */
const ConnectExistingPixelIdActions = ( {
	isConnected,
	onCreateNewClick,
	onDisconnected,
	disabled,
	...restProps
} ) => {
	const { existingPixels } = useExistingPixels();
	const { hasConnection } = useRedditPixelId();

	if ( hasConnection && existingPixels?.length > 1 ) {
		return <DisconnectPixelIdButton onDisconnected={ onDisconnected } />;
	}

	const disabledButton = disabled || ! existingPixels?.length;
	return (
		<AppButton
			isTertiary
			onClick={ onCreateNewClick }
			disabled={ disabledButton }
			{ ...restProps }
		>
			{ __( 'Or, create a new Pixel ID', 'reddit-for-woo' ) }
		</AppButton>
	);
};

export default ConnectExistingPixelIdActions;
