# STEP DB-04 - Product Price Unique Index & Safe Update Flow Implementation

Date: 2026-06-13
Status: Completed.

## Summary

Implemented product price integrity for `product_prices` with a small, focused scope.

Completed changes:

- Added a new migration for unique `product_prices(product_id, currency)`.
- Added a migration duplicate guard so the unique index is not applied while duplicate data exists.
- Added ProductPrice currency constants and supported currency helper.
- Updated ProductPriceService to write prices through a currency-aware `syncCurrency()` method.
- Preserved safe `updateOrCreate()` behavior using `product_id` and `currency`.
- Added non-negative validation for `idr_price` and `sgd_price`.
- Added focused ProductPrice integrity tests.
- Added database/module docs for product price integrity.
- Updated changelog.

Not changed:

- No old migration was modified.
- No existing product price row was deleted or changed manually.
- No frontend layout was changed.
- No global settings cache work was done.
- No additional product filter indexes were added.
- No soft-delete/cascade policy was changed.

## Files created

- `database/migrations/2026_06_13_000001_add_unique_product_currency_to_product_prices_table.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`
- `docs/database/schema-overview.md`
- `docs/database/relationships.md`
- `docs/modules/products.md`
- `ai/reports/database/db-04-product-price-unique-index-implementation-report.md`

## Files changed

- `app/Models/ProductPrice.php`
- `app/Services/ProductPriceService.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `docs/changelog/CHANGELOG.md`

## Migration details

New migration:

- `2026_06_13_000001_add_unique_product_currency_to_product_prices_table`

Unique index:

- Name: `product_prices_product_id_currency_unique`
- Columns:
  - `product_id`
  - `currency`

Migration behavior:

- Checks for duplicate `(product_id, currency)` rows before adding the unique index.
- Throws a clear exception if duplicates exist.
- Adds the unique index only when duplicate data is clean.

Rollback behavior:

- Drops `product_prices_product_id_currency_unique`.
- Does not delete product price rows.

Applied status:

- `php artisan migrate` applied the migration successfully.
- `php artisan migrate:status` shows the migration as `Ran` in batch `13`.

## Database impact

Database schema changed:

- Added a unique index to `product_prices(product_id, currency)`.

Data impact:

- No existing rows were deleted.
- No existing rows were updated manually.
- DB-03 duplicate pre-check showed zero duplicate groups.
- DB-04 re-ran the duplicate pre-check immediately before migration and confirmed zero duplicate groups.

Integrity impact:

- A Product can have one `IDR` price.
- A Product can have one `SGD` price.
- A Product can have both `IDR` and `SGD` prices together.
- A Product can no longer have duplicate rows for the same currency.

## Validation impact

Updated Form Request validation:

- `StoreProductRequest`
  - `idr_price`: `required`, `numeric`, `min:0`
  - `sgd_price`: `required`, `numeric`, `min:0`
- `UpdateProductRequest`
  - `idr_price`: `required`, `numeric`, `min:0`
  - `sgd_price`: `required`, `numeric`, `min:0`

Service-level guard:

- `ProductPriceService::syncCurrency()` rejects unsupported currencies.
- Supported currencies are defined in `ProductPrice::SUPPORTED_CURRENCIES`:
  - `IDR`
  - `SGD`
- Service also rejects null, empty, non-numeric, or negative price values.

## Product create/update impact

Admin product create/update still flows through:

- `Admin\ProductController`
- `ProductService`
- `ProductPriceService`

Safe update behavior:

- `ProductPriceService::sync()` calls `syncCurrency()` for IDR and SGD.
- `syncCurrency()` uses `updateOrCreate()` with:
  - `product_id`
  - `currency`
- Editing a product repeatedly updates existing IDR/SGD rows instead of creating duplicates.
- The service only touches the currencies it is asked to sync and does not delete unrelated currency rows.

Product model impact:

- `Product` relationships were not changed.
- `ProductPrice` fillable was not expanded.
- No business logic was moved into Blade.

## Test result

Focused test:

```text
php artisan test tests/Feature/Database/ProductPriceIntegrityTest.php
Result: PASSED
Tests: 8 passed
Assertions: 14
```

Full test suite:

```text
php artisan test
Result: PASSED
Tests: 128 passed
Assertions: 580
```

Migration verification:

```text
php artisan migrate
Result: PASSED
Migration applied: 2026_06_13_000001_add_unique_product_currency_to_product_prices_table
```

```text
php artisan migrate:status
Result: PASSED
Migration status: Ran
Batch: 13
```

Diff verification:

```text
git diff --check
Result: PASSED
Note: Git reported a line-ending normalization warning for app/Services/ProductPriceService.php, but no whitespace errors were found.
```

## Rollback procedure

Preferred rollback while this migration is the latest migration:

```bash
php artisan migrate:rollback --step=1
```

Path-specific rollback option:

```bash
php artisan migrate:rollback --path=database/migrations/2026_06_13_000001_add_unique_product_currency_to_product_prices_table.php
```

Code rollback:

- Revert `app/Models/ProductPrice.php`.
- Revert `app/Services/ProductPriceService.php`.
- Revert `app/Http/Requests/StoreProductRequest.php`.
- Revert `app/Http/Requests/UpdateProductRequest.php`.
- Remove `tests/Feature/Database/ProductPriceIntegrityTest.php`.
- Revert documentation/changelog updates if the schema rollback is kept.

Data rollback:

- No data restoration is expected because DB-04 did not delete or modify existing product price rows.

## Remaining risks

- Other environments may contain duplicate product price rows even though the inspected database was clean. Re-run the duplicate pre-check before deploying this migration elsewhere.
- There is no broader `product_prices(currency, price)` performance index yet; that remains a future performance/index step.
- Product filter price sorting/filter tests are still limited; DB-04 focused on integrity, not frontend price filter behavior.
- Currency support remains intentionally limited to `IDR` and `SGD`.
- Existing `TravelSeeder` remains local/demo oriented and destructive; this step did not alter seeder behavior.

## Recommended next step

Proceed to STEP DB-05: Product Query/Index Readiness Plan or Product Price Filter Test Coverage, depending on priority.

Recommended safe sequence:

1. Commit DB-04 product price integrity.
2. Run the app manually through admin product create/edit if desired.
3. Plan product price filter/sort tests.
4. Only then consider additional indexes such as `product_prices(currency, price)` and product filter indexes.

Do not combine the next step with global settings cache or soft-delete/cascade policy unless explicitly approved.
