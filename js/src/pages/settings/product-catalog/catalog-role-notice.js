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
import UnsupportedCurrencyMessage from '~/components/unsupported-currency-message';
import useCreateCatalog from '~/hooks/useCreateCatalog';
import useRedditAccountConfig from '~/hooks/useRedditAccountConfig';
import { rfwData } from '~/constants';
import './catalog-role-notice.scss';

/**
 * React component that displays a warning notice with link to set the catalog role and create the catalog
 * if no catalog is created for the user.
 *
 * @return {JSX.Element|null} A warning notice with a link to set the catalog role and create the catalog, or null if resolution is not finished.
 */
const CatalogRoleNotice = () => {
	const {
		catalog_id: catalogId,
		catalog_error: catalogError,
		business_id: businessId,
		pixel_id: pixelId,
		hasFinishedResolution,
	} = useRedditAccountConfig();

	const { createCatalog, loading, createdCatalogId, errorCode } =
		useCreateCatalog();
	const catalogCreationError = errorCode || catalogError;

	if (
		! hasFinishedResolution ||
		catalogId ||
		createdCatalogId ||
		! businessId ||
		! pixelId
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

	const pixelAlreadyAttachedNotice = (
		<p>
			{ createInterpolateElement(
				__(
					'Catalog creation failed because the pixel is already associated with an existing catalog. Please click <strong>Replace Catalog</strong> to delete the existing catalog and create a new one.',
					'reddit-for-woocommerce'
				),
				{
					strong: <strong />,
				}
			) }
			<br />
			<strong>
				{ __(
					'Careful: this can not be undone.',
					'reddit-for-woocommerce'
				) }
			</strong>
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

	const isPermissionError = catalogCreationError === 'PERMISSION_ERROR';
	const isCatalogAlreadyExists =
		catalogCreationError === 'CATALOG_ALREADY_EXISTS';
	// Use the live store currency rather than the stored error, so the notice clears
	// and the button re-enables as soon as the merchant switches to a supported currency.
	const isUnsupportedCurrency = rfwData?.isCurrencySupported === false;
	// A stored UNSUPPORTED_CURRENCY error is stale once the currency is supported again,
	// so don't treat it as an unknown error that points the merchant at the logs.
	const isOtherError =
		! isPermissionError &&
		! isCatalogAlreadyExists &&
		! isUnsupportedCurrency &&
		catalogCreationError !== 'UNSUPPORTED_CURRENCY';

	return (
		<AppNotice
			status="warning"
			isDismissible={ false }
			className="rfw-reddit-catalog-role-notice"
		>
			{ isPermissionError && permissionsErrorNotice }
			{ isCatalogAlreadyExists && pixelAlreadyAttachedNotice }
			{ isUnsupportedCurrency && <UnsupportedCurrencyMessage /> }
			{ isOtherError && otherErrorNotice }
			<AppButton
				className="rfw-reddit-catalog-role-notice__create-catalog-button"
				variant="secondary"
				text={
					isCatalogAlreadyExists
						? __( 'Replace Catalog', 'reddit-for-woocommerce' )
						: __( 'Create Catalog', 'reddit-for-woocommerce' )
				}
				isBusy={ loading }
				isDisabled={ loading || isUnsupportedCurrency }
				onClick={ () => {
					createCatalog(
						catalogCreationError === 'CATALOG_ALREADY_EXISTS'
							? true
							: false
					);
				} }
			/>
		</AppNotice>
	);
};

export default CatalogRoleNotice;
