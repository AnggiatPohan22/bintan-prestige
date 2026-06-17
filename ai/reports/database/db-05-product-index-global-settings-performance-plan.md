# STEP DB-05 - Product Query Index & Global Settings Performance Plan

Date: 2026-06-13
Status: Completed as planning/read-only.

## 1. Executive Summary

DB-05 reviewed the existing product query flow, current database indexes, and global/site settings loading flow before any performance implementation.

No Laravel runtime code, database schema, migration, cache behavior, frontend/admin UI, package, or data was changed. This report is the only created file.

Key findings:

- Product queries are backend-side and mostly well scoped, but `products` only has indexes for primary key, unique slug, `category_id`, and `destination_id`.
- Public product listing and homepage queries repeatedly filter by `status`, sort by `created_at`, and sometimes filter by `is_featured`, `category_id`, `destination_id`, `pickup_type`, `duration`, and IDR price.
- DB-04 already added unique `product_prices(product_id, currency)`, which is useful for product-owned price lookups but not ideal for global IDR price range queries.
- The only product index that is clearly required now is a composite index that supports the most frequent published/latest and homepage featured/latest product queries.
- A `product_prices(currency, price)` index is useful, but should be paired with focused price filter/sort tests before implementation.
- `AppServiceProvider` global settings composer can run 1 `site_assets` query plus up to 11 `site_settings` group queries per composed view target when data is not already supplied.
- `SiteSettingController@edit` also runs many settings/asset queries for the admin settings page and should keep reading fresh data, not cached public data.
- No existing Laravel cache layer or invalidation exists for site settings/assets.

Recommended DB-06 direction:

1. Implement a Laravel-native global settings reader/cache service first, because it removes repeated queries without changing schema.
2. Add focused tests for cache hit and cache invalidation.
3. Defer product indexes to DB-07 unless product listing scale is already painful.

## 2. Current Product Query Map

Admin Product index:

- File: `app/Http/Controllers/Admin/ProductController.php`
- Query:
  - `Product::query()`
  - eager loads `category`, `destination`, `prices`
  - `withCount` for product child modules
  - optional `WHERE name LIKE %search%`
  - optional `WHERE category_id = ?`
  - optional `WHERE destination_id = ?`
  - optional `WHERE status = ?`
  - `ORDER BY created_at DESC`
  - `paginate(10)`
- Columns used:
  - WHERE: `name`, `category_id`, `destination_id`, `status`
  - ORDER BY: `created_at`
  - pagination: `id`/`created_at` ordering from `latest()`

Public Product listing:

- File: `app/Http/Controllers/Frontend/ProductController.php`
- Base query:
  - `Product::query()->published()->frontendReady()`
- Filters:
  - `WHERE status = 'published'`
  - `WHERE EXISTS prices WHERE currency = 'IDR' AND price >= min`
  - `WHERE EXISTS prices WHERE currency = 'IDR' AND price <= max`
  - `WHERE IN duration (...)`
  - `WHERE IN destination_id (...)`
  - `WHERE IN category_id (...)`
  - `WHERE IN pickup_type (...)`
- Sorting:
  - `withMin(prices where currency = IDR, price)`
  - `ORDER BY idr_price_sort ASC/DESC`
  - or `ORDER BY CAST(duration AS UNSIGNED)`
  - or `ORDER BY created_at DESC`
- Pagination:
  - `paginate(8)`

Public Product filter support queries:

- Durations:
  - `Product::query()->published()->whereNotNull('duration')->where('duration', '!=', '')->distinct()->orderBy('duration')->pluck('duration')`
- Vehicle types:
  - `Product::query()->published()->whereNotNull('pickup_type')->where('pickup_type', '!=', '')->distinct()->orderBy('pickup_type')->pluck('pickup_type')`
- Price range:
  - `ProductPrice::query()->where('currency', 'IDR')->min('price')`
  - `ProductPrice::query()->where('currency', 'IDR')->max('price')`

