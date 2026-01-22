=== Reddit for WooCommerce ===
Contributors: automattic, woocommerce
Tags: woocommerce, reddit, product feed, ads
Tested up to: 6.9
Stable tag: 1.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrate your WooCommerce store with Reddit Ads to track conversions and export products for advertising.

== Description ==

Reddit for WooCommerce seamlessly integrates your WooCommerce store with Reddit's powerful advertising platform, enabling you to reach millions of potential customers through engaging visual ads.

Connect your Reddit Business account to automatically sync your product catalog, create targeted advertising campaigns, and track conversions directly from your WooCommerce dashboard. The plugin provides a streamlined setup process with step-by-step guidance to get your first campaign running quickly.

Key features include product catalog export for Reddit's Dynamic Ads, Conversion tracking to measure campaign performance, and Pixel tracking to build custom audiences. The integration works through WooCommerce's Marketing menu, providing a familiar interface for managing your Reddit advertising alongside other marketing channels.

Whether you're looking to increase brand awareness, drive sales, or re-engage existing customers, Reddit for WooCommerce provides the tools you need to create effective advertising campaigns that convert visitors into customers.

== Instructions ==
Getting started with the Reddit for WooCommerce plugin is quick and easy! Just follow these simple steps:

1. Install and activate the plugin.
	- Once activated, go to **Marketing → Reddit** in your WordPress dashboard.
2. Connect your WordPress.com account.
	- On the setup screen, click **Connect** next to **WordPress.com** and sign in.
3. Connect your Reddit Business account.
	- Click **Connect** next to **Reddit** and follow the on-screen steps to sign in to your Reddit Business account.
4. Complete the setup.
	- Once both accounts are connected, click **Continue** to complete the setup process.
5. Manage your settings.
	- You’ll be taken to the settings page where you can turn **Conversions tracking** on or off anytime, or disconnect Reddit if needed.
6. Verify everything is working.
	- In your Reddit Event Manager, confirm that events are being tracked correctly.
	- Check that your product catalog appears in your Reddit dashboard and that your products are syncing properly.

== Instructions to build the plugin ==

- Requires node v20 and NPM v10.
- Requires PHP composer v2.
- Run `npm ci && npm run build` in the plugin's root.
- Use the generated `reddit-for-woocommerce.zip` file.

== External services ==

This plugin connects to a [Reddit](https://www.reddit.com) Business account to automatically sync the product catalog and track pixel and conversions events. For pixel tracking, the plugin loads the pixel script from https://www.redditstatic.com/ and sends product details, order totals, user IP address, and user agent information in the event payload if user has given consent.

This service is provided by "Reddit, Inc.": [terms of use](https://redditinc.com/policies/user-agreement), [privacy policy](https://www.reddit.com/policies/privacy-policy).

== FAQ ==

= Does the plugin use any external services? =
Yes, it uses a [Jetpack](https://jetpack.com/) account to connect and communicate with the [Reddit](https://www.reddit.com) API.

== Changelog ==

= 1.0.3 - 2026-01-22 =
* Update - Replace the billing card with an informational note to prevent potential confusion.
* Update - UX improvements for the Reddit Ads card.
* Fix - Ensure that the catalog is recreated after product export if the user deletes the auto-generated catalog.
* Fix - Ensure that the created campaign is archived when disconnecting the Reddit account.
* Fix - Strip HTML tags from the product description for the CSV export.
* Fix - UX improvements for catalog creation failure handling.
* Fix – Ensure that switching businesses during onboarding connects the correct ad account.
* Dev – Fix flaky PHPUnit test in `ProductExportServiceTest`.

= 1.0.2 - 2026-01-07 =
* Update - Average daily budget suggestions.
* Tweak - WooCommerce 10.4 compatibility.
* Dev - Set up woorelease GH workflows.

= 1.0.1 - 2025-12-09 =
* Add - Implement Create Campaign during onboarding.
* Improved - create campaign defaults and UI improvements.
* Tweak - WordPress 6.9 compatibility.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/reddit-for-woocommerce/trunk/changelog.txt).
