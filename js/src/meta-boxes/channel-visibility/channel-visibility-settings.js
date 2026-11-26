/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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

/**
 * Channel Visibility Settings component.
 *
 * Renders a toggle whose state is submitted with the WC product form through
 * a hidden input. No REST endpoints are called.
 *
 * @return {JSX.Element} The Channel Visibility Settings component.
 */
const ChannelVisibilitySettings = () => {
	const {
		channelVisibility: {
			field_name: fieldName,
			product_catalog_item: productCatalogItem,
			product_is_visible: productIsVisible,
		} = {},
	} = window.redditAdsMetaBoxData || {};
	const catalogValue = productCatalogItem || '1';
	const defaultChecked = productIsVisible && catalogValue === '1';

	const [ checked, setChecked ] = useState( defaultChecked );

	return (
		<Flex
			className="rfw-channel-visibility rfw-channel-visibility--settings"
			direction="column"
			gap={ 4 }
		>
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
							 * Unchecked checkboxes are not sent via POST, so
							 * this hidden input submits the toggle state. It is
							 * disabled while the product is hidden from the store
							 * catalog, so saving then keeps the stored opt-in.
							 */ }
							<input
								type="hidden"
								name={ fieldName }
								value={ checked ? '1' : '0' }
								disabled={ ! productIsVisible }
							/>
							<FormToggle
								aria-label={ __(
									'Channel visibility setting',
									'reddit-for-woocommerce'
								) }
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
						<Notice
							className="rfw-channel-visibility__notice"
							status="info"
							isDismissible={ false }
						>
							<p>
								{ __(
									'This product cannot be shown on any channel because it is hidden from your store catalog.',
									'reddit-for-woocommerce'
								) }
							</p>
						</Notice>
					</FlexBlock>
				) }
			</Flex>
		</Flex>
	);
};

export default ChannelVisibilitySettings;
