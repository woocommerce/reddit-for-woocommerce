/**
 * Returns common locators on the setup and settings page.
 */
export default class ElementLocators {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Get a generic account card by its title.
	 *
	 * @param {string} title The title text of the account card (e.g., 'WordPress.com', 'Reddit').
	 * @return {import('@playwright/test').Locator} The locator for the matching account card.
	 */
	getCard( title = '' ) {
		return this.page.locator( '.rfw-account-card', {
			has: this.page.locator( '.rfw-account-card__title', {
				hasText: title,
			} ),
		} );
	}

	/**
	 * Get WP account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WP account card.
	 */
	getWPAccountCard() {
		return this.getCard( 'WordPress.com' );
	}

	/**
	 * Get the connect button inside the WordPress.com account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getWpConnectButton() {
		return this.getWPAccountCard().getByRole( 'button', {
			hasText: 'Connect',
		} );
	}

	/**
	 * Get the connected label inside the WordPress.com account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getWpConnectedLabel() {
		return this.getWPAccountCard().locator( '.rfw-connected-icon-label' );
	}

	/**
	 * Get Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Reddit account card.
	 */
	getRedditAccountCard() {
		return this.getCard( 'Reddit' );
	}

	/**
	 * Get the connect button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditConnectButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			hasText: 'Connect',
		} );
	}

	/**
	 * Get the Edit button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditCardEditButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Edit',
		} );
	}

	/**
	 * Get the Cancel button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditCardCancelButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Cancel',
		} );
	}

	/**
	 * Get the Connect to a different Business button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getConnectToDifferentBusinessButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Or, connect to a different Reddit account',
		} );
	}

	/**
	 * Get the Connect to a different Ad Accounts button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getConnectToDifferentAdAccountsButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Or, connect to a different Ads account',
		} );
	}

	/**
	 * Get the Connect to a different Ad Accounts button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getConnectToDifferentPixelIdButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Or, connect to a different Pixel ID',
		} );
	}

	/**
	 * Get the Reddit Business Edit card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditBusinessCard() {
		return this.getCard( 'Connect to existing Reddit Business account' );
	}

	/**
	 * Get the Reddit Business Edit card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditAdsAccountCard() {
		return this.getCard( 'Connect to existing Reddit Ads account' );
	}

	/**
	 * Get the Reddit Pixel Edit card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getRedditPixelCard() {
		return this.getCard( 'Connect to existing Pixel ID' );
	}

	/**
	 * Get the continue button that navigates to the setup screen after click.
	 *
	 * @return {import('@playwright/test').Locator} The continue button locator.
	 */
	getContinueToSetupButton() {
		return this.page.getByRole( 'button', { name: 'Continue' } );
	}

	/**
	 * Get the Skip ads creation button that navigates to the settings screen after click.
	 *
	 * @return {import('@playwright/test').Locator} The skip ads creation button locator.
	 */
	getSkipAdsCreationButton() {
		return this.page.getByRole( 'button', { name: 'Skip ads creation' } );
	}

	/**
	 * Get the modal shown after successful onboarding.
	 *
	 * @return {import('@playwright/test').Locator} The onboarding successful modal.
	 */
	getOnboardingSuccessfulModal() {
		return this.page.locator( '.rfw-onboarding-success-modal', {
			hasText: 'You’ve successfully set up Reddit for WooCommerce!',
		} );
	}

	/**
	 * Get the modal shown after successful onboarding.
	 *
	 * @return {import('@playwright/test').Locator} The onboarding successful modal.
	 */
	getOnboardingSuccessfulCloseModalButton() {
		return this.getOnboardingSuccessfulModal().getByRole( 'button', {
			name: 'Close',
		} );
	}

	/**
	 * Get the disconnect button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The disconnect button locator.
	 */
	getRedditDisconnectButton() {
		return this.getRedditAccountCard().getByRole( 'button', {
			name: 'Disconnect Reddit account',
		} );
	}

	/**
	 * Get the Reddit disconnect confirmation modal.
	 *
	 * @return {import('@playwright/test').Locator} The Disconnect confirmation modal.
	 */
	getRedditDisconnectModal() {
		return this.page.locator( '.rfw-disconnect-accounts-modal', {
			hasText:
				'I understand that I am disconnecting my Reddit account from this WooCommerce extension.',
		} );
	}

	/**
	 * Get the Reddit disconnect confirmation checkbox inside the modal.
	 *
	 * @return {import('@playwright/test').Locator} The Disconnect confirmation modal.
	 */
	getRedditDisconnectConfirmCheckbox() {
		return this.getRedditDisconnectModal().getByRole( 'checkbox', {
			name: 'Yes, I want to disconnect my Reddit account',
		} );
	}

	/**
	 * Get the final disconnect button inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The disconnect button locator.
	 */
	getRedditFinalDisconnectButton() {
		return this.getRedditDisconnectModal().getByRole( 'button', {
			name: 'Disconnect Reddit Account',
		} );
	}

	/**
	 * Get the connected label inside the Reddit account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getRedditConnectedLabel() {
		return this.getRedditAccountCard().locator(
			'.rfw-connected-icon-label'
		);
	}

	/**
	 * Get the checkbox for enabling Conversions API tracking.
	 *
	 * @return {import('@playwright/test').Locator} The checkbox locator.
	 */
	getCapiCheckbox() {
		return this.getCard( 'Conversions API' ).getByLabel(
			'Enable Conversions API tracking'
		);
	}

	/**
	 * Get the Create Business Account button.
	 *
	 * @return {import('@playwright/test').Locator} The Create Business Account button.
	 */
	getCreateBusinessButton() {
		return this.page.getByRole( 'button', {
			name: 'Create Business Account',
		} );
	}
}
