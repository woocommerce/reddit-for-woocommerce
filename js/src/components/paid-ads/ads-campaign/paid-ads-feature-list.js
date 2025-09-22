/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import tagUrl from '~/images/logo/tag.svg';
import speakerUrl from '~/images/logo/speaker.svg';
import mobileViewUrl from '~/images/logo/mobile-view.svg';
import './paid-ads-feature-list.scss';

export default function PaidAdsFeatureList() {
	const options = [
		{
			iconUrl: tagUrl,
			title: __( 'Feed', 'reddit-for-woocommerce' ),
			description: __(
				"Appear directly in Reddit's home and community feeds, blending with user-generated content.",
				'reddit-for-woocommerce'
			),
		},
		{
			iconUrl: speakerUrl,
			title: __( 'Conversation threads', 'reddit-for-woocommerce' ),
			description: __(
				'Show ads within relevant discussions where people are actively engaging with topics related to your products.',
				'reddit-for-woocommerce'
			),
		},
		{
			iconUrl: tagUrl,
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
				{ /* eslint-disable-next-line jsx-a11y/alt-text */ }
				<img src={ option.iconUrl } width="16" height="16" />
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
