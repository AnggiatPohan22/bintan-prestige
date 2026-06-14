# FRONTEND-07 Public Product Listing Visibility & Query Integrity Implementation Report

Date: 2026-06-13

## Status

Implemented.

FRONTEND-07 applies only to the public Product Listing visibility and query path. Product Detail, Homepage, admin Product CRUD behavior, price semantics, duration parsing semantics, image pipeline work, SEO overhaul, Page Sections content sync, and layout redesign remain out of scope.

## References Inspected

- `AGENTS.md`
- `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md`
- `ai/reports/frontend/frontend-06-public-product-listing-data-flow-fix-plan.md`
- `ai/reports/frontend/frontend-02-mobile-nav-empty-price-product-card-implementation-report.md`
- `ai/reports/frontend/frontend-04d-homepage-renderer-consolidation-tests-report.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/architecture/frontend-backend-sync.md`
- `ai/reports/database/db-08-category-destination-delete-integrity-policy-audit.md`
- `ai/reports/database/db-09-category-destination-fk-restriction-implementation-report.md`
- `docs/qa/README.md`

## Files Changed

- `app/Models/Product.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/frontend/products/index.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`

## Visibility Policy Implemented

`Product::publiclyVisible()` now represents the public Product Listing visibility rule:

- Product status must be `published`.
- Related Category must be active.
- Related Category must not be archived.
- Related Destination must be active.
- Related Destination must not be archived.

This is a query scope, not a global scope. Admin Product queries remain unchanged.

## Query Architecture Changes

`Frontend\ProductController@index` now uses:

- `Product::publiclyVisible()` for public listing product results, filter option sources, and IDR price range bounds.
- `Product::frontendListingReady()` for listing-card eager loading only.
- Normalized query parameters before filters are applied.
- Valid query parameters passed to pagination with `appends()`.

Invalid filter values now narrow to an empty listing result instead of widening the query. Unsupported unsafe query keys are not intentionally preserved in pagination links.

## Eager Loading Changes

The public Product Listing no longer uses the broad `frontendReady()` eager-load scope.

Listing cards now eager load only:

- `category`
- `destination`
- `prices`
- `images`

The following Product Detail relations remain excluded from listing eager loading:

- `features`
- `faqs`
- `itineraries`
- `notes`
- `highlights`

## Blade Query State Changes

The listing Blade view now receives sanitized query state from the controller instead of re-reading raw request values for selected filters and price inputs.

This prevents array-shaped query parameters from rendering as unsafe or noisy input values.

## Tests Added

Focused coverage was added to `tests/Feature/Frontend/ProductIndexUiTest.php` for:

- Published Product with active public parents appears on Product Listing.
- Draft Product is hidden from Product Listing.
- Inactive Category hides related published Products from Product Listing.
- Archived Category hides related published Products from Product Listing.
- Inactive Destination hides related published Products from Product Listing.
- Archived Destination hides related published Products from Product Listing.
- Hidden Products remain present in the database and visible to the admin Product index.
- Invalid/nested query parameters do not throw and do not widen listing results.
- Valid listing filters persist across pagination.
- Unknown unsafe query parameters are not preserved in pagination links.
- Product card detail route remains stable.
- Listing-card eager loading excludes Product Detail-only relations.

## Documentation Updated

- `docs/modules/products.md` now documents the public Product Listing visibility policy and listing-card eager-load scope.
- `docs/architecture/frontend-backend-sync.md` now documents Product Listing visibility/query normalization while keeping Product Detail explicitly separate.

## Deferred Items

Deferred to later frontend steps:

- Price semantics.
- IDR/SGD filter behavior.
- Price sorting redesign.
- Missing-price redesign.
- Duration parsing and sorting semantics.
- Page Sections listing content/title/intro sync.
- Product card/grid/responsive redesign.
- Image pipeline changes.
- Accessibility overhaul.
- SEO/layout metadata work.
- Product Detail parent-visibility policy.

## Verification

Focused test command:

```bash
php artisan test --filter=ProductIndexUiTest
```

Result: Passed, 7 tests, 59 assertions.

Full test command:

```bash
php artisan test
```

Result: Passed, 169 tests, 902 assertions.

Final post-report verification commands to run:

```bash
git diff --check
git status --short
```

## Rollback Note

Revert the changed files listed above to restore the previous public Product Listing behavior. No migration, schema, route, status value, package, or admin query rollback is required.
