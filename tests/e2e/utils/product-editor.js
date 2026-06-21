/**
 * External dependencies
 */
import { expect } from '@playwright/test';

/**
 * Gets E2E test utils for facilitating writing tests for the classic product editor.
 *
 * @param {import('@playwright/test').Page} page Playwright page object.
 */
export function getClassicProductEditorUtils( page ) {
	const locators = {
		getChannelVisibilityMetaBox() {
			return page.locator( '#channel_visibility' );
		},

		getChannelVisibilityMetaBoxContent() {
			return this.getChannelVisibilityMetaBox().locator(
				'.rfw-channel-visibility__content'
			);
		},
	};

	const asyncActions = {
		async gotoEditProductPage( id ) {
			await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
			await this.waitForInteractionReady();
		},

		waitForInteractionReady() {
			return expect(
				page.locator( '.product_data_tabs li.active' )
			).toHaveCount( 1 );
		},

		clickSave() {
			return page
				.getByRole( 'button', { name: /^(Save Draft|Update)$/ } )
				.click();
		},

		async save() {
			const observer = page.waitForResponse( ( response ) => {
				const url = new URL( response.url() );

				return (
					url.pathname === '/wp-admin/post.php' &&
					url.searchParams.has( 'post' ) &&
					url.searchParams.has( 'action', 'edit' ) &&
					response.ok() &&
					response.request().method() === 'GET'
				);
			} );

			await this.clickSave();
			await observer;
			await this.waitForInteractionReady();
		},
	};

	return { ...locators, ...asyncActions };
}
