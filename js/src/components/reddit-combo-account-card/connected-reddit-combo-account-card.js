/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountDetails from './account-details';
import AppButton from '~/components/app-button';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';
import useAutoConnectAdsBusinessAccounts from '~/hooks/useAutoConnectAdsBusinessAccounts';
import ConnectAds from './connect-ads';
import ConnectBusiness from './connect-business';
import SwitchAccountButton from './switch-account-button';
import getAccountConnectionTexts from './getAccountConnectionTexts';
import Indicator from './indicator';

const ConnectedRedditComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const { hasDetermined, connectingWhich } =
		useAutoConnectAdsBusinessAccounts();
	const { hasConnection: hasBusinessConnection } = useRedditBusinessAccount();
	const { hasConnection: hasAdsConnection } = useRedditAdsAccount();
	const {
		existingAccounts: existingBusinessAccounts,
		hasFinishedResolution: hasResolvedExistingBusinessAccounts,
	} = useExistingBusinessAccounts();
	const {
		existingAccounts: existingRedditAdsAccounts,
		hasFinishedResolution: hasResolvedExistingRedditAdsAccounts,
	} = useExistingAdsAccounts();
	const { text, subText } = getAccountConnectionTexts( connectingWhich );

	console.log( connectingWhich, hasDetermined );

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	const showConnectAds =
		( hasBusinessConnection &&
			! hasAdsConnection &&
			existingRedditAdsAccounts?.length > 1 ) ||
		editMode;
	const showConnectBusiness =
		( ! hasBusinessConnection && existingBusinessAccounts?.length > 1 ) ||
		editMode;
	const switchAccountButton = <SwitchAccountButton isTertiary />;

	const getCardActions = () => {
		if ( editMode ) {
			return (
				<div className="rfw-reddit-combo-account-card__description-actions">
					{ switchAccountButton }
					<AppButton isTertiary onClick={ handleCancelClick }>
						{ __( 'Cancel', 'reddit-for-woo' ) }
					</AppButton>
				</div>
			);
		}

		// When not in edit mode, only show the edit button if clicking the
		// button would change the visibility of the ConnectAds or ConnectBusiness cards.
		return (
			<div className="rfw-reddit-combo-account-card__description-actions">
				{ ! hasBusinessConnection || ! hasAdsConnection ? (
					switchAccountButton
				) : (
					<AppButton
						isTertiary
						text={ __( 'Edit', 'reddit-for-woo' ) }
						onClick={ handleEditClick }
					/>
				) }
			</div>
		);
	};

	const showSpinner = Boolean( connectingWhich );

	return (
		<div className="rfw-reddit-combo-account-card-wrapper">
			<AccountCard
				appearance={ APPEARANCE.REDDIT }
				alignIcon="top"
				className="rfw-reddit-combo-account-card rfw-reddit-combo-account-card--connected rfw-reddit-combo-service-account-card--reddit"
				description={ text || <AccountDetails /> }
				actions={ getCardActions() }
				helper={ subText }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				expandedDetail
			/>

			{ showConnectAds && <ConnectAds /> }

			{ showConnectBusiness && <ConnectBusiness /> }
		</div>
	);
};

export default ConnectedRedditComboAccountCard;
