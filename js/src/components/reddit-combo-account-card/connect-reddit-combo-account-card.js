/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { rfwData } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * Clicking on the button to connect a Reddit account.
 *
 * @event rfw_reddit_account_connect_button_click
 * @property {string} context (`setup-reddit`|`reconnect`) - indicates from which page the button was clicked.
 */

/**
 * @fires rfw_reddit_account_connect_button_click
 */
const ConnectRedditComboAccountCard = ( { disabled } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const nextPageName = rfwData?.setupComplete ? 'reconnect' : 'setup-reddit';
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/reddit/connect`, query );
	const [ fetchRedditConnect, { loading, data } ] = useApiFetchCallback( {
		path,
	} );

	const handleConnectClick = async () => {
		try {
			const d = await fetchRedditConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Reddit account. Please try again later.',
					'reddit-for-woocommerce'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.REDDIT }
			disabled={ disabled }
			description={ __(
				'Connect your Reddit Business Account to sync your catalog and launch campaigns.',
				'reddit-for-woocommerce'
			) }
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled }
					loading={ loading || data }
					eventName="rfw_reddit_account_connect_button_click"
					eventProps={ { context: nextPageName } }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'reddit-for-woocommerce' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectRedditComboAccountCard;