Homepage product queries:

- File: `app/Http/Controllers/Frontend/HomeController.php`
- Featured products:
  - `Product::query()->published()->frontendReady()->with(['category', 'destination', 'images'])->where('is_featured', true)->latest()->take(6)->get()`
- Home products:
  - `Product::query()->published()->frontendReady()->with(['category', 'destination', 'images'])->latest()->take(12)->get()`

Category and destination published counts:

- `Category::query()->where('is_active', true)->withCount(['products' => fn ($query) => $query->published()])->orderBy('name')->get()`
- `Destination::query()->where('is_active', true)->withCount(['products' => fn ($query) => $query->published()])->orderBy('name')->get()`

Product detail by slug:

- File: `routes/frontend.php`
- Route: `/products/{product:slug}`
- Uses unique `products.slug` lookup through Laravel route model binding.
- Product detail then checks `status === 'published'`.

Admin dashboard product counts:

- File: `app/Http/Controllers/Admin/DashboardController.php`
- Queries:
  - `Product::count()`
  - `Product::where('status', 'published')->count()`
  - `Product::where('status', 'draft')->count()`
  - recent products: `Product::with(['category', 'destination'])->latest()->take(5)->get()`

## 3. Current Product Index Map

Actual indexes inspected with `SHOW INDEX`.

`products`:

- `PRIMARY` on `id`
- `products_slug_unique` on `slug`
- `products_category_id_foreign` on `category_id`
- `products_destination_id_foreign` on `destination_id`

`product_prices`:

- `PRIMARY` on `id`
- `product_prices_product_id_currency_unique` unique on:
  - `product_id`
  - `currency`

Related lookup tables:

- `categories`
  - `PRIMARY` on `id`
  - `categories_slug_unique` on `slug`
- `destinations`
  - `PRIMARY` on `id`
  - `destinations_slug_unique` on `slug`

Settings tables:

- `site_settings`
  - `PRIMARY` on `id`
  - `site_settings_key_unique` on `key`
  - `site_settings_group_index` on `group`
- `site_assets`
  - `PRIMARY` on `id`
  - `site_assets_key_unique` on `key`

Notes:

- MySQL/InnoDB creates indexes for foreign key columns, confirmed by `SHOW INDEX`.
- `products.status`, `products.is_featured`, `products.created_at`, `products.pickup_type`, and `products.duration` currently have no direct or composite indexes.
- `product_prices(currency, price)` does not exist yet.

## 4. Missing Index Findings

High-confidence missing index candidates:

- `products(status, created_at)`
  - Supports public product listing default sort: published + newest.
  - Supports homepage latest products: published + latest.
  - Supports dashboard published/draft counts partially.

- `products(status, is_featured, created_at)`
  - Supports homepage featured products: published + featured + latest.
  - Could also support the published/latest query when `is_featured` is included, but it is less general than `status, created_at`.

- `product_prices(currency, price)`
  - Supports min/max IDR price.
  - Supports price range `whereHas` subqueries.
  - Supports price-based listing filters better than the DB-04 `product_id,currency` unique index.

Lower-confidence candidates:

- `products(status, category_id, created_at)`
  - Useful when category filter is common and product count grows.
  - Existing FK index on `category_id` already helps category-only lookup, but not necessarily status + latest ordering.

- `products(status, destination_id, created_at)`
  - Useful when destination filter is common and product count grows.
  - Existing FK index on `destination_id` already helps destination-only lookup.

- `products(status, pickup_type, created_at)`
  - Useful only if vehicle/pickup filter becomes common and cardinality is high enough.

- `products(status, duration)`
  - Useful only for duration filter values.
  - Not useful for numeric duration sorting because current code uses `CAST(duration AS UNSIGNED)`.

## 5. Redundant Index Findings

Do not add:

