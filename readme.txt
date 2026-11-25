=== Reddit for WooCommerce ===
Contributors: automattic, woocommerce
Tags: woocommerce, woo, reddit, product feed, ads
Tested up to: 7.1
Stable tag: 1.1.0
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

- Requires node v24 and NPM v11.
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

= 1.1.0 - 2026-11-25 =
* Add - Send hashed customer match keys (email, phone number, external ID) with Reddit conversion events when Collect Customer PII is enabled.
* Dev - Update dompurify to 3.4.18 to resolve Dependabot security alerts.
* Fix - adds tracking to customer PII state toggle in settings.
* Fix - Collect Customer PII can no longer be changed while Conversions API tracking is off, and its help text now explains that customer details are hashed before they are sent.
* Fix - Collect Customer PII checkbox to Reddit settings.
* Fix - Collect Customer PII setting: new stores enable customer match by default, GDPR-region stores start disabled so the merchant opts in, and existing stores keep their current setting.
* Fix - Feature: Collect PII.
* Fix - Include the Collect Customer PII setting state in WooCommerce usage tracking data.
* Fix - None. The 1.0.8 changelog already covers PHP 8.5 testing.
* Fix - Phone numbers that can't be reliably converted to international format are no longer sent to Reddit in the wrong form when Collect Customer PII is on.
* Fix - Pin Composer platform to PHP 7.4 so locked dependencies stay installable on all supported PHP versions, and bump PHPUnit to 9.6.39.
* Fix - REDTWOO-216: Fix the data sharing option E2E test.
* Fix - REDTWOO-216: Rework Conversions API settings to match the data sharing designs.
* Fix - Targets a feature branch, so no entry.
* Fix - Targets a feature branch, so no entry.

= 1.0.8 - 2026-11-16 =
* Dev - Tested with PHP 8.5.
* Dev - Updates to local development files and dependencies to support Node 24.
* Fix - Correct the Ad Partner base class namespace so optimized builds can load it.
* Fix - Variation forms no longer stay stuck loading when Reddit tracking data is missing from the page.
* Tweak - Bump WooCommerce "tested up to" version to 11.3.
* Tweak - Update the WordPress.com logo on the account connection card.

= 1.0.7 - 2026-10-08 =
* Add - "Channel visibility" meta box on the product edit screen to show or hide each product in the Reddit catalog.
* Add - Reddit Ads promotion in the "Order attribution" meta box for orders from Reddit.
* Tweak - Bump WooCommerce "tested up to" version to 11.2.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/reddit-for-woocommerce/trunk/changelog.txt).
