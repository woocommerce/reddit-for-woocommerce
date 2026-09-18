/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { STEP_NAME_KEY_MAP } from './constants';
import AppSpinner from '~/components/app-spinner';
import AppNotice from '~/components/app-notice';
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

	if ( ! isCurrencySupported ) {
		return (
			<AppNotice status="warning" isDismissible={ false }>
				{ __(
					'Store currency is not supported by Reddit for WooCommerce. Please change your store currency to one of the supported options: USD, GBP, CAD, EUR, AUD, JPY, CHF, NZD, SEK, NOK.',
					'reddit-for-woocommerce'
				) }
			</AppNotice>
		);
	}

	return <SavedSetupStepper savedStep={ STEP_NAME_KEY_MAP[ step ] } />;
};

export default SetupStepper;
