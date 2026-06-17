# STEP DB-01 - Product Factory, Seeder, and Product Price Integrity Plan

Date: 2026-06-13
Status: Completed as planning-only.

## Executive Summary

This planning step reviews the product factory, seeders, product price integrity, query/index readiness, global settings query flow, and Category/Destination soft-delete behavior before any database or runtime implementation.

No Laravel code, database schema, migration, route, controller, model, view, config, public asset, seeder, factory, or test file was changed. This report is the only created file.

Key findings:

- `ProductFactory` still writes `status => true`, while the current product publication flow expects string values: `draft` or `published`.
- `TravelSeeder` creates IDR and SGD prices per product, but `product_prices` does not enforce unique `(product_id, currency)` at database level.
- `ProductPriceService` already uses `updateOrCreate(['product_id', 'currency'])`, so the application behavior expects one row per currency per product.
- Product listing filters and price sorting are backend-side, but the indexes for `products` filters and `product_prices` price range queries should be planned carefully.
- `AppServiceProvider` view composer repeatedly queries `site_assets` and multiple `site_settings` groups for shared layouts/partials.
- Category and Destination use SoftDeletes, while `products.category_id` and `products.destination_id` still use `cascadeOnDelete()`. Current admin force-delete guards reduce the practical risk, but database-level cascade remains risky if bypassed.

Recommended safe sequence:

1. DB-02: Fix `ProductFactory` status and add focused tests.
2. DB-03: Run duplicate product price pre-checks and only then add unique/index migration.
3. DB-04: Add targeted product/product price indexes based on confirmed query patterns.
4. DB-05: Add cached global settings reader with invalidation on admin settings updates.
5. DB-06: Revisit Category/Destination force-delete and FK policy after a dedicated data safety plan.

## Current ProductFactory Findings

File inspected:

- `database/factories/ProductFactory.php`

Findings:

- The factory creates products with random existing Category and Destination IDs:
  - `Category::inRandomOrder()->first()->id`
  - `Destination::inRandomOrder()->first()->id`
- This means the factory requires at least one Category and one Destination to already exist.
- The generated slug uses the product name plus a fake unique number, which reduces collision risk.
- `status` is currently set to boolean `true`.
- Current product logic expects string status values:
  - `Product::scopePublished()` filters `status = 'published'`.
  - `StoreProductRequest` validates `status` as `in:draft,published`.
  - `Admin\ProductController::toggleStatus()` toggles between `draft` and `published`.
  - Frontend Product index/detail only render published products.

Risk:

- ProductFactory-created records can store status as `1`/`true` depending on database casting/storage, so factory-created demo products may not be visible through `published()` queries.
- Existing tests often override `status => 'published'`, which masks the factory drift.

Recommended DB-02 plan:

- Change ProductFactory default status from `true` to a valid string.
- Recommended default: `published`, because the existing TravelSeeder uses `Product::factory(30)->create()` to generate demo catalog content intended for frontend visibility.
- Add explicit factory states:
  - `published()`
  - `draft()`
- Keep existing category/destination dependency unchanged in DB-02 unless tests reveal failures.
- Add a focused test proving a default factory product has a valid string status.
- Add a focused test proving `Product::published()` returns default factory products if default is changed to `published`.

Optional later cleanup:

- Improve `CategoryFactory` and `DestinationFactory` to avoid slug collisions when many records are created.
- Add a safer fallback inside ProductFactory only if future tests need products without pre-created Category/Destination.

## Current Seeder Findings

