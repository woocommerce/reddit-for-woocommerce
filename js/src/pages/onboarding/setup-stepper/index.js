/**
 * Internal dependencies
 */
import { STEP_NAME_KEY_MAP } from './constants';
import AppSpinner from '~/components/app-spinner';
import AppNotice from '~/components/app-notice';
import UnsupportedCurrencyMessage from '~/components/unsupported-currency-message';
import SavedSetupStepper from './saved-setup-stepper';
import useSetup from '~/hooks/useSetup';

const SetupStepper = () => {
	const { hasFinishedResolution, data: rfwSetup } = useSetup();

	if ( ! hasFinishedResolution && ! rfwSetup ) {
		return <AppSpinner />;
	}

	if ( hasFinishedResolution && ! rfwSetup ) {
		// this means error occurred, we just need to return null here,
		// wp-data actions will display an error snackbar at the bottom of the page.
		return null;
	}

	const { step, isCurrencySupported } = rfwSetup;

	if ( isCurrencySupported === false ) {
		return (
			<AppNotice status="warning" isDismissible={ false }>
				<UnsupportedCurrencyMessage />
			</AppNotice>
		);
	}

	return <SavedSetupStepper savedStep={ STEP_NAME_KEY_MAP[ step ] } />;
};

export default SetupStepper;
