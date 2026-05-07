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
	window.redditAdsOrderAttributionData || {};

const RedditAdsPromo = () => {
	const url = isConnected ? settingsUrl : setupUrl;
	const title = isConnected
		? __( 'Boost sales with Reddit Ads', 'reddit-for-woocommerce' )
		: __( 'Get started with Reddit Ads', 'reddit-for-woocommerce' );
	const description = isConnected
		? __(
				'Launch a Reddit Ads campaign and get your products discovered by high-intent shoppers across Reddit.',
				'reddit-for-woocommerce'
		  )
		: __(
				'Create or connect a Reddit Ads account to start running campaigns and reach customers across Reddit.',
				'reddit-for-woocommerce'
		  );
	const ctaLabel = isConnected
		? __( 'Create campaign', 'reddit-for-woocommerce' )
		: __( 'Get started', 'reddit-for-woocommerce' );

	return (
		<Flex
			className="rfw-order-attribution-promo"
			direction="column"
			gap={ 3 }
		>
			<FlexBlock>
				<Flex gap={ 2 } align="center">
					<FlexItem>
						<img
							className="rfw-order-attribution-promo__logo"
							src={ redditLogoURL }
							alt={ __(
								'Reddit Logo',
								'reddit-for-woocommerce'
							) }
							width={ 24 }
							height={ 24 }
						/>
					</FlexItem>
					<FlexBlock>
						<h3 className="rfw-order-attribution-promo__title">
							{ title }
						</h3>
					</FlexBlock>
				</Flex>
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
	);
};

export default RedditAdsPromo;
