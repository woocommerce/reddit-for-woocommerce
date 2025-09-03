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
	CONNECTING_PIXEL_ID,
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
			text = __( 'Connecting ads account…', 'reddit-for-woocommerce' );
			subText = '';
			break;

		case CONNECTING_BUSINESS_ACCOUNT:
			text = __(
				'Connecting business account…',
				'reddit-for-woocommerce'
			);
			subText = '';
			break;

		case CONNECTING_PIXEL_ID:
			text = __( 'Connecting pixel ID…', 'reddit-for-woocommerce' );
			subText = '';
			break;
	}

	return {
		text,
		subText,
	};
};

export default getAccountConnectionTexts;
