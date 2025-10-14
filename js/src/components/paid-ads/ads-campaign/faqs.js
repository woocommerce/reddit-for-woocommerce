/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FaqsPanel from '~/components/faqs-panel';

const faqItems = [
	{
		trackId: 'how-much-do-reddit-ads-cost',
		question: __(
			'How much do Reddit Ads cost?',
			'reddit-for-woocommerce'
		),
		answer: __( 'TBD', 'reddit-for-woocommerce' ),
	},
	{
		trackId: 'what-is-the-minimum-spend-for-reddit-ads',
		question: __(
			'What is the minimum spend for Reddit Ads?',
			'reddit-for-woocommerce'
		),
		answer: __( 'TBD', 'reddit-for-woocommerce' ),
	},
	{
		trackId: 'how-can-i-optimize-my-budget',
		question: __(
			'How can I optimize my budget?',
			'reddit-for-woocommerce'
		),
		answer: __( 'TBD', 'reddit-for-woocommerce' ),
	},
	{
		trackId: 'can-i-promote-my-business-for-free-on-reddit',
		question: __(
			'Can I promote my business for free on Reddit?',
			'reddit-for-woocommerce'
		),
		answer: __( 'TBD', 'reddit-for-woocommerce' ),
	},
];

/**
 * Clicking on faq items to collapse or expand it in the Onboarding Flow or creating/editing a campaign
 *
 * @event rfw_setup_ads_faq
 * @property {string} id FAQ identifier
 * @property {string} action (`expand`|`collapse`)
 */

/**
 * Renders a toggleable FAQs about Reddit Ads.
 *
 * @fires rfw_setup_ads_faq
 */
const Faqs = () => {
	return (
		<FaqsPanel
			trackName="rfw_setup_ads_faq"
			context="setup-ads"
			faqItems={ faqItems }
		/>
	);
};

export default Faqs;
