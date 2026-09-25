<?php
/**
 * Shared onboarding / connection status checks.
 *
 * @package RedditForWooCommerce\Utils
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Utils;

use RedditForWooCommerce\Utils\Storage\OptionDefaults;
use RedditForWooCommerce\Utils\Storage\Options;

/**
 * Helpers for setup state used by admin UI, marketing channel, and meta boxes.
 *
 * @since 0.1.0
 */
final class OnboardingStatus {

	/**
	 * Whether Reddit onboarding is complete (connected to Reddit).
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function is_setup_completed(): bool {
		return 'connected' === Options::get( OptionDefaults::ONBOARDING_STATUS );
	}
}
