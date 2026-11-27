/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProductCatalog from '.';
import useSettings from '~/hooks/useSettings';

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );
jest.mock( '~/constants', () => ( {
	rfwData: {
		adminNonce: 'nonce',
		isExportInProgress: '',
		exportFileUrl: '',
		lastTimestamp: 'November 20, 2026 at 10:00 am',
	},
} ) );
jest.mock( './useExportPoller', () => jest.fn() );
jest.mock( './useProductCatalogExport', () => () => ( {
	generateCsv: jest.fn(),
} ) );
jest.mock( './catalog-role-notice', () => () => null );
// AppButton transitively imports the wp-data store, which reads `window.redditAdsAdminData`
// at module load time. That global isn't set up in the test environment.
jest.mock( '~/components/app-button', () => ( { children } ) => (
	<button type="button">{ children }</button>
) );

describe( 'ProductCatalog', () => {
	function mockSettings( settings ) {
		useSettings.mockReturnValue( {
			shouldTriggerExport: false,
			lastExportTimeStamp: '',
			exportFileUrl: '',
			hasFinishedResolution: true,
			...settings,
		} );
	}

	it( 'only explains that physical products can be exported when a previous CSV is gone and nothing can be exported', () => {
		mockSettings( { hasExportableProducts: false } );

		render( <ProductCatalog /> );

		expect(
			screen.getByText( /Only physical products can be exported/i )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /may have been deleted/i )
		).not.toBeInTheDocument();
	} );

	it( 'asks to regenerate the CSV when a previous CSV is gone and products can be exported', () => {
		mockSettings( { hasExportableProducts: true } );

		render( <ProductCatalog /> );

		expect(
			screen.getByText( /may have been deleted/i )
		).toBeInTheDocument();
	} );
} );
