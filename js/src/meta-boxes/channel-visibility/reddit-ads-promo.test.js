/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import usePreference from '~/hooks/usePreference';
import { recordRfwEvent } from '~/utils/tracks';
import RedditAdsPromo from './reddit-ads-promo';

jest.mock( '~/hooks/usePreference', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( { recordRfwEvent: jest.fn() } ) );
jest.mock( './channel-visibility-settings', () => () => (
	<div>Sync settings</div>
) );
jest.mock( './get-started-cta', () => () => <button>Get started</button> );
jest.mock( './promo-cta', () => () => <button>Promo CTA</button> );

describe( 'Channel visibility RedditAdsPromo', () => {
	afterEach( () => {
		delete window.redditAdsMetaBoxData;
		jest.clearAllMocks();
	} );

	test( 'Records has_onboarded false and is_dismissed false for the full promo', () => {
		window.redditAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( undefined );

		render( <RedditAdsPromo /> );

		expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordRfwEvent ).toHaveBeenCalledWith(
			'rfw_reddit_ads_promo_shown',
			{
				context: 'channel-visibility-meta-box',
				has_onboarded: false,
				is_dismissed: false,
			}
		);
	} );

	test( 'Records is_dismissed true from the first event when dismissed on an earlier visit', () => {
		window.redditAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( true );

		render( <RedditAdsPromo /> );

		expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordRfwEvent ).toHaveBeenCalledWith(
			'rfw_reddit_ads_promo_shown',
			{
				context: 'channel-visibility-meta-box',
				has_onboarded: false,
				is_dismissed: true,
			}
		);
	} );

	test( 'Does not record a second event when the promo is dismissed during the same page load', () => {
		window.redditAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( undefined );

		const { rerender } = render( <RedditAdsPromo /> );

		usePreference.mockReturnValue( true );
		rerender( <RedditAdsPromo /> );

		expect( recordRfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordRfwEvent ).toHaveBeenCalledWith(
			'rfw_reddit_ads_promo_shown',
			{
				context: 'channel-visibility-meta-box',
				has_onboarded: false,
				is_dismissed: false,
			}
		);
	} );

	test( 'Does not record the shown event once onboarded', () => {
		window.redditAdsMetaBoxData = { onboardingComplete: '1' };
		usePreference.mockReturnValue( undefined );

		render( <RedditAdsPromo /> );

		expect( recordRfwEvent ).not.toHaveBeenCalled();
	} );
} );
