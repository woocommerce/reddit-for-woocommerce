/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSwitchRedditAccount from '~/hooks/useSwitchRedditAccount';

/**
 * Clicking on the "connect to a different Reddit account" button.
 *
 * @event rfw_reddit_account_connect_different_account_button_click
 */

/**
 * Renders a switch button that lets user connect with another Reddit account.
 *
 * @fires rfw_reddit_account_connect_different_account_button_click
 * @param {Object} props React props
 * @param {string} [props.text="Or, connect to a different Reddit account"] Text to display on the button
 */
const SwitchAccountButton = ( {
	text = __(
		'Or, connect to a different Reddit account',
		'reddit-for-woocommerce'
	),
	...restProps
} ) => {
	const [ handleSwitch, { loading } ] = useSwitchRedditAccount();

	return (
		<AppButton
			isLink
			disabled={ loading }
			text={ text }
			eventName="rfw_reddit_account_connect_different_account_button_click"
			onClick={ handleSwitch }
			{ ...restProps }
		/>
	);
};

export default SwitchAccountButton;
