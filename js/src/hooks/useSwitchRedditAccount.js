/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useRedditAuthorization from '~/hooks/useRedditAuthorization';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * Custom React hook to handle switching Reddit accounts within the WooCommerce plugin.
 *
 * This hook provides a function to disconnect the current Reddit account and initiate
 * the connection flow for a new Reddit account. It manages loading states and user notifications.
 *
 * @return {Array} `[ handleSwitch, { loading } ]`
 *     - handleSwitch: Function to trigger the account switch process.
 *     - An object with a `loading` boolean indicating if the process is ongoing.
 */
const useSwitchRedditAccount = () => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();
	const { disconnectRedditAccount } = useAppDispatch();
	const [ loadingRedditDisconnect, setLoadingRedditDisconnect ] =
		useState( false );

	const [
		fetchRedditConnect,
		{ loading: loadingRedditConnect, data: dataRedditConnect },
	] = useRedditAuthorization( 'setup-reddit' );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Reddit account, please wait…',
				'reddit-for-woocommerce'
			)
		);

		setLoadingRedditDisconnect( true );
		try {
			await disconnectRedditAccount();
			const { url } = await fetchRedditConnect();
			if ( url ) {
				window.location.href = url;
			}
		} catch ( error ) {
			removeNotice( notice.id );
			createNotice(
				'error',
				__(
					'Unable to connect to a different Reddit account. Please try again later.',
					'reddit-for-woocommerce'
				)
			);
		} finally {
			setLoadingRedditDisconnect( false );
		}
	};

	const loading =
		loadingRedditDisconnect || loadingRedditConnect || dataRedditConnect;

	return [ handleSwitch, { loading } ];
};

export default useSwitchRedditAccount;