- `products(category_id)` because it already exists through FK index.
- `products(destination_id)` because it already exists through FK index.
- `products(slug)` because unique slug index already exists.
- `product_prices(product_id)` as a standalone index unless proven needed, because DB-04 unique `(product_id, currency)` can serve product-owned price lookups by leftmost prefix.
- `site_settings(key)` because unique key index already exists.
- `site_assets(key)` because unique key index already exists.

Potentially redundant depending on final composite choice:

- Adding both `products(status, created_at)` and `products(status, is_featured, created_at)` may be acceptable if homepage/featured traffic is high, but start with one to avoid over-indexing.
- Adding both `product_prices(currency)` and `product_prices(currency, price)` is redundant if the composite index is implemented.

## 6. Composite Index Analysis

`products(status, created_at)`:

- Best general first product index.
- Helps:
  - public listing default newest
  - homepage latest
  - status counts partially
  - admin status filter + latest
- Does not directly optimize featured filter, category filter, destination filter, pickup type filter, or price filter.
- Recommended first if only one product index is added.

`products(status, is_featured, created_at)`:

- Best for homepage featured products.
- Helps query with `status = published`, `is_featured = true`, and newest order.
- Less general for normal listing because `is_featured` is not used there.
- Recommended later if homepage scale needs it.

`products(status, category_id, created_at)`:

- Helps public category filter and admin category/status filter when combined with latest sort.
- Existing `category_id` FK index already helps some category filtering.
- Recommended later after measuring category-filter usage.

`products(status, destination_id, created_at)`:

- Same reasoning as category index.
- Recommended later after measuring destination-filter usage.

`products(status, pickup_type, created_at)`:

- Only useful if pickup/vehicle filter is frequently used.
- Deferred.

`products(status, duration)`:

- Helps duration filter list and exact duration filter.
- Does not solve `ORDER BY CAST(duration AS UNSIGNED)`.
- Not recommended now.

`product_prices(currency, price)`:

- Useful for `WHERE currency = IDR` + min/max price range queries.
- Useful for `MIN(price)` and `MAX(price)` by currency.
- Does not replace unique `(product_id, currency)`.
- Recommended after focused price filter/sort tests.

## 7. Required Indexes Now

Recommended now for DB-06/DB-07 consideration:

1. `products(status, created_at)`

Reason:

- It maps to the most common product pattern found in the current code:
  - public product index default: published + latest + pagination
  - homepage latest products: published + latest + take
  - admin status filter + latest
  - dashboard published/draft counts can benefit from status leading column

Implementation caution:

- Use a clear index name, for example `products_status_created_at_index`.
- Add in a new migration only.
- Run tests and rollback check.

No index should be added in DB-05 because this step is planning/read-only.

## 8. Deferred Indexes

Recommended deferred:

- `product_prices(currency, price)`
  - Add after product price filter/sort tests prove behavior and query volume justifies it.

- `products(status, is_featured, created_at)`
  - Add if homepage product queries become a performance hotspot.

- `products(status, category_id, created_at)`
  - Add if category-filtered listing has enough volume/content.

- `products(status, destination_id, created_at)`
  - Add if destination-filtered listing has enough volume/content.

- `page_sections(page_key, is_active, sort_order)`
  - Relevant to homepage/page-section performance but outside this product/global settings DB-05 scope.

- `site_settings(group, is_active)`
  - Useful if cache is not implemented or admin settings data grows.
  - Lower priority if cache service will consolidate reads.

- `site_assets(is_active)`
  - Useful if active asset listing grows, but `site_assets` should remain small.
  - Lower priority than cache consolidation.

## 9. Indexes Not Recommended

Not recommended now:

- `products(name)` for admin search.
  - Current search is `%term%`, which normal BTREE cannot use effectively for leading wildcard.
  - Full-text/search solution should be a separate feature if needed.

- `products(duration)` for numeric sorting.
  - Current `CAST(duration AS UNSIGNED)` is not index-friendly.
  - A normalized duration column would be a separate schema/business change.

