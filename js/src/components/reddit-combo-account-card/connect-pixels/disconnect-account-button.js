/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ACCOUNT_TYPE } from '~/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';

/**
 * Clicking on the button to disconnect the Pixel ID.
 *
 * @event rfw_pixel_id_disconnect_button_click
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Renders a button to disconnect the Pixel ID.
 *
 * @fires rfw_pixel_id_disconnect_button_click When the user clicks on the button to disconnect the Pixel ID.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 */
const DisconnectPixelIdButton = ( { onDisconnected = noop } ) => {
	const { resetRedditAccountConfig } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleSwitch = () => {
		setDisconnecting( true );
		resetRedditAccountConfig( ACCOUNT_TYPE.PIXEL )
			.then( () => onDisconnected() )
			.catch( () => setDisconnecting( false ) );
	};

	return (
		<AppButton
			isTertiary
			loading={ isDisconnecting }
			text={ __(
				'Or, connect to a different Pixel ID',
				'reddit-for-woocommerce'
			) }
			eventName="rfw_pixel_id_disconnect_button_click"
			onClick={ handleSwitch }
		/>
	);
};

export default DisconnectPixelIdButton;
