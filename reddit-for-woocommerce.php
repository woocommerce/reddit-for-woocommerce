<?php
/**
 * Plugin Name: Reddit for WooCommerce
 * Description: Seamlessly integrates your WooCommerce store with Reddit's powerful advertising platform, enabling you to reach millions of potential customers through engaging visual ads.
 * Version: 0.1.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: reddit-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.7
 * WC requires at least: 9.9
 * WC tested up to: 10.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package reddit-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_VERSION' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_VERSION', '0.1.0' );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_FILE' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_FILE', __FILE__ );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_DIR' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_URL' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_PLUGIN_BUILD_PATH', REDDIT_FOR_WOOCOMMERCE_PLUGIN_DIR . 'js/build/' );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_BUILD_URL' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_BUILD_URL', REDDIT_FOR_WOOCOMMERCE_PLUGIN_URL . 'js/build/' );
}

if ( ! defined( 'REDDIT_FOR_WOOCOMMERCE_DEBUG' ) ) {
	define( 'REDDIT_FOR_WOOCOMMERCE_DEBUG', false );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

add_action(
	'woocommerce_loaded',
	function () {
		\RedditForWooCommerce\Plugin::init();
	}
);
