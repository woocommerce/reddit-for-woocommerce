/**
 * Tracking event declarations shared by the meta box promos.
 *
 * These events are fired from more than one meta box context, so they are
 * declared once here and referenced with `@fires` by each emitter. Declaring
 * them in each emitter would render a separate section per declaration in
 * `Tracking.md`.
 *
 * This module intentionally contains no runtime code.
 */

/**
 * Reddit Ads Promo is loaded in a meta box.
 *
 * In the `order-attribution-meta-box` this fires only when the promo actually
 * renders, i.e. not once the store is onboarded and has a campaign. In the
 * `channel-visibility-meta-box` it fires on every meta box load, including
 * after onboarding when the settings panel is shown instead of the promo, and
 * when the promo has been dismissed.
 *
 * @event rfw_reddit_ads_promo_shown
 * @property {string} context (`order-attribution-meta-box`|`channel-visibility-meta-box`) - indicates the meta box the event fires in.
 */

/**
 * Reddit Ads Promo "Get started" button is clicked.
 *
 * @event rfw_reddit_ads_promo_get_started_click
 * @property {string} context (`order-attribution-meta-box`|`channel-visibility-meta-box`) - indicates the meta box the promo is rendered in.
 * @property {string} href URL of the "Get started" button.
 */

/**
 * Reddit Ads Promo "Create campaign" button is clicked.
 *
 * @event rfw_reddit_ads_promo_create_campaign_click
 * @property {string} context (`order-attribution-meta-box`) - indicates the meta box the promo is rendered in.
 * @property {string} href URL of the "Create campaign" button.
 */

/**
 * Reddit Ads Promo "Dismiss" button is clicked.
 *
 * @event rfw_reddit_ads_promo_dismiss_click
 * @property {string} context (`channel-visibility-meta-box`) - indicates the meta box the promo is rendered in.
 */
