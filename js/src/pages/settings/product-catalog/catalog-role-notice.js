/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';
import AppButton from '~/components/app-button';
import useSettings from '~/hooks/useSettings';
import useCreateCatalog from '~/hooks/useCreateCatalog';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import './catalog-role-notice.scss';

/**
 * React component that displays a warning notice with link to set the catalog role and create the catalog
 * if no catalog is created for the user.
 *
 * @return {JSX.Element|null} A warning notice with a link to set the catalog role and create the catalog, or null if resolution is not finished.
 */
const CatalogRoleNotice = () => {
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
		<AppNotice
			status="warning"
			isDismissible={ false }
			className="rfw-reddit-catalog-role-notice"
		>
			<p>
				{ __(
					"Your account doesn't have the Catalog Manager role, which is required for catalog creation. Please assign it by going to",
					'reddit-for-woo'
				) }{ ' ' }
				<strong>
					{ __(
						'Your Account › Edit › Member Details & Business Role › Advanced Role',
						'reddit-for-woo'
					) }
				</strong>{ ' ' }
				<Link
					href={ rolesLink }
					target="_blank"
					rel="noopener noreferrer"
					type="external"
				>
					{ __( 'here.', 'reddit-for-woo' ) }
				</Link>{ ' ' }
				{ __(
					'Once the role is assigned, please click Create Catalog.',
					'reddit-for-woo'
				) }
			</p>
			<AppButton
				className="rfw-reddit-catalog-role-notice__create-catalog-button"
				variant="secondary"
				text={ __( 'Create Catalog', 'reddit-for-woo' ) }
				isBusy={ loading }
				isDisabled={ loading }
				onClick={ createCatalog }
			/>
		</AppNotice>
	);
};

export default CatalogRoleNotice;