Files inspected:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/TravelSeeder.php`
- `database/seeders/HomePageSectionSeeder.php`
- `database/seeders/FaqSeeder.php`

Findings:

- `DatabaseSeeder` calls:
  - `TravelSeeder`
  - `HomePageSectionSeeder`
  - `FaqSeeder`
- `TravelSeeder` deletes all categories and destinations before inserting fixed demo records:
  - `Category::query()->delete()`
  - `Destination::query()->delete()`
- `TravelSeeder` then runs `Product::factory(30)->create()`.
- Because ProductFactory currently uses boolean status, TravelSeeder likely creates products with a stale status value.
- `TravelSeeder` creates exactly two price rows per product:
  - IDR
  - SGD
- `HomePageSectionSeeder` and `FaqSeeder` use `updateOrCreate`, which is safer and repeatable for canonical seed data.

Risks:

- `TravelSeeder` is destructive and should be treated as local/demo-only unless redesigned.
- Deleting categories/destinations can cascade to products if rows are truly deleted and FK constraints are enforced.
- TravelSeeder does not use `updateOrCreate` for products/prices, so it is not a production-safe content sync seeder.
- ProductFactory status drift makes demo seed content inconsistent with frontend `published()` scope.

Recommended DB-02 plan:

- Fix ProductFactory first, so TravelSeeder output becomes valid without editing the seeder.
- Add documentation/report note that `TravelSeeder` is local/demo seed data only.
- Do not rewrite TravelSeeder in the same step as product price unique migration.

Recommended later seeder plan:

- Split demo seeders from canonical seeders.
- Consider making demo seeders opt-in by environment or artisan command.
- If TravelSeeder must become repeatable, redesign it with stable slugs and `updateOrCreate` instead of broad deletes.

## Current Product Price Integrity Findings

Files inspected:

- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `app/Models/ProductPrice.php`
- `app/Models/Product.php`
- `app/Services/ProductPriceService.php`
- `app/Services/ProductService.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`

Current schema:

- `product_prices.id`
- `product_prices.product_id`
- `product_prices.currency`
- `product_prices.price`
- timestamps
- FK: `product_id` constrained with `cascadeOnDelete()`

Current application intent:

- Product has many prices.
- Product price belongs to product.
- `ProductPriceService::sync()` uses `updateOrCreate` for:
  - `product_id`
  - `currency`
- Store/update validation requires `idr_price` and `sgd_price`.
- Frontend price range filters query `product_prices` by `currency = IDR` and `price`.
- Frontend price sorting uses `withMin` on product prices where `currency = IDR`.

Gap:

- The database does not enforce uniqueness for `(product_id, currency)`.
- Duplicate price rows can happen through manual DB edits, old scripts, direct model creates, future code paths, or repeated custom imports.

## Duplicate Price Risk

Risk level: Medium.

Why medium:

- Current admin product save/update path is reasonably safe because `ProductPriceService::sync()` uses `updateOrCreate`.
- TravelSeeder creates exactly one IDR and one SGD price per newly created product.
- However, the database allows duplicates, and the frontend accessors use the first matching row in the loaded collection.
- Duplicate rows could make displayed price, price sorting, min/max filters, and structured data inconsistent.

Potential symptoms if duplicates exist:

- Product card displays one IDR price while sort/filter uses another row.
- `withMin` sorts by the lowest duplicate IDR price.
- Admin edit may show unexpected price if collection order changes.
- Adding a future unique index would fail during migration.

## Recommended Data Pre-check

Before any unique index migration, run a read-only duplicate check in the target database.

SQL pre-check:

```sql
SELECT product_id, currency, COUNT(*) AS total
FROM product_prices
GROUP BY product_id, currency
HAVING COUNT(*) > 1;
```

Laravel query builder equivalent:

```php
DB::table('product_prices')
    ->select('product_id', 'currency', DB::raw('COUNT(*) as total'))
    ->groupBy('product_id', 'currency')
    ->havingRaw('COUNT(*) > 1')
    ->get();
