/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import useSetupCompleteCallback from '~/hooks/useSetupCompleteCallback';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { getSettingsUrl } from '~/utils/urls';
import { handleApiError } from '~/utils/handleError';
import { FILTER_BUDGET_RECOMMENDATIONS, recordRfwEvent } from '~/utils/tracks';
import { GUIDE_NAMES } from '~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';
import clientSession from './clientSession';

/**
 * Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
 *
 * @event rfw_onboarding_complete_with_paid_ads_button_click
 * @property {string} level The selected level of the budget recommendation, e.g. 'low', 'recommended', 'high', 'custom'.
 * @property {number} budget The budget for the campaign
 * @property {string} audiences The targeted audiences for the campaign
 * @property {string} source The data source of the budget recommendations, e.g. 'reddit-ads-api', 'fallback-database'.
 * @property {number} recommended_budget The recommended daily budget displayed to merchants regardless of the final amount they choose.
 */

/**
 * Renders the onboarding step for setting up the paid ads (Reddit Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @fires rfw_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const [ handleSetupComplete ] = useSetupCompleteCallback();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
		try {
			await onBeforeFinish();
		} catch ( e ) {
			setCompleting( null );

			handleApiError(
				e,
				__( 'Unable to complete your setup.', 'reddit-for-woocommerce' )
			);
			return;
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getSettingsUrl( query );
	};

	const handleSkipCreatePaidAds = async () => {
		setCompleting( ACTION_SKIP );
		await finishOnboardingSetup();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm } = formContext;

		return (
			<SkipButton
				isValidForm={ isValidForm }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				disabled={ completing === ACTION_COMPLETE }
				loading={ completing === ACTION_SKIP }
			/>
		);
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm } = formContext;
		const disabled = completing === ACTION_SKIP || ! isValidForm;

		const handleClick = () => {
			formContext.handleSubmit();
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleClick }
				loading={ completing === ACTION_COMPLETE }
				text={ __( 'Complete setup', 'reddit-for-woocommerce' ) }
			/>
		);
	};

	const paidAds = {
		...clientSession.getCampaign(),
	};

	const handleSubmit = async ( values ) => {
		const { level, dailyBudget } = values;
		const onBeforeFinish = handleSetupComplete.bind( null, dailyBudget );

		setCompleting( ACTION_COMPLETE );

		recordRfwEvent(
			'rfw_onboarding_complete_with_paid_ads_button_click',
			getEventProps( {
				level,
				budget: dailyBudget,
			} )
		);

		await finishOnboardingSetup( onBeforeFinish );
	};

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
			onSubmit={ handleSubmit }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a Reddit campaign to promote your products',
					'reddit-for-woocommerce'
				) }
				continueButton={ createContinueButton }
				skipButton={ createSkipButton }
			/>
		</CampaignAssetsForm>
	);
}
