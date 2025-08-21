/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import { getOnboardingUrl } from '~/utils/urls';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * @typedef {Object} UpsertRedditConfig
 * @property {Function} upsertRedditConfig Function to create or update the Reddit account configuration.
 * @property {boolean} loading Indicates whether the upsert operation is in progress.
 */

/**
 * Set up a Reddit account.
 * It fetches the Reddit account configuration from the API and updates the state in the data store.
 *
 * @return {UpsertRedditConfig} An array containing the upsert function and a loading state.
 *
 * @see useApiFetchCallback
 */
const useUpsertRedditConfig = ( configId ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchRedditAccount, fetchSetup } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	const [ fetchCreateAccount ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/reddit/config`,
		method: 'POST',
		data: {
			id: configId,
		},
	} );

	const upsertRedditConfig = useCallback( async () => {
		if ( ! configId ) {
			return false;
		}

		setLoading( true );

		try {
			await fetchCreateAccount( { parse: false } );
		} catch ( e ) {
			createNotice( 'error', e.message );
		}

		// Update Reddit account data in the data store after posting an account update.
		await fetchRedditAccount();
		await fetchSetup();

		// Remove the config_id from the URL.
		getHistory().replace( getOnboardingUrl() );

		setLoading( false );
	}, [
		createNotice,
		fetchCreateAccount,
		fetchRedditAccount,
		fetchSetup,
		configId,
	] );

	return { upsertRedditConfig, loading };
};

export default useUpsertRedditConfig;
