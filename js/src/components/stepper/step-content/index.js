/**
 * Internal dependencies
 */
import './index.scss';

const StepContent = ( props ) => {
	const { className = '', children, ...rest } = props;

	return (
		<div className={ `rfw-step-content ${ className }` } { ...rest }>
			<div className="rfw-step-content__container">{ children }</div>
		</div>
	);
};

export default StepContent;
