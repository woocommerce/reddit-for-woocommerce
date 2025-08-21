/**
 * Internal dependencies
 */
import './body.scss';

const Body = ( props ) => {
	const { children } = props;

	return <div className="rfw-subsection-body">{ children }</div>;
};

export default Body;
