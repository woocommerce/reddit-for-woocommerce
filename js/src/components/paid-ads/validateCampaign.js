/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * @typedef {Object} ValidateCampaignOptions
 * @property {number|undefined} dailyBudget Daily budget for the campaign.
 * @property {Function} formatAmount A function to format the budget amount according to the currency settings.
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {CampaignFormValues} values Campaign form values.
 * @param {ValidateCampaignOptions} opts Extra form options.
 * @return {Object} errors.
 */
const validateCampaign = ( values, opts ) => {
	const errors = {};

	// Only the amount entered by the user needs to be verified.
	if ( values.level !== 'custom' ) {
		return errors;
	}

	if (
		Number.isFinite( values.amount ) &&
		Number.isFinite( opts.dailyBudget ) &&
		opts.dailyBudget > 0
	) {
		const { amount } = values;
		const { dailyBudget, formatAmount } = opts;

		const minAmount = Number( dailyBudget );

		if ( amount < minAmount ) {
			return {
				amount: sprintf(
					/* translators: %1$s: minimum daily budget */
					__(
						'Please make sure daily average cost is at least %s',
						'reddit-for-woocommerce'
					),
					formatAmount( minAmount )
				),
			};
		}
	}

	if ( ! Number.isFinite( values.amount ) || values.amount <= 0 ) {
		return {
			amount: __(
				'Please make sure daily average cost is greater than 0.',
				'reddit-for-woocommerce'
			),
		};
	}

	return errors;
};

export default validateCampaign;
