/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { queueRecordEvent, recordEvent } from '@woocommerce/tracks';
import { noop } from 'lodash';
import { createHooks } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { rfwData } from '~/constants';
import { STORE_KEY } from '~/data';

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
 * - redtwoo_version: Plugin version
 * - redtwoo_ads_id: Reddit Ads account ID if connected
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { trackingSlug } = rfwData;
	const { version, adAccountId } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		[ `${ trackingSlug }_version` ]: version,
	};

	if ( adAccountId ) {
		mixedProperties[ `${ trackingSlug }_ads_id` ] = adAccountId;
	}

	return mixedProperties;
}

/**
 * Record a tracking event with base properties.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function recordRfwEvent( eventName, eventProperties ) {
	recordEvent( eventName, addBaseEventProperties( eventProperties ) );
}

/**
 * Queue a tracking event with base properties.
 *
 * This allows you to delay tracking events that would otherwise cause a race condition.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function queueRecordRfwEvent( eventName, eventProperties ) {
	queueRecordEvent( eventName, addBaseEventProperties( eventProperties ) );
}
