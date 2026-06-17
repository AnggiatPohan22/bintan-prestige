# STEP DB-06 - Global Settings Service & Cache Implementation Report

Date: 2026-06-13
Status: Completed.

## 1. Summary

DB-06 implemented a Laravel-native global settings/assets service and cache layer to reduce repeated global settings queries from the view composer.

No database schema, migration, route, frontend/admin layout, package, product index, or setting key was changed.

## 2. Previous query flow

Before DB-06, `App\Providers\AppServiceProvider` prepared global layout data directly in the view composer. A composed view could run:

- 1 query for active `site_assets`
- up to 11 separate active `site_settings` group queries

`Frontend\HomeController` also loaded active `SiteAsset` rows separately.

## 3. New service flow

`App\Services\GlobalSettingsService` is now the single public read layer for global settings and active site assets.

The service:

- loads public settings groups through one cached query
- groups settings by `group` and keys rows by `key`
- loads active site assets through one cached query
- stores cache payloads as primitive arrays
- hydrates runtime `Collection` and `SiteAsset` objects after cache reads
- discards invalid or legacy object payloads and rebuilds from the database
- maps data through existing support classes
- returns the same Blade variable names used before DB-06
- uses request-level memoization through the service singleton

## 4. Cache keys

- `global_settings.public.v1`
- `global_assets.public.v1`

## 5. Cache TTL strategy

TTL is 30 minutes for both cache keys.

`rememberForever` was not used because this is the first cache implementation step and TTL provides a self-healing fallback if a future invalidation path is missed.

## 6. Cache invalidation flow

Invalidation is scoped:

- `SiteSetting` saved/deleted clears `global_settings.public.v1`
- `SiteAsset` saved/deleted clears `global_assets.public.v1`
- `Cache::flush()` is not used

Three admin asset alt update paths were adjusted from query-builder `update()` to model `update()` so Eloquent invalidation events fire.

## 7. Files created

- `app/Services/GlobalSettingsService.php`
- `tests/Feature/Performance/GlobalSettingsCacheTest.php`
- `docs/performance/checklist.md`
- `docs/performance/audit-report.md`
- `docs/admin/site-settings.md`
- `docs/architecture/frontend-backend-sync.md`
- `ai/reports/performance/db-06-global-settings-cache-implementation-report.md`

## 8. Files changed

- `app/Providers/AppServiceProvider.php`
- `app/Models/SiteSetting.php`
- `app/Models/SiteAsset.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `docs/changelog/CHANGELOG.md`

## 9. Frontend impact

Frontend output is expected to remain unchanged.

Existing Blade variable names were preserved. Public frontend pages now receive global settings/assets from the cached service through the provider or controller.

## 10. Admin impact

Admin settings forms, routes, validation, uploads, and layout were not changed.

Admin updates now invalidate the relevant public cache key through model events. Admin edit reads still use the existing fresh database queries in `SiteSettingController`.

## 11. Security impact

No auth flow, public registration, admin middleware, role logic, or credential handling was changed.

The service only caches known public-facing setting groups and active public site assets. It does not add credential, token, password, or private configuration caching.

## 12. Query/performance impact

The repeated composer group queries are consolidated behind two versioned cache keys:

- one public settings cache
- one public assets cache

Focused service verification confirmed that a cache hit returns the same output without issuing `site_settings` or `site_assets` queries.

Regression finding:

- Initial DB-06 cache payloads stored serialized Laravel/Eloquent objects.
- In the local database cache table, `global_settings.public.v1` contained a serialized `Illuminate\Support\Collection` with nested Eloquent collections.
- `config/cache.php` disables unserializing arbitrary PHP classes through `serializable_classes => false`.
- That made the cache vulnerable to returning `__PHP_Incomplete_Class`, which caused a TypeError when assigned to the typed `$settingsByGroup` property.

## 13. Focused test result

Passed:

```bash
php artisan test tests\Feature\Performance\GlobalSettingsCacheTest.php
```

Result after regression fix: 8 tests, 23 assertions.

Regression check:

```bash
php artisan test tests\Feature\Admin\GlobalStructuredDataSettingsTest.php
```

Result: 6 tests, 22 assertions.

## 14. Full php artisan test result

Passed:

```bash
php artisan test
```

Result after regression fix: 136 tests, 603 assertions.

## 15. Remaining risks

- Future private settings must not be added to the public cached group allowlist.
- Total page-level query-count tests are still not implemented because they can be brittle.
- Product index migration remains deferred to a later DB step.
- Larger settings controller cleanup remains a future backend improvement.
- If cache payload shape changes in a future version, bump the cache key version or keep a normalizer for old payloads.

## 16. Rollback procedure

1. Revert the files changed/created in DB-06.
2. Run:

```bash
php artisan cache:clear
```

No database rollback is required because no migration or schema change was made.

## 17. Recommended next step

Proceed to a separate DB/performance step for product query index implementation only after focused product listing/filter tests are confirmed.
