/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import WarningIcon from '~/components/warning-icon';
import { useAppDispatch } from '~/data';
import { ALL_ACCOUNTS, REDDIT_ACCOUNT } from './constants';

const textDict = {
	[ ALL_ACCOUNTS ]: {
		title: __( 'Disconnect all accounts', 'reddit-for-woocommerce' ),
		confirmButton: __(
			'Disconnect all accounts',
			'reddit-for-woocommerce'
		),
		confirmation: __(
			'Yes, I want to disconnect all my accounts.',
			'reddit-for-woocommerce'
		),
		contents: [
			__(
				'I understand that I am disconnecting any WordPress.com account and Reddit account connected to this extension.',
				'reddit-for-woocommerce'
			),
			__( 'Lorem ipsum', 'reddit-for-woocommerce' ),
			__(
				'Dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
				'reddit-for-woocommerce'
			),
		],
	},
	[ REDDIT_ACCOUNT ]: {
		title: __( 'Disconnect Reddit account', 'reddit-for-woocommerce' ),
		confirmButton: __(
			'Disconnect Reddit Account',
			'reddit-for-woocommerce'
		),
		confirmation: __(
			'Yes, I want to disconnect my Reddit account.',
			'reddit-for-woocommerce'
		),
		contents: [
			__(
				'I understand that I am disconnecting my Reddit account from this WooCommerce extension.',
				'reddit-for-woocommerce'
			),
			__(
				'Some configurations for Reddit created through WooCommerce may be lost. This cannot be undone.',
				'reddit-for-woocommerce'
			),
		],
	},
};

export default function ConfirmModal( {
	disconnectTarget,
	onRequestClose,
	onDisconnected,
	disconnectAction,
} ) {
	const [ isAgreed, setAgreed ] = useState( false );
	const [ isDisconnecting, setDisconnecting ] = useState( false );
	const dispatcher = useAppDispatch();

	const { title, confirmButton, confirmation, contents } =
		textDict[ disconnectTarget ];

	const handleRequestClose = () => {
		if ( isDisconnecting ) {
			return;
		}
		onRequestClose();
	};

	const handleConfirmClick = () => {
		let disconnect =
			disconnectTarget === ALL_ACCOUNTS
				? dispatcher.disconnectAllAccounts
				: dispatcher.disconnectRedditAccount;

		if ( disconnectAction ) {
			disconnect = disconnectAction;
		}

		setDisconnecting( true );
		disconnect()
			.then( () => {
				onDisconnected();
				onRequestClose();
			} )
			.catch( () => {
				setDisconnecting( false );
			} );
	};

	return (
		<AppModal
			className="rfw-disconnect-accounts-modal"
			title={
				<>
					<WarningIcon size={ 20 } />
					{ title }
				</>
			}
			isDismissible={ ! isDisconnecting }
			buttons={ [
				<AppButton
					key="1"
					isSecondary
					disabled={ isDisconnecting }
					onClick={ handleRequestClose }
				>
					{ __( 'Never mind', 'reddit-for-woocommerce' ) }
				</AppButton>,
				<AppButton
					key="2"
					isPrimary
					isDestructive
					loading={ isDisconnecting }
					disabled={ ! isAgreed }
					onClick={ handleConfirmClick }
				>
					{ confirmButton }
				</AppButton>,
			] }
			onRequestClose={ handleRequestClose }
		>
			{ contents.map( ( text, idx ) => (
				<p key={ idx }>{ text }</p>
			) ) }
			<CheckboxControl
				label={ confirmation }
				checked={ isAgreed }
				disabled={ isDisconnecting }
				onChange={ setAgreed }
			/>
		</AppModal>
	);
}
