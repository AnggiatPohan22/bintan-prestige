# Performance Audit Report

Last updated: 2026-06-13

## DB-06 Global Settings Cache

Before DB-06, the global view composer in `App\Providers\AppServiceProvider` could run one active `site_assets` query and up to eleven separate active `site_settings` group queries for each composed view target.

DB-06 introduced `App\Services\GlobalSettingsService` as the public global settings/assets read layer. The service loads public settings groups through one cached settings query and active site assets through one cached assets query, then maps them back to the same Blade variable names used before.

## DB-06 Regression Fix

After initial DB-06 testing passed, a runtime dashboard regression appeared on `GET /dashboard`:

```txt
Cannot assign __PHP_Incomplete_Class to property App\Services\GlobalSettingsService::$settingsByGroup of type ?Illuminate\Support\Collection
```

Cause:

- The first DB-06 cache format stored serialized Laravel/Eloquent objects in the database cache.
- `config/cache.php` has `serializable_classes` disabled.
- A database cache payload such as `global_settings.public.v1` could return `__PHP_Incomplete_Class`.
- The service then assigned that invalid object payload to a typed `?Illuminate\Support\Collection` property.

Fix:

- Settings and assets cache payloads are now stored as primitive arrays.
- The service hydrates runtime `Collection` and `SiteAsset` objects only after reading the primitive cache payload.
- Invalid or legacy cache payloads are forgotten and rebuilt from database data.
- A focused dashboard regression test covers stale invalid settings cache.

Cache keys:

- `global_settings.public.v1`
- `global_assets.public.v1`

TTL:

- 30 minutes.

Invalidation:

- `SiteSetting` save/delete clears the settings cache.
- `SiteAsset` save/delete clears the assets cache.
- Admin update paths that previously used direct query-builder asset alt updates now update the model so cache invalidation is triggered.

Verification:

- `php artisan test tests\Feature\Performance\GlobalSettingsCacheTest.php` passed: 6 tests, 16 assertions.
- `php artisan test` passed after the regression fix: 136 tests, 603 assertions.

Regression verification after the fix:

- `php artisan test tests\Feature\Performance\GlobalSettingsCacheTest.php` passed: 8 tests, 23 assertions.
- `backend.dashboard` rendered successfully through Laravel bootstrap after cache rebuild.

No database schema, migration, route, layout, or product index was changed in this step.

## DB-07 Product Status/Created At Index

DB-07 added one product query index:

- `products_status_created_at_index` on `products(status, created_at)`

Before the index, EXPLAIN for `WHERE status = 'published' ORDER BY created_at DESC LIMIT ...` showed a full table scan and filesort.

After the index, EXPLAIN uses `products_status_created_at_index` with a backward index scan for latest/newest queries. Draft/published behavior, product query output, pagination, route behavior, and frontend/admin layouts are unchanged.

Deferred:

- `product_prices(currency, price)` remains deferred.
- Additional product indexes are not added in DB-07.
