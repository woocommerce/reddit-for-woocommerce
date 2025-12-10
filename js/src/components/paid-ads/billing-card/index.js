/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import './billing-card.scss';

/**
 * @return {JSX.Element} Card filled with content.
 */
const BillingCard = () => {
	const handleClick = ( e ) => {
		if ( e.currentTarget.nodeName === 'BUTTON' ) {
			window.open(
				'https://ads.reddit.com/billing',
				'_blank',
				'noopener,noreferrer'
			);
		}
	};

	return (
		<Section>
			<Section.Card className="rfw-reddit-ads-billing-setup-card">
				<Section.Card.Body>
					<div className="rfw-reddit-ads-billing-setup-card__description">
						{ __(
							"To run ads on Reddit, you need to have billing information set up in your Reddit Ads account. If you haven't set up billing yet, please do so before continuing.",
							'reddit-for-woocommerce'
						) }
					</div>
					<AppButton isSecondary onClick={ handleClick }>
						{ __( 'Set up billing', 'reddit-for-woocommerce' ) }
					</AppButton>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default BillingCard;
