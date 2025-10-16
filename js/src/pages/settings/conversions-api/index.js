/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import AccountCard from '~/components/account-card';
import SpinnerCard from '~/components/spinner-card';
import './index.scss';
import { recordRfwEvent } from '~/utils/tracks';

/**
 * Toggle the Conversions API tracking.
 *
 * @event rfw_conversion_tracking_toggle
 * @property {string} status (`on`\|`off`) - indicates the status of the Conversions API tracking.
 */

/**
 * ConversionsAPI component for managing the tracking setting.
 *
 * Renders a card UI allowing users to enable or disable server-side conversion event tracking.
 * Handles asynchronous state updates and displays success notifications upon status change.
 * Shows a loading spinner while the current tracking status is being resolved.
 *
 * @fires rfw_conversion_tracking_toggle When the user toggles the Conversions API tracking.
 *
 * @return {JSX.Element} The rendered ConversionsAPI settings card.
 */
const ConversionsAPI = () => {
	const { isCapiEnabled, hasFinishedResolution } = useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateSettings } = useAppDispatch();

	const toggleTrackConversions = useCallback( async () => {
		await updateSettings( { trackConversions: ! isCapiEnabled } );
		recordRfwEvent( 'rfw_conversion_tracking_toggle', {
			status: ! isCapiEnabled ? 'on' : 'off',
		} );
	}, [ updateSettings, isCapiEnabled ] );

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
				<div className="rfw-settings-track-conversions__actions">
					<CheckboxControl
						label={ __(
							'Enable Conversions API tracking',
							'reddit-for-woocommerce'
						) }
						checked={ isCapiEnabled }
						disabled={ isSaving }
						onChange={ handleCapiStatusOnChange }
					/>
				</div>
			}
		/>
	);
};

export default ConversionsAPI;
