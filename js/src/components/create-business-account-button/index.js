/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { rfwData } from '~/constants';

/**
 * Renders a button that opens the Reddit Business Account registration page in a new tab.
 *
 * @param {Object} props - Component props.
 * @param {string} [props.text] - The button text. Defaults to 'Create Business Account'.
 * @param {Function} [props.onClick] - The click handler for the button.
 * @return {JSX.Element} The rendered button component.
 */
const CreateBusinessAccountButton = ( {
	text = __( 'Create Business Account', 'reddit-for-woocommerce' ),
	onClick = noop,
	...rest
} ) => {
	const handleOnClick = useCallback( () => {
		onClick();
		window.location.href = `https://ads.reddit.com/register/?utm_source=partnership&utm_name=woo_commerce&dest-ext=${ rfwData.returnUrl }`;
	}, [ onClick ] );

	return <AppButton text={ text } onClick={ handleOnClick } { ...rest } />;
};

export default CreateBusinessAccountButton;
