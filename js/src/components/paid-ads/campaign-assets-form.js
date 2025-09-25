/**
 * Internal dependencies
 */
import AdaptiveForm from '~/components/adaptive-form';
import validateCampaign from '~/components/paid-ads/validateCampaign';
import useAdsCurrency from '~/hooks/useAdsCurrency';

/**
 * @typedef {import('~/components/types.js').CampaignFormValues} CampaignFormValues
 * @typedef {import('~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

function injectDailyBudget( values, budgetRecommendation ) {
	return Object.defineProperty( values, 'dailyBudget', {
		enumerable: true,
		get() {
			if ( this.level === 'custom' ) {
				return this.amount;
			}

			if ( this.level === 'current' ) {
				return this.currentAmount;
			}

			return budgetRecommendation[ this.level ].dailyBudget;
		},
	} );
}

function resolveInitialCampaign(
	initialCampaign,
	defaultCampaign,
	budgetRecommendation
) {
	const values = {
		...defaultCampaign,
		...initialCampaign,
	};

	if (
		values.level !== 'custom' &&
		! budgetRecommendation?.[ values.level ]
	) {
		values.level =
			budgetRecommendation?.recommended && values.level !== 'current'
				? 'recommended'
				: 'current';
	}

	return injectDailyBudget( values, budgetRecommendation );
}

/**
 * Renders a form based on AdaptiveForm for managing campaign and assets.
 *
 * @augments AdaptiveForm
 * @param {Object} props React props.
 * @param {CampaignFormValues} props.initialCampaign Initial campaign values.
 * @param {AssetEntityGroup} [props.assetEntityGroup] The asset entity group to be used in initializing the form values for editing.
 * @param {Array<CountryCode>} props.countryCodes Country codes to fetch budget recommendations.
 * @param {number} [props.currentAmount] Current daily budget amount of the campaign.
 */
export default function CampaignAssetsForm( {
	initialCampaign,
	assetEntityGroup,
	countryCodes,
	currentAmount,
	...adaptiveFormProps
} ) {
	const { formatAmount } = useAdsCurrency();
	const budgetRecommendation = {
		dailyBudgetBaseline: 13,
		recommended: {
			dailyBudget: 46.17,
			metrics: {
				cost: 323.19,
				conversions: 2.1,
				conversionsValue: 87.41182588215452,
			},
			country: 'ZW',
			currency: 'USD',
		},
		high: {
			dailyBudget: 55.4,
			metrics: {
				cost: 387.8,
				conversions: 2.2,
				conversionsValue: 87.41182588215452,
			},
			country: 'ZW',
			currency: 'USD',
		},
		low: {
			dailyBudget: 36.94,
			metrics: {
				cost: 258.58,
				conversions: 2.3,
				conversionsValue: 87.41182588215452,
			},
			country: 'ZW',
			currency: 'USD',
		},
		recommendedDailyBudget: 46.17,
		eventProps: {
			source: 'reddit-ads-api',
			recommended_budget: 46.17,
			metrics_availability: 'all',
		},
	};

	const validateCampaignWithMinimumAmount = ( values ) => {
		return validateCampaign( values, {
			formatAmount,
		} );
	};

	const handleChange = function ( ...args ) {
		if ( adaptiveFormProps.onChange ) {
			return adaptiveFormProps.onChange.apply( this, args );
		}
	};

	const extendAdapter = () => {
		return {
			budgetRecommendation,
		};
	};

	return (
		<AdaptiveForm
			initialValues={ {
				...resolveInitialCampaign(
					initialCampaign,
					{
						level: 'recommended',
					},
					budgetRecommendation
				),
			} }
			validate={ validateCampaignWithMinimumAmount }
			extendAdapter={ extendAdapter }
			{ ...adaptiveFormProps }
			onChange={ handleChange }
		/>
	);
}
