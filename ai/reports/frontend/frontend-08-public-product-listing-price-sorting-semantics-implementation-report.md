# FRONTEND-08 Public Product Listing Price Sorting Semantics Implementation Report

Date: 2026-06-13

## 1. Executive Summary

FRONTEND-08 implemented focused public Product Listing price and sorting semantics on top of the FRONTEND-07 visibility/query baseline.

The public listing now keeps multi-currency display while making price filtering and price sorting explicitly IDR-based. Missing IDR prices are not treated as zero and are placed after IDR-priced Products in both ascending and descending price sorts. Deprecated duration sort inputs now fall back safely to newest because `duration` remains human-readable text.

No schema, migration, route, admin Product CRUD, Product Detail, currency conversion, exchange-rate integration, page builder, package, or layout redesign change was made.

## 2. FRONTEND-06 Decisions Applied

- Keep display multi-currency.
- Keep near-term price filtering/sorting IDR-only.
- Do not add a public currency selector.
- Do not compare IDR and SGD values directly.
- Do not convert currencies.
- Place missing selected-currency prices after priced Products during price sorting.
- Disable duration sorting until normalized duration data exists.

## 3. FRONTEND-07 Baseline Confirmed

`frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md` exists and records:

- `php artisan test --filter=ProductIndexUiTest`: passed, 7 tests, 59 assertions.
- `php artisan test`: passed, 169 tests, 902 assertions.

Current code uses `Product::publiclyVisible()` and `Product::frontendListingReady()` for Product Listing.

## 4. Previous Price Behavior

Before FRONTEND-08:

- Product cards could render IDR, SGD, or `Price on request`.
- `min_price` and `max_price` already checked IDR rows.
- `price_low` and `price_high` sorted by an IDR aggregate.
- Missing IDR prices were not explicitly ordered last across databases.
- Duration sort options were exposed and used `CAST(duration AS UNSIGNED)`.

## 5. Product Price Data Model

Product prices are stored in `product_prices`:

- `product_id`
- `currency`
- `price`

Supported currencies:

- `IDR`
- `SGD`

Guardrail:

- Unique index `product_prices_product_id_currency_unique` enforces one row per Product per currency.

## 6. Implemented Currency Context

The public Product Listing uses an explicit fixed price context:

```text
IDR
```

No new currency selector or conversion behavior was introduced.

## 7. Currency Normalization

Because FRONTEND-06 selected near-term IDR-only semantics, the listing ignores unsupported `currency` query parameters and keeps `priceCurrency = IDR`.

## 8. IDR Display Semantics

If an IDR row exists, the listing card renders IDR as the primary price, for example:

```text
Rp 720.000
```

## 9. SGD Display Semantics

If an SGD row exists and IDR is missing, the listing card renders SGD as the primary price.

If both IDR and SGD exist, the listing card renders IDR first and SGD as secondary.

## 10. Multi-currency Product Behavior

Products with both IDR and SGD:

- Display both prices.
- Match IDR filters using only IDR amount.
- Sort by IDR amount for price sort.
- Do not compare or convert SGD.

## 11. IDR-only Product Behavior

IDR-only Products:

- Display IDR.
- Match IDR filters when amount is in range.
- Sort by IDR amount.
- Do not render `SGD 0`.

## 12. SGD-only Product Behavior

SGD-only Products:

- Display SGD.
- Remain eligible for the default listing.
- Do not match IDR range filters.
- Are treated as missing IDR for price sort and placed after IDR-priced Products.
- Do not render `Rp 0`.

## 13. Missing-price Behavior

Products with no price rows:

- Remain eligible for the default listing.
- Display `Price on request`.
- Do not match IDR range filters.
- Are treated as missing IDR for price sort and placed after IDR-priced Products.

## 14. Price Filter Semantics

`min_price` and `max_price` use IDR rows only.

Invalid, negative, array-shaped, or reversed range input is safe and cannot widen the result set.

