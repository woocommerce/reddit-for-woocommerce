/**
 * Internal dependencies
 */
import useRedditPixelId from './useRedditPixelId';
import useExistingPixels from './useExistingPixels';

/**
 * Custom hook to determine if the user should connect an pixel ID.
 *
 * Returns `null` if the Reddit pixel ID or existing pixel IDs have not finished resolving.
 * Otherwise, returns `true` if there is no current connection and a single existing account,
 * indicating that the user should connect an pixel ID.
 *
 * @return {boolean|null} `true` if the user should connect an pixel ID, `false` otherwise, or `null` if still resolving.
 */
const useShouldConnectPixelId = () => {
	const { hasFinishedResolution, hasConnection } = useRedditPixelId();

	const { hasFinishedResolution: hasResolvedExistingPixels, existingPixels } =
		useExistingPixels();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasFinishedResolution || ! hasResolvedExistingPixels ) {
		return null;
	}

	return ! hasConnection && existingPixels?.length === 1;
};

export default useShouldConnectPixelId;
