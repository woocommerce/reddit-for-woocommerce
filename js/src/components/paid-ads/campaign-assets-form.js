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
		dailyBudgetBaseline: 10,
		recommended: {
			dailyBudget: 65,
			metrics: {
				cost: 443.1,
				conversions: 13.1,
				conversionsValue: 523.75,
			},
			country: 'US',
			currency: 'USD',
		},
		high: {
			dailyBudget: 85,
			metrics: {
				cost: 531.72,
				conversions: 13.5,
				conversionsValue: 539.43,
			},
			country: 'US',
			currency: 'USD',
		},
		low: {
			dailyBudget: 45,
			metrics: {
				cost: 354.48,
				conversions: 12,
				conversionsValue: 479.15,
			},
			country: 'US',
			currency: 'USD',
		},
		recommendedDailyBudget: 65,
		eventProps: {
			source: 'reddit-ads-api',
			recommended_budget: 65,
			metrics_availability: 'all',
		},
	};

	const validateCampaignWithMinimumAmount = ( values ) => {
		return validateCampaign( values, {
			dailyBudget: budgetRecommendation.dailyBudgetBaseline,
			formatAmount,
		} );
	};

	const handleChange = function ( ...args ) {
		const values = args?.[ 1 ];

		if ( values ) {
			injectDailyBudget( values, budgetRecommendation );
		}

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
						amount: budgetRecommendation.recommendedDailyBudget,
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
