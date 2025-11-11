/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { rfwData, SETUP_STATUS } from '~/constants';
import useSetup from '~/hooks/useSetup';
import { getOnboardingUrl, getSettingsUrl } from '~/utils/urls';
import useAdminMenuLink from './useAdminMenuLink';

/**
 * Updates the "Reddit" admin nav menu link to
 * either /setup or /settings
 */
function useMenuLinkUpdate() {
	const {
		data: { status },
	} = useSetup();

	const { setupLink } = useAdminMenuLink();

	useEffect( () => {
		if ( ! setupLink ) {
			return;
		}

		const onboardingUrl = getOnboardingUrl();
		const settingsUrl = getSettingsUrl();

		const redirectUrl =
			rfwData.setupComplete || status === SETUP_STATUS.CONNECTED
				? settingsUrl
				: onboardingUrl;

		if (
			setupLink.getAttribute( 'href' ) ===
			'admin.php?page=wc-admin&path=%2Freddit%2Fsetup'
		) {
			setupLink.setAttribute( 'href', redirectUrl );
		}
	}, [ setupLink ] );
}

export default useMenuLinkUpdate;
