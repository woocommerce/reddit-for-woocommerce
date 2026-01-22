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
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import BudgetSection from '../budget-section';
import Faqs from './faqs';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import CatalogRoleNotice from '~/pages/settings/product-catalog/catalog-role-notice';
import Section from '~/components/section';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';

/**
 * @typedef {import('~/components/adaptive-form/adaptive-form-context').AdaptiveFormContext} AdaptiveFormContext
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @param {Object} props React props.
 * @param {string} props.headerTitle The title of the step.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} [props.skipButton] A React element or function to render the "Skip" button. If a function is passed, it receives the form context and returns the button element.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} [props.continueButton] A React element or function to render the "Continue" button. If a function is passed, it receives the form context and returns the button element.
 */
export default function AdsCampaign( {
	headerTitle,
	skipButton,
	continueButton,
} ) {
	const formContext = useAdaptiveFormContext();
	const { catalog_id: catalogId, hasFinishedResolution } =
		useRedditAccountConfig();

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

			<BudgetSection />
			{ hasFinishedResolution && ! catalogId && (
				<Section>
					<CatalogRoleNotice />
				</Section>
			) }

			<StepContentFooter>
				<StepContentActions>
					{ typeof skipButton === 'function'
						? skipButton( formContext )
						: skipButton }

					{ typeof continueButton === 'function'
						? continueButton( formContext )
						: continueButton }
				</StepContentActions>
				<Faqs />
			</StepContentFooter>
		</StepContent>
	);
}
