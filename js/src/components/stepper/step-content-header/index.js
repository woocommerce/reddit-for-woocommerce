/**
 * Internal dependencies
 */
import './index.scss';

const StepContentHeader = ( props ) => {
	const { className = '', title, description } = props;

	return (
		<header className={ `rfw-step-content-header ${ className }` }>
			<h1>{ title }</h1>
			<div className="rfw-step-content-header__description">
				{ description }
			</div>
		</header>
	);
};

export default StepContentHeader;