```

Recommended manual review query if duplicates are found:

```sql
SELECT id, product_id, currency, price, created_at, updated_at
FROM product_prices
WHERE (product_id, currency) IN (
    SELECT product_id, currency
    FROM product_prices
    GROUP BY product_id, currency
    HAVING COUNT(*) > 1
)
ORDER BY product_id, currency, id;
```

Decision policy before unique index:

- If duplicate rows have the same price, keep the newest or lowest ID consistently and delete the duplicate only after backup.
- If duplicate rows have different prices, resolve manually with business confirmation.
- Do not run the unique migration until the duplicate pre-check returns zero rows.
- Record the duplicate check result in the DB-03 report before migration.

## Recommended Migration Plan

Do not edit old migrations.

Recommended DB-03 migration:

- Create a new migration named similar to:
  - `xxxx_xx_xx_xxxxxx_add_unique_product_currency_to_product_prices_table.php`

Recommended migration behavior:

- Add a unique constraint for:
  - `product_id`
  - `currency`
- Suggested index name:
  - `product_prices_product_id_currency_unique`

Recommended migration shape:

```php
Schema::table('product_prices', function (Blueprint $table) {
    $table->unique(['product_id', 'currency'], 'product_prices_product_id_currency_unique');
});
```

Recommended rollback:

```php
Schema::table('product_prices', function (Blueprint $table) {
    $table->dropUnique('product_prices_product_id_currency_unique');
});
```

Migration safety:

- Prefer running the duplicate pre-check manually before migration.
- If the team wants extra safety, the migration can include a guard that checks duplicates and throws an exception before adding the unique index.
- If adding a guard, document that the migration can intentionally stop when duplicate data exists.

Do not combine with broad index changes unless DB-03 explicitly approves a larger schema step.

## Recommended Index Plan

Index planning should be phased to avoid over-indexing.

Current useful constraints/indexes already present:

- `products.slug` unique.
- `products.category_id` FK index.
- `products.destination_id` FK index.
- `product_prices.product_id` FK index.
- `categories.slug` unique.
- `destinations.slug` unique.
- `site_settings.key` unique.
- `site_settings.group` index.
- `site_assets.key` unique.

Recommended priority 1:

- `product_prices(product_id, currency)` unique.
  - Reason: integrity first.
  - Also supports product-level price lookup/update.

Recommended priority 2:

- `product_prices(currency, price)`.
  - Reason: frontend product price range filter and min/max IDR price lookups use `currency` and `price`.
  - Useful for `where currency = IDR and price >=/<= value`.

Recommended priority 3:

- `products(status, is_featured, created_at)`.
  - Reason: homepage featured/latest products query uses `published`, `is_featured`, and latest order.

- `products(status, category_id)`.
  - Reason: frontend filtering by category only shows published products.

- `products(status, destination_id)`.
  - Reason: frontend filtering by destination only shows published products.

Recommended priority 4:

- `products(status, pickup_type)`.
  - Reason: product listing filters vehicle/pickup type for published products.

Recommended later review:

- `products(duration)` only if duration remains a stable string filter.
- Avoid relying on `duration` for numeric sorting because `orderByRaw('CAST(duration AS UNSIGNED)')` is not index-friendly.
- Consider a future normalized duration field, such as `duration_minutes`, only after separate approval.

Child content ordering indexes if content volume grows:

- `product_images(product_id, sort_order)`
- `product_highlights(product_id, sort_order)`
- `product_features(product_id, sort_order)`
- `product_faqs(product_id, sort_order)`
- `product_itineraries(product_id, sort_order, start_time)`
- `product_notes(product_id, sort_order)`

Page/global indexes if content volume grows:

- `page_sections(page_key, is_active, sort_order)`
- `page_section_media(page_section_id, role, slot_key, sort_order)`
- `site_settings(group, is_active)`
- `site_assets(is_active)`
- `faqs(is_active, sort_order)`

Index caution:

- Add only the first one or two indexes in the first DB implementation step.
- Confirm query usage with real app behavior and tests before adding every possible index.

## Global Settings Query Plan

Files inspected:

- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Models/SiteSetting.php`
- `app/Models/SiteAsset.php`

Current behavior:

- `AppServiceProvider` registers a view composer for shared frontend/admin/auth layouts and partials.
- The composer loads active site assets and then queries separate `site_settings` groups:
  - brand colors
  - business identity
  - contact information
  - social media links
  - navigation
  - footer
  - SEO defaults
  - tracking integrations
  - booking CTA
  - default media
  - structured data

Risk:

- Multiple settings queries can run for common layout rendering.
- The composer checks whether each view already has data, but the same request can still compose multiple related view targets.
- Global settings are stable enough to cache, but settings updates need invalidation.

Recommended cache strategy:

- Introduce a small settings reader/service in a later step, for example:
  - `app/Services/GlobalSettingsService.php`
  - or `app/Support/GlobalSettingsRepository.php`
- Cache all active settings grouped by `group` in one call:
  - cache key: `global_settings.active.grouped`
