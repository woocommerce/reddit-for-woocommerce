/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import useExistingAdsAccounts from '~/hooks/useExistingAdsAccounts';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts } = useExistingAdsAccounts();

	const options = existingAccounts?.map( ( acc ) => ( {
		value: acc.ad_account_id,
		label: `${ acc.ad_account_name } (${ acc.ad_account_id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
