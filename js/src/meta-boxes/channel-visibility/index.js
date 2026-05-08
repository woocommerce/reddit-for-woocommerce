/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

const RedditAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "channel-visibility-reddit-ads-promo" */ './reddit-ads-promo'
	)
);

document.addEventListener( 'DOMContentLoaded', () => {
	const channelVisibilityBox = document.querySelector(
		'#gla-channel-visibility-box'
	);

	if ( ! channelVisibilityBox ) {
		return;
	}

	const element = document.createElement( 'div' );
	const root = createRoot( element );

	root.render(
		<Suspense>
			<RedditAdsPromo />
		</Suspense>
	);

	channelVisibilityBox.append( element );
} );
