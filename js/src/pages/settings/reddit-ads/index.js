/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCard from '~/components/account-card';

/**
 * Clicking on the button to go to the Reddit Ads manager.
 *
 * @event rfw_reddit_ads_manager_button_click
 */

/**
 * RedditAds card component for a button to go to the Reddit Ads manager.
 *
 * @fires rfw_reddit_ads_manager_button_click When the user clicks on the button to go to the Reddit Ads manager.
 *
 * @return {JSX.Element} The rendered RedditAds settings UI.
 */
const RedditAds = () => {
	const getDescription = () => {
		return __(
			'Manage your campaigns and view performance reports.',
			'reddit-for-woocommerce'
		);
	};

	const getIndicator = () => {
		const handleOnClick = () => {
			window.open(
				'https://ads.reddit.com/',
				'_blank',
				'noopener,noreferrer'
			);
		};

		return (
			<AppButton
				variant="secondary"
				onClick={ handleOnClick }
				eventName="rfw_reddit_ads_manager_button_click"
				eventProps={ { context: 'settings' } }
			>
				{ __( 'Go to my Reddit Ads Manager', 'reddit-for-woocommerce' ) }
			</AppButton>
		);
	};

	return (
		<>
			<AccountCard
				className="rfw-reddit-ads"
				title={ __(
					'Reddit Ads',
					'reddit-for-woocommerce'
				) }
				description={ getDescription() }
				indicator={ getIndicator() }
			></AccountCard>
		</>
	);
};

export default RedditAds;
