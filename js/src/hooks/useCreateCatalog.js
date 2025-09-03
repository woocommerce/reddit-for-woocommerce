/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
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
	const [ loading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();

	const createCatalog = useCallback( async () => {
		setLoading( true );
		const response = await fetch( ajaxurl, {
			method: 'POST',
			body: new URLSearchParams( {
				action: `${ redditAdsAdminData.prefix }create_catalog`,
			} ),
		} );

		if ( response.ok ) {
			const res = await response.json();

			setLoading( false );

			if ( res.success ) {
				setCreatedCatalogId( res.data.id || '' );
				createNotice(
					'success',
					__(
						'Product catalog created successfully.',
						'reddit-for-woo'
					)
				);
			} else {
				createNotice(
					'error',
					sprintf(
						/* translators: %s is the error message */
						__(
							'Failed to create product catalog: %s',
							'reddit-for-woo'
						),
						res.data.message
					)
				);
			}
		} else {
			createNotice(
				'error',
				__( 'There was an error with the request.', 'reddit-for-woo' )
			);
			setLoading( false );
		}
	}, [ createNotice, setCreatedCatalogId, setLoading ] );

	return { createCatalog, loading, createdCatalogId };
};

export default useCreateCatalog;
