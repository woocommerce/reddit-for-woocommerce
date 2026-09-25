/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import UnsupportedCurrencyMessage from './index';

jest.mock(
	'~/hooks/useAdminUrl',
	() => () => 'https://example.com/blog/wp-admin/'
);
jest.mock( '~/constants', () => ( {
	rfwData: { supportedCurrencies: [ 'USD', 'GBP', 'EUR' ] },
} ) );

describe( 'UnsupportedCurrencyMessage', () => {
	it( 'lists the supported currencies provided by the backend', () => {
		render( <UnsupportedCurrencyMessage /> );

		expect(
			screen.getByText( /supported options: USD, GBP, EUR\./i )
		).toBeInTheDocument();
	} );

	it( 'links to the WooCommerce general settings using the admin URL', () => {
		render( <UnsupportedCurrencyMessage /> );

		const link = screen.getByRole( 'link', {
			name: 'change your store currency',
		} );

		expect( link ).toHaveAttribute(
			'href',
			'https://example.com/blog/wp-admin/admin.php?page=wc-settings&tab=general'
		);
		expect( link ).not.toHaveAttribute( 'target' );
	} );
} );
