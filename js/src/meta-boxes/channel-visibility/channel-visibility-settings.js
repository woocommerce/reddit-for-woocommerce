/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import redditLogoURL from '~/images/logo/reddit.svg';
import { SYNC_STATUS_HAS_ERRORS, SYNC_STATUS_SYNCED } from './constants';

const {
	channelVisibility: {
		field_name: fieldName,
		product_catalog_item: productCatalogItem,
		product_is_visible: productIsVisible,
		options: syncOptions,
		sync_status: syncStatus = null,
		issues = [],
	} = {},
} = window.redditAdsMetaBoxData || {};

/**
 * Channel Visibility Settings component.
 *
 * Renders an uncontrolled SelectControl that participates in the WC product
 * form submission via its `name` attribute. No REST endpoints are called.
 *
 * @return {JSX.Element} The Channel Visibility Settings component.
 */
const ChannelVisibilitySettings = () => {
	const defaultValue = productIsVisible ? productCatalogItem || '1' : '0';

	const [ value, setValue ] = useState( defaultValue );

	let syncStatusText = null;

	if ( syncStatus === SYNC_STATUS_HAS_ERRORS ) {
		syncStatusText = __( 'Issues detected', 'reddit-for-woocommerce' );
	} else if ( syncStatus ) {
		syncStatusText =
			syncStatus.charAt( 0 ).toUpperCase() +
			syncStatus.slice( 1 ).replace( '-', ' ' );
	}

	const shouldDisplaySyncNotice =
		productIsVisible &&
		syncStatus &&
		value === '1' &&
		syncStatus !== SYNC_STATUS_SYNCED;

	const hasIssues = issues.length > 0;

	return (
		<Flex direction="column" gap={ 4 } className="rfw-channel-visibility">
			<Flex direction="column" gap={ 4 }>
				<FlexBlock>
					<Flex gap={ 2 } align="center" justify="flex-start">
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

						<FlexBlock>
							<SelectControl
								aria-label={ __(
									'Channel visibility setting',
									'reddit-for-woocommerce'
								) }
								name={ fieldName }
								options={ syncOptions }
								value={ value }
								onChange={ setValue }
								disabled={ ! productIsVisible }
								__nextHasNoMarginBottom
							/>
						</FlexBlock>
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
