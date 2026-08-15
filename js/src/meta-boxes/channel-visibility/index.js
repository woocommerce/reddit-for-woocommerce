/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot, lazy, Suspense } from '@wordpress/element';

const RedditAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "channel-visibility-reddit-ads-promo" */ './reddit-ads-promo'
	)
);

domReady( () => {
	const data = window.redditAdsMetaBoxData?.channelVisibility;

	if ( ! data ) {
		return;
	}

	let mountEl = document.getElementById( 'reddit-channel-visibility-box' );

	if ( ! mountEl ) {
		const inside = document.querySelector( '#channel_visibility .inside' );

		if ( ! inside ) {
			return;
		}

		mountEl = document.createElement( 'div' );
		mountEl.id = 'reddit-channel-visibility-row';
		inside.insertBefore( mountEl, inside.firstChild );
	}

	createRoot( mountEl ).render(
		<Suspense>
			<RedditAdsPromo />
		</Suspense>
	);
} );
