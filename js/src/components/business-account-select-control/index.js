/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import useExistingBusinessAccounts from '~/hooks/useExistingBusinessAccounts';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const BusinessAccountSelectControl = ( props ) => {
	const { existingAccounts } = useExistingBusinessAccounts();

	const options = existingAccounts?.map( ( acc ) => ( {
		value: acc.business_id,
		label: `${ acc.business_name } (${ acc.business_id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default BusinessAccountSelectControl;
