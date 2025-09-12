/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, TextControl } from '@wordpress/components';
import {
	useState,
	useCallback,
	useEffect,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppDocumentationLink from '~/components/app-documentation-link';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useDebouncedInput from '~/hooks/useDebouncedInput';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';
import './index.scss';

/**
 * ConversionsAPI component for managing the tracking setting.
 *
 * Renders a card UI allowing users to enable or disable server-side conversion event tracking.
 * Handles asynchronous state updates and displays success notifications upon status change.
 * Shows a loading spinner while the current tracking status is being resolved.
 *
 * @return {JSX.Element} The rendered ConversionsAPI settings card.
 */
const ConversionsAPI = () => {
	const { isCapiEnabled, capiToken, hasFinishedResolution, adAccountId } =
		useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const [ localCapiToken, setLocalCapiToken, debouncedLocalCapiToken ] =
		useDebouncedInput( '' );
	const { createNotice } = useDispatchCoreNotices();
	const { updateSettings } = useAppDispatch();

	const toggleTrackConversions = useCallback( async () => {
		await updateSettings( { trackConversions: ! isCapiEnabled } );
	}, [ updateSettings, isCapiEnabled ] );

	const updateConversionAccessToken = useCallback(
		async ( val ) => {
			await updateSettings( { capiToken: val } );
		},
		[ updateSettings ]
	);

	const handleCapiStatusOnChange = async () => {
		try {
			setIsSaving( true );
			await toggleTrackConversions();

			createNotice(
				'success',
				__(
					'Conversions API Tracking status updated successfully.',
					'reddit-for-woocommerce'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	const handleCapiTokenOnChange = useCallback(
		async ( val = '' ) => {
			try {
				setIsSaving( true );
				await updateConversionAccessToken( val );

				createNotice(
					'success',
					__(
						'Conversions API Access Token updated successfully.',
						'reddit-for-woocommerce'
					)
				);
			} catch ( error ) {
				// Silently fail because the error is handled within `updateSettings` action.
			} finally {
				setIsSaving( false );
			}
		},
		[ updateConversionAccessToken, createNotice ]
	);

	/**
	 * Validates and sets the CAPI token state.
	 *
	 * @param {string} token Input CAPI token
	 */
	function setToken( token ) {
		if ( token !== '' && token.trim() === '' ) {
			// Return early if the string is only '\s'
			return;
		}

		setLocalCapiToken( token );
	}

	useEffect( () => {
		if ( hasFinishedResolution ) {
			setLocalCapiToken( capiToken );
		}
	}, [ hasFinishedResolution, setLocalCapiToken, capiToken ] );

	useEffect( () => {
		if ( undefined === capiToken ) {
			return;
		}

		if ( capiToken !== debouncedLocalCapiToken ) {
			handleCapiTokenOnChange( debouncedLocalCapiToken );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ debouncedLocalCapiToken, handleCapiTokenOnChange ] );

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<AccountCard
			className="rfw-settings-track-conversions"
			title={ __( 'Conversions API', 'reddit-for-woocommerce' ) }
			description={ __(
				'Send server-side conversion events to improve attribution.',
				'reddit-for-woocommerce'
			) }
			actions={
				<>
					<div>
						<TextControl
							label={ __(
								'Conversion Access Token',
								'reddit-for-woocommerce'
							) }
							value={ localCapiToken }
							readOnly={ isSaving }
							onChange={ setToken }
							help={
								<>
									{ createInterpolateElement(
										__(
											'Need help? <link>Follow this link</link>',
											'reddit-for-woocommerce'
										),
										{
											link: (
												<AppDocumentationLink
													href={
														adAccountId
															? `https://ads.reddit.com/account/${ adAccountId?.substring(
																	3
															  ) }/events-manager/conversion-tokens`
															: 'https://business.reddithelp.com/s/article/conversion-access-token'
													}
												/>
											),
										}
									) }
								</>
							}
						/>
					</div>
					<div className="rfw-settings-track-conversions__actions">
						<CheckboxControl
							label={ __(
								'Enable Conversions API tracking',
								'reddit-for-woocommerce'
							) }
							checked={ isCapiEnabled }
							disabled={ isSaving || ! localCapiToken }
							onChange={ handleCapiStatusOnChange }
							help={
								! localCapiToken
									? __(
											'Set the Conversion Access Token to enable tracking',
											'reddit-for-woocommerce'
									  )
									: ''
							}
						/>
					</div>
				</>
			}
		/>
	);
};

export default ConversionsAPI;
