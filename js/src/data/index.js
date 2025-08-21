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
			version: '0.1',
		},
		setup: {
			status: rfwData.status,
			step: rfwData.step,
		},
		accounts: {
			jetpack: null,
			reddit: null,
		},
		reddit: null,
		trackConversions: null,
	},
} );
register( store );

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};

export { STORE_KEY };
