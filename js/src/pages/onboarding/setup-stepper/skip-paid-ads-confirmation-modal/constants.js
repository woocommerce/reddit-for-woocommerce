/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const OPTIONS = [
	{
		label: __( 'I already have ads on Reddit', 'reddit-for-woocommerce' ),
		value: 'i_already_have_ads_on_reddit',
		hasTextInput: false,
	},
	{
		label: __(
			'I don’t have the budget to create ads now',
			'reddit-for-woocommerce'
		),
		value: 'i_dont_have_the_budget_to_create_ads_now',
		hasTextInput: false,
	},
	{
		label: __(
			'I’ve tried Reddit ads before without success',
			'reddit-for-woocommerce'
		),
		value: 'ive_tried_reddit_ads_before_without_success',
		hasTextInput: false,
	},
	{
		label: __( 'I don’t want ads on Reddit', 'reddit-for-woocommerce' ),
		value: 'i_dont_want_ads_on_reddit',
		hasTextInput: true,
	},
	{
		label: __( 'I’ll create ads later', 'reddit-for-woocommerce' ),
		value: 'ill_create_ads_later',
		hasTextInput: true,
	},
	{
		label: __( 'Other', 'reddit-for-woocommerce' ),
		value: 'other',
		hasTextInput: true,
	},
];
