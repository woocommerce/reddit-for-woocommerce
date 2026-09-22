/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ChannelVisibilitySettings from './channel-visibility-settings';

const FIELD_NAME = '_product_catalog_item';

/**
 * Sets `window.redditAdsMetaBoxData.channelVisibility`, merging overrides
 * over sensible defaults.
 *
 * @param {Object} overrides Overrides for the `channelVisibility` payload.
 */
function setChannelVisibilityData( overrides = {} ) {
	window.redditAdsMetaBoxData = {
		channelVisibility: {
			field_name: FIELD_NAME,
			product_catalog_item: '1',
			product_is_visible: true,
			sync_status: null,
			issues: [],
			...overrides,
		},
	};
}

describe( 'ChannelVisibilitySettings', () => {
	afterEach( () => {
		delete window.redditAdsMetaBoxData;
	} );

	test( 'Renders a checked toggle when the product is set to sync and show', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox', {
			name: 'Channel visibility setting',
		} );

		const hiddenInput = container.querySelector(
			`input[name="${ FIELD_NAME }"]`
		);

		expect( toggle ).toBeChecked();
		expect( hiddenInput ).toHaveValue( '1' );
	} );

	test( "Renders an unchecked toggle when set to don't sync and show", () => {
		setChannelVisibilityData( { product_catalog_item: '0' } );
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	test( 'Defaults to checked when product_catalog_item is unset', () => {
		setChannelVisibilityData( { product_catalog_item: undefined } );
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	test( 'Clicking the toggle flips the checked state', () => {
		setChannelVisibilityData();
		render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );

		expect( toggle ).toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).not.toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).toBeChecked();
	} );

	test( 'Disables the toggle and shows a notice when the product is hidden from the catalog', () => {
		setChannelVisibilityData( { product_is_visible: false } );
		const { container } = render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeDisabled();
		expect(
			within( container ).getByText(
				'This product cannot be shown on any channel because it is hidden from your store catalog.'
			)
		).toBeInTheDocument();
	} );

	test( 'Unchecks and disables the toggle when the product is hidden from the catalog, even if previously synced', () => {
		setChannelVisibilityData( {
			product_is_visible: false,
			product_catalog_item: '1',
		} );
		render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );

		expect( toggle ).not.toBeChecked();
		expect( toggle ).toBeDisabled();
	} );

	test( 'Shows the sync status notice when visible, synced on, and not yet synced', () => {
		setChannelVisibilityData( { sync_status: 'not-synced' } );
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByText( 'Reddit sync status' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Not Synced' ) ).toBeInTheDocument();
	} );

	test( 'Does not show the sync status notice once the product has synced', () => {
		setChannelVisibilityData( { sync_status: 'synced' } );
		render( <ChannelVisibilitySettings /> );

		expect(
			screen.queryByText( 'Reddit sync status' )
		).not.toBeInTheDocument();
	} );

	test( 'Does not show the sync status notice when the toggle is off', () => {
		setChannelVisibilityData( {
			product_catalog_item: '0',
			sync_status: 'not-synced',
		} );
		render( <ChannelVisibilitySettings /> );

		expect(
			screen.queryByText( 'Reddit sync status' )
		).not.toBeInTheDocument();
	} );

	test( 'Hidden input value mirrors the toggle state', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		const hiddenInput = container.querySelector(
			`input[name="${ FIELD_NAME }"]`
		);
		const toggle = screen.getByRole( 'checkbox' );

		expect( hiddenInput ).toHaveValue( '1' );

		fireEvent.click( toggle );
		expect( hiddenInput ).toHaveValue( '0' );

		fireEvent.click( toggle );
		expect( hiddenInput ).toHaveValue( '1' );
	} );

	test( 'Lists sync issues when the sync status has errors', () => {
		setChannelVisibilityData( {
			sync_status: 'has-errors',
			issues: [ 'Missing product image' ],
		} );
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByText( 'Issues detected' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Missing product image' )
		).toBeInTheDocument();
	} );
} );