## 15. Price Sorting Semantics

Supported public listing price sort values:

- `price_low`: IDR ascending.
- `price_high`: IDR descending.

Deprecated duration sort values and invalid sort values normalize to `newest`.

## 16. Missing-price Ordering

Price sorting uses:

```text
CASE WHEN idr_price_sort IS NULL THEN 1 ELSE 0 END
```

This keeps Products without an IDR price after Products with an IDR price for both ascending and descending price sorts.

## 17. Stable Secondary Sorting

Price sorting includes deterministic secondary order:

- `products.created_at` descending.
- `products.id` descending.

Default newest sorting also uses those qualified columns.

## 18. Duration Sorting Decision

Duration remains display/filter text only.

Removed from public sort options:

- `duration_short`
- `duration_long`

Old URLs using those values fall back safely to `newest`.

## 19. Deprecated/Invalid Sort Handling

`sort` is allowlisted by backend keys only.

Array-shaped, unsupported, SQL-like, or deprecated sort input returns to `newest` and is not used in raw SQL.

## 20. Query Architecture Changes

Changed `Frontend\ProductController@index` to:

- Define fixed `$priceCurrency = ProductPrice::CURRENCY_IDR`.
- Use IDR constants instead of raw currency strings in price filters/range.
- Remove duration sort options from the allowlist.
- Add explicit missing-price-last ordering for IDR price sorts.
- Keep FRONTEND-07 public visibility and normalized filter behavior.

## 21. Backend-to-Blade Contract

The listing view now receives:

- `priceCurrency`
- normalized `sort`
- normalized `minPrice`
- normalized `maxPrice`
- normalized selected filters
- sanitized pagination query parameters

Blade renders the prepared state and does not query prices.

## 22. Product Card Impact

The shared Product card price component did not need structural changes.

Existing safe behavior remains:

- IDR primary when present.
- SGD fallback when IDR is missing.
- `Price on request` when both are missing.
- Strict null checks prevent missing price from becoming zero.

## 23. Pagination Persistence

Pagination uses normalized query parameters.

Preserved:

- `sort`
- `min_price`
- `max_price`
- `duration`
- `destination`
- `category`
- `vehicle_type`

Unknown unsafe parameters and unsupported `currency` inputs are not intentionally preserved.

## 24. Tests Added or Updated

Updated `tests/Feature/Frontend/ProductIndexUiTest.php` with focused coverage for:

- IDR-only display.
- Missing-price fallback.
- No `Rp 0` or `SGD 0` fallback for missing prices.
- IDR-only price range filtering.
- SGD-only and no-price exclusion from IDR range filters.
- Draft and inactive-parent Products staying hidden under price filters.
- Reversed price range safety.
- IDR ascending sort.
- IDR descending sort.
- Missing IDR prices after priced Products.
- Unsupported currency input keeping IDR context.
- Deprecated duration sort fallback.
- Array-shaped/invalid sort fallback.
- Product duplication prevention in sorted paginator output.

## 25. Focused Test Result

```bash
php artisan test --filter=ProductIndexUiTest
```

Result: Passed, 14 tests, 122 assertions.

## 26. Full Test Result

```bash
php artisan test
```

Result: Passed, 176 tests, 965 assertions.

## 27. Frontend Build Result

Initial command:

```bash
npm run build
```

Result: Failed because PowerShell blocked `npm.ps1` by execution policy.

Windows-safe retry:

```bash
npm.cmd run build
```

Result: Passed. Vite built successfully. No tracked build artifact changes were reported by `git status --short`.

## 28. Files Changed

FRONTEND-08 changed:

- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/frontend/products/index.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- `ai/reports/frontend/frontend-08-public-product-listing-price-sorting-semantics-implementation-report.md`

Pre-existing FRONTEND-07 worktree changes remain present and are not accidental FRONTEND-08 edits:

