# FRONTEND-06 Public Product Listing Data Flow Fix Plan

Date: 2026-06-13
Status: Planning/read-only only. No runtime implementation was performed.
Primary source: `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md`

Expected file change:

- `ai/reports/frontend/frontend-06-public-product-listing-data-flow-fix-plan.md`

Runtime code, database schema, migrations, routes, controllers, models, Blade, CSS, JavaScript, config, and tests were not changed.

## 1. Executive Summary

FRONTEND-06 turns the FRONTEND-05 audit findings into a safe implementation plan for public Product Listing data flow. The recommended direction is incremental:

1. Add a public listing visibility policy: public products should appear only when the Product is `published` and both parent Category and Destination are active and not archived/soft-deleted.
2. Keep admin Product visibility broader than public visibility so products remain manageable when parents are inactive or archived.
3. Keep price display multi-currency, but make the first implementation price filter/sort explicitly IDR-only rather than introducing user-selected currency context too early.
4. Keep missing prices display-safe as `Price on request`; when price sorting is used, missing selected-currency prices should be placed last.
5. Remove or hide duration sort from the public listing until a reliable duration policy exists. Keep duration as a filter only when values are exact known strings.
6. Use existing `products.index.*` Page Section keys for listing copy/media, but keep queries, filters, sorting, pagination, route names, product visibility, and price calculations code-owned.
7. Split implementation into FRONTEND-07, FRONTEND-08, and FRONTEND-09 instead of combining policy, price/sort, and CMS-content work.

No schema change is recommended for the immediate implementation path. A future normalized duration field can be proposed later, but it requires explicit schema approval.

## 2. FRONTEND-05 Findings Used

FRONTEND-05 completed the listing audit with an overall score of 76/100 and no critical issues.

High-priority findings used for this plan:

- Page Sections are registered and keyed for `products.index`, but public listing copy/media is not loaded from Page Sections.
- Published products under inactive or archived parents can still be selected for public listing because the current public query uses `published()` without active parent constraints.
- Price filter and price sort use IDR-only semantics while the card can display IDR, SGD, or missing price.
- Duration sorting casts a free-form string field with `CAST(duration AS UNSIGNED)`, which is unreliable for values like `Half Day` and `Full Day`.

Medium/low findings used:

- `Product::scopeFrontendReady()` over-fetches detail relations for listing cards.
- `product_prices(currency, price)` remains deferred.
- Listing metadata is generic, but SEO implementation is outside this step.
- Existing tests cover price display and section keys, but not filter, sort, visibility policy, or pagination query persistence.

## 3. Confirmed Current Architecture

Targeted verification only was performed. This was not a full-project re-audit.

Current route:

- `routes/frontend.php` maps `GET /products` to `Frontend\ProductController@index` and names it `products.index`.

Current listing controller:

- `app/Http/Controllers/Frontend/ProductController.php@index` reads filter arrays for `duration`, `destination`, `category`, and `vehicle_type`.
- The base query is `Product::query()->published()->frontendReady()`.
- IDR min/max filters use `whereHas('prices')` with `currency = IDR`.
- Sorting supports `price_low`, `price_high`, `duration_short`, `duration_long`, and default `newest`.
- Pagination uses `paginate(8)->withQueryString()`.
- Category and Destination filter options are loaded with `where('is_active', true)`.

Current Product model contract:

- `Product::category()` and `Product::destination()` use `withTrashed()`.
- `Product::prices()` is a `HasMany`.
- `getIdrPriceAttribute()` and `getSgdPriceAttribute()` read the loaded `prices` collection.
- `scopePublished()` filters `status = published`.
- `scopeFrontendReady()` eager-loads category, destination, prices, images, highlights, features, FAQs, itineraries, and notes.

Current listing Blade/component contract:

- `resources/views/frontend/products/index.blade.php` renders `data-page-key="products.index"` and section keys for hero, catalog, filter modal, and sort modal.
- The listing includes `resources/views/frontend/products/partials/card.blade.php`.
- That partial delegates to `resources/views/frontend/components/product-card.blade.php` with `variant => listing`.
- `resources/views/frontend/components/product-price.blade.php` renders IDR first, SGD fallback, or `Price on request`.

