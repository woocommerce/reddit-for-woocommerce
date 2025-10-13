/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock } from '@wordpress/components';
import { megaphone, tag, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import FreeAdCredit from '~/components/free-ad-credit';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import mobileViewUrl from '~/images/logo/mobile-view.svg';
import './paid-ads-features-section.scss';

function FeatureList() {
	const options = [
		{
			icon: tag,
			title: __( 'Feed', 'reddit-for-woocommerce' ),
			description: __(
				"Appear directly in Reddit's home and community feeds, blending with user-generated content.",
				'reddit-for-woocommerce'
			),
		},
		{
			icon: megaphone,
			title: __( 'Conversation threads', 'reddit-for-woocommerce' ),
			description: __(
				'Show ads within relevant discussions where people are actively engaging with topics related to your products.',
				'reddit-for-woocommerce'
			),
		},
		{
			icon: tag,
			title: __( 'Explore tab', 'reddit-for-woocommerce' ),
			description: __(
				'Reach users browsing trending posts and communities.',
				'reddit-for-woocommerce'
			),
		},
	];

	const optionItems = options.map( ( option, index ) => (
		<div key={ index } className="rfw-paid-ads-feature-list-item">
			<div className="rfw-paid-ads-feature-list-icon">
				<Icon icon={ option.icon } size={ 24 } />
			</div>
			<div>
				<div className="rfw-paid-ads-feature-list-title">
					{ option.title }
				</div>
				<div className="rfw-paid-ads-feature-list-description">
					{ option.description }
				</div>
			</div>
		</div>
	) );

	return (
		<div className="rfw-paid-ads-features-section__feature-info">
			<div className="rfw-paid-ads-feature-list">{ optionItems }</div>
			<div className="rfw-paid-ads-feature-mobile-view-image">
				<img
					src={ mobileViewUrl }
					alt={ __(
						'Reddit site mobile view',
						'reddit-for-woocommerce'
					) }
				/>
			</div>
		</div>
	);
}

/**
 * Renders a section layout to elaborate on the features of paid ads and show the buttons
 * for the next actions: skip or continue the paid ads setup.
 */
export default function PaidAdsFeaturesSection() {
	return (
		<Section
			className="rfw-paid-ads-features-section"
			title={ __( 'Ad campaign', 'reddit-for-woocommerce' ) }
		>
			<Section.Card>
				<Section.Card.Body>
					<div className="rfw-paid-ads-features-section__header">
						<Section.Card.Title className="rfw-paid-ads-section-title">
							{ __(
								'Use Dynamic Ads to Promote Products',
								'reddit-for-woocommerce'
							) }
						</Section.Card.Title>
						<p className="rfw-paid-ads-features-section__subtitle">
							{ __(
								'Reddit Dynamic Ads automatically turn your product catalog into creative, delivering relevant, personalized ads across Reddit — including:',
								'reddit-for-woocommerce'
							) }
						</p>
					</div>

					<VerticalGapLayout size="large">
						<Flex
							className="rfw-paid-ads-features-section__content"
							align="center"
							gap={ 9 }
						>
							<FlexBlock>
								<FeatureList />
							</FlexBlock>
						</Flex>
						<FreeAdCredit />
					</VerticalGapLayout>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
}
