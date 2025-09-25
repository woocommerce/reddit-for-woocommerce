/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

// The `normal` gap is the default style, and no need to append any additional class name.
const sizeClassName = {
	normal: false,
	medium: 'rfw-vertical-gap-layout__medium',
	large: 'rfw-vertical-gap-layout__large',
	overlap: 'rfw-vertical-gap-layout__overlap',
};

/**
 * Renders a wrapper layout to make its children to be aligned vertically with a specified gap.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'normal'|'medium'|'large'|'overlap'} [props.size='normal'] Indicate the gap between children.
 *     'normal': A normal gap.
 *     'medium': A medium gap.
 *     'large': A large gap.
 *     'overlap': Overlap the back child on the front child with -1 pixel spacing.
 */
const VerticalGapLayout = ( props ) => {
	const { className, size = 'normal', ...rest } = props;

	return (
		<div
			className={ classnames(
				'rfw-vertical-gap-layout',
				sizeClassName[ size ],
				className
			) }
			{ ...rest }
		/>
	);
};

export default VerticalGapLayout;
