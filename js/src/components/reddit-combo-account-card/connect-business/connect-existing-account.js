/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCard from '~/components/account-card';
import ConnectExistingAccountActions from './connect-existing-account-actions';
import LoadingLabel from '~/components/loading-label';
import BusinessAccountSelectControl from '~/components/business-account-select-control';
import ConnectedIconLabel from '~/components/connected-icon-label';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';

/**
 * Renders an account card to connect to an existing Reddit Business account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onCreateClick Callback when clicking on the button to connect a new account
 */
const ConnectExistingAccount = ( { onCreateClick } ) => {
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const { upsertBusinessAccount } = useAppDispatch();
	const { refetchRedditAccountConfig } = useRedditAccountConfig();
	const { existingAccounts } = useExistingBusinessAccounts();
	const { hasConnection, businessId, hasFinishedResolution } =
		useRedditBusinessAccount();

	useEffect( () => {
		if ( hasConnection ) {
			setValue( businessId );
		}
	}, [ businessId, hasConnection ] );

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			const businessAccount = existingAccounts?.find(
				( acc ) => acc.business_id === value
			);
			const businessAccountName = businessAccount?.business_name;

			await upsertBusinessAccount( value, businessAccountName );
			await refetchRedditAccountConfig();
		} catch ( error ) {
			handleApiError(
				error,
				null,
				__(
					'Unable to connect your Reddit Business account. Please try again later.',
					'reddit-for-woocommerce'
				)
			);
		} finally {
			setLoading( false );
		}
	};

	const handleDisconnected = () => {
		/*
		 * Prevent the `value` from staying on the unclaimed and disconnected account ID.
		 * Please note that the reset works because the `BusinessAccountSelectControl` happens to
		 * switch between two different `AppSelectControls` so that `autoSelectFirstOption`
		 * can be triggered again.
		 */
		setValue( undefined );
	};

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		if ( isLoading ) {
			return (
				<LoadingLabel
					text={ __( 'Connecting…', 'reddit-for-woocommerce' ) }
				/>
			);
		}

		if ( hasConnection ) {
			return <ConnectedIconLabel />;
		}

		return (
			<AppButton
				isSecondary
				disabled={ hasConnection }
				eventName="rfw_ads_account_connect_button_click"
				// eventProps={ getEventProps( {
				// 	id: Number( value ),
				// } ) }
				onClick={ handleConnectClick }
			>
				{ __( 'Connect', 'reddit-for-woocommerce' ) }
			</AppButton>
		);
	};

	return (
		<AccountCard
			className="rfw-reddit-combo-account-card rfw-reddit-combo-service-account-card--business"
			title={ __(
				'Connect to existing Reddit Business account',
				'reddit-for-woocommerce'
			) }
			alignIndicator="toDetail"
			indicator={ getIndicator() }
			detail={
				<BusinessAccountSelectControl
					// Setting `key` is to ensure that `autoSelectFirstOption` will be
					// triggered after disconnecting, so that the automatically selected
					// account can call back to this component.
					key={ Boolean( value ) }
					value={ value }
					onChange={ setValue }
					autoSelectFirstOption
					nonInteractive={ hasConnection }
				/>
			}
			actions={
				<ConnectExistingAccountActions
					disabled={ isLoading }
					isConnected={ hasConnection }
					onCreateNewClick={ onCreateClick }
					onDisconnected={ handleDisconnected }
				/>
			}
		/>
	);
};

export default ConnectExistingAccount;