Current Page Sections contract:

- `app/Support/PageSectionRegistry.php` already defines `products.index`, `products.index.hero`, `products.index.catalog`, `products.index.filter_modal`, and `products.index.sort_modal`.
- Admin Page Sections sync registered product pages through `PageSectionRegistry::syncRegisteredSections()`.
- Public Product Listing currently does not consume Page Section copy/media.

## 4. Public Product Visibility Policy Options

Option A: Product status only.

- Public query: `published()` only.
- Pros: smallest code change, matches current behavior.
- Cons: inactive/archived Category or Destination can still leak into public listing.
- Verdict: not recommended.

Option B: Product status plus active, non-archived Category and Destination.

- Public query: Product is `published`, Category is active and not soft-deleted, Destination is active and not soft-deleted.
- Pros: matches CMS expectation that inactive/archived parents should not promote public content.
- Cons: changes public visibility for existing published products under inactive/archived parents.
- Verdict: recommended, with tests and clear rollback.

Option C: Product status plus active parent if parent exists, but tolerate missing parent.

- Public query: Product is `published`; parent checks are nullable/tolerant.
- Pros: avoids hiding data with incomplete relationships.
- Cons: project docs say Products require Category and Destination; missing parent should not be treated as public-ready.
- Verdict: not recommended.

Option D: Product status plus active parent filters only when request includes category/destination filters.

- Public query: default listing remains status-only, filters require active parent.
- Pros: smaller behavioral change.
- Cons: inconsistent; same product may appear by default but disappear from filter flows.
- Verdict: not recommended.

## 5. Recommended Visibility Policy

Recommended policy:

Public Product is visible only if:

- Product `status` is `published`.
- Product has a Category relation.
- Category is active.
- Category is not soft-deleted/archived.
- Product has a Destination relation.
- Destination is active.
- Destination is not soft-deleted/archived.

Recommended query location:

- Add a dedicated Product model scope such as `scopePubliclyVisible($query)`.
- Keep `scopePublished()` as the lower-level status-only scope because admin and internal flows may still need it.
- Use the new public scope in public listing and later public detail/home shared product queries after tests.

Scope vs controller trade-off:

- Model scope is preferred because the policy is domain visibility, not one controller's UI concern.
- Controller should select filters, validate/sanitize request state, and call the public scope.
- Avoid hiding this policy in Blade or duplicated controller closures.

Impact on homepage/shared Product query:

- Homepage product cards should eventually use the same public visibility scope to avoid showing products hidden from `/products`.
- Apply first in FRONTEND-07 only where public listing scope is tested; then reuse for homepage only if tests are included or in a follow-up step.

Backward compatibility:

- Existing published products under inactive/archived parents will stop showing publicly.
- Admin Product index should remain able to display and manage those products.
- No Product rows should be deleted.

## 6. Category/Destination Inactive Behavior

Recommended behavior:

- Inactive Category: public products under it are hidden.
- Archived/soft-deleted Category: public products under it are hidden.
- Inactive Destination: public products under it are hidden.
- Archived/soft-deleted Destination: public products under it are hidden.
- Missing Category/Destination relation: public product is hidden and should be treated as data integrity risk.
- Product draft with active parents: hidden publicly.
- Admin Product listing: still visible/manageable, with parent labels available through existing `withTrashed()` relation behavior.

This policy must not delete products or detach relationships.

### Visibility Decision Matrix

