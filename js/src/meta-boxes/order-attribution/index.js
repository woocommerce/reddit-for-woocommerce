/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot, lazy, Suspense } from '@wordpress/element';

const RedditAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "order-attribution-reddit-ads-promo" */ './reddit-ads-promo'
	)
);

domReady( () => {
	if ( redditAdsMetaBoxData?.orderAttributionSource !== 'reddit' ) {
		return;
	}

	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);
	const orderAttributionDetailsContainer = document.querySelector(
		'#woocommerce-order-source-data .woocommerce-order-attribution-details-container'
	);

	if ( ! orderAttributionDetailsContainer && ! orderAttributionBox ) {
		return;
	}

	const rfwElement = document.createElement( 'div' );
	const root = createRoot( rfwElement );

	root.render(
		<Suspense>
			<RedditAdsPromo />
		</Suspense>
	);

	if ( orderAttributionDetailsContainer ) {
		orderAttributionDetailsContainer.insertAdjacentElement(
			'afterend',
			rfwElement
		);

		return;
	}

	orderAttributionBox.prepend( rfwElement );
} );
