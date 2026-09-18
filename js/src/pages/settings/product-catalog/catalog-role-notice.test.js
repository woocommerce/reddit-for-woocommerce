/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CatalogRoleNotice from './catalog-role-notice';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import useCreateCatalog from '~/hooks/useCreateCatalog';

jest.mock( '~/hooks/useRedditAccountConfig', () => jest.fn() );
jest.mock( '~/hooks/useCreateCatalog', () => jest.fn() );
// AppButton transitively imports the wp-data store, which reads `window.redditAdsAdminData`
// at module load time. That global isn't set up in the test environment, so stub the button
// out rather than exercising it here; it isn't what this test is verifying.
jest.mock( '~/components/app-button', () => () => (
	<button type="button">mock-app-button</button>
) );

const baseAccountConfig = {
	catalog_id: '',
	business_id: 'biz_123',
	pixel_id: 'pix_456',
	hasFinishedResolution: true,
};

const baseCreateCatalog = {
	createCatalog: jest.fn(),
	loading: false,
	createdCatalogId: '',
	errorCode: 0,
};

describe( 'CatalogRoleNotice', () => {
	beforeEach( () => {
		useCreateCatalog.mockReturnValue( baseCreateCatalog );
	} );

	it( 'renders the unsupported currency message distinctly from the generic error notice', () => {
		useRedditAccountConfig.mockReturnValue( {
			...baseAccountConfig,
			catalog_error: 'UNSUPPORTED_CURRENCY',
		} );

		const { container } = render( <CatalogRoleNotice /> );

		expect(
			within( container ).getByText( /Store currency is not supported/i )
		).toBeInTheDocument();
		expect(
			within( container ).queryByText( /check the/i )
		).not.toBeInTheDocument();
	} );

	it( 'renders the generic error notice for an unrecognised error code', () => {
		useRedditAccountConfig.mockReturnValue( {
			...baseAccountConfig,
			catalog_error: 'SOMETHING_ELSE',
		} );

		const { container } = render( <CatalogRoleNotice /> );

		expect(
			within( container ).getByText( /some error creating the Catalog/i )
		).toBeInTheDocument();
		expect(
			within( container ).queryByText(
				/Store currency is not supported/i
			)
		).not.toBeInTheDocument();
	} );
} );
