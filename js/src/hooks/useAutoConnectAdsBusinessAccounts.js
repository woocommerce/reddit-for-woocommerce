/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useShouldConnectPixelId from './useShouldConnectPixelId';
import useShouldConnectAdsAccount from './useShouldConnectAdsAccount';
import useShouldConnectBusinessAccount from './useShouldConnectBusinessAccount';
import useExistingBusinessAccounts from './useExistingBusinessAccounts';
import useExistingAdsAccounts from './useExistingAdsAccounts';
import useExistingPixels from './useExistingPixels';
import {
	CONNECTING_ADS_ACCOUNT,
	CONNECTING_BUSINESS_ACCOUNT,
	CONNECTING_PIXEL_ID,
} from '~/constants';

const useAutoConnectAdsBusinessAccounts = () => {
	const {
		upsertAdsAccount,
		upsertBusinessAccount,
		upsertPixelId,
		invalidateResolution,
		fetchSetup,
	} = useAppDispatch();
	const lockedRef = useRef( false );
	const [ connectingWhich, setConnectingWhich ] = useState( null );
	const [ hasDetermined, setDetermined ] = useState( false );
	const shouldConnectAds = useShouldConnectAdsAccount();
	const shouldConnectBusiness = useShouldConnectBusinessAccount();
	const shouldConnectPixelId = useShouldConnectPixelId();
	const { existingAccounts: existingBusinessAccounts } =
		useExistingBusinessAccounts();
	const { existingAccounts: existingAdsAccounts } = useExistingAdsAccounts();
	const { existingPixels } = useExistingPixels();

	useEffect( () => {
		if (
			// Wait for all determinations to be ready
			shouldConnectAds === null ||
			shouldConnectBusiness === null ||
			shouldConnectPixelId === null ||
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
		} else if ( shouldConnectPixelId ) {
			which = CONNECTING_PIXEL_ID;
		}

		setConnectingWhich( which );
		setDetermined( true );

		if ( which ) {
			const handleConnectAccountCallback = async () => {
				if ( which === CONNECTING_PIXEL_ID ) {
					const pixelId = existingPixels[ 0 ];
					await upsertPixelId( pixelId );
				} else if ( which === CONNECTING_ADS_ACCOUNT ) {
					const adsAccount = existingAdsAccounts[ 0 ];
					await upsertAdsAccount(
						adsAccount.ad_account_id,
						adsAccount.ad_account_name
					);
					invalidateResolution( 'getExistingPixels', [] );
				} else if ( which === CONNECTING_BUSINESS_ACCOUNT ) {
					const businessAccount = existingBusinessAccounts[ 0 ];
					await upsertBusinessAccount(
						businessAccount.business_id,
						businessAccount.business_name
					);
					invalidateResolution( 'getExistingAdsAccounts', [] );
					invalidateResolution( 'getExistingPixels', [] );
				}

				if (
					which === CONNECTING_PIXEL_ID ||
					which === CONNECTING_ADS_ACCOUNT ||
					which === CONNECTING_BUSINESS_ACCOUNT
				) {
					fetchSetup();
				}

				setConnectingWhich( null );
			};

			handleConnectAccountCallback();
		}
	}, [
		upsertPixelId,
		upsertAdsAccount,
		upsertBusinessAccount,
		shouldConnectPixelId,
		shouldConnectAds,
		shouldConnectBusiness,
		existingBusinessAccounts,
		existingAdsAccounts,
		existingPixels,
		invalidateResolution,
		fetchSetup,
	] );

	return {
		hasDetermined,
		connectingWhich,
	};
};

export default useAutoConnectAdsBusinessAccounts;
