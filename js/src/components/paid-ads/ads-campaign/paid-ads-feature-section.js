/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import './paid-ads-features-section.scss';

export default function PaidAdsFeaturesSection() {
	return (
		<Section
			className="rfw-paid-ads-features-section"
			title={ __( 'Ad campaign', 'reddit-for-woocommerce' ) }
			description={ __(
				'Setup an Ad campaign.',
				'reddit-for-woocommerce'
			) }
		>
			<Section.Card>
				<Section.Card.Body>
					<FlexBlock>
						<Section.Card.Title>
							{ __(
								'Use Dynamic Ads to Promote Products',
								'reddit-for-woocommerce'
							) }
						</Section.Card.Title>
						<div className="rfw-paid-ads-features-section__subtitle">
						{ __(
							'Reddit Dynamic Ads automatically turn your product catalog into creative, delivering relevant, personalized ads across Reddit — including:.',
							'reddit-for-woocommerce'
						) }
					</div>
					</FlexBlock>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
}
