/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	CONNECTING_ADS_ACCOUNT,
	CONNECTING_BUSINESS_ACCOUNT,
} from '~/constants';

/**
 * Account connection in progress description.
 * @param {string|null} connectingWhich Which account is being connected.
 * @return {Object} Text and subtext.
 */
const getAccountConnectionTexts = ( connectingWhich ) => {
	let text = null;
	let subText = null;

	switch ( connectingWhich ) {
		case CONNECTING_ADS_ACCOUNT:
			text = __( 'Connecting ads account…', 'reddit-for-woo' );
			subText = __(
				'Required to set up conversion measurement for your store.', // @TODO: review
				'reddit-for-woo'
			);
			break;

		case CONNECTING_BUSINESS_ACCOUNT:
			text = __( 'Connecting business account…', 'reddit-for-woo' );
			subText = __(
				'Required to sync products so they show on Reddit.', // @TODO: review
				'reddit-for-woo'
			);
			break;
	}

	return {
		text,
		subText,
	};
};

export default getAccountConnectionTexts;
