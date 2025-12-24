/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import { getOnboardingUrl } from '~/utils/urls';
import useSettings from '~/hooks/useSettings';
import AppButton from '~/components/app-button';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';

/**
 * Clicking on the button to create a campaign on the Reddit Ads manager.
 *
 * @event rfw_reddit_ads_create_campaign_on_reddit_button_click
 */

/**
 * Clicking on the button to create a campaign from the Onboarding flow.
 *
 * @event rfw_reddit_ads_create_campaign_button_click
 */

/**
 * Clicking on the button to go to the Reddit Ads dashboard.
 *
 * @event rfw_reddit_go_to_dashboard_button_click
 */

/**
 * RedditAds card component for a button to go to the Reddit Ads manager.
 *
 * @fires rfw_reddit_ads_create_campaign_on_reddit_button_click When the user clicks on the button to create a campaign on the Reddit Ads manager.
 * @fires rfw_reddit_ads_create_campaign_button_click When the user clicks on the button to create a campaign from the Onboarding flow.
 * @fires rfw_reddit_go_to_dashboard_button_click When the user clicks on the button to go to the Reddit Ads dashboard.
 *
 * @return {JSX.Element} The rendered RedditAds settings UI.
 */
const RedditAds = () => {
	const { isCampaignCreated, hasFinishedResolution } = useSettings();
	const adminUrl = useAdminUrl();
	const getDescription = () => {
		return isCampaignCreated
			? __(
					'Manage your campaigns and view performance reports.',
					'reddit-for-woocommerce'
			  )
			: __(
					'Create a Reddit campaign to promote your products.',
					'reddit-for-woocommerce'
			  );
	};

	const getIndicator = () => {
		const handleCreateCampaignButtonClick = () => {
			window.location.href = adminUrl + getOnboardingUrl();
		};

		const handleCreateCampaignOnRedditButtonClick = () => {
			window.open(
				'https://ads.reddit.com/create?flow=advanced&objective=catalogSales&utm_source=partnership&utm_name=woo_commerce',
				'_blank',
				'noopener,noreferrer'
			);
		};

		const handleGoToDashboardButtonClick = () => {
			window.open(
				'https://ads.reddit.com/dashboard?utm_source=partnership&utm_name=woo_commerce',
				'_blank',
				'noopener,noreferrer'
			);
		};

		if ( ! isCampaignCreated ) {
			return (
				<AppButton
					variant="secondary"
					onClick={ handleCreateCampaignButtonClick }
					eventName="rfw_reddit_ads_create_campaign_button_click"
					eventProps={ { context: 'settings' } }
				>
					{ __( 'Create Campaign', 'reddit-for-woocommerce' ) }
				</AppButton>
			);
		}

		return (
			<div className="rfw-reddit-ads-buttons">
				<AppButton
					variant="secondary"
					onClick={ handleCreateCampaignOnRedditButtonClick }
					eventName="rfw_reddit_ads_create_campaign_on_reddit_button_click"
					eventProps={ { context: 'settings' } }
				>
					{ __( 'Create Campaign', 'reddit-for-woocommerce' ) }
				</AppButton>
				<AppButton
					variant="secondary"
					onClick={ handleGoToDashboardButtonClick }
					eventName="rfw_reddit_go_to_dashboard_button_click"
					eventProps={ { context: 'settings' } }
				>
					{ __( 'Go to Dashboard', 'reddit-for-woocommerce' ) }
				</AppButton>
			</div>
		);
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<>
			<AccountCard
				className="rfw-reddit-ads"
				title={ __( 'Reddit Ads', 'reddit-for-woocommerce' ) }
				description={ getDescription() }
				indicator={ getIndicator() }
			></AccountCard>
		</>
	);
};

export default RedditAds;
