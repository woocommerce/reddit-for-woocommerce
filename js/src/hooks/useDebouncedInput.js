/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Helper hook for input fields that need to debounce the value before using it.
 *
 * @param {string} [defaultValue=''] The initial value for the input.
 * @return {Array.<string|Function>}
 *   A tuple containing:
 *   - The current input value.
 *   - A setter function to update the input value.
 *   - The debounced input value (updated after the debounce delay).
 */
function useDebouncedInput( defaultValue = '' ) {
	const [ input, setInput ] = useState( defaultValue );
	const [ debouncedInput, setDebouncedState ] = useState( defaultValue );

	const setDebouncedInput = useDebounce( setDebouncedState, 500 );

	useEffect( () => {
		setDebouncedInput( input );
	}, [ input, setDebouncedInput ] );

	return [ input, setInput, debouncedInput ];
}

export default useDebouncedInput;
