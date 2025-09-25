/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

export default function useSetupCompleteCallback() {
	const { createAdsCampaign } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();
	const [ loading, setLoading ] = useState( false );

	const completeAdsSetup = useCallback( () => {
		const options = {
			path: '/wc/mfw/ads/setup/complete',
			method: 'POST',
		};
		return apiFetch( options ).catch( () => {
			createNotice(
				'error',
				__(
					'Unable to complete your ads setup. Please try again later.',
					'reddit-for-woocommerce'
				)
			);
			return Promise.reject();
		} );
	}, [ createNotice ] );

	const handleFinishSetup = useCallback(
		( amount, onCompleted ) => {
			setLoading( true );
			return createAdsCampaign( amount )
				.then( completeAdsSetup )
				.then( onCompleted )
				.catch( () => setLoading( false ) );
		},
		[ createAdsCampaign, completeAdsSetup ]
	);

	return [ handleFinishSetup, loading ];
}