| Scenario | Public visible | Admin visible | Recommended behavior |
| -------- | -------------: | ------------: | -------------------- |
| Published Product with active Category and active Destination | Yes | Yes | Show publicly and in admin. |
| Published Product with inactive Category | No | Yes | Hide publicly; keep admin manageable. |
| Published Product with inactive Destination | No | Yes | Hide publicly; keep admin manageable. |
| Published Product with archived Category | No | Yes | Hide publicly; preserve relation for admin/history. |
| Published Product with archived Destination | No | Yes | Hide publicly; preserve relation for admin/history. |
| Published Product without Category | No | Yes, if row exists | Treat as integrity issue; hide publicly. |
| Published Product without Destination | No | Yes, if row exists | Treat as integrity issue; hide publicly. |
| Missing parent relation | No | Yes, if row exists | Hide publicly; investigate data integrity. |
| Draft Product with active parents | No | Yes | Existing draft policy remains. |
| Product under inactive parent after parent is reactivated | Yes, if published and other parent is public-visible | Yes | Automatically returns to public if policy passes. |

## 7. Price Data States

Current supported currencies:

- `IDR`
- `SGD`

Current guardrails:

- `ProductPrice::SUPPORTED_CURRENCIES` allows IDR and SGD.
- `product_prices_product_id_currency_unique` prevents duplicate rows per product/currency.
- `ProductPriceService` updates existing currency rows and rejects invalid/negative prices.

Relevant states:

- Product has IDR and SGD.
- Product has IDR only.
- Product has SGD only.
- Product has no price rows.
- Product has valid zero price, because current service allows non-negative values.

## 8. Price Display Semantics

Recommended display policy:

- IDR + SGD: show IDR as primary, SGD as secondary.
- IDR only: show IDR as primary.
- SGD only: show SGD as primary.
- No price: show `Price on request`.
- Numeric zero: show as a real numeric price if a row exists; do not collapse it into missing price.

Rationale:

- FRONTEND-02 already centralized missing-price display and tests verify no `Rp 0`.
- This keeps cards honest without currency conversion.
- Display can remain multi-currency even if filters/sorts are initially IDR-only.

## 9. Price Filter Semantics

Options compared:

Option A: Keep filter/sort IDR-only but label explicitly.

- Query stays close to current code.
- UI labels must say IDR, for example `Rentang Harga IDR`.
- SGD-only products are not eligible for IDR price filtering.
- Best for near-term implementation.

Option B: User-selected currency context with `currency=IDR` or `currency=SGD`.

- More complete long-term semantics.
- Requires currency query parameter validation, labels, price range lookup per selected currency, sorting per selected currency, and tests for invalid currency.
- More moving parts than needed for first fix.

Option C: Disable price filter/sort until semantics are approved.

- Safest from correctness standpoint.
- Removes useful current UX and may feel like regression.
- Use only if business rejects IDR-only semantics.

Recommendation:

- Implement Option A in FRONTEND-08: keep price filter/sort IDR-only, make UI labels explicit, validate price inputs, and test behavior.
- Document Option B as a future upgrade requiring approval.

## 10. Price Sorting Semantics

Recommended near-term sort behavior:

- `price_low`: sort by IDR price ascending.
- `price_high`: sort by IDR price descending.
- Products without IDR price should appear after products with IDR price.
- Ties can fall back to latest or product id for stable ordering.
- SGD-only products remain visible by default and under non-price sorts, but are placed after IDR-priced products when IDR price sorting is selected.

Do not:

- Convert SGD to IDR.
- Compare IDR and SGD numeric values as if equivalent.
- Fetch exchange rates.
- Add money/currency packages.

## 11. Multi-currency Decision

Recommended immediate decision:

- Display remains multi-currency.
- Filter/sort currency context remains IDR-only and explicit in labels.
- Do not add `currency` query parameter in FRONTEND-08.

Future optional decision:

- Add `currency=IDR|SGD` only if business wants visitors to filter/sort by selected currency.
- This requires approval because it expands query parameters, UI labels, tests, and price range behavior.

## 12. Missing Price Policy

Recommended behavior:

- Display: `Price on request`.
- Price filter: products without the selected filter currency do not match min/max price filters.
- Price sort: missing selected-currency price appears last.
- Default newest sort: missing price remains eligible if visibility policy passes.

### Price Decision Matrix

