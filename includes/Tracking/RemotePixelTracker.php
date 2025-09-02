<?php
/**
 * Implementation of PixelTracker that retrieves and injects the Reddit Pixel script remotely.
 *
 * This tracker checks whether pixel tracking is enabled, and if so, either retrieves a cached script
 * or fetches a fresh one from Reddit via the WCS API. The script is optionally personalized
 * with the logged-in user’s email address for audience matching.
 *
 * @package RedditForWooCommerce\Tracking
 */

namespace RedditForWooCommerce\Tracking;

use RedditForWooCommerce\Connection\WcsClient;
use RedditForWooCommerce\Utils\Storage\Options;
use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Helper;
use RedditForWooCommerce\Tracking\Consent;
use RedditForWooCommerce\Config;

/**
 * Fetches and injects Reddit pixel tracking code into WooCommerce frontend pages.
 *
 * Responsibilities:
 * - Checks plugin option to determine if pixel tracking is enabled.
 * - Retrieves pixel script from local cache or Reddit Ads API.
 * - Injects sanitized pixel JavaScript into the frontend via `wp_head`.
 * - Optionally personalizes the script using the logged-in user's email.
 *
 * Dependencies:
 * - {@see WcsClient} for making remote API calls to fetch the pixel script.
 * - {@see JetpackAuthenticator} for securely authenticating requests.
 * - {@see OptionsStore} and {@see OptionDefaults} for managing plugin settings and cache.
 *
 * @see \RedditForWooCommerce\Tracking\PixelTrackerInterface
 * @since 0.1.0
 */
final class RemotePixelTracker implements PixelTrackerInterface {
	/**
	 * Meta key used to mark orders that have already been tracked.
	 */
	protected const ORDER_PIXEL_TRACKED_META_KEY = '_reddit_pixel_tracked';

	/**
	 * Client for making authenticated proxy requests to Reddit Ads API.
	 *
	 * @var WcsClient
	 */
	private WcsClient $wcs_client;

	/**
	 * Constructor.
	 *
	 * @param WcsClient $wcs_client WCS API client.
	 */
	public function __construct( WcsClient $wcs_client ) {
		$this->wcs_client = $wcs_client;
	}

	/**
	 * Injects the Reddit Pixel script into the footer.
	 * Personalized if possible, and sanitized using `wp_kses`.
	 *
	 * @since 0.1.0
	 */
	public function maybe_inject_pixel(): void {
		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$allowed_tags = array(
			'script'   => array(
				'type'  => array(),
				'src'   => array(),
				'async' => array(),
			),
			'#comment' => array(),
		);

		echo wp_kses( $this->get_pixel_script(), $allowed_tags );
	}

	/**
	 * Adds personalization to the pixel script based on the current user.
	 *
	 * If the user is logged in, injects their email address into the script for
	 * more accurate audience tracking. If not logged in, removes placeholder elements.
	 *
	 * @since 0.1.0
	 *
	 * @param string $script Raw pixel JavaScript with placeholders.
	 * @return string Personalized pixel script.
	 */
	protected static function personalize_tracking_script( string $script ): string {
		// Will be implemented client-side.
		$script = str_replace(
			"rdt('track', 'PAGE_VIEW');",
			'',
			$script
		);

		// @todo: use this once we integrate with Consent API.
		if ( 0 && is_user_logged_in() ) { // for future use.
			$user       = wp_get_current_user();
			$user_email = $user->user_email;

			// Escape the email for JS safety.
			$escaped_email = esc_js( $user_email );

			// Replace the placeholder with actual email.
			return str_replace(
				"'__INSERT_USER_EMAIL__'",
				"'" . $escaped_email . "'",
				$script
			);
		}

		// If user is not logged in, replace with empty string or remove the key.
		return str_replace(
			"'user_email': '__INSERT_USER_EMAIL__'",
			'',
			$script
		);
	}

	/**
	 * Retrieves the Reddit Pixel script, either from cache or the remote API.
	 *
	 * If not cached, it authenticates with Jetpack and queries the Reddit Ads API for pixel script.
	 * The result is cached in the options store and sanitized before being returned.
	 *
	 * @since 0.1.0
	 *
	 * @return string The sanitized pixel script, or empty string on failure.
	 */
	private function get_pixel_script() {
		$script = apply_filters( Helper::with_prefix( 'filter_pixel_script' ), false );

		if ( false !== $script ) {
			return $script;
		}

		$pixel_id = Options::get( OptionDefaults::PIXEL_ID );

		if ( empty( $pixel_id ) ) {
			return '';
		}

		ob_start();
		?>
		<!-- Reddit Pixel -->
		<script>
		!function(w,d){if(!w.rdt){var p=w.rdt=function(){p.sendEvent?p.sendEvent.apply(p,arguments):p.callQueue.push(arguments)};p.callQueue=[];var t=d.createElement("script");t.src="https://www.redditstatic.com/ads/pixel.js",t.async=!0;var s=d.getElementsByTagName("script")[0];s.parentNode.insertBefore(t,s)}}(window,document);rdt('init', "<?php echo esc_js( $pixel_id ); ?>");
		</script>
		<!-- DO NOT MODIFY UNLESS TO REPLACE A USER IDENTIFIER -->
		<!-- End Reddit Pixel -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Emits the Reddit `PURCHASE` tracking event after a successful order.
	 *
	 * Hooked into `woocommerce_before_thankyou`. Avoids duplicate firing via meta key.
	 *
	 * @since 0.1.0
	 */
	public function track_purchase_event() {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ! Consent::has_marketing_consent() ) {
			return;
		}

		$order_id = (int) get_query_var( 'order-received' );
		$order    = wc_get_order( $order_id );

		// Make sure there is a valid order object and it is not already marked as tracked.
		if ( ! $order || 1 === (int) $order->get_meta( self::ORDER_PIXEL_TRACKED_META_KEY, true ) ) {
			return;
		}

		// Mark the order as tracked, to avoid double-reporting if the confirmation page is reloaded.
		$order->update_meta_data( self::ORDER_PIXEL_TRACKED_META_KEY, 1 );
		$order->save_meta_data();

		$order_key = $order->get_order_key();
		$total     = $order->get_total();
		$currency  = $order->get_currency();
		$products  = array();

		foreach ( $order->get_items() as $item ) {
			/**
			 * Product from the Order Line Item.
			 *
			 * @var \WC_Order_Item_Product $item Product item.
			 */
			$product = $item->get_product();
			if ( $product ) {
				$products[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}
		}

		$payload = array(
			'value'        => $total,
			'currency'     => $currency,
			'conversionId' => $order_key,
			'products'     => $products,
			'itemCount'    => $order->get_item_count(),
		);

		$tracking_data = sprintf(
			'rdt("track", "Purchase", %s);',
			wp_json_encode( $payload )
		);

		wp_add_inline_script( Config::ASSET_HANDLE_PREFIX . 'tracking', $tracking_data );
	}
}