- `product_prices(product_id)` standalone.
  - Covered by leftmost prefix of unique `(product_id, currency)`.

- `site_settings(key)` and `site_assets(key)`.
  - Already unique.

- Broad "index everything" pass.
  - Adds write overhead, storage cost, migration time, and rollback complexity.

## 10. Current Global Settings Data Flow

Global composer:

- File: `app/Providers/AppServiceProvider.php`
- Composer targets:
  - `frontend.partials.header`
  - `frontend.partials.footer`
  - `frontend.frontend`
  - `layouts.frontend`
  - `layouts.admin`
  - `layouts.app`
  - `layouts.guest`

Per composed view, when data is not already provided:

- 1 query for active `SiteAsset` rows:
  - `SiteAsset::query()->where('is_active', true)->get()->keyBy('key')`
- Up to 11 separate `SiteSetting` group queries:
  - Brand colors
  - Business identity
  - Contact information
  - Social media links
  - Navigation
  - Footer
  - SEO defaults
  - Tracking integrations
  - Booking CTA
  - Default media
  - Structured data

Admin settings edit page:

- File: `app/Http/Controllers/Admin/SiteSettingController.php`
- Reads:
  - one `SiteAsset::whereIn('key', [...])` query
  - several `SiteSetting` queries by group or key group
  - converts rows to `keyBy('key')`

Frontend homepage:

- File: `app/Http/Controllers/Frontend/HomeController.php`
- Separately reads active `SiteAsset` rows for home view data.
- The composer may also prepare `siteAssets` depending on composed views/data availability.

## 11. Repeated Query Findings

Findings:

- `AppServiceProvider` repeats the same `SiteSetting::query()->where('group')->where('is_active')->get()->keyBy('key')` pattern for each global setting domain.
- There is no cache layer in application code for settings/assets.
- `rg` found no `Cache::remember()` or `Cache::forget()` usage for global settings/assets.
- Composer checks `array_key_exists()` for each view data key, which helps when a controller passes data, but does not prevent all repeated work across multiple composed view targets.
- Admin settings page intentionally loads many groups for editing; this should remain fresh and should not depend only on public cache.
- `HomeController` loads `siteAssets` separately from the global composer.

Potential query count per request:

- A single composed view can run up to 12 global queries.
- A frontend layout with header/footer/layout targets could trigger more unless data is shared early or cached.
- Admin and auth layouts also receive the global composer, even if some settings are not needed for every admin/auth view.

No Blade DB queries:

- Blade scan found no direct DB/Model queries.
- Matches were collection operations or error-bag component calls.

## 12. Recommended Cache Strategy

Use Laravel-native cache behind a dedicated reader service.

Recommended new class:

- `app/Services/GlobalSettingsService.php`

Responsibilities:

- Load active site settings once and group/key them in memory.
- Load active site assets once and key them by `key`.
- Convert grouped settings through existing support classes.
- Expose stable methods for composer/controller use.

Recommended cache style:

- Public/global read cache:
  - `Cache::remember()`
  - TTL: 10 to 30 minutes, or `rememberForever` if invalidation is complete.
- Prefer TTL first for DB-06 because invalidation is new.
- Move to `rememberForever` only after tests prove all update/delete/reset paths invalidate correctly.

Recommended separation:

- Public composer cache:
  - safe for public frontend/global layout display.
- Admin settings edit page:
  - should read fresh DB data or use a method with `fresh: true`.
  - admin edit forms should not show stale cached settings after updates.

Sensitive settings:

- Tracking/custom script settings can be cached for frontend rendering, but do not log or document raw values.
- Do not cache secrets/credentials in docs or reports.
- If future settings include API keys/secrets, keep them out of public composer cache entirely.

## 13. Cache Key and Invalidation Plan

Recommended cache keys:

