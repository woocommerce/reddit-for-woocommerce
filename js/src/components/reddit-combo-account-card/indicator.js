/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '~/components/connected-icon-label';
import LoadingLabel from '~/components/loading-label';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';
import useRedditPixelId from '~/hooks/useRedditPixelId';

/**
 * Account connection indicator.
 * Displays a loading indicator when accounts are being connected or a connected icon when accounts are connected.
 * @param {Object} props Component props.
 * @param {boolean} props.showSpinner Whether to display a spinner.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = ( { showSpinner } ) => {
	const { hasConnection: hasBusinessConnection } = useRedditBusinessAccount();
	const { hasConnection: hasAdsConnection } = useRedditAdsAccount();
	const { hasConnection: hasPixelIdConnection } = useRedditPixelId();

	if ( showSpinner ) {
		return (
			<LoadingLabel
				text={ __( 'Connecting…', 'reddit-for-woocommerce' ) }
			/>
		);
	}

	if ( hasBusinessConnection && hasAdsConnection && hasPixelIdConnection ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
