<?php
/**
 * Interface definition for PixelTracker implementations.
 *
 * This interface defines a contract for classes responsible for conditionally injecting
 * tracking pixels (e.g., Reddit Pixel) into WooCommerce store pages.
 *
 * Implementing classes should handle the logic that determines when and how
 * a pixel should be injected — typically during page rendering or in response to hooks.
 *
 * @package RedditForWooCommerce\Tracking
 */

namespace RedditForWooCommerce\Tracking;

/**
 * Interface for tracking pixel injectors used in Reddit for WooCommerce integration.
 *
 * Implementers of this interface should determine if and when a tracking pixel should be
 * rendered into the HTML output. This is commonly used to track user behavior for ad platforms.
 *
 * Example implementations might inject JavaScript pixels on product or checkout pages
 * depending on configuration and user consent.
 *
 * @since 0.1.0
 */
interface PixelTrackerInterface {
	/**
	 * Conditionally injects the tracking pixel into the page output.
	 *
	 * This method is expected to be hooked into WordPress actions (e.g., `wp_head`)
	 * or called during template rendering. It should determine internally whether the
	 * pixel should be output based on plugin settings, page context, or filters.
	 *
	 * @since 0.1.0
	 */
	public function maybe_inject_pixel(): void;
}