| Price state | Display | Filter eligibility | Sort behavior |
| ----------- | ------- | ------------------ | ------------- |
| IDR + SGD | IDR primary, SGD secondary | Eligible for IDR filter | Sort by IDR in near-term policy. |
| IDR only | IDR primary | Eligible for IDR filter | Sort by IDR. |
| SGD only | SGD primary | Not eligible for IDR filter | Last in IDR price sort; normal in newest sort. |
| No price | `Price on request` | Not eligible for price filter | Last in price sort; normal in newest sort. |
| IDR price = 0 | `Rp 0` as valid row if business allows | Eligible for IDR filter | Sort as numeric zero. |
| SGD price = 0 only | `SGD 0` as valid row if business allows | Not eligible for IDR filter | Last in IDR price sort. |

## 13. Duration Data Findings

Confirmed current state:

- `products.duration` is a nullable string.
- Product factory examples include `2 Hours`, `4 Hours`, `Half Day`, and `Full Day`.
- Current listing sort uses `CAST(duration AS UNSIGNED)` for short/long duration.

Duration states to support or reject:

- Minutes, for example `90 minutes`.
- Hours, for example `2 Hours`.
- Full day, for example `Full Day`.
- Multiple days, for example `2 Days`.
- Flexible, for example `Flexible`.
- Null/empty.
- Mixed human-readable strings.

Current data is not reliable enough for numeric duration sorting.

## 14. Duration Sorting Options

Option 1: Keep current string/numeric cast sorting.

- Pros: no UI removal.
- Cons: misleading for `Half Day`, `Full Day`, `Flexible`, and mixed strings.
- Verdict: not recommended.

Option 2: Parse strings in backend.

- Pros: no schema change.
- Cons: fragile, locale-sensitive, and can turn content copy into business logic.
- Verdict: not recommended for immediate implementation.

Option 3: Controlled mapping in backend.

- Pros: safer than generic parsing if allowed values are known.
- Cons: requires defining allowed duration taxonomy and admin input conventions.
- Verdict: possible future no-schema step if business accepts fixed labels.

Option 4: Remove/hide duration sort for now.

- Pros: avoids misleading UX immediately.
- Cons: removes existing sort options.
- Verdict: recommended immediate behavior, pending approval.

Option 5: Future normalized database field.

- Pros: best long-term query/sort semantics, for example `duration_minutes`.
- Cons: schema change requiring explicit approval, migration, validation, admin UI, data migration, and tests.
- Verdict: future proposal only.

## 15. Recommended Duration Sorting Policy

Recommended immediate policy:

- Remove or hide `duration_short` and `duration_long` from public sort options in FRONTEND-08, after approval.
- Keep exact duration filter options if they remain useful and are sourced from published/public-visible products.
- Default sort remains newest.
- Future normalized duration field can be proposed separately.

### Duration Decision Matrix

| Duration format | Sortable now | Risk | Recommendation |
| --------------- | -----------: | ---- | -------------- |
| `90 minutes` | No | Requires parsing rules. | Do not numeric-sort until normalized. |
| `2 Hours` | Partially | Cast gets leading number but unit semantics are informal. | Avoid public duration sort. |
| `Half Day` | No | Cast becomes unreliable. | Avoid public duration sort. |
| `Full Day` | No | Cast becomes unreliable. | Avoid public duration sort. |
| `2 Days` | Partially | Numeric cast misses unit conversion to hours/minutes. | Avoid public duration sort. |
| `Flexible` | No | Not numeric. | Treat as display/filter label only. |
| Null/empty | No | Unknown duration. | Do not include in duration filter options. |
| Mixed human-readable strings | No | Misleading order. | Remove duration sort until policy exists. |

## 16. Page Sections Listing Content Boundary

CMS-owned for Product Listing:

- Eyebrow/section label.
- Page title.
- Introduction/description paragraph.
- Optional listing/hero image.
- Optional final CTA label.
- Optional final CTA URL.
- Optional final CTA supporting copy.
- Active/inactive display of content fields only, if fallback behavior is defined.

Code-owned for Product Listing:

