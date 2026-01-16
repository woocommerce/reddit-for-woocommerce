/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
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
import SpinnerCard from '~/components/spinner-card';
import CreateBusinessAccountNotice from './create-business-account-notice';
import './connected-reddit-combo-account-card.scss';
import CatalogRoleNotice from '~/pages/settings/product-catalog/catalog-role-notice';

const ConnectedRedditComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const [ isConnectingAdsAccount, setIsConnectingAdsAccount ] =
		useState( false );
	const { upsertAdsAccount, fetchSetup } = useAppDispatch();
	const { hasDetermined, connectingWhich } =
		useAutoConnectAdsBusinessAccounts();
	const { hasConnection: hasBusinessConnection } = useRedditBusinessAccount();
	const { hasConnection: hasAdsConnection } = useRedditAdsAccount();
	const { existingAccounts: existingBusinessAccounts } =
		useExistingBusinessAccounts();
	const {
		existingAccounts: existingAdsAccounts,
		hasFinishedResolution: hasResolvedExistingAdsAccounts,
	} = useExistingAdsAccounts();
	const { text, subText } = getAccountConnectionTexts( connectingWhich );

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	const canShowConnectBusiness =
		hasBusinessConnection || existingBusinessAccounts?.length > 0;
	const showConnectBusiness =
		canShowConnectBusiness &&
		! isConnectingAdsAccount &&
		! connectingWhich &&
		( editMode || ! hasBusinessConnection );

	const canShowConnectAds =
		hasAdsConnection || existingAdsAccounts?.length > 0;
	const showConnectAds =
		canShowConnectAds &&
		! connectingWhich &&
		! isConnectingAdsAccount &&
		( editMode || ! hasAdsConnection );
	const showCatalogRoleNotice = hasBusinessConnection && hasAdsConnection;

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
				fetchSetup();
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
		fetchSetup,
	] );

	if ( ! hasDetermined ) {
		return <SpinnerCard />;
	}

	const switchAccountButton = <SwitchAccountButton isTertiary />;

	const getCardActions = () => {
		if (
			connectingWhich ||
			isConnectingAdsAccount ||
			( ! hasResolvedExistingAdsAccounts && ! hasAdsConnection )
		) {
			return null;
		}

		if ( editMode ) {
			return (
				<div className="rfw-reddit-combo-account-card__description-actions">
					{ switchAccountButton }
					<AppButton isTertiary onClick={ handleCancelClick }>
						{ __( 'Cancel', 'reddit-for-woocommerce' ) }
					</AppButton>
				</div>
			);
		}

		// When not in edit mode, only show the edit button if clicking the
		// button would change the visibility of the ConnectAds or ConnectBusiness cards.
		return (
			<div className="rfw-reddit-combo-account-card__description-actions">
				{ ( showConnectAds || ! canShowConnectAds ) &&
				( showConnectBusiness || ! canShowConnectBusiness ) ? (
					switchAccountButton
				) : (
					<AppButton
						isTertiary
						text={ __( 'Edit', 'reddit-for-woocommerce' ) }
						onClick={ handleEditClick }
					/>
				) }
			</div>
		);
	};

	const showSpinner =
		Boolean( connectingWhich ) ||
		isConnectingAdsAccount ||
		( ! hasResolvedExistingAdsAccounts && ! hasAdsConnection );
	const description = text || <AccountDetails />;

	return (
		<div className="rfw-reddit-combo-account-card-wrapper">
			<AccountCard
				appearance={ APPEARANCE.REDDIT }
				alignIcon="top"
				className="rfw-reddit-combo-account-card rfw-reddit-combo-account-card--connected rfw-reddit-combo-service-account-card--reddit"
				description={ description }
				actions={
					<>
						{ getCardActions() }
						<CreateBusinessAccountNotice />
					</>
				}
				helper={ subText }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				expandedDetail
			/>

			{ showConnectBusiness && <ConnectBusiness /> }
			{ showConnectAds && <ConnectAds /> }
			{ showCatalogRoleNotice && <CatalogRoleNotice /> }
		</div>
	);
};

export default ConnectedRedditComboAccountCard;
