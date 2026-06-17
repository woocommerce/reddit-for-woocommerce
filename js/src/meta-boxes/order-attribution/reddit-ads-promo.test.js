/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { recordRfwEvent } from '~/utils/tracks';
import RedditAdsPromo from './reddit-ads-promo';

jest.mock( '~/utils/tracks', () => ( {
	recordRfwEvent: jest.fn(),
} ) );

describe( 'RedditAdsPromo', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.redditAdsMetaBoxData = {
			onboardingComplete: false,
			hasCampaign: false,
		};
	} );

	afterEach( () => {
		delete window.redditAdsMetaBoxData;
	} );

	describe( 'When not onboarded', () => {
		test( 'Renders get-started state', () => {
			render( <RedditAdsPromo /> );
			expect(
				screen.getByRole( 'heading', {
					level: 3,
					name: 'Your next customers are on Reddit',
				} )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: 'Get started' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'When onboarded without a campaign', () => {
		test( 'Renders create-campaign state', () => {
			window.redditAdsMetaBoxData.onboardingComplete = true;
			render( <RedditAdsPromo /> );
			expect(
				screen.getByRole( 'heading', {
					level: 3,
					name: 'Get more sales with Reddit Ads',
				} )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: 'Create campaign' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Conditional rendering', () => {
		test( 'Does not render when onboarded with a campaign', () => {
			window.redditAdsMetaBoxData.onboardingComplete = true;
			window.redditAdsMetaBoxData.hasCampaign = true;
			const { container } = render( <RedditAdsPromo /> );
			expect( container.firstChild ).toBeNull();
		} );
	} );

	describe( 'Tracking events', () => {
		test( 'Fires rfw_reddit_ads_promo_shown when component renders', () => {
			render( <RedditAdsPromo /> );
			expect( recordRfwEvent ).toHaveBeenCalledWith(
				'rfw_reddit_ads_promo_shown',
				{ context: 'order-attribution-meta-box' }
			);
			expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'Does not fire rfw_reddit_ads_promo_shown when returning null', () => {
			window.redditAdsMetaBoxData.onboardingComplete = true;
			window.redditAdsMetaBoxData.hasCampaign = true;
			render( <RedditAdsPromo /> );
			expect( recordRfwEvent ).not.toHaveBeenCalled();
		} );

		test( 'Fires tracking event only once on re-render', () => {
			const { rerender } = render( <RedditAdsPromo /> );
			expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
			rerender( <RedditAdsPromo /> );
			expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'Fires rfw_reddit_ads_promo_get_started_click when Get started is clicked', () => {
			render( <RedditAdsPromo /> );
			fireEvent.click(
				screen.getByRole( 'link', { name: 'Get started' } )
			);
			expect( recordRfwEvent ).toHaveBeenCalledWith(
				'rfw_reddit_ads_promo_get_started_click',
				{
					context: 'order-attribution-meta-box',
					href: 'admin.php?page=wc-admin&path=%2Freddit%2Fstart',
				}
			);
		} );

		test( 'Fires rfw_reddit_ads_promo_create_campaign_click when Create campaign is clicked', () => {
			window.redditAdsMetaBoxData.onboardingComplete = true;
			render( <RedditAdsPromo /> );
			fireEvent.click(
				screen.getByRole( 'link', { name: 'Create campaign' } )
			);
			expect( recordRfwEvent ).toHaveBeenCalledWith(
				'rfw_reddit_ads_promo_create_campaign_click',
				{
					context: 'order-attribution-meta-box',
					href: 'https://ads.reddit.com/create',
				}
			);
		} );
	} );
} );
