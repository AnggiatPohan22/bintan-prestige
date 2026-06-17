# STEP DB-03 - Product Price Duplicate Pre-check & Unique Index Plan

Date: 2026-06-13
Status: Completed as planning/pre-check only.

## Executive Summary

DB-03 completed a read-only integrity pre-check for `product_prices` before adding any unique constraint.

Result:

- No duplicate `(product_id, currency)` groups were found.
- Currency values are currently limited to `IDR` and `SGD`.
- No `product_id`, `currency`, or `price` null values were found.
- No orphan `product_id` values were found.
- No negative or zero price values were found.
- Admin store/update flow already uses `ProductPriceService::sync()` with `updateOrCreate()`, so repeated product edits should update existing IDR/SGD rows instead of inserting duplicates through the normal admin path.

Conclusion:

- The current data is ready for a DB-04 migration that adds a unique constraint on `product_prices(product_id, currency)`.
- No data cleanup is required before DB-04 based on the inspected database state.
- DB-04 should still include a migration safety check/test plan and focused ProductPrice integrity tests.

No code, database data, database schema, migration, route, controller, model, view, config, public asset, package, or test file was changed in DB-03. This report is the only created file.

## Current product_prices Structure

Migration inspected:

- `database/migrations/2026_05_21_142810_create_product_prices_table.php`

Current schema:

- `id`
- `product_id`
- `currency` string length 3
- `price` decimal(12, 2)
- `created_at`
- `updated_at`

Current constraint:

- `product_id` is a foreign key constrained to `products.id`.
- Product price rows cascade when a product is deleted.

Current gap:

- There is no database-level unique constraint on `(product_id, currency)`.

Current application intent:

- One product should have at most one price row per currency.
- The current admin flow only manages two currencies:
  - `IDR`
  - `SGD`

## Duplicate Pre-check Result

Read-only duplicate check performed:

```sql
SELECT product_id, currency, COUNT(*) AS row_count
FROM product_prices
GROUP BY product_id, currency
HAVING COUNT(*) > 1;
```

Result:

- Duplicate group count: `0`
- Impacted `product_id`: none
- Impacted `currency`: none
- Rows per duplicate group: none

Actual pre-check output:

```json
{
    "duplicates": [],
    "counts": {
        "total_rows": 64,
        "null_product_id": 0,
        "null_currency": 0,
        "null_price": 0,
        "negative_price": 0,
        "zero_price": 0,
        "orphan_product_id": 0
    }
}
```

Note:

- `php artisan tinker --execute` could not be used because PsySH attempted to write to a user directory that is unavailable in this environment.
- The final pre-check was run through Laravel bootstrap with PHP and only executed read-only aggregate/select queries.

## Currency Data Quality

Read-only currency distribution:

```json
[
    {
        "currency": "IDR",
        "row_count": 32,
        "min_price": "216432.00",
        "max_price": "2901185.00"
    },
    {
        "currency": "SGD",
        "row_count": 32,
        "min_price": "32.00",
        "max_price": "293.00"
    }
]
```

Findings:

- Only `IDR` and `SGD` exist in `product_prices`.
- Invalid currency count: `0`
- `currency` null count: `0`
- Current data matches the existing admin/product service behavior.

Planning note:

- DB-04 does not need currency cleanup before adding unique `(product_id, currency)`.
- Currency allowlist validation should remain explicit in backend/service design, even though the current admin flow hardcodes `IDR` and `SGD` in `ProductPriceService`.

## Amount Data Quality

Read-only amount checks:

- Total rows: `64`
- Null price rows: `0`
- Negative price rows: `0`
- Zero price rows: `0`
- IDR min: `216432.00`
- IDR max: `2901185.00`
- SGD min: `32.00`
- SGD max: `293.00`

Findings:

- Current amounts are positive numeric values.
- Existing `price` column type `decimal(12,2)` is appropriate for the current IDR/SGD values.
- Store/update Form Requests currently use `numeric` for `idr_price` and `sgd_price`, but do not yet include an explicit `min:0` or `min:1` rule.

Recommended validation rule for DB-04 or a nearby validation step:

- `idr_price`: `required`, `numeric`, `min:0`
- `sgd_price`: `required`, `numeric`, `min:0`

Business note:

- If zero-priced products are not valid for Bintan Prestige CMS, use `min:1` instead of `min:0`.

## Admin Store/Update Price Flow

Files inspected:

- `app/Http/Controllers/Admin/ProductController.php`
- `app/Services/ProductService.php`
- `app/Services/ProductPriceService.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`

Current flow:

- Admin `store()` uses `StoreProductRequest`.
- Admin `update()` uses `UpdateProductRequest`.
- `ProductService::store()` creates the Product and then calls `ProductPriceService::sync()`.
- `ProductService::update()` updates the Product and then calls `ProductPriceService::sync()`.
- `ProductPriceService::sync()` uses `updateOrCreate()` for:
  - `product_id`
  - `currency = IDR`
  - `currency = SGD`

