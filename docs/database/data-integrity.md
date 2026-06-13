# Database Data Integrity

Last updated: 2026-06-13

This document summarizes the canonical data integrity state after DB-01 through DB-09.

## Improvement Scope

Database improvements were implemented incrementally:

- DB-01 planned ProductFactory, seeder, product price, global settings, and delete integrity work.
- DB-02 fixed ProductFactory status values.
- DB-03 performed a read-only duplicate pre-check for product prices.
- DB-04 added Product Price unique integrity and safe update flow.
- DB-05 planned product query index and global settings performance work.
- DB-06 added Global Settings Service and cache.
- DB-07 added Product status/created_at index.
- DB-08 audited Category/Destination delete integrity.
- DB-09 changed Product parent FK delete behavior from cascade to restrict.

Old migrations were not edited. Runtime/database changes were made through new migrations and scoped code changes in the original implementation steps.

## Product Status Integrity

Product status uses string values:

- `draft`
- `published`

`ProductFactory` now creates valid status values and supports explicit factory states:

- `published()`
- `draft()`

This keeps factory-generated products aligned with `Product::published()` and admin/frontend status rules.

## Product Price Integrity

`product_prices` supports these currencies:

- `IDR`
- `SGD`

Integrity rules:

- One Product may have one `IDR` price.
- One Product may have one `SGD` price.
- A Product may have both `IDR` and `SGD`.
- Duplicate `(product_id, currency)` rows are rejected by the database.
- Product prices are written through `ProductPriceService::sync()`.
- The service writes with `updateOrCreate()` by `product_id` and `currency`.
- Price amounts must be numeric and non-negative through Form Request and service checks.

Database constraint:

- `product_prices_product_id_currency_unique` on `product_prices(product_id, currency)`.

DB-03 pre-check found:

- Duplicate `(product_id, currency)` groups: 0.
- Invalid currency values: 0.
- Null product/currency/price values: 0.
- Negative prices: 0.
- Orphan product references: 0.

## Product Index Integrity

Implemented product query index:

- `products_status_created_at_index` on `products(status, created_at)`.

Purpose:

- Published/draft listing.
- Newest/latest ordering.
- Pagination support for common product listing flows.
- Partial support for dashboard/admin status counts.

Deferred:

- `product_prices(currency, price)` remains deferred.
- Additional product composite indexes remain deferred.

## Category and Destination Delete Integrity

Category and Destination use soft delete as the archive flow.

Rules:

- Soft-deleting a Category does not delete Products.
- Soft-deleting a Destination does not delete Products.
- Permanent parent delete is rejected while Products reference the parent.
- Product parent references remain required and must not become orphaned.
- Controller guards and database constraints now provide defense-in-depth.

Current Product parent FKs:

- `products.category_id` uses `products_category_id_foreign` with `DELETE_RULE = RESTRICT`.
- `products.destination_id` uses `products_destination_id_foreign` with `DELETE_RULE = RESTRICT`.

Product child foreign keys were not changed by DB-09. Product child rows still cascade when the Product itself is intentionally deleted.

## Global Settings Cache Integrity

`App\Services\GlobalSettingsService` is the public read layer for global settings and active site assets.

Cache keys:

- `global_settings.public.v1`
- `global_assets.public.v1`

Cache TTL:

- 30 minutes

Integrity rules:

- Settings and assets cache payloads are primitive arrays.
- Runtime `Collection` and model objects are hydrated after cache reads.
- Invalid or legacy object cache payloads are forgotten and rebuilt.
- `SiteSetting` save/delete clears settings cache.
- `SiteAsset` save/delete clears assets cache.
- Public cache must not include private credentials, API tokens, or secrets.

## Verification References

Actual verification results from implementation reports:

- DB-02 full test: 120 tests, 566 assertions.
- DB-04 full test: 128 tests, 580 assertions.
- DB-06 full test after regression fix: 136 tests, 603 assertions.
- DB-07 full test: 136 tests, 603 assertions.
- DB-09 full test: 145 tests, 667 assertions.
- DB-09 focused delete integrity test: 9 tests, 64 assertions.

Additional DB-09 verification:

- `products.category_id` delete rule verified as `RESTRICT`.
- `products.destination_id` delete rule verified as `RESTRICT`.
- Product orphan check after migration: 0 invalid category references and 0 invalid destination references.

## Remaining Risks

- Product itself still uses hard delete, and product child rows still cascade when Product is deleted.
- Public product visibility for products under archived/inactive parents remains a future policy decision.
- Category/Destination permanent delete does not yet have granular per-module policy/gate enforcement.
- Reassign-before-delete workflow for Category/Destination is not implemented.
- `product_prices(currency, price)` remains deferred until price filter/sort tests exist.
- `TravelSeeder` remains local/demo oriented and destructive.

## Rollback Notes

Rollback procedures are documented in the step reports:

- DB-04: rollback drops `product_prices_product_id_currency_unique`.
- DB-06: rollback reverts service/cache changes and runs `php artisan cache:clear`.
- DB-07: rollback drops `products_status_created_at_index`.
- DB-09: rollback restores cascade delete behavior for Product parent FKs, which reintroduces the DB-08 cascade risk.
