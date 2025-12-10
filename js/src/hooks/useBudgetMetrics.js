/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('~/data/types.js').AdsBudgetMetrics} AdsBudgetMetrics
 */

/**
 * @typedef {Object} BudgetMetricsPayload
 * @property {AdsBudgetMetrics|null} data The budget metrics data.
 * @property {boolean} hasResolved Whether the data fetching is finished.
 */

const selectorName = 'getAdsBudgetMetrics';

/**
 * Hook to fetch the budget metrics for the given countries and daily budget.
 * If the store country is included in the country codes, it will be moved to
 * the first position in the array as the primary country.
 *
 * @param {number} budget The daily budget. It expects a positive number.
 * @return {BudgetMetricsPayload} Budget metrics.
 */
export default function useBudgetMetrics( budget ) {
	return useSelect(
		( select ) => {
			if ( ! Number.isFinite( budget ) || budget <= 0 ) {
				return {
					data: null,
					hasResolved: true,
				};
			}

			const selector = select( STORE_KEY );
			const args = [ budget ];

			return {
				data: selector[ selectorName ]( ...args ),
				hasResolved: selector.hasFinishedResolution(
					selectorName,
					args
				),
			};
		},
		[ budget ]
	);
}