Finding:

- Normal admin create/update flow is duplicate-resistant at application level.
- Repeated product edits should update existing IDR/SGD rows instead of adding duplicates.

Remaining gap:

- Without a database unique constraint, duplicates can still be created through:
  - manual database insert
  - future import scripts
  - direct `ProductPrice::create()`
  - future code paths outside `ProductPriceService::sync()`
  - concurrent writes if two requests create the same missing currency at the same time

Recommended DB-04 backend rule:

- Preserve `updateOrCreate()` in `ProductPriceService`.
- Add database-level unique constraint as final enforcement.
- Add tests proving repeated service sync does not duplicate rows.

## Frontend Price Read Flow

Files inspected:

- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Models/Product.php`
- `app/Support/StructuredDataBuilder.php`

Current frontend listing flow:

- Product index starts with `Product::query()->published()->frontendReady()`.
- Price min/max filters use `whereHas('prices')` with `currency = IDR`.
- Price sorting uses `withMin()` on `prices` where `currency = IDR`.
- Price range reads min/max from `ProductPrice` where `currency = IDR`.

Current product detail flow:

- Product detail aborts if status is not `published`.
- Product detail eager loads `prices`.
- Product price accessors read from the loaded `prices` collection:
  - `idr_price`
  - `sgd_price`

Structured data flow:

- `StructuredDataBuilder` chooses `idr_price` first, then falls back to `sgd_price`.
- If duplicate prices existed, the collection accessor could pick the first loaded row while listing sort/filter might use min price.

Finding:

- Frontend flow is safe when each product has one row per currency.
- Unique `(product_id, currency)` will make frontend display, filtering, sorting, and JSON-LD price selection more deterministic.

## Seeder/Factory Risk

Files inspected:

- `database/seeders/TravelSeeder.php`
- `database/factories/ProductFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/DestinationFactory.php`

Current seeder behavior:

- `TravelSeeder` creates one IDR and one SGD price row for each created product.
- Because each product is newly created during the seeder run, the seeder does not create duplicate currency rows for the same product in its current flow.

Current factory behavior:

- `ProductFactory` does not create `ProductPrice` rows.
- DB-02 changed ProductFactory default status to `published` and added `published()`/`draft()` states.

Seeder risk:

- `TravelSeeder` remains local/demo oriented and destructive because it deletes Categories and Destinations before inserting demo content.
- Product price duplication is not the main seeder risk; the bigger seeder risk is destructive parent data behavior noted in DB-01/IMPROVE-04.

Recommended DB-04 note:

- Do not edit `TravelSeeder` for unique price enforcement unless a test failure proves it is needed.
- Unique index should be compatible with current TravelSeeder price creation.

## Test Coverage Gap

Existing coverage found:

- `tests/Feature/Frontend/ProductDetailBookingFormTest.php` creates one IDR `ProductPrice` and verifies product detail booking UI renders pricing context.
- `tests/Feature/Database/ProductFactoryStatusTest.php` covers ProductFactory status after DB-02.
- Several frontend/admin/global tests create published products, but they do not assert ProductPrice uniqueness.

Current gaps:

- No dedicated test proves `ProductPriceService::sync()` creates one IDR and one SGD row.
- No dedicated test proves repeated product update/sync does not duplicate price rows.
- No test currently proves database-level duplicate `(product_id, currency)` is rejected.
- No test validates IDR/SGD price sorting or filtering correctness.
- No test validates invalid currency rejection because there is no public/admin form field for arbitrary price currency.
- No test validates `idr_price`/`sgd_price` minimum amount rule.

Recommended DB-04 tests:

- `ProductPriceService::sync()` creates IDR and SGD rows.
- Re-running `ProductPriceService::sync()` updates prices and keeps row count at two.
- Database rejects duplicate `(product_id, currency)` after unique migration.
- Product update flow does not duplicate prices.
- IDR and SGD can both exist for the same product.
- Invalid negative `idr_price`/`sgd_price` is rejected if validation min rule is added.

## Unique Index Readiness

Current data readiness: Ready.

Evidence:

- Duplicate `(product_id, currency)` groups: `0`
- Invalid currencies: `0`
- Null `product_id`: `0`
- Null `currency`: `0`
- Null `price`: `0`
- Negative price: `0`
- Orphan `product_id`: `0`

Recommended DB-04 unique index:

- Add a new migration.
- Do not edit old migrations.
- Constraint:
  - `unique(['product_id', 'currency'])`
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

Optional safety guard:

- DB-04 can include a pre-migration duplicate guard that throws a clear exception if duplicates exist.
- This guard is helpful for shared/staging/production databases where data may differ from local.

## Recommended DB-04 Implementation Plan

Recommended DB-04 scope:

1. Create a new migration for unique `(product_id, currency)`.
2. Do not modify the original `create_product_prices_table` migration.
3. Add ProductPrice integrity tests.
4. Optionally add `min:0` to `idr_price` and `sgd_price` validation rules if approved as part of DB-04.
5. Run `php artisan migrate`.
6. Run focused tests.
7. Run `php artisan test`.
8. Run `git diff --check`.
9. Create DB-04 report.

Recommended DB-04 test file:

- `tests/Feature/Database/ProductPriceIntegrityTest.php`

Recommended DB-04 test cases:

- `test_product_price_service_creates_idr_and_sgd_prices`
- `test_product_price_service_updates_existing_prices_without_duplicates`
- `test_database_rejects_duplicate_product_currency_price`
- `test_product_can_have_idr_and_sgd_prices`
- `test_product_price_amount_validation_rejects_negative_values` if validation is included

Recommended validation/backend rule:

- One product may have only one price per currency.
- Allowed currencies remain `IDR` and `SGD` for now.
- Amount must be numeric and not negative.
- Keep arbitrary currency creation unavailable from admin UI unless a future multi-currency feature is explicitly planned.

Recommended model/service note:

- Keep `ProductPriceService::sync()` as the canonical write path for admin product prices.
- Avoid direct `ProductPrice::create()` in runtime product update flows.
- If future importers are added, they should also use upsert/updateOrCreate semantics.

## Files inspected

Reports and skills:

- `ai/reports/database/db-01-product-factory-seeder-price-integrity-plan.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `ai/reports/database/db-02-product-factory-status-fix-report.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/testing-qa-skill.md`

