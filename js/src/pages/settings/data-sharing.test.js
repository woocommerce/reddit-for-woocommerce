/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import DataSharing from './data-sharing';
import useSettings from '~/hooks/useSettings';
import { recordRfwEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

const mockUpdateSettings = jest.fn();
const mockCreateNotice = jest.fn();

jest.mock( '~/data', () => ( {
	useAppDispatch: () => ( { updateSettings: mockUpdateSettings } ),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: mockCreateNotice,
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
		jest.clearAllMocks();
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
		[ false, true, 'off' ],
		[ true, false, 'on' ],
	] )(
		'saves Conversions API tracking as %s when the toggle is clicked',
		async ( trackConversions, isCapiEnabled, status ) => {
			mockSettings( { isCapiEnabled, isPiiCollectionEnabled: false } );
			render( <DataSharing /> );

			await userEvent.click( getShareToggle() );

			expect( mockUpdateSettings ).toHaveBeenCalledWith( {
				trackConversions,
			} );
			await waitFor( () => {
				expect( recordRfwEvent ).toHaveBeenCalledWith(
					'rfw_conversion_tracking_toggle',
					{ status }
				);
			} );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'success',
				'Data sharing status updated successfully.'
			);
		}
	);

	it.each( [
		[ 'Advanced Sharing', false, true, 'on' ],
		[ 'Standard Sharing', true, false, 'off' ],
	] )(
		'saves Collect Customer PII when %s is chosen',
		async ( name, isPiiCollectionEnabled, collectPii, status ) => {
			mockSettings( { isCapiEnabled: true, isPiiCollectionEnabled } );
			render( <DataSharing /> );

			await userEvent.click( queryRadio( name ) );

			expect( mockUpdateSettings ).toHaveBeenCalledWith( {
				collectPii,
			} );
			await waitFor( () => {
				expect( recordRfwEvent ).toHaveBeenCalledWith(
					'rfw_collect_pii_toggle',
					{ status }
				);
			} );
			expect( mockCreateNotice ).not.toHaveBeenCalled();
		}
	);

	it( 'records no event and shows no notice when the toggle change fails to save', async () => {
		mockUpdateSettings.mockRejectedValue( new Error( 'Save failed' ) );
		mockSettings( { isCapiEnabled: true, isPiiCollectionEnabled: false } );
		render( <DataSharing /> );

		await userEvent.click( getShareToggle() );

		await waitFor( () => {
			expect( getShareToggle() ).toBeEnabled();
		} );
		expect( recordRfwEvent ).not.toHaveBeenCalled();
		expect( mockCreateNotice ).not.toHaveBeenCalled();
	} );

	it( 'records no event when a sharing option change fails to save', async () => {
		mockUpdateSettings.mockRejectedValue( new Error( 'Save failed' ) );
		mockSettings( { isCapiEnabled: true, isPiiCollectionEnabled: false } );
		render( <DataSharing /> );

		await userEvent.click( queryRadio( 'Advanced Sharing' ) );

		await waitFor( () => {
			expect( getShareToggle() ).toBeEnabled();
		} );
		expect( recordRfwEvent ).not.toHaveBeenCalled();
		expect( mockCreateNotice ).not.toHaveBeenCalled();
	} );

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
