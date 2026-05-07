/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

const RedditAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "order-attribution-reddit-ads-promo" */ './reddit-ads-promo'
	)
);

document.addEventListener( 'DOMContentLoaded', () => {
	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);
	const orderAttributionDetailsContainer = document.querySelector(
		'#woocommerce-order-source-data .woocommerce-order-attribution-details-container'
	);

	if ( ! orderAttributionDetailsContainer && ! orderAttributionBox ) {
		return;
	}

	const element = document.createElement( 'div' );
	const root = createRoot( element );

	root.render(
		<Suspense>
			<RedditAdsPromo />
		</Suspense>
	);

	if ( orderAttributionDetailsContainer ) {
		orderAttributionDetailsContainer.insertAdjacentElement(
			'afterend',
			element
		);
		return;
	}

	orderAttributionBox.append( element );
} );