Migrations:

- `database/migrations/2026_05_21_142810_create_product_prices_table.php`

Models:

- `app/Models/ProductPrice.php`
- `app/Models/Product.php`

Services, requests, controllers, and support:

- `app/Services/ProductPriceService.php`
- `app/Services/ProductService.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Support/StructuredDataBuilder.php`

Seeders and factories:

- `database/seeders/TravelSeeder.php`
- `database/factories/ProductFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/DestinationFactory.php`

Tests:

- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Database/ProductFactoryStatusTest.php`
- `rg` scan for `ProductPrice`, `idr_price`, `sgd_price`, `price_low`, `price_high`, and `currency`

Read-only database pre-check:

- Duplicate `(product_id, currency)` aggregate
- Currency distribution aggregate
- Invalid currency aggregate
- Null field counts
- Negative/zero amount counts
- Orphan product foreign key count

## Files recommended for DB-04 changes

Recommended:

- New migration:
  - `database/migrations/xxxx_xx_xx_xxxxxx_add_unique_product_currency_to_product_prices_table.php`
- New test file:
  - `tests/Feature/Database/ProductPriceIntegrityTest.php`
- New report:
  - `ai/reports/database/db-04-product-price-unique-index-implementation-report.md`

Optional if validation hardening is approved in DB-04:

- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`

Probably unchanged:

- `app/Services/ProductPriceService.php`, unless tests expose an issue.
- `app/Http/Controllers/Admin/ProductController.php`, because it already delegates to `ProductService`.
- `database/seeders/TravelSeeder.php`, because current seeder creates one IDR and one SGD price per new product.

## Risk Level

DB-03 risk: Low.

Reason:

- Only read-only inspection and aggregate queries were performed.
- No data/schema/code changes were made.

DB-04 implementation risk: Medium.

Reason:

- Adding a unique index is a schema change.
- Current data is ready, but other environments could still have duplicates.
- Migration can fail if duplicate data exists outside the inspected database.

Risk reducer:

- Run the duplicate pre-check again in the exact environment before migration.
- Add migration guard or stop manually if duplicates exist.
- Backup database before applying schema changes outside local/dev.

## Rollback Plan

DB-03 rollback:

- Remove `ai/reports/database/db-03-product-price-duplicate-precheck-unique-index-plan.md` if this planning record should be discarded.
- No runtime/database rollback is required.

DB-04 rollback plan:

- Use the migration `down()` method to drop `product_prices_product_id_currency_unique`.
- If validation rules are added, revert `StoreProductRequest` and `UpdateProductRequest` validation changes.
- Remove or adjust tests that depend on the unique constraint if DB-04 is rolled back.
- No data restoration should be needed if DB-04 only adds a unique index and does not delete/modify rows.

## Recommended next step

Proceed to STEP DB-04: Product Price Unique Index Implementation.

Safe DB-04 sequence:

1. Re-run duplicate pre-check immediately before migration.
2. If duplicate groups remain `0`, create the unique index migration.
3. Add focused ProductPrice integrity tests.
4. Consider adding non-negative price validation if approved.
5. Run `php artisan migrate`.
6. Run `php artisan test`.
7. Run `git diff --check`.

Do not add broader product filter indexes in DB-04 unless explicitly approved.
