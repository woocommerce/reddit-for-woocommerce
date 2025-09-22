/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import PaidAdsFeaturesSection from './paid-ads-feature-section';

export default function AdsCampaign( {
	headerTitle,
	context,
	skipButton,
	continueButton,
} ) {
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
		</StepContent>
	);
}