- Cache all active site assets keyed by `key`:
  - cache key: `site_assets.active.keyed`
- Use `Cache::remember()` with a conservative TTL, for example 10-30 minutes.
- On admin settings update or asset upload/delete/reset, call `Cache::forget()` for the affected global cache keys.
- Keep existing support classes such as `BrandColorSettings::valuesFromSettings()` and pass them cached collections.

Recommended implementation order:

1. Add service/repository.
2. Replace repeated composer queries with service calls.
3. Add invalidation to `SiteSettingController` update actions and `PageSectionImageService` site asset mutations.
4. Add tests that settings updates are reflected after cache invalidation.

Do not implement this in DB-02 if the goal is only factory/price integrity.

## Soft Delete vs Cascade Notes

Files inspected:

- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_31_000001_add_deleted_at_to_categories_and_destinations_table.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/Product.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`

Current behavior:

- Category and Destination models use SoftDeletes.
- Product belongs to Category and Destination with `withTrashed()`, so products can still reference archived parents.
- Products table uses:
  - `category_id` constrained with `cascadeOnDelete()`
  - `destination_id` constrained with `cascadeOnDelete()`
- Admin force-delete actions load `products_count` and block permanent delete when products still exist.

Risk:

- Normal admin archive behavior is safe because soft delete does not trigger FK cascade.
- Admin force delete is guarded in controller.
- Direct database deletes, future code paths, or unguarded force deletes can still cascade-delete products.
- This schema is stricter than the current business intent, because products should likely survive archived categories/destinations.

Recommended safe policy:

- Keep current controller force-delete guards.
- Add tests that Category/Destination with products cannot be force-deleted from admin.
- Avoid direct force deletes in seeders or maintenance scripts.
- Treat changing FK behavior as a separate high-care migration plan.

Future FK options:

- `restrictOnDelete()`: safest for preserving products and preventing accidental parent deletion.
- `nullOnDelete()`: possible only if `category_id`/`destination_id` become nullable and frontend/admin can handle uncategorized products.
- Keep cascade: acceptable only if the business intentionally wants products deleted when parent is permanently deleted.

Recommended direction:

- Prefer `restrictOnDelete()` in a future dedicated DB step, but only after checking existing data, admin flows, tests, and rollback requirements.

## Files inspected

Rules, skills, reports, and docs:

- `AGENTS.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `docs/database/README.md`
- `docs/modules/README.md`

Factories and seeders:

- `database/factories/ProductFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/DestinationFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/TravelSeeder.php`
- `database/seeders/HomePageSectionSeeder.php`
- `database/seeders/FaqSeeder.php`

Migrations:

- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `database/migrations/2026_05_24_055929_drop_price_from_coloumn_from_products_table.php`
- `database/migrations/2026_05_24_111108_change_status_coloum_in_products_table.php`
- `database/migrations/2026_05_31_000001_add_deleted_at_to_categories_and_destinations_table.php`
- `database/migrations/2026_06_03_000005_create_site_assets_table.php`
- `database/migrations/2026_06_04_000001_create_site_settings_table.php`

Models:

- `app/Models/Product.php`
- `app/Models/ProductPrice.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/SiteSetting.php`
- `app/Models/SiteAsset.php`

Controllers, services, provider, and requests:

- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/ProductPriceService.php`
- `app/Services/ProductService.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`

Tests:

- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php`
- `tests/Feature/Security/SuperAdminUserManagementTest.php`
- `tests/TestCase.php`

Scans:

- `rg` for product factory, product prices, status, price inputs, site settings/assets, cache usage, force delete, cascade, soft delete, and tests.
- `git status --short`

## Files recommended for DB-02 changes

Recommended low-risk DB-02 file changes:

- `database/factories/ProductFactory.php`
- `tests/Feature/Database/ProductFactoryStatusTest.php` or similar new focused test file.
- `ai/reports/database/db-02-product-factory-status-fix-report.md`

Optional DB-02 documentation updates:

- `docs/database/README.md`
- `docs/modules/README.md`
- `docs/changelog/CHANGELOG.md`

Recommended DB-03 files after duplicate pre-check:

