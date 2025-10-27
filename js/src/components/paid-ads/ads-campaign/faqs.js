/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import FaqsPanel from '~/components/faqs-panel';

const faqItems = [
	{
		trackId: 'how-should-i-set-budget-and-pacing-at-launch',
		question: __(
			'How should I set budget and pacing at launch?',
			'reddit-for-woocommerce'
		),
		answer: __(
			'Start with a weekly budget that can generate meaningful signal—roughly 20–30× your target CPA per ad group—so the system has enough data to learn. Fewer, well-funded ad groups beat a bunch of starved ones. Avoid major edits for the first 48–72 hours; constant changes reset learning and slow results. When you scale, do it in controlled steps (about 20–30% at a time) once performance is stable.',
			'reddit-for-woocommerce'
		),
	},
	{
		trackId: 'how-do-i-confirm-pixel-and-capi-are-working-before-i-launch',
		question: __(
			'How do I confirm Pixel and CAPI are working before I launch?',
			'reddit-for-woocommerce'
		),
		answer: createInterpolateElement(
			__(
				'Open Events Manager and make sure the Pixel shows <strong>Active</strong> and Server events show <strong>Receiving</strong>. Trigger a quick test flow (view a product, add to cart, complete a test purchase) and confirm you see both client <strong>and</strong> server events within a few minutes, with the <strong>same event_id</strong> for deduplication. Check that match rates look healthy (hashed email/phone present) and that the <strong>product id in events exactly matches your feed id</strong>.',
				'reddit-for-woocommerce'
			),
			{
				strong: <strong />,
			}
		),
	},
	{
		trackId:
			'my-catalog-products-arent-showing-up-during-setup-what-should-i-check',
		question: __(
			"My catalog/products aren't showing up during setup—what should I check?",
			'reddit-for-woocommerce'
		),
		answer: __(
			'Run a sync and review Diagnostics for missing required fields like title, price, availability, link, and image. Only in-stock items with valid images and working links will populate eligible sets. Make sure product IDs in the feed match the IDs your Pixel/CAPI send in events; mismatches break DPA targeting and reporting. Also confirm your store currency and country align with your ad account settings.',
			'reddit-for-woocommerce'
		),
	},
	{
		trackId: 'what-needs-to-be-true-for-shopping-dpas-to-work-well',
		question: __(
			'What needs to be true for Shopping/DPAs to work well?',
			'reddit-for-woocommerce'
		),
		answer: __(
			'Your catalog must be healthy (no critical errors) and kept fresh with scheduled refresh so price and availability are current. Pixel + CAPI should fire purchase and view events reliably, with product IDs matching the feed exactly. Build product sets that mirror how you actually want to advertise—top sellers, new arrivals, or in-stock under a price point—so delivery stays relevant. ',
			'reddit-for-woocommerce'
		),
	},
	{
		trackId: 'where-do-i-manage-the-campaign-after-creating-it-in-woo',
		question: __(
			'Where do I manage the campaign after creating it in Woo?',
			'reddit-for-woocommerce'
		),
		answer: createInterpolateElement(
			__(
				'Manage it in <strong>Reddit Ads Manager at <adsLink>https://ads.reddit.com</adsLink>.</strong>',
				'reddit-for-woocommerce'
			),
			{
				strong: <strong />,
				adsLink: (
					<Link
						href="https://ads.reddit.com/"
						target="_blank"
						rel="noopener noreferrer"
						external
					/>
				),
			}
		),
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
