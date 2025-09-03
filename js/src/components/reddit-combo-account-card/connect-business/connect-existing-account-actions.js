/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import DisconnectAccountButton from './disconnect-account-button';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';

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
	const { existingAccounts } = useExistingBusinessAccounts();
	const { hasConnection } = useRedditBusinessAccount();

	if ( hasConnection && existingAccounts?.length > 1 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}

	const disabledButton = disabled || ! existingAccounts?.length;
	return (
		<AppButton
			isTertiary
			onClick={ onCreateNewClick }
			disabled={ disabledButton }
			{ ...restProps }
		>
			{ __(
				'Or, create a new Business account',
				'reddit-for-woocommerce'
			) }
		</AppButton>
	);
};

export default ConnectExistingAccountActions;
