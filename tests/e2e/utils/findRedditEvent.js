/**
 * Finds a specific Reddit Pixel event in the mocked `rdt.queue`.
 *
 * This utility is used in E2E tests to locate a tracked event (e.g. 'PAGE_VISIT', 'ADD_CART')
 * from the global `rdt.queue`, which is populated by the mocked Reddit pixel script:
 *
 * ```html
 * <script>
 *   var x="https://sc-static.net/scevent.min.js";
 *   window.rdt=function(){window.rdt.queue.push(Array.from(arguments))},
 *   window.rdt.queue=[],rdt("track","PAGE_VISIT");
 * </script>
 * ```
 *
 * @param {Array<Array>} queue - The mocked `rdt.queue`, an array of event calls.
 * @param {string} eventName - The name of the event to find (e.g., 'PAGE_VISIT').
 * @return {Array|null} The matched event call (e.g., `['track', 'PAGE_VISIT']`), or `null` if not found.
 */
export function findRedditEvent( queue, eventName ) {
	return (
		queue.find( ( [ command, name ] ) => {
			return command === 'track' && name === eventName;
		} ) || null
	);
}
