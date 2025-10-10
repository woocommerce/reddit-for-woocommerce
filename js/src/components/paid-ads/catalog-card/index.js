/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import './catalog-card.scss';
import useSettings from '~/hooks/useSettings';
import useCreateCatalog from '~/hooks/useCreateCatalog';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import { Link } from '@woocommerce/components';

/**
 * @return {JSX.Element} Card filled with content.
 */
const CatalogCard = () => {
	const { catalogId, hasFinishedResolution } = useSettings();
	const {
		business_id: businessId,
		hasFinishedResolution: hasFinishedResolutionRedditAccountConfig,
	} = useRedditAccountConfig();

	const { createCatalog, loading, createdCatalogId } = useCreateCatalog();

	if (
		! hasFinishedResolution ||
		! hasFinishedResolutionRedditAccountConfig ||
		catalogId ||
		createdCatalogId ||
		! businessId
	) {
		return null;
	}

	const rolesLink = `https://ads.reddit.com/business/${ businessId }/members`;

	return (
		<Section>
			<Section.Card className="rfw-reddit-ads-catalog-setup-card">
				<Section.Card.Body>
					<div className="rfw-reddit-ads-catalog-setup-card__description">
						<p>
							{ __(
								"To run dynamic product ads on Reddit, you need to have a catalog set up in your Reddit Ads account. Your account doesn't have the Catalog Manager role, which is required for catalog creation. Please assign it by going to",
								'reddit-for-woocommerce'
							) }{ ' ' }
							<strong>
								{ __(
									'Your Account › Edit › Member Details & Business Role › Advanced Role',
									'reddit-for-woocommerce'
								) }
							</strong>{ ' ' }
							<Link
								href={ rolesLink }
								target="_blank"
								rel="noopener noreferrer"
								type="external"
							>
								{ __( 'here.', 'reddit-for-woocommerce' ) }
							</Link>{ ' ' }
							{ __(
								'Once the role is assigned, please click Create Catalog.',
								'reddit-for-woocommerce'
							) }
						</p>
					</div>
					<AppButton
						isSecondary
						onClick={ createCatalog }
						isBusy={ loading }
						isDisabled={ loading }
					>
						{ __( 'Create Catalog', 'reddit-for-woocommerce' ) }
					</AppButton>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default CatalogCard;
