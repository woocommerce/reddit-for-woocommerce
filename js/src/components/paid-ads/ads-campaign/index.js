/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import AppButton from '~/components/app-button';
import PaidAdsFeaturesSection from './paid-ads-feature-section';

export default function AdsCampaign( { headerTitle, onSkip, onContinue } ) {
	return (
		<StepContent>
			<StepContentHeader
				title={ headerTitle }
				description={ __(
					"You're ready to launch a Reddit Ads campaign to reach your target audience across Reddit communities. Once your product catalog is synced and approved, ads will be generated and shown to relevant Reddit users.",
					'reddit-for-woocommerce'
				) }
			/>
			<PaidAdsFeaturesSection />

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isLink
						text={ __(
							'Skip ads creation',
							'reddit-for-woocommerce'
						) }
						onClick={ onSkip }
					/>
					<AppButton
						isPrimary
						text={ __( 'Continue', 'reddit-for-woocommerce' ) }
						onClick={ onContinue }
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
}
