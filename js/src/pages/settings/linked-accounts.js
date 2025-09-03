/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useRedditAccountStatus from '~/hooks/useRedditAccountStatus';
import ConnectedRedditAccountCard from '~/components/reddit-combo-account-card/connected-reddit-account-card';
import AppButton from '~/components/app-button';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import DisconnectModal, { REDDIT_ACCOUNT } from './disconnect-modal';

/**
 * Accounts are disconnected from the Setting page
 *
 * @event rfw_disconnected_accounts
 * @property {string} context (`all-accounts`|`reddit-account`) - indicate which accounts have been disconnected.
 */

/**
 * @fires rfw_disconnected_accounts
 */
export default function LinkedAccounts() {
	const { hasFinishedResolution: hasResolvedRedditAccount } =
		useRedditAccountStatus();
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
