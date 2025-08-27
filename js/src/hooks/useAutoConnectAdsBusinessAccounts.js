/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useShouldConnectAdsAccount from './useShouldConnectAdsAccount';
import useShouldConnectBusinessAccount from './useShouldConnectBusinessAccount';
import useExistingBusinessAccounts from './useExistingBusinessAccounts';
import useExistingAdsAccounts from './useExistingAdsAccounts';
import {
	CONNECTING_ADS_ACCOUNT,
	CONNECTING_BUSINESS_ACCOUNT,
} from '~/constants';

const useAutoConnectAdsBusinessAccounts = () => {
	const { upsertAdsAccount, upsertBusinessAccount, invalidateResolution } =
		useAppDispatch();
	const lockedRef = useRef( false );
	const [ connectingWhich, setConnectingWhich ] = useState( null );
	const [ hasDetermined, setDetermined ] = useState( false );
	const shouldConnectAds = useShouldConnectAdsAccount();
	const shouldConnectBusiness = useShouldConnectBusinessAccount();
	const { existingAccounts: existingBusinessAccounts } =
		useExistingBusinessAccounts();
	const { existingAccounts: existingAdsAccounts } = useExistingAdsAccounts();

	useEffect( () => {
		if (
			// Wait for all determinations to be ready
			shouldConnectAds === null ||
			shouldConnectBusiness === null ||
			// Avoid repeated calls
			lockedRef.current
		) {
			return;
		}

		let which = null;
		lockedRef.current = true;

		if ( shouldConnectAds ) {
			which = CONNECTING_ADS_ACCOUNT;
		} else if ( shouldConnectBusiness ) {
			which = CONNECTING_BUSINESS_ACCOUNT;
		}

		setConnectingWhich( which );
		setDetermined( true );

		if ( which ) {
			const handleConnectAccountCallback = async () => {
				if ( which === CONNECTING_ADS_ACCOUNT ) {
					const adsAccount = existingAdsAccounts[ 0 ];
					await upsertAdsAccount( adsAccount.ad_account_id );
				} else if ( which === CONNECTING_BUSINESS_ACCOUNT ) {
					const businessAccount = existingBusinessAccounts[ 0 ];
					await upsertBusinessAccount( businessAccount.business_id );
					invalidateResolution( 'getExistingAdsAccounts', [] );
				}
				setConnectingWhich( null );
			};

			handleConnectAccountCallback();
		}
	}, [
		upsertAdsAccount,
		upsertBusinessAccount,
		shouldConnectAds,
		shouldConnectBusiness,
		existingBusinessAccounts,
		existingAdsAccounts,
		invalidateResolution,
	] );

	return {
		hasDetermined,
		connectingWhich,
	};
};

export default useAutoConnectAdsBusinessAccounts;
