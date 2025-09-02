/**
 * Internal dependencies
 */
import useRedditAccountConfig from './useRedditAccountConfig';

/**
 * @typedef {Object} RedditPixelId
 * @property {string} pixel_id The Reddit pixel ID.
 * @property {boolean} hasConnection Whether the Reddit pixel ID has been connected.
 */

/**
 * Hook to retrieve the Reddit Pixel ID and connection information.
 * @return {RedditPixelId} The Reddit Pixel ID.
 */
const useRedditPixelId = () => {
	const { pixel_id: pixelId, hasFinishedResolution } =
		useRedditAccountConfig();

	return {
		pixelId,
		hasConnection: !! pixelId,
		hasFinishedResolution,
	};
};

export default useRedditPixelId;
