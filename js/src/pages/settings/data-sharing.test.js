/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import DataSharing from './data-sharing';
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

describe( 'DataSharing', () => {
	function getShareToggle() {
		return screen.getByRole( 'checkbox', {
			name: 'Share data with Reddit',
		} );
	}

	function queryRadio( name ) {
		return screen.queryByRole( 'radio', { name } );
	}

	function mockSettings( settings ) {
		useSettings.mockReturnValue( {
			hasFinishedResolution: true,
			...settings,
		} );
	}

	beforeEach( () => {
		mockUpdateSettings.mockReset();
	} );

	it( 'shows the toggle off and hides the sharing options when Conversions API tracking is off', () => {
		mockSettings( {
			isCapiEnabled: false,
			isPiiCollectionEnabled: true,
		} );
		render( <DataSharing /> );

		expect( getShareToggle() ).not.toBeChecked();
		expect( queryRadio( 'Advanced Sharing' ) ).not.toBeInTheDocument();
		expect( queryRadio( 'Standard Sharing' ) ).not.toBeInTheDocument();
	} );

	it.each( [
		[ 'Advanced Sharing', true ],
		[ 'Standard Sharing', false ],
	] )(
		'selects %s when Conversions API tracking is on and Collect Customer PII is %s',
		( selectedName, isPiiCollectionEnabled ) => {
			mockSettings( { isCapiEnabled: true, isPiiCollectionEnabled } );
			render( <DataSharing /> );

			expect( getShareToggle() ).toBeChecked();
			expect( queryRadio( selectedName ) ).toBeChecked();
		}
	);

	it.each( [
		[ false, true ],
		[ true, false ],
	] )(
		'saves Conversions API tracking as %s when the toggle is clicked',
		async ( trackConversions, isCapiEnabled ) => {
			mockSettings( { isCapiEnabled, isPiiCollectionEnabled: false } );
			render( <DataSharing /> );

			await userEvent.click( getShareToggle() );

			expect( mockUpdateSettings ).toHaveBeenCalledWith( {
				trackConversions,
			} );
		}
	);

	it.each( [
		[ 'Advanced Sharing', false, true ],
		[ 'Standard Sharing', true, false ],
	] )(
		'saves Collect Customer PII when %s is chosen',
		async ( name, isPiiCollectionEnabled, collectPii ) => {
			mockSettings( { isCapiEnabled: true, isPiiCollectionEnabled } );
			render( <DataSharing /> );

			await userEvent.click( queryRadio( name ) );

			expect( mockUpdateSettings ).toHaveBeenCalledWith( {
				collectPii,
			} );
		}
	);

	it( 'disables the sharing options while the toggle change is saving', async () => {
		mockUpdateSettings.mockReturnValue( new Promise( () => {} ) );
		mockSettings( {
			isCapiEnabled: true,
			isPiiCollectionEnabled: false,
		} );
		render( <DataSharing /> );

		await userEvent.click( getShareToggle() );

		expect( queryRadio( 'Advanced Sharing' ) ).toBeDisabled();
		expect( queryRadio( 'Standard Sharing' ) ).toBeDisabled();
	} );

	it( 'disables the toggle while a sharing option change is saving', async () => {
		mockUpdateSettings.mockReturnValue( new Promise( () => {} ) );
		mockSettings( {
			isCapiEnabled: true,
			isPiiCollectionEnabled: false,
		} );
		render( <DataSharing /> );

		await userEvent.click( queryRadio( 'Advanced Sharing' ) );

		expect( getShareToggle() ).toBeDisabled();
	} );
} );
