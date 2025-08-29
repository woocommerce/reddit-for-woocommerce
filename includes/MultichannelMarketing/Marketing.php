<?php
/**
 * Registers the Reddit marketing channel with WooCommerce.
 *
 * This file defines the `Marketing` class responsible for hooking into
 * WooCommerce's marketing framework and registering the Reddit channel
 * so it can appear in the Marketing > Overview section.
 *
 * @package RedditForWooCommerce\MultichannelMarketing
 *
 * @since 0.1.0
 */

namespace RedditForWooCommerce\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;

/**
 * Class Marketing
 *
 * Registers the Reddit marketing channel with WooCommerce's Marketing UI.
 * Acts as an integration point between WooCommerce's core marketing infrastructure
 * and the Reddit channel defined in this plugin.
 *
 * @package RedditForWooCommerce\MultichannelMarketing
 *
 * @since 0.1.0
 */
class Marketing {
	/**
	 * Registers necessary WordPress/WooCommerce hooks to enable the Reddit marketing channel.
	 *
	 * This function should be called during the plugin initialization phase.
	 * It hooks into the `woocommerce_marketing_channels` filter to register the Reddit channel
	 * so it can be displayed in the WooCommerce marketing interface.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_marketing_channels', array( self::class, 'add_reddit_channel' ) );
	}

	/**
	 * Adds the Reddit marketing channel to the list of WooCommerce marketing channels.
	 *
	 * This method is hooked into the `woocommerce_marketing_channels` filter and appends
	 * a new instance of the `RedditChannel` class to the array of available channels.
	 *
	 * @since 0.1.0
	 *
	 * @param MarketingChannelInterface[] $channels Associative array of registered marketing channels, keyed by their slug.
	 *
	 * @return MarketingChannelInterface[] Modified array of marketing channels including the Reddit channel.
	 */
	public static function add_reddit_channel( $channels ) {
		$reddit_channel    = new RedditChannel();
		$slug              = $reddit_channel->get_slug();
		$channels[ $slug ] = $reddit_channel;

		return $channels;
	}
}
