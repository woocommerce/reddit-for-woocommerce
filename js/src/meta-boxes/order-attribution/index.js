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
	// Fallback container rendered by MetaBoxRegistration when GLA is inactive.
	const standaloneBox = document.getElementById(
		'reddit-order-attribution-box'
	);

	if (
		! orderAttributionDetailsContainer &&
		! orderAttributionBox &&
		! standaloneBox
	) {
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

	if ( orderAttributionBox ) {
		orderAttributionBox.prepend( rfwElement );
		return;
	}

	standaloneBox.appendChild( rfwElement );
} );
