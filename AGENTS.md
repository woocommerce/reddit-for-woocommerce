# AGENTS.md — Reddit for WooCommerce

Guidelines for AI coding agents working in this repository. This file contains information that is hard to discover from the codebase alone.

## Project Knowledge

Reddit for WooCommerce is a WooCommerce extension that integrates a store with Reddit's advertising platform — enabling pixel tracking, server-side conversion events (CAPI), product catalog export, and campaign creation through Reddit Ads.

- **Tech stack:** PHP 7.4+, WordPress 6.7+, WooCommerce 10.2+, Node 20, React, SCSS
- **Repository:** `woocommerce/reddit-for-woocommerce`, text domain: `reddit-for-woocommerce`
- **External dependencies:** Jetpack (auth/connection), WooCommerce Connect Server (proxy to Reddit APIs)

### Directory Structure

| Directory | Purpose |
|-----------|---------|
| `includes/` | Core PHP — PSR-4 root (`RedditForWooCommerce\`) |
| `includes/Admin/` | Admin UI: menu, onboarding wizard, assets, product meta |
| `includes/Admin/Export/` | Batch product catalog CSV export pipeline |
| `includes/API/Site/Controllers/` | REST API controllers (settings, onboarding, Reddit connection, Jetpack, campaigns) |
| `includes/API/AdPartner/` | Reddit Ads API wrappers (catalog, feed, pixels, campaigns, ad groups, etc.) |
| `includes/Connection/` | WCS and Jetpack HTTP clients and authentication |
| `includes/CsvExporter/` | Product export service and category provider |
| `includes/MultichannelMarketing/` | WooCommerce Marketing channel registration |
| `includes/Tracking/` | Pixel injection, server-side conversion tracking, event classes |
| `includes/Utils/` | Helpers, asset loader, user identifier, option/transient storage |
| `js/src/` | React admin app and frontend tracking source |
| `js/build/` | **Generated** webpack output — never edit directly |
| `tests/phpunit/` | PHPUnit unit tests |
| `tests/e2e/` | Playwright E2E tests |
| `vendor/` | Composer dependencies (Jetpack autoloader) |
| `languages/` | i18n translation files |

### Data Storage

The plugin uses **no custom database tables**. All data is stored in WordPress options and transients.

**Options** (prefix `reddit_`):

| Key | Purpose |
|-----|---------|
| `onboarding_status`, `onboarding_step` | Setup wizard progress |
| `ad_account_id`, `ad_account_name` | Connected Reddit ad account |
| `business_id`, `business_name` | Connected Reddit business |
| `pixel_id`, `ads_pixel_enabled` | Reddit pixel configuration |
| `conversion_enabled` | Server-side CAPI toggle |
| `catalog_id`, `product_feed_id`, `feed_status` | Product catalog/feed state |
| `catalog_export_path`, `catalog_export_url` | Export file location |
| `last_export_timestamp`, `wcs_products_token` | Export metadata |
| `campaign_ids` | Created campaign IDs |

**Transients:** `ads_pixel_script` (1 month), `reddit_account_email` (1 day), `product_set_id_{ad_account_id}`.

**Order meta:** `reddit_conversion_tracked` — marks an order as tracked.

**Product meta:** `product_catalog_item` — include/exclude from catalog export.

### REST API

All endpoints use namespace `wc/rfw`:

| Endpoint | Methods | Purpose |
|----------|---------|---------|
| `/reddit/settings` | GET, POST | Plugin settings |
| `/reddit/connect` | GET | Start Reddit OAuth |
| `/reddit/connection` | GET, DELETE | Connection status / disconnect |
| `/reddit/config` | GET, POST | Reddit config (business, ad account, pixel) |
| `/reddit/businesses` | GET | List businesses |
| `/reddit/ad_accounts` | GET | List ad accounts |
| `/reddit/pixels` | GET | List pixels |
| `/reddit/setup` | GET | Onboarding state |
| `/reddit/setup/complete` | POST | Complete onboarding |
| `/reddit/campaigns` | POST | Create campaign |
| `/jetpack/connect` | GET, DELETE | Jetpack connect/disconnect |
| `/jetpack/connected` | GET | Jetpack connection status |

### External Service Integration

All Reddit API calls are proxied through WooCommerce Connect Server (WCS) at `https://public-api.wordpress.com/wpcom/v2/sites/{site_id}/wc/reddit/`. The plugin never calls Reddit APIs directly — the `WcsClient` handles authentication via Jetpack and routes requests through WCS.

## Commands

### Setup

```bash
npm install
composer install
```

### Build

```bash
npm run build           # Production build (webpack)
npm run start           # Dev mode with hot reload
```

### Lint

```bash
npm run lint            # Run all linters (PHP + JS + CSS)
npm run lint:php        # PHPCS
npm run lint:js         # ESLint
npm run lint:js:fix     # ESLint auto-fix
npm run lint:css        # Stylelint
```

### Unit Tests

```bash
npm run env:start                   # Start wp-env containers
npm run test:unit:wp-env:setup      # One-time setup after containers start
npm run test:unit:wp-env            # Run PHPUnit via wp-env (latest WP, nightly WC)
```

Tests are in `tests/phpunit/Unit/` and follow the namespace structure of `includes/`.

### E2E Tests

```bash
npm run env:start       # Start wp-env (runs initialize.sh automatically)
npm run test:e2e        # Run Playwright tests
npm run env:stop        # Stop containers
npm run env:destroy     # Tear down containers
```

- E2E tests use **Playwright** (not Codeception).
- Test specs are in `tests/e2e/specs/`.
- A test helper mu-plugin (`tests/e2e/plugins/reddit-options.php`) pre-configures plugin options for the test environment.

### Other

```bash
npm run i18n            # Generate .pot translation file
npm run doc:tracking    # Generate tracking documentation
```

## Conventions to Follow

### Naming

| Type | Convention | Example |
|------|-----------|---------|
| PHP namespace | `RedditForWooCommerce\` | `RedditForWooCommerce\Tracking\PixelTrackingService` |
| PHP class files | PSR-4 (class name = file name) | `PixelTrackingService.php` |
| Service keys | `ServiceKey::CONSTANT` | `ServiceKey::PIXEL_TRACKING` |
| Options prefix | `reddit_` | `reddit_pixel_id` |
| Asset handle prefix | `reddit_` | `reddit_tracking` |
| JS global prefix | `redditAds` | `window.redditAdsTrackingData` |
| REST namespace | `wc/rfw` | `/wp-json/wc/rfw/reddit/settings` |
| Text domain | `reddit-for-woocommerce` | `__( 'text', 'reddit-for-woocommerce' )` |
| Plugin slug | `reddit_for_woocommerce` | Action Scheduler group, hook prefix |
| Custom hooks | `reddit_for_woocommerce_` prefix | `reddit_for_woocommerce_onboarding_complete` |

### Coding Standards

- **PHP:** WordPress Coding Standards (WPCS) via PHPCS.
- **JS:** `@woocommerce/eslint-plugin/recommended` with `eslint-plugin-import`.
- **CSS:** Stylelint with `stylelint-config-standard-scss`.
- **Formatting:** `wp-prettier`.
- **i18n:** All user-facing strings must use `reddit-for-woocommerce` text domain.
- **Security:** Nonce verification, sanitization (`wc_clean()`), escaping (`esc_html()`, `esc_attr()`).

## Architectural Decisions

### Service Container (not Singleton)

The plugin uses a static `ServiceContainer` with lazy-instantiated services identified by `ServiceKey` constants. Services are resolved in `ServiceContainer::resolve()`. When adding a new service, add a constant to `ServiceKey` and a matching case in `ServiceContainer::resolve()`.

### WCS Proxy Architecture

The plugin never calls Reddit APIs directly. All external API calls go through `WcsClient` → Jetpack → WooCommerce Connect Server → Reddit. This means:
- Authentication is handled by Jetpack tokens, not stored Reddit credentials.
- The `AdPartnerApi` class wraps all Reddit API endpoints and delegates to `WcsClient`.
- To add a new Reddit API integration, add a method to the appropriate `API/AdPartner/` class.

### Tracking: Dual Pixel + CAPI

Conversion tracking has two parallel paths:
1. **Pixel (client-side):** `PixelTrackingService` injects a remote pixel script into `wp_head` and fires events via JavaScript (ViewContent, AddToCart, PageVisit, Purchase).
2. **CAPI (server-side):** `ConversionTrackingService` hooks into WooCommerce actions (`woocommerce_add_to_cart`, `woocommerce_thankyou`) and sends conversion events to Reddit via WCS.

Both use `EventIdRegistry` for deduplication. The `ConversionEvent/` directory contains event-specific data classes.

### Product Catalog Export Pipeline

Catalog export uses Action Scheduler for async batch processing:
1. `ProductExportService` schedules recurring exports.
2. `ProductIdCacheBuilder` builds a list of product IDs to export.
3. `ProductEntityProvider` fetches WC product objects in batches.
4. `ProductRowBuilder` transforms products into CSV rows.
5. `CsvExportWriter` writes the CSV file.
6. `AdPartnerApi` uploads the feed to Reddit.

### Admin UI via WooCommerce Admin

Admin pages are registered as WooCommerce Admin (React) pages under Marketing, using the `woocommerce_admin_pages_list` filter. Routes: `/reddit/start`, `/reddit/setup`, `/reddit/settings`. JS entry point is `js/src/index.js`.

### Webpack Configuration

The build extends `@wordpress/scripts` webpack config with `WooCommerceDependencyExtractionWebpackPlugin` for WC dependency extraction. There are two entry points: `index` (admin app) and `tracking` (frontend pixel). Common code is split into `commons.js` and `vendors.js` chunks. Source alias `~` maps to `js/src/`.

## Common Pitfalls

- **Never edit files in `js/build/`.** These are generated by webpack. Edit source files in `js/src/` and run `npm run build` or `npm run start`.
- **Always run linting before committing.** Run `npm run lint` to catch PHP, JS, and CSS issues early.
- **Don't call Reddit APIs directly.** All external calls must go through `WcsClient` → WCS. Direct HTTP requests to Reddit will bypass auth and break in production.
- **Don't store Reddit credentials locally.** Authentication is handled entirely by Jetpack tokens. The plugin holds no OAuth tokens directly.
- **Don't create new REST namespaces.** All endpoints use `wc/rfw`. Add new controllers in `API/Site/Controllers/` and register them in `API/SetupService`.
- **Service registration requires two steps.** When adding a service, update both `ServiceKey` (add a constant) and `ServiceContainer::resolve()` (add a case). Missing either will cause runtime errors.
- **Product meta key is `product_catalog_item`.** This controls catalog export inclusion. Don't introduce a second meta key for the same purpose.
- **E2E tests use Playwright.** Do not introduce Codeception or other frameworks. Write new tests in `tests/e2e/specs/` following existing patterns.
