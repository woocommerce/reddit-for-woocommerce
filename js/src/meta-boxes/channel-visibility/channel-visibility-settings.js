/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { startCase } from 'lodash';
import { useState } from '@wordpress/element';
import {
	Flex,
	FlexBlock,
	FlexItem,
	FormToggle,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import redditLogoURL from '~/images/logo/reddit.svg';
import { SYNC_STATUS_HAS_ERRORS, SYNC_STATUS_SYNCED } from './constants';

/**
 * Channel Visibility Settings component.
 *
 * Renders an uncontrolled toggle that participates in the WC product form
 * submission via its `name` attribute. No REST endpoints are called.
 *
 * @return {JSX.Element} The Channel Visibility Settings component.
 */
const ChannelVisibilitySettings = () => {
	const {
		channelVisibility: {
			field_name: fieldName,
			product_catalog_item: productCatalogItem,
			product_is_visible: productIsVisible,
			sync_status: syncStatus = null,
			issues = [],
		} = {},
	} = window.redditAdsMetaBoxData || {};
	const catalogValue = productCatalogItem || '1';
	const defaultChecked = productIsVisible && catalogValue === '1';

	const [ checked, setChecked ] = useState( defaultChecked );

	let syncStatusText = null;

	if ( syncStatus === SYNC_STATUS_HAS_ERRORS ) {
		syncStatusText = __( 'Issues detected', 'reddit-for-woocommerce' );
	} else if ( syncStatus ) {
		// Convert the sync status to a human-readable string, e.g. "not-synced" -> "Not Synced"
		syncStatusText = startCase( syncStatus );
	}

	const shouldDisplaySyncNotice =
		productIsVisible && checked && syncStatus !== SYNC_STATUS_SYNCED;
	const hasIssues = issues.length > 0;

	return (
		<Flex direction="column" gap={ 4 } className="rfw-channel-visibility">
			<Flex direction="column" gap={ 4 }>
				<FlexBlock>
					<Flex gap={ 2 } align="center">
						<FlexItem>
							<Flex gap={ 2 } align="center">
								<FlexItem>
									<img
										className="rfw-channel-visibility__logo"
										src={ redditLogoURL }
										alt={ __(
											'Reddit Logo',
											'reddit-for-woocommerce'
										) }
										width={ 16 }
										height={ 16 }
									/>
								</FlexItem>
								<FlexItem>
									{ __( 'Reddit', 'reddit-for-woocommerce' ) }
								</FlexItem>
							</Flex>
						</FlexItem>

						<FlexItem>
							{ /*
							 * Unchecked checkboxes are not sent via POST.
							 * This hidden input submits the OFF value whenever
							 * the control is on the page.
							 */ }
							<input type="hidden" name={ fieldName } value="0" />
							<FormToggle
								aria-label={ __(
									'Channel visibility setting',
									'reddit-for-woocommerce'
								) }
								name={ fieldName }
								value="1"
								checked={ checked }
								onChange={ ( event ) =>
									setChecked( event.target.checked )
								}
								disabled={ ! productIsVisible }
							/>
						</FlexItem>
					</Flex>
				</FlexBlock>

				{ ! productIsVisible && (
					<FlexBlock>
						<Notice status="info" isDismissible={ false }>
							<p>
								{ __(
									'This product cannot be shown on any channel because it is hidden from your store catalog.',
									'reddit-for-woocommerce'
								) }
							</p>
						</Notice>
					</FlexBlock>
				) }

				{ shouldDisplaySyncNotice && syncStatusText && (
					<FlexBlock>
						<Notice
							className="rfw-channel-visibility__sync-notice"
							isDismissible={ false }
							status={ hasIssues ? 'warning' : 'info' }
						>
							<p>
								<strong>
									{ __(
										'Reddit sync status',
										'reddit-for-woocommerce'
									) }
								</strong>
							</p>
							<p className="rfw-channel-visibility__sync-status">
								{ syncStatusText }
							</p>

							{ hasIssues && (
								<>
									<p>
										<strong>
											{ __(
												'Issues',
												'reddit-for-woocommerce'
											) }
										</strong>
									</p>
									<ul>
										{ issues.map( ( issue ) => (
											<li key={ issue }>{ issue }</li>
										) ) }
									</ul>
								</>
							) }
						</Notice>
					</FlexBlock>
				) }
			</Flex>
		</Flex>
	);
};

export default ChannelVisibilitySettings;
