/**
 * @typedef {Object} AdminMenuLinks
 * @property {string} currentLink The current admin nav menu link under Marketing.
 * @property {string} setupLink The Reddit nav menu link.
 */

/**
 * Returns Reddit related nav menu links.
 *
 * @return {AdminMenuLinks} Object of links.
 */
function useAdminMenuLink() {
	const marketingLi = document.querySelector(
		'li.wp-has-submenu.menu-top.toplevel_page_woocommerce-marketing'
	);

	if ( ! marketingLi ) {
		return null;
	}

	const currentLink = marketingLi.querySelector(
		'.wp-submenu > .current > a'
	);
	const setupLink = document.querySelector(
		'a[href="admin.php?page=wc-admin&path=%2Freddit%2Fsetup"]'
	);

	return { currentLink, setupLink };
}

export default useAdminMenuLink;
