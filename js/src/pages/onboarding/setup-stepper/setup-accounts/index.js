/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { getQuery } from '@woocommerce/navigation';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useRedditAccount from '~/hooks/useRedditAccount';
import StepContent from '~/components/stepper/step-content';
import WPComAccountCard from '~/components/wpcom-account-card';
import RedditComboAccountCard from '~/components/reddit-combo-account-card';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import useRedditAdsAccount from '~/hooks/useRedditAdsAccount';
import useRedditBusinessAccount from '~/hooks/useRedditBusinessAccount';
import useRedditPixelId from '~/hooks/useRedditPixelId';
import { useAppDispatch } from '~/data';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import './index.scss';

/**
 * Clicking on the "Continue" button to complete the onboarding process.
 *
 * @event rfw_onboarding_completed
 */

/**
 * Renders the onboarding setup accounts step.
 *
 * @fires rfw_onboarding_completed When the user clicks on the "Continue" button to complete the onboarding process.
 */
const SetupAccounts = ( props ) => {
	const { onContinue = noop } = props;
	const { products_token: productsTokenParam } = getQuery();
	const { jetpack } = useJetpackAccount();
	const { updateSettings, completeSetupAccounts } = useAppDispatch();
	const { hasConnection: hasBusinessConnection } = useRedditBusinessAccount();
	const { hasConnection: hasAdsConnection } = useRedditAdsAccount();
	const { hasConnection: hasPixelIdConnection } = useRedditPixelId();
	const [ isSubmitting, setSubmitting ] = useState( false );
	const { catalog_id: catalogId, hasFinishedResolution } =
		useRedditAccountConfig();
	const {
		isConnected: isRedditConnected,
		hasFinishedResolution: hasResolvedRedditAccount,
	} = useRedditAccount();

	const isCatalogCreated = catalogId && hasFinishedResolution;

	/**
	 * When jetpack is loading, or when Reddit account is loading,
	 *  we display the AppSpinner.
	 *
	 * The account loading is in sequential manner, one after another.
	 * @todo add reddit account loading state when available.
	 */
	const isLoadingJetpack = ! jetpack;
	const isJetpackActive = jetpack?.active === 'yes';

	const isContinueButtonDisabled =
		! isJetpackActive ||
		! isRedditConnected ||
		! hasBusinessConnection ||
		! hasAdsConnection ||
		! hasPixelIdConnection ||
		! isCatalogCreated;

	useEffect( () => {
		if ( ! productsTokenParam ) {
			return;
		}

		( async () => {
			await updateSettings( { productsToken: productsTokenParam } );
		} )();
	}, [ productsTokenParam ] );

	if ( isLoadingJetpack || ! hasResolvedRedditAccount ) {
		return <AppSpinner />;
	}

	const handleOnClick = async () => {
		setSubmitting( true );
		completeSetupAccounts()
			.then( () => {
				onContinue();
			} )
			.catch( () => {
				setSubmitting( false );
			} );
	};

	return (
		<StepContent>
			<StepContentHeader
				title={ __( 'Set up your accounts', 'reddit-for-woocommerce' ) }
				description={ __(
					'Connect the accounts required to use Reddit integration.',
					'reddit-for-woocommerce'
				) }
			/>
			<Section
				className="rfw-wp-reddit-accounts-section"
				title={ __( 'Connect accounts', 'reddit-for-woocommerce' ) }
				description={ __(
					'The following accounts are required to use the Reddit plugin.',
					'reddit-for-woocommerce'
				) }
			>
				<WPComAccountCard jetpack={ jetpack } />
				<RedditComboAccountCard disabled={ ! isJetpackActive } />
			</Section>

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						loading={ isSubmitting }
						text={ __( 'Continue', 'reddit-for-woocommerce' ) }
						eventName="rfw_onboarding_completed"
						onClick={ handleOnClick }
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
