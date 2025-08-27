/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import LoadingLabel from '~/components/loading-label';

/**
 * Renders indication that the user is in the process of creating or connecting a Reddit Business account.
 *
 * @param {Object} props Component props.
 * @param {string} props.upsertingAction The action the user is performing.
 */
const UpsertingAccount = ( { upsertingAction } ) => {
	const isConnecting = upsertingAction === 'update';

	let title = __( 'Creating a new Reddit Ads account', 'reddit-for-woo' );
	let indicatorLabel = __( 'Creating…', 'reddit-for-woo' );

	if ( isConnecting ) {
		title = __( 'Connecting your Reddit Ads account', 'reddit-for-woo' );
		indicatorLabel = __( 'Connecting…', 'reddit-for-woo' );
	}

	return (
		<AccountCard
			className="rfw-reddit-combo-service-account-card--ads"
			title={ title }
			helper={ __(
				'This may take a few moments, please wait…',
				'reddit-for-woo'
			) }
			indicator={ <LoadingLabel text={ indicatorLabel } /> }
		/>
	);
};

export default UpsertingAccount;
