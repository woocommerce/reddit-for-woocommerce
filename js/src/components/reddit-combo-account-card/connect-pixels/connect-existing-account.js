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
import ConnectedIconLabel from '~/components/connected-icon-label';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import useRedditPixelId from '~/hooks/useRedditPixelId';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import PixelIdSelectControl from '~/components/pixel-id-select-control';

/**
 * Renders an account card to connect to an existing Reddit Pixel ID.
 *
 */
const ConnectExistingAccount = () => {
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const { upsertPixelId } = useAppDispatch();
	const { refetchRedditAccountConfig } = useRedditAccountConfig();
	const { hasConnection, pixelId, hasFinishedResolution } =
		useRedditPixelId();

	useEffect( () => {
		if ( hasConnection ) {
			setValue( pixelId );
		}
	}, [ pixelId, hasConnection ] );

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			await upsertPixelId( value );
			await refetchRedditAccountConfig();
		} catch ( error ) {
			handleApiError(
				error,
				null,
				__(
					'Unable to connect your Reddit Pixel ID. Please try again later.',
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
		 * Please note that the reset works because the `PixelIdSelectControl` happens to
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
				eventName="rfw_pixel_id_connect_button_click"
				// @TODO: Review tracking
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
			className="rfw-reddit-combo-account-card rfw-reddit-combo-service-account-card--pixel-id"
			title={ __(
				'Connect to existing Pixel ID',
				'reddit-for-woocommerce'
			) }
			alignIndicator="toDetail"
			indicator={ getIndicator() }
			detail={
				<PixelIdSelectControl
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
					onDisconnected={ handleDisconnected }
				/>
			}
		/>
	);
};

export default ConnectExistingAccount;
