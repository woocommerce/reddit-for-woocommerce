/**
 * Sets the marketing consent state for E2E tests via the test REST endpoint.
 *
 * Requires the `reddit-e2e/v1/set-consent` endpoint to be available
 * and the `E2E_CONTEXT` constant defined on the server.
 *
 * @param {import('@playwright/test').Page} page    - The Playwright page object.
 * @param {boolean}                         consent - Whether to grant or deny consent.
 * @throws {Error} If the request fails or the response indicates an error.
 */
export async function setConsent( page, consent ) {
	const response = await page.request.post(
		'/wp-json/reddit-e2e/v1/set-consent',
		{
			data: { consent },
			headers: { 'Content-Type': 'application/json' },
		}
	);

	if ( ! response.ok() ) {
		const errorBody = await response.text();
		throw new Error(
			`Failed to set consent. HTTP ${ response.status() }: ${ errorBody }`
		);
	}
}
