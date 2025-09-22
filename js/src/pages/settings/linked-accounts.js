/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useRedditAccount from '~/hooks/useRedditAccount';
import ConnectedRedditAccountCard from '~/components/reddit-combo-account-card/connected-reddit-account-card';
import AppButton from '~/components/app-button';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import DisconnectModal, { REDDIT_ACCOUNT } from './disconnect-modal';

/**
 * Clicking on the button to disconnect the Reddit account.
 *
 * @event rfw_disconnect_reddit_account_button_click
 */

/**
 * @fires rfw_disconnect_reddit_account_button_click When the user clicks on the button to disconnect the Reddit account.
 */
export default function LinkedAccounts() {
	const { hasFinishedResolution: hasResolvedRedditAccount } =
		useRedditAccount();
	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectRedditAccountModal = () =>
		setOpenedModal( REDDIT_ACCOUNT );
	const dismissModal = () => setOpenedModal( null );

	const handleDisconnected = () => {
		// Reload WC admin page to update the `rfwData` initiated from the static script.
		window.location.reload();
	};

	return (
		<>
			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ handleDisconnected }
					disconnectTarget={ openedModal }
				/>
			) }

			{ ! hasResolvedRedditAccount && <SpinnerCard /> }

			{ hasResolvedRedditAccount && (
				<ConnectedRedditAccountCard hideAccountSwitch>
					<Section.Card.Footer>
						<AppButton
							isDestructive
							isLink
							onClick={ openDisconnectRedditAccountModal }
							eventName="rfw_disconnect_reddit_account_button_click"
						>
							{ __(
								'Disconnect Reddit account',
								'reddit-for-woocommerce'
							) }
						</AppButton>
					</Section.Card.Footer>
				</ConnectedRedditAccountCard>
			) }
		</>
	);
}
