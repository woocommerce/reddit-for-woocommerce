/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { noop } from 'lodash';
import { createHooks } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { rfwData } from '~/constants';
import { STORE_KEY } from '~/data';

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
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { slug } = rfwData;
	const { version } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		[ `${ slug }_version` ]: version,
	};

	return mixedProperties;
}
