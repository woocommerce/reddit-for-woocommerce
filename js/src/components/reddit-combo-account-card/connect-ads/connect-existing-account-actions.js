/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import DisconnectAccountButton from './disconnect-account-button';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';

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
const ConnectExistingAccountActions = ( {
	isConnected,
	onCreateNewClick,
	onDisconnected,
	disabled,
	...restProps
} ) => {
	const { existingAccounts } = useExistingAdsAccounts();
	const { hasConnection } = useRedditAdsAccount();

	if ( hasConnection && existingAccounts.length > 0 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}

	const disabledButton = disabled || ! existingAccounts.length;
	return (
		<AppButton
			isTertiary
			onClick={ onCreateNewClick }
			disabled={ disabledButton }
			{ ...restProps }
		>
			{ __( 'Or, create a new Ads account', 'reddit-for-woo' ) }
		</AppButton>
	);
};

export default ConnectExistingAccountActions;
