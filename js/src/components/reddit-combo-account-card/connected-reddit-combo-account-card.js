/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { CONNECTING_ADS_ACCOUNT, CONNECTING_PIXEL_ID } from '~/constants';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountDetails from './account-details';
import AppButton from '~/components/app-button';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';
import useExistingPixels from '~/hooks/useExistingPixels';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';
import useRedditPixelId from '~/hooks/useRedditPixelId';
import useAutoConnectAdsBusinessAccounts from '~/hooks/useAutoConnectAdsBusinessAccounts';
import ConnectAds from './connect-ads';
import ConnectBusiness from './connect-business';
import ConnectPixels from './connect-pixels';
import SwitchAccountButton from './switch-account-button';
import getAccountConnectionTexts from './getAccountConnectionTexts';
import Indicator from './indicator';
import SpinnerCard from '~/components/spinner-card';
import './connected-reddit-combo-account-card.scss';

const ConnectedRedditComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const [ isConnectingAdsAccount, setIsConnectingAdsAccount ] =
		useState( false );
	const [ isConnectingPixelId, setIsConnectingPixelId ] = useState( false );
	const { upsertAdsAccount, upsertPixelId } = useAppDispatch();
	const { hasDetermined, connectingWhich } =
		useAutoConnectAdsBusinessAccounts();
	const { hasConnection: hasBusinessConnection } = useRedditBusinessAccount();
	const { hasConnection: hasAdsConnection } = useRedditAdsAccount();
	const { hasConnection: hasPixelIdConnection } = useRedditPixelId();
	const { existingAccounts: existingBusinessAccounts } =
		useExistingBusinessAccounts();
	const { existingAccounts: existingAdsAccounts } = useExistingAdsAccounts();
	const { existingPixels } = useExistingPixels();
	const { text, subText } = getAccountConnectionTexts( connectingWhich );
	const { text: connectingAdText } = getAccountConnectionTexts(
		CONNECTING_ADS_ACCOUNT
	);
	const { text: connectingPixelIdText } = getAccountConnectionTexts(
		CONNECTING_PIXEL_ID
	);

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	const canShowConnectBusiness =
		hasBusinessConnection || existingBusinessAccounts?.length > 0;
	const showConnectBusiness =
		canShowConnectBusiness && ( editMode || ! hasBusinessConnection );

	const canShowConnectAds =
		hasAdsConnection || existingAdsAccounts?.length > 0;
	const showConnectAds =
		canShowConnectAds && ( editMode || ! hasAdsConnection );

	const canShowConnectPixelId =
		hasPixelIdConnection || existingPixels?.length > 0;
	const showConnectPixelId =
		canShowConnectPixelId && ( editMode || ! hasPixelIdConnection );

	useEffect( () => {
		const upsertAccount = async () => {
			// Auto connect a single Ads account once a Business account is first connected.
			// The initial business connection happens in the useAutoConnectAdsBusinessAccounts hook.
			if (
				hasDetermined &&
				! hasAdsConnection &&
				existingAdsAccounts?.length === 1 &&
				hasBusinessConnection &&
				connectingWhich === null &&
				! isConnectingAdsAccount
			) {
				setIsConnectingAdsAccount( true );
				await upsertAdsAccount(
					existingAdsAccounts[ 0 ].ad_account_id,
					existingAdsAccounts[ 0 ].ad_account_name
				);
				setIsConnectingAdsAccount( false );
			}
		};

		upsertAccount();
	}, [
		hasDetermined,
		hasAdsConnection,
		hasBusinessConnection,
		existingAdsAccounts,
		upsertAdsAccount,
		connectingWhich,
		isConnectingAdsAccount,
	] );

	useEffect( () => {
		// Auto connect a single Pixel ID once an Ads and Business account are first connected.
		const upsertPixel = async () => {
			if (
				hasDetermined &&
				! hasPixelIdConnection &&
				existingPixels?.length === 1 &&
				hasBusinessConnection &&
				hasAdsConnection &&
				connectingWhich === null &&
				! isConnectingPixelId
			) {
				setIsConnectingPixelId( true );
				await upsertPixelId(
					existingPixels[ 0 ].pixel_id
				);
				setIsConnectingPixelId( false );
			}
		};

		upsertPixel();
	}, [
		hasDetermined,
		hasAdsConnection,
		hasBusinessConnection,
		hasPixelIdConnection,
		existingPixels,
		upsertPixelId,
		connectingWhich,
		isConnectingPixelId,
	] );

	if ( ! hasDetermined ) {
		return <SpinnerCard />;
	}

	const switchAccountButton = <SwitchAccountButton isTertiary />;

	const getCardActions = () => {
		if ( connectingWhich || isConnectingAdsAccount ) {
			return null;
		}

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
				{ ( showConnectAds || ! canShowConnectAds ) &&
				( showConnectBusiness || ! canShowConnectBusiness ) &&
				( showConnectPixelId || ! canShowConnectPixelId ) ? (
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

	const showSpinner = Boolean( connectingWhich ) || isConnectingAdsAccount;
	let description = text || <AccountDetails />;
	if ( isConnectingAdsAccount ) {
		description = connectingAdText;
	} else if ( isConnectingPixelId ) {
		description = connectingPixelIdText;
	}

	return (
		<div className="rfw-reddit-combo-account-card-wrapper">
			<AccountCard
				appearance={ APPEARANCE.REDDIT }
				alignIcon="top"
				className="rfw-reddit-combo-account-card rfw-reddit-combo-account-card--connected rfw-reddit-combo-service-account-card--reddit"
				description={ description }
				actions={ getCardActions() }
				helper={ subText }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				expandedDetail
			/>

			{ showConnectBusiness && <ConnectBusiness /> }
			{ showConnectAds && <ConnectAds /> }
			{ showConnectPixelId && <ConnectPixels /> }
		</div>
	);
};

export default ConnectedRedditComboAccountCard;
