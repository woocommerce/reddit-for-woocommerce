/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * @typedef {Object} CreateCatalog
 * @property {Function} createCatalog Function to create a product catalog.
 * @property {boolean} loading Indicates whether the create operation is in progress.
 * @property {string} createdCatalogId The ID of the created product catalog.
 */

/**
 * Create a product catalog.
 * It creates a product catalog by calling the API.
 *
 * @return {CreateCatalog} An array containing the create function and a loading state.
 *
 * @see useApiFetchCallback
 */
const useCreateCatalog = () => {
	const [ createdCatalogId, setCreatedCatalogId ] = useState( '' );
	const { createNotice } = useDispatchCoreNotices();

	const [
		fetchCreateCatalog,
		{ loading: loadingCreateCatalog, data: dataCreateCatalog },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/reddit/catalog`,
		method: 'POST',
	} );

	const createCatalog = useCallback( async () => {
		try {
			const { data } = await fetchCreateCatalog();
			setCreatedCatalogId( data?.id || '' );
			createNotice(
				'success',
				__( 'Product catalog created successfully.', 'reddit-for-woo' )
			);
		} catch ( e ) {
			createNotice(
				'error',
				sprintf(
					/* translators: %s is the error message */
					__(
						'Failed to create product catalog: %s',
						'reddit-for-woo'
					),
					e.message
				)
			);
		}
	}, [ createNotice, fetchCreateCatalog ] );

	const loading = loadingCreateCatalog || dataCreateCatalog;

	return [ createCatalog, { loading, createdCatalogId } ];
};

export default useCreateCatalog;
