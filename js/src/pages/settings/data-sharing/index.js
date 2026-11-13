/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	CardDivider,
	RadioControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import { recordRfwEvent } from '~/utils/tracks';

const SHARING_ADVANCED = 'advanced';
const SHARING_STANDARD = 'standard';

/**
 * Toggle the Conversions API tracking with the "Share data with Reddit" toggle.
 *
 * @event rfw_conversion_tracking_toggle
 * @property {string} status (`on`|`off`) - indicates the status of the Conversions API tracking.
 */

/**
 * Toggle the Collect Customer PII setting by choosing Advanced Sharing (`on`) or Standard Sharing (`off`).
 *
 * @event rfw_collect_pii_toggle
 * @property {string} status (`on`|`off`) - indicates the status of the Collect Customer PII setting.
 */

/**
 * DataSharing component for managing what customer data is shared with Reddit.
 *
 * The "Share data with Reddit" toggle maps to the Conversions API tracking setting.
 * While it's on, Advanced Sharing and Standard Sharing map to the Collect Customer PII setting being on and off.
 *
 * @fires rfw_conversion_tracking_toggle When the user toggles "Share data with Reddit".
 * @fires rfw_collect_pii_toggle When the user picks Advanced Sharing or Standard Sharing.
 *
 * @return {JSX.Element} The rendered data sharing settings card.
 */
const DataSharing = () => {
	const { isCapiEnabled, isPiiCollectionEnabled, hasFinishedResolution } =
		useSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateSettings } = useAppDispatch();

	const saveSettings = async ( settings ) => {
		setIsSaving( true );

		try {
			await updateSettings( settings );
			return true;
		} catch {
			// Silently fail because the error is handled within `updateSettings` action.
			return false;
		} finally {
			setIsSaving( false );
		}
	};

	const handleShareDataChange = async ( isEnabled ) => {
		if ( ! ( await saveSettings( { trackConversions: isEnabled } ) ) ) {
			return;
		}

		recordRfwEvent( 'rfw_conversion_tracking_toggle', {
			status: isEnabled ? 'on' : 'off',
		} );
		createNotice(
			'success',
			__(
				'Data sharing status updated successfully.',
				'reddit-for-woocommerce'
			)
		);
	};

	const handleSharingLevelChange = async ( value ) => {
		const collectPii = value === SHARING_ADVANCED;

		if ( ! ( await saveSettings( { collectPii } ) ) ) {
			return;
		}

		recordRfwEvent( 'rfw_collect_pii_toggle', {
			status: collectPii ? 'on' : 'off',
		} );
	};

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return (
		<Section.Card className="rfw-data-sharing">
			<Section.Card.Body>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Share data with Reddit',
						'reddit-for-woocommerce'
					) }
					help={ __(
						"Reddit Pixel shares data from customer actions on your website to measure conversions. Without it, you can't measure conversions from your Reddit campaigns.",
						'reddit-for-woocommerce'
					) }
					checked={ isCapiEnabled }
					disabled={ isSaving }
					onChange={ handleShareDataChange }
				/>

				{ isCapiEnabled && (
					<>
						<CardDivider margin={ 6 } />

						{ /* Hidden since the radio control already announces this text. */ }
						<Section.Card.Title aria-hidden="true">
							{ __(
								'Choose what to share',
								'reddit-for-woocommerce'
							) }
						</Section.Card.Title>

						<RadioControl
							label={ __(
								'Choose what to share',
								'reddit-for-woocommerce'
							) }
							hideLabelFromVision
							selected={
								isPiiCollectionEnabled
									? SHARING_ADVANCED
									: SHARING_STANDARD
							}
							options={ [
								{
									value: SHARING_ADVANCED,
									label: __(
										'Advanced Sharing',
										'reddit-for-woocommerce'
									),
									description: __(
										'This option uses Reddit Pixel to share all available data from customer actions on your website, including customer phone number and e-mail.',
										'reddit-for-woocommerce'
									),
								},
								{
									value: SHARING_STANDARD,
									label: __(
										'Standard Sharing',
										'reddit-for-woocommerce'
									),
									description: __(
										'This option uses Reddit Pixel to share data from customer actions on your website, excluding customer phone number and e-mail.',
										'reddit-for-woocommerce'
									),
								},
							] }
							disabled={ isSaving }
							onChange={ handleSharingLevelChange }
						/>
					</>
				) }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default DataSharing;
