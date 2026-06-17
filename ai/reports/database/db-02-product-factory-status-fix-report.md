# STEP DB-02 - ProductFactory Status Fix & Focused Tests

Date: 2026-06-13
Status: Completed.

## Summary

Fixed the ProductFactory status mismatch identified in IMPROVE-04 and DB-01.

The factory now creates products with a valid string status instead of boolean `true`. The default factory status is `published`, matching the DB-01 recommendation because `TravelSeeder` uses `Product::factory(30)->create()` for demo catalog content that should be visible through the current `Product::published()` scope.

This step stayed inside the approved DB-02 scope:

- No database schema change.
- No new migration.
- No old migration edit.
- No package install.
- No frontend UI change.
- No product runtime logic change outside factory/test mismatch.
- No product price unique index implementation.

## Files changed

- `database/factories/ProductFactory.php`

## Files created

- `tests/Feature/Database/ProductFactoryStatusTest.php`
- `ai/reports/database/db-02-product-factory-status-fix-report.md`

## Factory changes

Updated ProductFactory:

- Changed default `status` from boolean `true` to string `published`.
- Added `published()` factory state.
- Added `draft()` factory state.

Reason:

- Current app status policy uses `draft` and `published`.
- `Product::scopePublished()` filters `status = 'published'`.
- `StoreProductRequest` validates `status` as `in:draft,published`.
- Admin status toggle switches only between `draft` and `published`.

Compatibility:

- Existing tests that explicitly set `status => 'published'` remain compatible.
- New factory default aligns with frontend visibility and demo seeding intent.

## Seeder impact

No seeder files were changed.

Expected impact:

- `TravelSeeder` uses `Product::factory(30)->create()`.
- After this fix, TravelSeeder-created products should use `status = 'published'` by default.
- This improves demo catalog consistency without editing the seeder.

Remaining seeder notes:

- `TravelSeeder` is still destructive because it deletes Categories and Destinations before inserting demo data.
- Product price uniqueness is still pending for a later DB step.
- No DB-02 change was made to `TravelSeeder` because tests did not require it.

## Test changes

Created focused tests in `tests/Feature/Database/ProductFactoryStatusTest.php`:

- Factory creates product with valid default status.
- Factory can create draft product.
- Factory can create published product.
- Draft product is not returned by `Product::published()`.
- Published product is returned by `Product::published()`.

The test creates Category and Destination dependencies directly with model `create()` calls to avoid unrelated CategoryFactory/DestinationFactory slug or faker uniqueness issues.

## Database impact

No database schema impact.

- No migration created.
- No migration modified.
- No table or column changed.
- No index or unique constraint added.
- No database data deleted or modified outside test database refreshes.

## Final php artisan test result

Focused test:

```text
php artisan test tests/Feature/Database/ProductFactoryStatusTest.php
Result: PASSED
Tests: 3 passed
Assertions: 6
```

Full test suite:

```text
php artisan test
Result: PASSED
Tests: 120 passed
Assertions: 566
```

Additional verification:

```text
git diff --check
Result: PASSED
```

## Security impact

Positive low-risk impact:

- Factory-created products now follow the approved status policy.
- No public registration, admin access, middleware, auth logic, or security-sensitive runtime code was changed.
- Product publication behavior remains controlled by existing `published()` scope and admin status controls.

No new security risk identified.

## Rollback note

Rollback for DB-02:

1. Revert `database/factories/ProductFactory.php` to the previous default status if needed.
2. Remove `tests/Feature/Database/ProductFactoryStatusTest.php`.
3. Remove this report if the DB-02 record should be discarded.

No database rollback is required because no schema or persistent data change was made.

## Recommended next step

Proceed to STEP DB-03: Product Price Duplicate Pre-check & Unique Constraint Plan/Implementation.

Recommended DB-03 order:

1. Run duplicate pre-check for `(product_id, currency)`.
2. If duplicates exist, stop and report cleanup plan.
3. If no duplicates exist, add a new migration for unique `(product_id, currency)`.
4. Add ProductPrice integrity tests.
5. Run `php artisan test`.
6. Run `git diff --check`.

Do not combine DB-03 with broad product filter index changes unless explicitly approved.
