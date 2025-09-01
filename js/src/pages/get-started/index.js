/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { rfwData, SETUP_STATUS } from '~/constants';
import useSetup from '~/hooks/useSetup';
import { getOnboardingUrl, getSettingsUrl } from '~/utils/urls';

const GetStarted = () => {
	const {
		data: { status },
	} = useSetup();
	const onboardingUrl = getOnboardingUrl();
	const settingsUrl = getSettingsUrl();

	const redirectUrl =
		rfwData.setupComplete || status === SETUP_STATUS.CONNECTED
			? settingsUrl
			: onboardingUrl;
	getHistory().replace( redirectUrl );
	return null;
};

export default GetStarted;
