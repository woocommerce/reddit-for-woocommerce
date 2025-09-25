/**
 * External dependencies
 */
import { noop } from 'lodash';
import { select } from '@wordpress/data';
import { createHooks } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data';
import { mfwData } from '~/constants';

export const recordRfwEvent = noop;
export const recordStepperChangeEvent = noop;
export const recordStepContinueEvent = noop;

export const hooks = createHooks();

export const NAMESPACE = 'tracking';
export const FILTER_ONBOARDING = 'FILTER_ONBOARDING';
export const FILTER_BUDGET_RECOMMENDATIONS = 'FILTER_BUDGET_RECOMMENDATIONS';

export const filterPropertiesMap = new Map();

filterPropertiesMap.set( FILTER_ONBOARDING, [ 'context', 'step' ] );
filterPropertiesMap.set( FILTER_BUDGET_RECOMMENDATIONS, [
	'source',
	'recommended_budget',
] );

/**
 * Returns an event properties with base properties.
 * - rfw_version: Plugin version
 * - rfw_ads_id: Reddit Ads account ID if connected
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { slug } = mfwData;
	const { version } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		[ `${ slug }_version` ]: version,
	};

	return mixedProperties;
}
