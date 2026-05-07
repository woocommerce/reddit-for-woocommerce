/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import redditLogoURL from '~/images/logo/reddit.svg';

const { isConnected, setupUrl, settingsUrl } =
	window.redditAdsChannelVisibilityData || {};

const RedditAdsPromo = () => {
	const url = isConnected ? settingsUrl : setupUrl;
	const title = isConnected
		? __( 'Advertise on Reddit', 'reddit-for-woocommerce' )
		: __( 'Get your products on Reddit', 'reddit-for-woocommerce' );
	const description = isConnected
		? __(
				'Your products are synced with Reddit. Manage your catalog in Reddit settings.',
				'reddit-for-woocommerce'
		  )
		: __(
				"Sync your products to reach customers when they're searching for products like yours across Reddit.",
				'reddit-for-woocommerce'
		  );
	const ctaLabel = isConnected
		? __( 'Manage settings', 'reddit-for-woocommerce' )
		: __( 'Get started', 'reddit-for-woocommerce' );

	return (
		<Flex className="rfw-channel-visibility" direction="column" gap={ 3 }>
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
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
			</FlexBlock>
			<Flex
				className="rfw-channel-visibility__content"
				direction="column"
				gap={ 3 }
			>
				<FlexBlock>
					<h3 className="rfw-channel-visibility__title">{ title }</h3>
				</FlexBlock>
				<FlexBlock>
					<p>{ description }</p>
				</FlexBlock>
				<FlexBlock>
					<AppButton href={ url } isSecondary>
						{ ctaLabel }
					</AppButton>
				</FlexBlock>
			</Flex>
		</Flex>
	);
};

export default RedditAdsPromo;
