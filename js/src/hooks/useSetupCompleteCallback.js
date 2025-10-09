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

	const handleFinishSetup = useCallback(
		( amount, onCompleted ) => {
			setLoading( true );
			return createAdsCampaign( amount )
				.then( onCompleted )
				.catch( () => setLoading( false ) );
		},
		[ createAdsCampaign ]
	);

	return [ handleFinishSetup, loading ];
}
