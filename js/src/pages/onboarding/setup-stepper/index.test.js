/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SetupStepper from './index';
import useSetup from '~/hooks/useSetup';

jest.mock( '~/hooks/useSetup', () => jest.fn() );

jest.mock( './saved-setup-stepper', () => () => (
	<div data-testid="saved-setup-stepper" />
) );

describe( 'SetupStepper', () => {
	it( 'renders a blocking notice instead of the stepper when the currency is unsupported', () => {
		useSetup.mockReturnValue( {
			hasFinishedResolution: true,
			data: { step: '1', isCurrencySupported: false },
		} );

		const { container } = render( <SetupStepper /> );

		expect(
			within( container ).getByText( /Store currency is not supported/i )
		).toBeInTheDocument();
		expect(
			within( container ).queryByTestId( 'saved-setup-stepper' )
		).not.toBeInTheDocument();
	} );

	it( 'renders the stepper normally when the currency is supported', () => {
		useSetup.mockReturnValue( {
			hasFinishedResolution: true,
			data: { step: '1', isCurrencySupported: true },
		} );

		const { container } = render( <SetupStepper /> );

		expect(
			within( container ).getByTestId( 'saved-setup-stepper' )
		).toBeInTheDocument();
		expect(
			within( container ).queryByText(
				/Store currency is not supported/i
			)
		).not.toBeInTheDocument();
	} );

	it( 'renders the stepper when isCurrencySupported is missing from the response', () => {
		useSetup.mockReturnValue( {
			hasFinishedResolution: true,
			data: { step: '1' },
		} );

		const { container } = render( <SetupStepper /> );

		expect(
			within( container ).getByTestId( 'saved-setup-stepper' )
		).toBeInTheDocument();
	} );
} );
