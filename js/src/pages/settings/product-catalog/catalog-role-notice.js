/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

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
	const { catalogId, catalogStatus, hasFinishedResolution } = useSettings();
	const {
		business_id: businessId,
		hasFinishedResolution: hasFinishedResolutionRedditAccountConfig,
	} = useRedditAccountConfig();

	const { createCatalog, loading, createdCatalogId, errorCode } =
		useCreateCatalog();
	const catalogCreationStatus = errorCode || catalogStatus;

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

	const permissionsErrorNotice = (
		<p>
			{ createInterpolateElement(
				__(
					"Your account doesn't have the Catalog Manager role, which is required for catalog creation. Please assign it by going to <strong>Your Account › Edit › Member Details & Business Role</strong> › Advanced Role <link>here</link>. Once the role is assigned, please click Create Catalog.",
					'reddit-for-woocommerce'
				),
				{
					strong: <strong />,
					link: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							target="_blank"
							rel="external noreferrer noopener"
							href={ rolesLink }
						/>
					),
				}
			) }
		</p>
	);

	const otherErrorNotice = (
		<p>
			{ createInterpolateElement(
				__(
					'There was some error creating the Catalog. Please check the <link>logs</link> for more details.',
					'reddit-for-woocommerce'
				),
				{
					link: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							target="_blank"
							rel="external noreferrer noopener"
							href="/wp-admin/admin.php?page=wc-status&tab=logs"
						/>
					),
				}
			) }
		</p>
	);

	const httpErrorCode = Number( catalogCreationStatus );

	return (
		<AppNotice
			status="warning"
			isDismissible={ false }
			className="rfw-reddit-catalog-role-notice"
		>
			{ httpErrorCode > 0 &&
				httpErrorCode === 403 &&
				permissionsErrorNotice }
			{ httpErrorCode > 0 && httpErrorCode !== 403 && otherErrorNotice }
			<AppButton
				className="rfw-reddit-catalog-role-notice__create-catalog-button"
				variant="secondary"
				text={ __( 'Create Catalog', 'reddit-for-woocommerce' ) }
				isBusy={ loading }
				isDisabled={ loading }
				onClick={ createCatalog }
			/>
		</AppNotice>
	);
};

export default CatalogRoleNotice;
