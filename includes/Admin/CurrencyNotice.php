<?php
/**
 * Displays a site-wide admin notice when the store currency is unsupported.
 *
 * @package RedditForWooCommerce\Admin
 * @since 1.0.7
 */

namespace RedditForWooCommerce\Admin;

use RedditForWooCommerce\Utils\CurrencyValidator;

/**
 * Warns merchants on every wp-admin screen when their store currency
 * isn't one Reddit's Catalog API accepts, before they reach onboarding.
 *
 * @since 1.0.7
 */
class CurrencyNotice {

	/**
	 * Hooks the notice rendering into `admin_notices`.
	 *
	 * @since 1.0.7
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	/**
	 * Renders the unsupported currency notice.
	 *
	 * @since 1.0.7
	 *
	 * @return void
	 */
	public function render_notice(): void {
		if ( CurrencyValidator::is_supported() ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=general' );
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: 1: opening link tag to WooCommerce currency settings, 2: closing link tag, 3: comma-separated list of supported currency codes */
					esc_html__( 'Store currency is not supported by Reddit for WooCommerce. Please %1$schange your store currency%2$s to one of the supported options: %3$s', 'reddit-for-woocommerce' ),
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>',
					esc_html( implode( ', ', CurrencyValidator::SUPPORTED_CURRENCIES ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}