- Product query.
- Visibility policy.
- Filter definitions.
- Query parameter names.
- Sorting definitions.
- Pagination.
- Product relation loading.
- Product card/grid logic.
- Price calculations and selected currency semantics.
- Route names.
- Validation/sanitization of request inputs.

### CMS Responsibility Decision Matrix

| Listing concern | Code-owned | CMS-owned | Reason |
| --------------- | ---------: | --------: | ------ |
| Hero title/copy | No | Yes | Editable content for fixed layout. |
| Hero/listing image | No | Yes | Media content can come from Page Section slots/fallback. |
| Product query | Yes | No | Business/data integrity behavior must stay in backend code. |
| Filter parameter names | Yes | No | Route/query contracts must remain stable. |
| Sort options | Yes | No | Sorting changes affect query behavior and tests. |
| Pagination size | Yes | No | Performance and UX behavior, not CMS copy. |
| Product visibility | Yes | No | Domain policy, not editable content. |
| Price display labels | Mostly | Optional | Labels can be CMS copy later, but semantics stay code-owned. |
| CTA text/URL | No | Yes | Safe CTA fields can be Page Section-controlled. |
| Card/grid layout | Yes | No | Fixed frontend layout, not page builder. |

## 17. Existing Section Key Reuse Assessment

Existing keys are sufficient for immediate implementation:

- `products.index.hero`: use for page eyebrow/title/intro/hero media.
- `products.index.catalog`: use for catalog heading/supporting copy and optional empty-state/final CTA copy if the existing fields are enough.
- `products.index.filter_modal`: keep as structural mapping; do not let CMS define filters.
- `products.index.sort_modal`: keep as structural mapping; do not let CMS define sort options.

No key should be renamed.

No new key is required for FRONTEND-09 if final CTA can live in `products.index.catalog` fields or controlled `extra_data`.

If a dedicated final CTA section is later desired, propose a new key such as `products.index.footer_cta`, but implementation requires separate approval.

One listing content set should serve all packages for now. Taxi, Activity, Hotel, and Tour Package-specific listing copy should be deferred until dedicated routes or category landing behavior is approved.

## 18. Backend-to-Blade Target Contract

Blade should receive display-ready data:

- `products`: paginated public-visible Product records.
- `filters`: normalized active filters.
- `filterOptions`: safe Category, Destination, duration, and vehicle type options.
- `sort`: validated active sort key.
- `sortOptions`: safe public sort labels.
- `priceContext`: selected or fixed currency context, near-term `IDR`.
- `priceRange`: display-ready min/max for the active price context.
- `activeFilterCount`: calculated backend-side.
- `filteredPackageCount`: calculated backend-side.
- `resetFilterUrl`: prepared backend-side.
- `listingContent`: Page Section-backed content with registered fallback.
- `pagination`: paginator with query string persistence.

Controller responsibility:

- Normalize request input.
- Validate allowed sort and optional future currency keys.
- Build public product query using model scope.
- Prepare filters and URL state.
- Pass display-ready data to Blade.

Model scope responsibility:

- Own domain visibility, for example `published()` and `publiclyVisible()`.
- Own listing eager-loading helper if added.

Optional service/query object responsibility:

- Use only if controller becomes hard to scan.
- A small ProductListingQuery or ProductListingData service can be introduced in FRONTEND-07/08 if it reduces duplication.

Component responsibility:

- Render already-prepared Product/card/price state.
- Do not decide query semantics.
- Do not query database.

## 19. Query and Eager Loading Plan

Near-term query plan:

- Add public visibility scope.
- Add listing-specific eager load scope/helper that loads category, destination, prices, and images only.
- Keep detail-heavy relations out of listing query unless cards actually render them.
- Filter options should be derived from active, non-archived parent records and public-visible products where practical.
- Duration and vehicle options should be sourced from public-visible products, not just published products, so filters do not expose hidden parent content.

Potential future performance plan:

- After correctness tests exist, evaluate `product_prices(currency, price)` with EXPLAIN for price filters/min/max.
- Do not add indexes in FRONTEND-07/08/09 unless a separate database/performance step is approved.

