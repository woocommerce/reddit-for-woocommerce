/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';
import CreateBusinessAccountButton from '~/components/create-business-account-button';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';
import './create-business-account-notice.scss';

/**
 * React component that displays a warning notice and a button to create a Reddit Business Account
 * if no existing business accounts are found for the user.
 *
 * @return {JSX.Element|null} A warning notice with a button to create a business account, or null if accounts exist or resolution is not finished.
 */
const CreateBusinessAccountNotice = () => {
	const { existingAccounts, hasFinishedResolution } =
		useExistingBusinessAccounts();

	if ( ! hasFinishedResolution || existingAccounts?.length > 0 ) {
		return null;
	}

	return (
		<AppNotice
			status="warning"
			isDismissible={ false }
			className="rfw-reddit-create-business-account-notice"
		>
			<p>
				{ __(
					"We couldn't find a Reddit Business Account connected to your user.",
					'reddit-for-woocommerce'
				) }
			</p>
			<CreateBusinessAccountButton isPrimary />
		</AppNotice>
	);
};

export default CreateBusinessAccountNotice;