- `global_settings.active.grouped.v1`
- `global_settings.active.values.v1`
- `site_assets.active.keyed.v1`

Recommended invalidation:

- Central method:
  - `GlobalSettingsService::forgetCache()`
- Call after any `SiteSetting::updateOrCreate()` loop.
- Call after any `SiteAsset` upload/update/delete/reset.
- Call inside `PageSectionImageService::storeSiteAssetUpload()`.
- Call inside `PageSectionImageService::clearSiteAsset()`.
- Call in `SiteSettingController` update methods after successful writes if service-level invalidation is not used.

Deployment behavior:

- `php artisan cache:clear` should refresh all global settings cache.
- The service should gracefully rebuild cache on miss.

Cache miss behavior:

- Query database.
- Key settings by `group`, then by `key`.
- Return support-class defaults when no DB rows exist.

Cache hit behavior:

- Reuse cached grouped/keyed collections or arrays.
- Composer output should match uncached output.

## 14. SiteSetting Controller Impact

DB-06 should avoid rewriting the large controller all at once.

Minimal impact plan:

- Keep `SiteSettingController` update logic intact.
- Add cache invalidation after write paths.
- Keep `edit()` reading fresh DB rows, at least initially.
- Move public/composer reads to `GlobalSettingsService`.

Write paths that need invalidation include:

- Logo update/delete
- Favicon update/delete
- Brand colors
- Social share image update/delete
- Business identity
- Contact information
- Social media links
- Navigation settings
- Footer settings
- SEO defaults and OG image delete
- Tracking integrations
- Booking CTA
- Default media update/delete
- Structured data

Avoid in DB-06:

- Splitting the full controller.
- Changing admin UI.
- Changing settings field definitions.
- Changing upload behavior.

## 15. Frontend/Admin Impact

Frontend impact:

- Expected output should not change.
- Header/footer/layout/SEO/structured data settings should render from cached values.
- Cache invalidation must make updates visible after admin saves.
- Public homepage currently passes `siteAssets`; service usage should avoid double querying.

Admin impact:

- Admin settings edit page should display fresh values.
- Admin layouts can use cached global settings if they only need display settings.
- Settings update actions must clear cache after save/delete/reset.

Security impact:

- No secrets should be added to docs or reports.
- Tracking scripts remain content settings and should be treated carefully.
- Cache should not become a hidden bypass for settings validation.

## 16. Testing Plan

Product query/index testing plan:

- Product listing shows only published products.
- Category filter works.
- Destination filter works.
- Featured product query works on homepage.
- Product detail by slug works.
- Admin product pagination/search/status/category/destination filters work.
- Price filter/sort tests should be added before `product_prices(currency, price)`.

Global settings cache testing plan:

- Composer output matches uncached values.
- Cache miss queries DB and builds expected arrays.
- Cache hit does not change frontend output.
- Updating brand colors invalidates cache.
- Updating navigation invalidates cache.
- Updating SEO defaults invalidates cache.
- Uploading/deleting site assets invalidates cache.
- Clearing default media invalidates cache.
- Admin settings edit page reads fresh values after update.
- Full `php artisan test` remains green.

Query-count test plan without package:

- Use Laravel `DB::enableQueryLog()` or `DB::listen()` in a focused Feature test.
- Hit a lightweight frontend route twice.
- Assert second service call uses cache for global settings/service-level reads.
- Avoid brittle total page query counts at first; assert specific settings queries are reduced.

## 17. Files Inspected

Rules, skills, reports:

- `AGENTS.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `ai/reports/database/db-01-product-factory-seeder-price-integrity-plan.md`
- `ai/reports/database/db-04-product-price-unique-index-implementation-report.md`
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`

Docs:

- `docs/database/README.md`
- `docs/database/schema-overview.md`
- `docs/database/relationships.md`
- `docs/performance/README.md`
- `docs/modules/README.md`
- `docs/modules/products.md`

