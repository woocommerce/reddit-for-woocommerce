/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { queueRecordEvent, recordEvent } from '@woocommerce/tracks';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { rfwData } from '~/constants';
import { STORE_KEY } from '~/data';

export const recordStepperChangeEvent = noop;
export const recordStepContinueEvent = noop;

/**
 * Returns an event properties with base properties.
 * - redtwoo_version: Plugin version
 * - redtwoo_ads_id: Reddit Ads account ID if connected
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { trackingSlug, pluginVersion } = rfwData;
	const redditAccountConfig = select( STORE_KEY ).getRedditAccountConfig();
	const adsAccountId = redditAccountConfig?.ad_account_id;

	const mixedProperties = {
		...eventProperties,
		[ `${ trackingSlug }_version` ]: pluginVersion,
	};

	if ( adsAccountId || rfwData.adsAccountId ) {
		mixedProperties[ `${ trackingSlug }_ads_id` ] = adsAccountId || rfwData.adsAccountId;
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
