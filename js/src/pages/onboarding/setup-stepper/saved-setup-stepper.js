/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { STEP_NAME_KEY_MAP } from './constants';
import { recordStepperChangeEvent } from '~/utils/tracks';
import SetupAccounts from './setup-accounts';
import SetupPaidAds from './setup-paid-ads';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 */
const SavedSetupStepper = ( { savedStep } ) => {
	const [ step, setStep ] = useState( savedStep );

	const handleSetupAccountsContinue = () => {
		setStep( '2' );
	};

	const handleStepClick = ( stepKey ) => {
		// Only allow going back to the previous steps.
		if ( Number( stepKey ) < Number( step ) ) {
			recordStepperChangeEvent( 'rfw_setup_mc', stepKey );
			setStep( stepKey );
		}
	};

	return (
		<Stepper
			className="rfw-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: STEP_NAME_KEY_MAP.accounts,
					label: __(
						'Set up your accounts',
						'reddit-for-woocommerce'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: STEP_NAME_KEY_MAP.paid_ads,
					label: __(
						'Create your campaign',
						'reddit-for-woocommerce'
					),
					content: <SetupPaidAds />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