- New migration for `product_prices(product_id, currency)` unique constraint.
- `tests/Feature/Database/ProductPriceIntegrityTest.php`
- `app/Services/ProductPriceService.php` only if tests expose a runtime bug.
- `ai/reports/database/db-03-product-price-uniqueness-report.md`

Recommended later performance/cache files:

- `app/Providers/AppServiceProvider.php`
- New settings service/repository.
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- Tests for settings cache invalidation.

## Testing Plan

DB-02 ProductFactory tests:

- Factory creates a product with `status = 'published'` or another approved string default.
- Factory status is always one of `draft` or `published`.
- `Product::published()` includes factory default products if default is `published`.
- Existing frontend tests remain green.

Product visibility tests:

- Published products appear on product index.
- Draft products do not appear on product index.
- Draft product detail returns 404.

DB-03 Product price integrity tests:

- `ProductPriceService::sync()` creates IDR and SGD rows for a product.
- Re-running `sync()` updates existing IDR/SGD rows and does not create duplicates.
- Database rejects duplicate `(product_id, currency)` after the unique migration.
- Product detail renders the expected IDR price.
- Structured data chooses the expected price and currency.

Product listing query tests:

- Price min filter returns only products with IDR price >= min.
- Price max filter returns only products with IDR price <= max.
- `price_low` sorts by IDR price ascending.
- `price_high` sorts by IDR price descending.
- Category and destination filters still work with published products.

Admin product save/update tests:

- Creating a product saves exactly one IDR and one SGD price.
- Updating a product changes prices without inserting duplicate rows.
- Invalid price input is rejected by existing Form Request validation.

Migration safety checks:

- Run duplicate pre-check before the unique migration.
- Run `php artisan migrate` on local/test database.
- Run `php artisan test`.
- Run `git diff --check`.

Soft delete/cascade tests for later:

- Category with products cannot be force-deleted through admin.
- Destination with products cannot be force-deleted through admin.
- Soft-deleted Category/Destination still allow product detail/admin edit to render through `withTrashed()`.

Global settings cache tests for later:

- Settings render from cached grouped settings.
- Updating global settings invalidates cache.
- Updating/removing site assets invalidates cache.
- Frontend layout still receives expected settings/assets.

## Risk Level

Overall risk for DB-01 planning: Low.

No runtime file or database object was changed.

Implementation risk by future step:

- ProductFactory status fix: Low.
- ProductFactory state helpers: Low.
- TravelSeeder repeatability redesign: Medium.
- Product price unique migration: Medium, because it depends on duplicate data pre-check.
- Product price index migration: Low to Medium, depending on database size and migration timing.
- Broader product filter indexes: Medium, because over-indexing can add write overhead.
- Global settings cache: Medium, because stale cache can affect public/admin display if invalidation is incomplete.
- Category/Destination FK behavior change: High, because it changes data deletion semantics and requires careful migration planning.

## Rollback Plan

Planning step rollback:

- Remove `ai/reports/database/db-01-product-factory-seeder-price-integrity-plan.md` if this planning record should be discarded.
- No runtime rollback is required.

Future DB-02 rollback:

- Revert ProductFactory status/default state changes.
- Remove the new focused factory tests if the plan is abandoned.

Future DB-03 rollback:

- Drop the new unique/index constraints using the migration `down()` method.
- Restore duplicate rows only from a database backup if any cleanup was performed before migration.

Future cache rollback:

- Revert the settings service/composer changes.
- Clear cache with `php artisan cache:clear`.
- Restore direct query composer behavior if needed.

Future FK rollback:

- Requires dedicated rollback migration.
- Take a database backup before changing FK behavior.
- Verify product/category/destination references before and after rollback.

## Recommended next step

Proceed to STEP DB-02: ProductFactory Status Fix & Focused Tests.

Recommended DB-02 scope:

1. Change only `ProductFactory` status from boolean `true` to approved string default, preferably `published`.
2. Add `published()` and `draft()` factory states if approved.
3. Add focused tests proving factory status and published visibility.
4. Run `php artisan test`.
5. Run `git diff --check`.
6. Update a DB-02 report.

Do not add the product price unique migration in DB-02 unless explicitly approved after the duplicate pre-check.