Routes/controllers/models/services:

- `routes/frontend.php`
- `routes/admin.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Models/Product.php`
- `app/Models/ProductPrice.php`
- `app/Models/SiteSetting.php`
- `app/Models/SiteAsset.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/PageSectionImageService.php`

Migrations:

- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `database/migrations/2026_06_13_000001_add_unique_product_currency_to_product_prices_table.php`
- `database/migrations/2026_06_03_000005_create_site_assets_table.php`
- `database/migrations/2026_06_04_000001_create_site_settings_table.php`

Commands/scans:

- `git status --short`
- `SHOW INDEX` read-only inspection for `products`, `product_prices`, `site_settings`, `site_assets`, `categories`, and `destinations`
- `rg` scan for product query usage
- `rg` scan for `SiteSetting`, `SiteAsset`, and cache usage
- `rg` Blade query scan
- `git diff --check`

## 18. Files Recommended for DB-06 Changes

Recommended DB-06 cache implementation files:

- `app/Services/GlobalSettingsService.php` or `app/Support/GlobalSettingsRepository.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- `tests/Feature/Performance/GlobalSettingsCacheTest.php`
- `docs/performance/global-settings-cache.md`
- `docs/changelog/CHANGELOG.md`
- `ai/reports/database/db-06-global-settings-cache-implementation-report.md`

Recommended later product index files:

- New migration for `products(status, created_at)`.
- Later optional migration for `product_prices(currency, price)`.
- `tests/Feature/Frontend/ProductFilterQueryTest.php`.
- `ai/reports/database/db-07-product-index-implementation-report.md`.

## 19. Database Migration Risk

No DB-05 migration was created.

Future index migration risks:

- Insert/update overhead increases with each index.
- Storage usage increases.
- Large tables can take longer to alter.
- Rollback requires dropping indexes by name.
- Duplicate/redundant indexes can slow writes without improving reads.

Risk reduction:

- Add only one or two indexes per step.
- Use explicit index names.
- Verify existing indexes before migration.
- Run `php artisan migrate`, `php artisan migrate:status`, `php artisan test`, and `git diff --check`.

## 20. Cache Staleness Risk

Primary staleness risk:

- Admin updates settings/assets but public frontend keeps rendering old cached values.

Mitigation:

- Central invalidation method.
- Invalidate after every `SiteSetting` write path.
- Invalidate after every `SiteAsset` upload/delete/reset path.
- Use TTL during first implementation so stale cache self-heals.
- Keep admin edit reads fresh until cache behavior is proven.

Secondary risk:

- Multiple cache keys drift from each other.

Mitigation:

- Prefer one service owning all global settings/assets keys.
- Keep cache key names versioned.
- Clear all related keys together.

## 21. Rollback Plan

DB-05 rollback:

- Remove `ai/reports/database/db-05-product-index-global-settings-performance-plan.md` if this planning record should be discarded.
- No runtime rollback is required.

Future DB-06 cache rollback:

- Revert service/composer/controller invalidation changes.
- Run `php artisan cache:clear`.
- Restore direct query composer behavior.
- Keep database unchanged.

Future product index rollback:

- Roll back the index migration.
- Drop index by explicit name in migration `down()`.
- Run `php artisan migrate:status` and `php artisan test`.

## 22. Recommended Next Step

Recommended next step:

STEP DB-06: Global Settings Cache Service Implementation.

Why DB-06 first:

- It addresses repeated query risk without changing database schema.
- It is reversible with code rollback and cache clear.
- It improves both frontend and admin layout performance.

Suggested DB-06 boundaries:

- Add a dedicated global settings/cache service.
- Replace repeated composer queries with service calls.
- Add cache invalidation after settings/assets writes.
- Keep admin settings edit page fresh.
- Add focused cache tests.
- Do not add product indexes in the same step.

Product indexes should follow in a separate DB-07 step after focused product filter/sort tests.
