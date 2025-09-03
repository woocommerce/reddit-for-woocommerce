/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Display a centered spinner.
 */
const AppSpinner = () => {
	return (
		<div
			className="rfw-app-spinner"
			role="status"
			aria-label={ __( 'Loading…', 'reddit-for-woocommerce' ) }
		>
			<Spinner />
		</div>
	);
};

export default AppSpinner;
