/**
 * External dependencies
 */
import { createReduxStore, register, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { rfwData } from '~/constants';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import reducer from './reducer';

const store = createReduxStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	reducer,
	initialState: {
		general: {
			version: rfwData.pluginVersion,
			adAccountId: rfwData.adsAccountId,
		},
		setup: {
			status: rfwData.status,
			step: rfwData.step,
		},
		accounts: {
			jetpack: null,
			reddit: null,
			existingAdsAccounts: [],
			existingBusinessAccounts: [],
			existingPixels: [],
		},
		reddit: null,
		trackConversions: null,
		adsBudgetMetrics: [],
		settings: {
			exportFileUrl: '',
			lastExportTimeStamp: '',
			productsToken: '',
			campaignCreated: false,
			trackConversions: false,
			triggerExport: false,
		},
	},
} );
register( store );

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};

export { STORE_KEY };
