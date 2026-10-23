/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConversionsAPI from './';
import useSettings from '~/hooks/useSettings';

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

const mockUpdateSettings = jest.fn();

jest.mock( '~/data', () => ( {
	useAppDispatch: () => ( { updateSettings: mockUpdateSettings } ),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: jest.fn(),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordRfwEvent: jest.fn(),
} ) );

describe( 'ConversionsAPI', () => {
	function getPiiCheckbox() {
		return screen.getByRole( 'checkbox', {
			name: 'Collect Customer PII',
		} );
	}

	function mockSettings( settings ) {
		useSettings.mockReturnValue( {
			hasFinishedResolution: true,
			...settings,
		} );
	}

	// `CheckboxControl` logs a one-time deprecation warning about its bottom margin.
	// Expect it once here so it doesn't fail whichever test renders first.
	beforeAll( () => {
		mockSettings( {} );
		// eslint-disable-next-line testing-library/no-render-in-setup
		render( <ConversionsAPI /> ).unmount();

		// eslint-disable-next-line jest/no-standalone-expect
		expect( console ).toHaveWarned();
	} );

	it( 'disables Collect Customer PII when Conversions API tracking is off', () => {
		mockSettings( {
			isCapiEnabled: false,
			isPiiCollectionEnabled: false,
		} );
		render( <ConversionsAPI /> );

		expect( getPiiCheckbox() ).toBeDisabled();
	} );

	it( 'enables Collect Customer PII when Conversions API tracking is on', () => {
		mockSettings( {
			isCapiEnabled: true,
			isPiiCollectionEnabled: false,
		} );
		render( <ConversionsAPI /> );

		expect( getPiiCheckbox() ).toBeEnabled();
	} );

	it( 'disables Collect Customer PII while the Conversions API tracking change is saving', async () => {
		mockUpdateSettings.mockReturnValue( new Promise( () => {} ) );
		mockSettings( {
			isCapiEnabled: true,
			isPiiCollectionEnabled: false,
		} );
		render( <ConversionsAPI /> );

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Enable Conversions API tracking',
			} )
		);

		expect( getPiiCheckbox() ).toBeDisabled();
	} );

	it( 'disables Conversions API tracking while the Collect Customer PII change is saving', async () => {
		mockUpdateSettings.mockReturnValue( new Promise( () => {} ) );
		mockSettings( {
			isCapiEnabled: true,
			isPiiCollectionEnabled: false,
		} );
		render( <ConversionsAPI /> );

		await userEvent.click( getPiiCheckbox() );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Enable Conversions API tracking',
			} )
		).toBeDisabled();
	} );

	it.each( [
		[ 'on', true ],
		[ 'off', false ],
	] )(
		'keeps the saved Collect Customer PII choice when Conversions API tracking is %s',
		( _status, isCapiEnabled ) => {
			mockSettings( {
				isCapiEnabled,
				isPiiCollectionEnabled: true,
			} );
			render( <ConversionsAPI /> );

			expect( getPiiCheckbox() ).toBeChecked();
		}
	);
} );
