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

**Order meta:** `_reddit_conversion_tracked` — marks an order as tracked.

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

### GitHub Actions

- **Pin every third-party action to a full commit SHA, not a version tag.** Use `owner/repo@<40-char-sha> # vX.Y.Z` instead of `owner/repo@v6`. A mutable tag can be repointed to a compromised or breaking release without any change in this repo; pinning to a SHA prevents that. Resolve the SHA for a tag with `gh api repos/{owner}/{repo}/commits/{tag} --jq '.sha'`, and find the matching version comment with `gh api repos/{owner}/{repo}/tags --jq '.[] | select(.commit.sha=="<sha>") | .name'`. This applies to every `uses:` line under `.github/workflows/` except references to files inside this repo (`./.github/actions/...`, `./.github/workflows/...`), which aren't third-party actions. See [WPCS: unpinned uses](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/github-actions/#unpinned-uses).

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. An internal-looking name or location is not by itself a guarantee that a symbol is safe to change: other extensions, themes, and custom site code implement and consume some of these contracts in practice. See the exposed-surface list for what counts and the **Scope** note for what does not; when a symbol is genuinely reachable and useful to outside code, err toward treating it as exposed.

**Externally exposed surface** — treat changes here as high-risk:

- **Custom hooks** — the actions and filters this plugin fires (the `reddit_for_woocommerce_` prefix, e.g. `reddit_for_woocommerce_onboarding_complete`). These are documented integration points for merchant site code and other extensions. Renaming a hook, changing or reordering its arguments, or dropping it breaks whatever is hooked in; to retire one, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window.
- **REST API** — the `wc/rfw` routes, their request/response shapes, and their auth expectations.
- **Public PHP** — any `public` class, method, or function another plugin or theme actually autoloads and calls, plus any symbol explicitly documented as extensible.
- **Front-end globals** — the `redditAds` JS globals and the pixel event contract that page scripts may read.

**Scope — what is *not* a third-party contract:** the static `ServiceContainer` and its `ServiceKey` constants are internal dependency-injection wiring — services are resolved internally via `ServiceContainer::resolve()`, not by outside code — so they are not an API other extensions build on. An ordinary signature change to a service that exists only so the container can construct and connect objects does **not** require a BC statement. When you are unsure whether a symbol is a contract, check the exposed surface above rather than assuming every `public` method is one.

Rules:

- **Never add or remove a required method on an interface that external code can implement** — existing implementers fatal on load. Prefer adding the method to the concrete class, introducing a new interface, or supplying a default implementation in an abstract base class. If an interface change is unavoidable, flag it explicitly.
- **Deprecate, don't rename.** Never rename or remove an existing public symbol in place: mark it `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window.
- **Never trust data that flows through hooks.** Keep hook callback parameters untyped and validate or coerce the value before passing it to strictly typed code, since any callback can receive a value another one produced. And when firing a filter, validate the final return value before using it, since any callback in the chain can return the wrong thing.
- **Don't implement or type-hint WooCommerce core `Internal\` classes or interfaces** — core treats them as changeable in any release. If unavoidable, guard the dependency with `class_exists()` / `interface_exists()` / `method_exists()` checks so a core change doesn't cause a fatal error in this plugin.

> Why: WooCommerce 10.9.0 was reverted on WP Cloud after woocommerce/woocommerce#64394 added a required method to core's internal `FeedInterface`, causing fatal errors in older WooCommerce Stripe Gateway versions that implemented it (fixed in woocommerce/woocommerce#65965). The same failure mode applies to any published WooCommerce extension.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. A change to any of the following is equally high-risk and needs the same backward-compatibility impact statement in the PR.

- **Overridable classes, including which internal methods get called.** Site code and extensions subclass exposed classes and override individual methods. Adding a fast path or skip that avoids calling an overridable method silently disables those overrides even though no signature changed: the subclass's code simply stops running. When optimizing such a class, ensure overridable methods are still invoked on every code path, or treat the change as breaking.
- **Script and style handles.** Registered handles (the `reddit_` asset handle prefix from `Config::ASSET_HANDLE_PREFIX`) are public contracts: third-party code enqueues them and lists them as dependencies, including handles only ever registered incidentally. Renaming a handle breaks those consumers. To rename with a compatibility window, register the legacy handle as an alias that depends on the new one (the same pattern WordPress core uses for `jquery` → `jquery-core`); do not register the same file under both handles, or pages with mixed consumers will load it twice.
- **Global state.** Code runs in admin, REST, CLI, cron, webhook, and front-end contexts, and not all set the globals a front-end request does (`$post`, `$wp_query`, an initialized session or cart). A new read of a global — or of `WC()->…` state — in a path reachable outside a standard request fatals or silently misbehaves where it isn't set. Guard the exact dependency (`function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle) and verify `WC()` and the component are initialized before dereferencing.
- **Multisite.** Site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, capabilities, and upload paths all differ under multisite. A change that reads or writes site state must state whether it behaves correctly under multisite, or say it wasn't tested there.
- **Install layout.** WordPress can run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind `home_url()` vs `site_url()`).

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, hook, script/style handle, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers — you cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone may consume it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it for review.

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
- **Plugin boots on `woocommerce_loaded`, not `plugins_loaded`.** WooCommerce is always available inside a service (intentional). Anything that must run earlier (e.g. hooking into `plugins_loaded` or `init`) has to be registered in the main plugin file `reddit-for-woocommerce.php`, not inside a service class.
- **`AdPartnerApi` is a separate singleton, not in ServiceContainer.** Use `AdPartnerApi::get_instance( $wcs_client )`. The constructor is private. It lives outside the container because it needs `$wcs_client` injected at first use.
- **Do not "fix" the `BaseAdPartnerApi` namespace.** Its namespace is `RedditForWooCommerce\Api\AdPartner` (lowercase `Api`); other classes in that directory use `RedditForWooCommerce\API\AdPartner`. The inconsistency is deliberate. Normalising the casing would break on Linux (case-sensitive filesystem).
- **`npm run build` strips PHP dev dependencies.** The prebuild script runs `composer install --no-dev`, removing PHPUnit, PHPCS, and everything in `require-dev` from `vendor/`. After a production build locally, run `composer install` again before linting or running tests.
- **Boolean options are stored as `'yes'` / `'no'` strings.** `PIXEL_ENABLED`, `CONVERSIONS_ENABLED`, `IS_JETPACK_CONNECTED`, and `DUMMY_PURCHASE_TRACKED` store literal strings, not PHP booleans. Use `'yes' === Options::get( OptionDefaults::PIXEL_ENABLED )`. A `(bool)` cast is wrong: `(bool) 'no'` is `true`.
- **`Store::get()` cannot distinguish "not set" from `false`.** If an option doesn't exist, `get_option()` returns `false`, and the storage layer treats that the same as an explicitly stored `false` — both fall back to the default. Bear this in mind when you need to tell "not configured yet" from "explicitly set to off".
