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
 * Renders indication that the user is in the process of creating or connecting a Reddit Pixel ID.
 *
 * @param {Object} props Component props.
 * @param {string} props.upsertingAction The action the user is performing.
 */
const UpsertingPixel = ( { upsertingAction } ) => {
	const isConnecting = upsertingAction === 'update';

	let title = __(
		'Creating a new Reddit Pixel ID',
		'reddit-for-woocommerce'
	);
	let indicatorLabel = __( 'Creating…', 'reddit-for-woocommerce' );

	if ( isConnecting ) {
		title = __(
			'Connecting your Reddit Pixel ID',
			'reddit-for-woocommerce'
		);
		indicatorLabel = __( 'Connecting…', 'reddit-for-woocommerce' );
	}

	return (
		<AccountCard
			className="rfw-reddit-combo-service-account-card--pixel"
			title={ title }
			helper={ __(
				'This may take a few moments, please wait…',
				'reddit-for-woocommerce'
			) }
			indicator={ <LoadingLabel text={ indicatorLabel } /> }
		/>
	);
};

export default UpsertingPixel;