## 20. Filter/Query Parameter Validation Plan

Recommended allowed parameters:

- `min_price`: nullable non-negative integer.
- `max_price`: nullable non-negative integer.
- `duration[]`: array of allowed public duration strings.
- `destination[]`: array of active, non-archived Destination ids.
- `category[]`: array of active, non-archived Category ids.
- `vehicle_type[]`: array of public-visible pickup type strings.
- `sort`: allow only `newest`, `price_low`, `price_high` immediately; remove/hide duration sorts unless approved otherwise.

If future currency context is approved:

- `currency`: allow only `IDR` or `SGD`.
- Default to `IDR` for missing/invalid values.

Invalid input behavior:

- Invalid sort falls back to `newest`.
- Invalid currency falls back to `IDR` if currency context exists.
- Invalid category/destination ids are ignored.
- Price min/max should not crash; malformed values should be ignored or normalized to safe defaults.

## 21. Pagination Persistence Plan

Keep:

- `paginate(8)`.
- `withQueryString()` or equivalent link generation that preserves active filters/sort.

Improve:

- Reset filter URL should intentionally drop filter params and page.
- Sort form should preserve filters but reset page to 1.
- Filter form should preserve selected sort if still valid and reset page to 1.
- Tests should assert page links keep selected filter/sort query parameters.

## 22. Focused Test Plan

Feature tests:

- Published Product with active Category and active Destination appears.
- Draft Product with active parents does not appear.
- Published Product with inactive Category does not appear if recommended policy is approved.
- Published Product with inactive Destination does not appear if recommended policy is approved.
- Published Product with archived Category does not appear if recommended policy is approved.
- Published Product with archived Destination does not appear if recommended policy is approved.
- Admin Product listing still shows or can manage Product under inactive/archived parents.
- Product with IDR and SGD displays IDR primary and SGD secondary.
- IDR-only Product displays IDR.
- SGD-only Product displays SGD and does not render `Rp 0`.
- Missing price displays `Price on request`.
- IDR price filter includes IDR-priced Products and excludes SGD-only/no-price Products.
- `price_low` and `price_high` sort by IDR and place missing-IDR Products last.
- Invalid sort falls back safely.
- Invalid future currency falls back safely if currency context is added.
- Filter query string persists through pagination.
- Listing CMS content uses `products.index.hero` when available.
- Listing falls back to registry/default copy if Page Section is missing/inactive according to chosen fallback policy.
- Product card wrapper still renders listing variant.

Unit tests:

- Only add unit tests if a dedicated parser/normalizer is introduced.
- For near-term plan, feature tests are enough.

Manual responsive QA:

- 320px, 375px, 768px, 1024px, 1280px, 1440px.
- Confirm filters, sort labels, empty state, pagination, and card price states do not overlap.

Manual CMS verification:

- Update Product Listing Hero Page Section in admin.
- Confirm public listing receives title/description/media via controller/service.
- Confirm filters/sort/query behavior remains code-owned.

Regression after implementation:

- Run focused frontend tests first.
- Run relevant Page Section tests.
- Run full `php artisan test` only after runtime code changes are made in implementation steps.

## 23. Proposed FRONTEND-07 Scope

Title: Public Listing Visibility & Query Integrity Implementation.

Objective:

- Implement public visibility policy and query integrity without changing price semantics or Page Section content yet.

Files likely affected:

- `app/Models/Product.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- Possibly `tests/Feature/Frontend/HomepageCmsContentTest.php` if homepage public product query is updated in same step.
- Optional report/docs for FRONTEND-07.

Code responsibility:

- Add `publiclyVisible()` Product scope.
- Add listing-card eager-loading helper/scope.
- Normalize allowed filter values.
- Keep admin queries unchanged.

Tests:

- Published active parents visible.
- Draft hidden.
- Inactive/archived parents hidden publicly.
- Admin still sees/manages products under inactive parents.
- Pagination query persistence.
- Invalid sort safe fallback.

Risks:

- Existing published products under inactive parents will disappear publicly.
- Homepage may temporarily differ if not updated in same step.

Rollback boundary:

- Revert Product scope/controller query changes and tests for FRONTEND-07.
- No database rollback.

Definition of done:

- Public listing only shows products passing approved policy.
- Admin visibility remains intact.
- Focused tests pass.
- `git diff --check` passes.

## 24. Proposed FRONTEND-08 Scope

Title: Public Listing Price and Sorting Semantics Implementation.

Objective:

- Make price and sorting behavior explicit, testable, and non-misleading.

Files likely affected:

- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/components/product-price.blade.php` only if display labels/states need adjustment.
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- Optional report/docs for FRONTEND-08.

