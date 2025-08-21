/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * A higher-order component for wrapping the app shell on top of the RFW admin page.
 * Cross-page shared things could be handled here.
 *
 * @param {JSX.Element} Page Top-level admin page component to be wrapped by app shell.
 */
const withAdminPageShell = createHigherOrderComponent(
	( Page ) => ( props ) => {
		return (
			// rfw-admin-page is for scoping particular styles to a RFW admin page.
			<div className="rfw-admin-page">
				<Page { ...props } />
			</div>
		);
	},
	'withAdminPageShell'
);

export default withAdminPageShell;
