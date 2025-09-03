/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import './index.scss';

/**
 * "Help" button is clicked.
 *
 * @event rfw_help_click
 * @property {string} context Indicates the place where the button is located, e.g. `setup-ads`.
 */

/**
 * Renders a button with a help icon and "Help" text.
 * Upon click, it will open documentation page in a new tab,
 * and call `rfw_help_click` track event.
 *
 * @fires rfw_help_click
 *
 * @param {Object} props Props
 * @param {string} props.eventContext Context to be used in `rfw_help_click` track event.
 * @return {JSX.Element} The button.
 */
const HelpIconButton = ( { eventContext } ) => {
	return (
		<AppButton
			className="rfw-help-icon-button"
			href="https://woocommerce.com/document/reddit-for-woocommerce/" // @TODO: Review
			target="_blank"
			eventName="rfw_help_click"
			eventProps={ {
				context: eventContext,
			} }
		>
			<GridiconHelpOutline />
			{ __( 'Help', 'reddit-for-woocommerce' ) }
		</AppButton>
	);
};

export default HelpIconButton;
