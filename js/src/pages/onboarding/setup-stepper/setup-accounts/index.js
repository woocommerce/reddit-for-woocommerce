/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

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
import RedditAccountCard from '~/components/reddit-account-card';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import './index.scss';

const SetupAccounts = ( props ) => {
	const { onContinue = noop } = props;
	const { jetpack } = useJetpackAccount();
	const {
		isConnected: isRedditConnected,
		hasFinishedResolution: hasResolvedRedditAccount,
	} = useRedditAccount();

	/**
	 * When jetpack is loading, or when Reddit account is loading,
	 *  we display the AppSpinner.
	 *
	 * The account loading is in sequential manner, one after another.
	 * @todo add reddit account loading state when available.
	 */
	const isLoadingJetpack = ! jetpack;
	const isJetpackActive = jetpack?.active === 'yes';

	if ( isLoadingJetpack || ! hasResolvedRedditAccount ) {
		return <AppSpinner />;
	}

	const handleOnClick = () => {
		onContinue();
	};

	const isContinueButtonDisabled = ! isJetpackActive || ! isRedditConnected;
	const isSubmitting = false;

	return (
		<StepContent>
			<StepContentHeader
				title={ __( 'Set up your accounts', 'reddit-for-woo' ) }
				description={ __(
					'Connect the accounts required to use Reddit integration.',
					'reddit-for-woo'
				) }
			/>
			<Section
				className="rfw-wp-reddit-accounts-section"
				title={ __( 'Connect accounts', 'reddit-for-woo' ) }
				description={ __(
					'The following accounts are required to use the Reddit plugin.',
					'reddit-for-woo'
				) }
			>
				<WPComAccountCard jetpack={ jetpack } />
				<RedditAccountCard disabled={ ! isJetpackActive } />
			</Section>

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						loading={ isSubmitting }
						text={ __( 'Continue', 'reddit-for-woo' ) }
						onClick={ handleOnClick }
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
