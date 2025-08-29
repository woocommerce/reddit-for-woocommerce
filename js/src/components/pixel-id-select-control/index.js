/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import useExistingPixels from '~/hooks/useExistingPixels';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const PixelIdSelectControl = ( props ) => {
	const { existingPixels } = useExistingPixels();

	const options = existingPixels?.map( ( pixel ) => ( {
		value: pixel.pixel_id,
		label: pixel.pixel_name,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default PixelIdSelectControl;
