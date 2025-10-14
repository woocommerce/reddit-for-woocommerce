/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';

export default function useSetupCompleteCallback() {
	const { createAdsCampaign } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	const handleFinishSetup = useCallback(
		( amount, onCompleted ) => {
			setLoading( true );
			return createAdsCampaign( amount )
				.then( ( data ) => {
					return data.createdCampaign;
				} )
				.then( onCompleted );
		},
		[ createAdsCampaign ]
	);

	return [ handleFinishSetup, loading ];
}