- `app/Models/Product.php`
- `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md`
- `ai/reports/frontend/frontend-06-public-product-listing-data-flow-fix-plan.md`
- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`

## 29. Deferred Items

- Public currency selector.
- SGD price filtering/sorting.
- Currency conversion.
- Exchange-rate integration.
- Price range performance index `product_prices(currency, price)`.
- Normalized duration field or taxonomy.
- Product Detail price/visibility policy changes.
- Product card/grid redesign.
- Page Sections listing content sync.
- SEO implementation.

## 30. Risks

- IDR-only filter/sort labels may still need business copy review.
- SGD-only Products remain visible by default but are missing in IDR price operations.
- Duration sort removal changes visible sort choices.
- Price range performance index remains deferred.

## 31. Rollback Procedure

Revert the FRONTEND-08 files listed above.

No database rollback is required because no schema, migration, or data changes were made.

## 32. Verification Result

- Focused Product Listing tests: passed.
- Full Laravel test suite: passed.
- Frontend build: passed via `npm.cmd run build`; plain `npm run build` was blocked by PowerShell policy.
- Pre-report `git diff --check`: passed.

Final post-report verification commands:

```bash
git diff --check
git status --short
```

## 33. Definition of Done

Done:

- IDR and SGD render consistently.
- Missing price does not become zero.
- Price filter uses explicit IDR context.
- Price sorting uses explicit IDR context.
- IDR and SGD are not compared directly.
- Missing IDR price is not treated as zero.
- Missing IDR prices sort after IDR-priced Products.
- Invalid sorting is safe.
- Deprecated duration sorting is not used.
- Pagination preserves normalized valid query parameters.
- FRONTEND-07 public visibility remains active.
- Focused tests passed.
- Full tests passed.
- Build passed.
- Implementation report created.

## 34. Recommended Next Step

Proceed to:

```text
FRONTEND-09: Public Product Listing CMS Content Sync
```

## Decision Tables

### Price State

| Product price state | Public display | Filter behavior | Sort behavior |
| --- | --- | --- | --- |
| IDR and SGD | IDR primary, SGD secondary | Eligible by IDR amount | Sorts by IDR amount |
| IDR only | IDR only | Eligible by IDR amount | Sorts by IDR amount |
| SGD only | SGD only | Not eligible for IDR range | Missing IDR, after IDR-priced Products |
| No price | `Price on request` | Not eligible for IDR range | Missing IDR, after IDR-priced Products |

### Currency Parameter

| Input | Normalized value | Behavior |
| --- | --- | --- |
| Missing | IDR | Default fixed context |
| `IDR` | IDR | Same fixed context |
| `idr` | IDR | Currency selector is not active; IDR remains fixed context |
| `SGD` | IDR | Unsupported for FRONTEND-08 filter/sort context |
| Invalid string | IDR | Ignored safely |
| Array-shaped input | IDR | Ignored safely |

### Sort Parameter

| Sort input | Behavior | Missing-price placement |
| --- | --- | --- |
| `newest` | Newest first | Normal default listing order |
| `price_low` | IDR ascending | Missing IDR last |
| `price_high` | IDR descending | Missing IDR last |
| `duration_short` | Fallback to newest | Normal default listing order |
| `duration_long` | Fallback to newest | Normal default listing order |
| Invalid/array-shaped input | Fallback to newest | Normal default listing order |

### Duration

| Input | Previous risk | Implemented behavior |
| --- | --- | --- |
| `2 Hours` | Numeric cast can work accidentally but is not a real duration model | Display/filter only |
| `Full Day` | Numeric cast is misleading | Display/filter only |
| `2 Days` | Numeric cast ignores units | Display/filter only |
| `Flexible` | Numeric cast is meaningless | Display/filter only |
| `duration_short` sort | Lexical/numeric string sort risk | Fallback to newest |
| `duration_long` sort | Lexical/numeric string sort risk | Fallback to newest |