Code responsibility:

- Keep IDR-only price filter/sort for immediate implementation.
- Make labels explicit, for example `Rentang Harga IDR`.
- Place missing-IDR Products last for price sort.
- Remove or hide duration sort options unless approved otherwise.
- Validate allowed sort keys.

Tests:

- IDR/SGD display states.
- IDR-only filter eligibility.
- Price sort order and missing-price placement.
- Invalid sort fallback.
- Missing price never renders `Rp 0`.
- Duration sort removed/hidden or behavior tested according to approved decision.

Risks:

- Removing duration sort changes visible UI options.
- Explicit IDR labels may require copy review.

Rollback boundary:

- Revert listing controller sort/filter changes, Blade label changes, and focused tests.
- No database rollback.

Definition of done:

- Price behavior is explicit and tested.
- Duration sort no longer misleads.
- Existing product card remains safe.

## 25. Proposed FRONTEND-09 Scope

Title: Public Listing CMS Content Sync.

Objective:

- Make Product Listing copy/media consume existing Page Sections while keeping application behavior code-owned.

Files likely affected:

- `app/Http/Controllers/Frontend/ProductController.php`
- Optional support class such as `app/Support/ProductListingContent.php` if needed.
- `resources/views/frontend/products/index.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php` only if existing assertions need expansion.
- Optional docs/report for FRONTEND-09.

Code responsibility:

- Load `products.index` Page Sections in backend.
- Build display-ready `listingContent`.
- Use `products.index.hero` and `products.index.catalog` fallback data.
- Keep filter/sort definitions in code.
- Do not create a page builder.

Tests:

- Listing uses Page Section title/description/media when active.
- Listing falls back safely when section is missing/inactive according to chosen policy.
- Existing section keys are preserved.
- No database queries in Blade.

Risks:

- Over-abstracting Page Section rendering.
- Admin expectations around inactive Page Section fallback need clear documentation.

Rollback boundary:

- Revert ProductController/content helper/view changes and tests.
- Page Section data remains untouched.

Definition of done:

- Product Listing public copy/media syncs with existing Page Sections.
- No new keys required.
- Fixed layout remains code-owned.

## 26. Files Inspected

- `AGENTS.md`
- `composer.json`
- `package.json`
- `ai/guidelines/03-backend-data-processing.md`
- `ai/guidelines/04-frontend-uiux-standard.md`
- `ai/guidelines/08-performance-optimization.md`
- `ai/guidelines/09-testing-qa-release.md`
- `ai/guidelines/10-documentation-system.md`
- `ai/skills/frontend-skill.md`
- `ai/skills/uiux-skill.md`
- `ai/skills/product-management-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/skills/documentation-skill.md`
- `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md`
- `ai/reports/frontend/frontend-02-mobile-nav-empty-price-product-card-implementation-report.md`
- `ai/reports/frontend/frontend-04d-homepage-renderer-consolidation-tests-report.md`
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`
- `ai/reports/database/db-01-product-factory-seeder-price-integrity-plan.md`
- `ai/reports/database/db-03-product-price-duplicate-precheck-unique-index-plan.md`
- `ai/reports/database/db-04-product-price-unique-index-implementation-report.md`
- `ai/reports/database/db-05-product-index-global-settings-performance-plan.md`
- `ai/reports/database/db-07-product-query-index-implementation-report.md`
- `ai/reports/database/db-08-category-destination-delete-integrity-policy-audit.md`
- `ai/reports/database/db-09-category-destination-fk-restriction-implementation-report.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/modules/page-sections.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/Product/Product_Page_UI/product-page-ui-refresh.md`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/database/schema-overview.md`
- `docs/database/indexes.md`
- `docs/database/data-integrity.md`
- `docs/performance/audit-report.md`
- `routes/frontend.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/ProductPrice.php`
- `app/Services/ProductPriceService.php`
- `app/Support/PageSectionRegistry.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/components/product-price.blade.php`
- `database/factories/ProductFactory.php`
- relevant product/category/destination/product price migrations
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`

## 27. Files Recommended for FRONTEND-07

Likely:

- `app/Models/Product.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `ai/reports/frontend/frontend-07-public-listing-visibility-query-integrity-implementation-report.md`

