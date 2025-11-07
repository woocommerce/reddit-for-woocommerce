/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAdminMenuLink from './useAdminMenuLink';

/**
 * Mocked result of parsing a page entry from {@link /js/src/index.js} by WC-admin's <Route>.
 *
 * @see https://github.com/woocommerce/woocommerce-admin/blob/release/2.7.1/client/layout/controller.js#L240-L244
 */
const setupPage = {
	match: { url: '/reddit/setup' },
	wpOpenMenu: 'toplevel_page_woocommerce-marketing',
};

const settingPage = {
	match: { url: '/reddit/setting' },
	wpOpenMenu: 'toplevel_page_woocommerce-marketing',
};

/**
 * Effect that highlights the SFW Dashboard menu entry in the WC menu.
 *
 * Should be called for every "root page" (`~/pages/*`) that wants to open the SFW menu.
 *
 * The hook could be removed once make the plugin fully use the routing feature of WC,
 * and let this be done by proper matching of URL matchers from {@link /js/src/index.js}
 *
 * @see window.wpNavMenuClassChange
 */
export default function useMenuEffect() {
	const { currentLink } = useAdminMenuLink();

	const setupLink = 'admin.php?page=wc-admin&path=%2Freddit%2Fsetup';
	const settingsLink = 'admin.php?page=wc-admin&path=%2Freddit%2Fsettings';

	return useEffect( () => {
		if ( ! currentLink ) {
			return;
		}

		const currentLinkHref = currentLink.getAttribute( 'href' );
		let matchedUrl = '';

		if ( setupLink === currentLinkHref ) {
			matchedUrl = setupPage.match.url;
		} else if ( settingsLink === currentLinkHref ) {
			matchedUrl = settingPage.match.url;
		}

		window.wpNavMenuClassChange( setupPage, matchedUrl );
	}, [ currentLink ] );
}