Possible:

- `tests/Feature/Frontend/HomepageCmsContentTest.php` if homepage product queries adopt the same public scope in that step.
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`

## 28. Files Recommended for FRONTEND-08

Likely:

- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/frontend/products/index.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `ai/reports/frontend/frontend-08-public-listing-price-sorting-semantics-implementation-report.md`

Possible:

- `resources/views/frontend/components/product-price.blade.php`
- `docs/Product/Product_Page_UI/product-page-ui-refresh.md`
- `docs/modules/products.md`

## 29. Files Recommended for FRONTEND-09

Likely:

- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/frontend/products/index.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `ai/reports/frontend/frontend-09-public-listing-cms-content-sync-implementation-report.md`

Possible:

- `app/Support/ProductListingContent.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/modules/page-sections.md`
- `docs/architecture/frontend-backend-sync.md`

## 30. Risks

- Visibility policy may hide products that were previously public.
- If homepage is not updated with the same public scope, homepage and listing can temporarily disagree.
- IDR-only price labels may not satisfy users who expect SGD filtering.
- Disabling duration sort may feel like feature removal.
- Page Section content sync can drift into page-builder behavior if boundaries are not enforced.
- Adding new abstractions too early can make the listing harder to maintain.
- Price range performance index remains deferred until correctness tests exist.

## 31. Rollback Strategy

FRONTEND-07 rollback:

- Revert Product visibility scope and ProductController query changes.
- Revert focused tests.
- No schema rollback.

FRONTEND-08 rollback:

- Restore prior price/sort query behavior and UI labels.
- Restore duration sort only if approved.
- Revert focused tests.
- No schema rollback.

FRONTEND-09 rollback:

- Revert listing Page Section content resolver/controller/view changes.
- Keep Page Section records intact.
- Restore hardcoded fallback copy in Blade only if needed.

Planning report rollback:

- Delete `ai/reports/frontend/frontend-06-public-product-listing-data-flow-fix-plan.md`.

## 32. Approval Points

Explicit approval is required before implementation for:

- Inactive/archived Category and Destination public visibility policy.
- Applying the same public visibility scope to homepage product queries.
- Keeping price filter/sort IDR-only with explicit labels.
- Adding `currency=IDR|SGD` query parameter in a future step.
- Removing or hiding duration sort options from the public listing.
- Any new Page Section key such as a future `products.index.footer_cta`.
- Any schema proposal for normalized duration, for example `duration_minutes`.
- Any new database index, especially `product_prices(currency, price)`.

## 33. Recommended Immediate Next Step

Proceed to FRONTEND-07 after approval:

`FRONTEND-07 Public Listing Visibility & Query Integrity Implementation`

Recommended approval decision before starting FRONTEND-07:

- Approve public visibility policy: public Product appears only when Product is `published`, Category is active and not archived, and Destination is active and not archived.
- Keep admin Product visibility unchanged.
- Do not change database schema.
- Do not change price/sort/Page Section behavior until FRONTEND-08 and FRONTEND-09.

## Verification

Required commands after this report:

- `git diff --check`
- `git status --short`

`php artisan test` is intentionally not required for this step because no runtime code changed. Focused and full tests should run in FRONTEND-07/08/09 after implementation changes are made.

