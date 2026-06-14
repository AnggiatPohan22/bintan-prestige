# FRONTEND-05 Public Product Listing UI/UX Data Flow Audit

Date: 2026-06-13
Status: Audit/read-only inspection. Runtime code was not changed.
Scope: Public Product Listing only, centered on `/products`, `products.index`, product listing query/data flow, listing Blade, reusable product card/price components, listing filter/search forms, pagination, listing images/prices/empty states, listing SEO/layout metadata, and existing focused tests.

Out of scope by instruction:

- Product Detail implementation, except where shared product card/price/SEO evidence directly informs Product Listing behavior.
- Homepage implementation, except where shared product card conventions or Page Sections docs directly inform Product Listing behavior.
- Admin Product CRUD, sitemap, and unrelated frontend sections.

Expected file change for this task:

- `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md`

No other project file should be changed.

## 1. Task Understanding

This audit checks whether the public Product Listing page is UI/UX-ready and whether its data flow is safe, scalable, and aligned with the existing Laravel MVC/CMS architecture. The task is documentation-only and read-only except for this report.

The audit does not implement fixes. Future implementation should be done in a separate step after approval, especially for any query policy, schema/index, Page Section rendering, route, or test changes.

## 2. Inspection Order Completed

A. Existing documentation and previous frontend reports
B. Public routes
C. Listing controllers and query builders
D. Product model scopes and relations
E. Listing Blade views and reusable product-card components
F. Filter/search forms and query parameters
G. Pagination
H. Images, prices, and empty states
I. SEO/layout metadata related directly to listing pages
J. Existing feature/unit tests

## 3. Files Read

Guidelines and skills:

- `AGENTS.md`
- `ai/guidelines/03-backend-data-processing.md`
- `ai/guidelines/04-frontend-uiux-standard.md`
- `ai/guidelines/07-seo-ai-discovery.md`
- `ai/guidelines/08-performance-optimization.md`
- `ai/guidelines/09-testing-qa-release.md`
- `ai/guidelines/10-documentation-system.md`
- `ai/skills/frontend-skill.md`
- `ai/skills/uiux-skill.md`
- `ai/skills/design-system-skill.md`
- `ai/skills/product-management-skill.md`
- `ai/skills/seo-ai-discovery-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/skills/documentation-skill.md`

Reports and product documentation:

- `ai/reports/frontend/frontend-01-mobile-nav-empty-price-product-card-fix-plan.md`
- `ai/reports/frontend/frontend-02-mobile-nav-empty-price-product-card-implementation-report.md`
- `ai/reports/frontend/improve-05-frontend-uiux-data-rendering-audit.md`
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`
- `ai/reports/database/db-01-product-factory-seeder-price-integrity-plan.md`
- `ai/reports/database/db-08-category-destination-delete-integrity-policy-audit.md`
- `ai/reports/database/db-09-category-destination-fk-restriction-implementation-report.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `docs/Product/Product_Page_UI/product-page-ui-refresh.md`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/modules/page-sections.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/performance/audit-report.md`

Runtime source and tests:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Support/PageSectionRegistry.php`
- `app/Support/StructuredDataBuilder.php`
- `resources/views/layouts/frontend.blade.php`
- `resources/views/frontend/frontend.blade.php`
- `resources/views/partials/site-social-share-meta.blade.php`
- `resources/views/partials/site-structured-data.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/components/product-price.blade.php`
- `resources/css/frontend.css`
- `resources/css/frontend-theme.css`
- `resources/css/frontend-products.css`
- `resources/js/app.js`
- `resources/js/frontend.js`
- `database/factories/ProductFactory.php`
- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `database/migrations/2026_06_13_000001_add_unique_product_currency_to_product_prices_table.php`
- `database/migrations/2026_06_13_000002_add_status_created_at_index_to_products_table.php`
- `database/migrations/2026_06_13_000003_restrict_category_destination_deletes_on_products_table.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`

## 4. Route and Data Flow Summary

Public listing route:

- Evidence: `routes/frontend.php`, public route section.
- Current behavior: `GET /products` maps to `Frontend\ProductController@index` and is named `products.index` at lines 12-15.
- Impact: public listing is route-clean and guest-accessible.

Listing controller:

- Evidence: `app/Http/Controllers/Frontend/ProductController.php`, `ProductController@index`.
- Current behavior: the controller prepares selected filters from request arrays at lines 16-19, builds a published product query at lines 21-49, counts filtered products at line 51, applies sorting at lines 55-78, paginates at lines 79-80, loads filter option data at lines 82-112, calculates active filter count at lines 115-122, defines sort labels at lines 124-130, and returns `frontend.products.index` at lines 132-146.
- Impact: heavy filtering/sorting/pagination is mostly backend-side, which matches the AI project rules.

Model data contract:

- Evidence: `app/Models/Product.php`.
- Current behavior: `category()` and `destination()` use `withTrashed()` at lines 63-72; `prices()` is a `HasMany` at lines 80-83; price accessors read loaded `prices` at lines 103-114; `scopePublished()` filters `status = published` at lines 228-234; `scopeFrontendReady()` eager-loads category, destination, prices, images, highlights, features, FAQs, itineraries, and notes at lines 238-250.
- Impact: listing cards avoid Blade database queries when the scope is used, but the scope currently loads more relations than listing cards need.

Listing Blade and components:

- Evidence: `resources/views/frontend/products/index.blade.php`, `resources/views/frontend/products/partials/card.blade.php`, `resources/views/frontend/components/product-card.blade.php`, `resources/views/frontend/components/product-price.blade.php`.
- Current behavior: listing root renders `data-page-key="products.index"` at line 22; hero section key renders at lines 60-62; catalog section key renders at line 82; listing includes `frontend.products.partials.card` for each product at lines 137-141; that partial delegates to `frontend.components.product-card` with `variant => listing`; the shared product card builds media from thumbnail plus `images` at lines 3-29; listing variant renders card/category/price/title/meta/actions at lines 32-124; shared price component renders IDR, SGD fallback, or `Price on request` at lines 11-27.
- Impact: current product card rendering is componentized and has fixed missing-price behavior.

## 5. Findings

### Critical Findings

No critical issue was confirmed in this audit.

### High Priority Findings

#### H1. Product Listing Page Sections are registered and keyed, but public listing copy/media is not loaded from Page Sections

Status: Confirmed issue.

Evidence:

- `app/Support/PageSectionRegistry.php`, `PageSectionRegistry::pages()` and `PageSectionRegistry::sections()`: registers `products.index` at lines 19-20 and registers `products.index.hero`, `products.index.catalog`, `products.index.filter_modal`, and `products.index.sort_modal` at lines 163-186.
- `docs/modules/page-sections.md`, Page Sections Module: states Page Sections supply copy, CTA fields, media, status, ordering metadata, and controlled JSON values for fixed layouts at line 5.
- `app/Http/Controllers/Frontend/ProductController.php`, `ProductController@index`: returns `frontend.products.index` with only product/filter/sort variables at lines 132-146; no `PageSection` or `HomepageSectionData` equivalent is loaded for `products.index`.
- `resources/views/frontend/products/index.blade.php`, product hero: hardcodes the H1 and description at lines 70-76 and derives hero background from the first product thumbnail or default hero asset at lines 10-17 and 63-64.

Current behavior:

- The public listing has `data-page-key` and `data-section-key` attributes, but public hero text/media does not read the registered `page_sections` content for `products.index`.

Concrete risk:

- Admin Page Sections can show a Product Listing page/hero shell, but updating that section may not change the public `/products` hero copy or hero media. This creates a CMS trust issue and keeps important listing copy hardcoded in Blade.

Recommended future action:

- Add a small public product-page section resolver for `products.index` that loads active registered Page Sections and supplies display-ready hero/catalog/modal copy/media to the view. Keep the fixed Blade layout and do not build a page builder.

#### H2. Published products under inactive or archived parents can still be selected for the public listing query

Status: Potential risk with confirmed code path.

Evidence:

- `app/Http/Controllers/Frontend/ProductController.php`, `ProductController@index`: base product query applies `Product::query()->published()->frontendReady()` at lines 21-23, with no active/non-deleted category or destination condition.
- `app/Http/Controllers/Frontend/ProductController.php`, filter option queries: categories and destinations shown in filters are limited to `where('is_active', true)` at lines 82-88.
- `app/Models/Product.php`, relations: `category()` and `destination()` call `withTrashed()` at lines 63-72.
- `docs/modules/products.md`, Remaining Product Risks: states public visibility for products under archived/inactive parents remains a future policy decision at line 60.
- `docs/architecture/frontend-backend-sync.md`: states a future step should decide whether published products under archived/inactive parents should remain visible or be hidden by a stricter public visibility scope at line 58.

Current behavior:

- The listing query is governed by product publication status, not by parent category/destination active or soft-delete status. Filter option lists show only active categories/destinations.

Concrete risk:

- A published product can appear on `/products` with an inactive or archived parent label while that parent is absent from the filter UI. This can confuse visitors and weaken CMS expectations around archiving/inactivation.

Recommended future action:

- Decide a public visibility policy. If inactive/archived parents should hide public products, add a dedicated public scope such as `publiclyVisible()` using `whereHas` against non-deleted active category and destination, then cover it with listing and detail tests.

#### H3. Price filter and price sort use IDR only while product cards can display SGD-only or missing-price states

Status: Potential risk with confirmed code path.

Evidence:

- `app/Http/Controllers/Frontend/ProductController.php`, `ProductController@index`: `min_price` and `max_price` filters use `whereHas('prices')` with `currency = IDR` at lines 24-36.
- `app/Http/Controllers/Frontend/ProductController.php`, sort builder: `withMin('prices as idr_price_sort')` filters to `currency = IDR` at lines 55-59; `price_low` and `price_high` sort by `idr_price_sort` at lines 60-64.
- `resources/views/frontend/components/product-price.blade.php`, listing context: if IDR is missing but SGD exists, the component displays SGD at lines 14-19; if both are missing it displays `Price on request` at lines 17-19.
- `tests/Feature/Frontend/ProductIndexUiTest.php`: confirms SGD-only products render `SGD 35` and do not render `Rp 0` at lines 69-87.

Current behavior:

- The UI can display SGD-only and no-price products, but listing price filter/sort logic uses only IDR price rows.

Concrete risk:

- SGD-only or missing-price products may sort/filter in a way visitors do not expect, especially under `price_low` or when min/max price filters are used. The filter label `Rentang Harga` does not state that the range is IDR-only.

Recommended future action:

- Define a listing price policy: either label the filter as IDR-only and sort missing-IDR products consistently, or prepare a normalized display/sort price in the backend. Add focused tests for IDR-only, SGD-only, dual-currency, and no-price products under price filter and price sort.

#### H4. Duration sorting casts a free-form string field as unsigned integer

Status: Confirmed issue.

Evidence:

- `app/Http/Controllers/Frontend/ProductController.php`, `ProductController@index`: `duration_short` and `duration_long` use `orderByRaw('CAST(duration AS UNSIGNED) ...')` at lines 65-70.
- `database/migrations/2026_05_21_142755_create_products_table.php`: `duration` is stored as a nullable string at lines 40-41.
- `database/factories/ProductFactory.php`: sample durations include `2 Hours`, `4 Hours`, `Half Day`, and `Full Day` at lines 41-46.
- `ai/reports/database/improve-04-database-relationship-query-audit.md`: previously noted that string duration plus `CAST(duration AS UNSIGNED)` is not index-friendly and values such as `Half Day` and `Full Day` are not naturally numeric at line 204.

Current behavior:

- Duration sort attempts numeric ordering from human-readable strings.

Concrete risk:

- `Half Day`, `Full Day`, and future text durations can sort inaccurately or tie unexpectedly, making the sort UI unreliable for users.

Recommended future action:

- Introduce a backend-prepared duration sort value or a controlled duration taxonomy in a future approved data-model step. Short term, document current limitation and add a test that captures the desired sort policy before changing behavior.

### Medium Priority Findings

#### M1. `Product::scopeFrontendReady()` over-fetches detail relations for listing cards

Status: Confirmed issue.

Evidence:

- `app/Models/Product.php`, `scopeFrontendReady()`: eager-loads category, destination, prices, images, highlights, features, FAQs, itineraries, and notes at lines 238-250.
- `resources/views/frontend/components/product-card.blade.php`, listing variant: uses image data at lines 3-29, category at lines 54-56, product price component at lines 60-63, title link at lines 64-68, destination/duration meta at lines 72-83, and media actions at lines 86-124.
- `resources/views/frontend/components/product-price.blade.php`: uses `idr_price` and `sgd_price`, which read the loaded `prices` relation, at lines 5-8.
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`: previously noted `Product::scopeFrontendReady()` may eager-load more data than needed for listing pages at lines 97, 200, and 316.

Current behavior:

- Listing cards use a subset of the relations loaded by `frontendReady()`.

Concrete risk:

- Product listing queries may carry unnecessary relation payload as the catalog grows, especially with FAQs, itineraries, notes, and features that are not rendered in listing cards.

Recommended future action:

- Add a narrower listing-specific scope or query helper, for example `forPublicListingCards()`, that eager-loads only category, destination, prices, and images needed by listing cards.

#### M2. Product price range queries do not have a dedicated `currency, price` index

Status: Potential risk with confirmed schema/query evidence.

Evidence:

- `app/Http/Controllers/Frontend/ProductController.php`, price range: reads `ProductPrice::query()->where('currency', 'IDR')->min('price')` and `max('price')` at lines 106-112.
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`: creates `product_prices` with `product_id`, `currency`, and `price` at lines 11-23.
- `database/migrations/2026_06_13_000001_add_unique_product_currency_to_product_prices_table.php`: adds unique index on `product_id, currency` at lines 26-31.
- `docs/modules/products.md`: remaining risks list the deferred product price range index at line 59.

Current behavior:

- Price min/max and price filters rely on `currency` and `price`, while the confirmed added price index is unique by `product_id, currency`.

Concrete risk:

- As product prices grow, min/max range reads and price filter subqueries may become slower than needed.

Recommended future action:

- In a separate approved database step, add and verify an index such as `product_prices(currency, price)` if EXPLAIN confirms benefit.

#### M3. Product listing metadata uses global SEO defaults, not listing-specific metadata

Status: Confirmed issue.

Evidence:

- `resources/views/layouts/frontend.blade.php`: title uses `$seoTitle ?? $title ?? $seoDefaults['meta_title'] ...` at lines 1-4 and includes social/structured-data partials at lines 19-20.
- `resources/views/partials/site-social-share-meta.blade.php`: meta description, canonical, robots, OG, and Twitter tags are generated from `$seoDescription`, `$canonicalUrl`, `$socialShareType`, `$seoImage`, or global defaults at lines 7-41.
- `app/Http/Controllers/Frontend/ProductController.php`, `index()`: returns the listing view with product/filter variables only at lines 132-146.
- `app/Http/Controllers/Frontend/ProductController.php`, `show()`: product detail does set `seoTitle`, `seoDescription`, `canonicalUrl`, `seoImage`, and `socialShareType` at lines 173-192, showing the project already has a pattern for page-specific metadata.

Current behavior:

- `/products` receives shared default SEO metadata unless global defaults happen to be product-listing-specific.

Concrete risk:

- Search/social previews for the listing page can be generic, and canonical/meta messaging may not reflect the catalog page intent.

Recommended future action:

- Add listing-specific `seoTitle`, `seoDescription`, `canonicalUrl`, and optional `socialShareType` in `ProductController@index`, preferably backed by Page Section or SEO default settings if already available.

#### M4. Listing filter/sort modals need stronger accessibility contracts

Status: Potential risk; browser behavior unverified.

Evidence:

- `resources/views/frontend/products/index.blade.php`: filter and sort triggers are buttons at lines 107-129, but no `aria-expanded` or `aria-controls` is rendered for their modal panels.
- `resources/views/frontend/products/index.blade.php`: filter, sort, and media overlays use `aria-modal="true"` and `role="dialog"` at lines 170-174, 344-348, and 437-442.
- `resources/views/frontend/products/index.blade.php`: Escape key closes overlays at line 55.
- `resources/css/frontend-products.css`: overlay panels are fixed and scrollable at lines 339-356 and 509-510.

Current behavior:

- Dialogs have basic modal roles and close affordances, but trigger state and labelled dialog relationships are not fully explicit in markup.

Concrete risk:

- Keyboard and assistive-technology users may not receive clear state/label context for filter/sort dialogs. Focus trapping and focus return were not verified in browser during this read-only audit.

Recommended future action:

- Add `aria-controls`, dynamic `aria-expanded`, `aria-labelledby`, and focus management tests/manual QA for the listing dialogs. Keep behavior lightweight and Alpine-based.

#### M5. Listing hero background is tied to the first product thumbnail on the current page/result set

Status: Potential risk with confirmed code path.

Evidence:

- `resources/views/frontend/products/index.blade.php`: `$productHeroBackground` is set from `$products->getCollection()->firstWhere('thumbnail_url')?->thumbnail_url` or the default hero asset at line 17.
- `resources/views/frontend/products/index.blade.php`: that value is applied as `--product-hero-image` at lines 63-64.
- `resources/views/frontend/products/index.blade.php`: `$products` is a paginated listing from `ProductController@index` at lines 137-146.

Current behavior:

- Hero image can vary with filters, sorting, and pagination because it comes from the current paginated product collection.

Concrete risk:

- The catalog hero can change unpredictably between filter states/pages and may use a product image that is not ideal as a page-level hero.

Recommended future action:

- Prefer a Page Section hero media slot or global default hero asset for the listing hero. Keep product thumbnails inside cards.

### Low Priority Findings

#### L1. Existing tests cover key UI states but not filter/sort/pagination behavior

Status: Confirmed issue.

Evidence:

- `tests/Feature/Frontend/ProductIndexUiTest.php`: covers Product Listing page/section keys, hero title, missing price, no `Rp 0`, no empty video action, dual IDR/SGD price rendering, and SGD-only rendering at lines 16-87.
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`: covers Product Listing page and section keys at lines 15-32.
- Repository search found no focused frontend tests for `min_price`, `max_price`, selected `duration[]`, `destination[]`, `category[]`, `vehicle_type[]`, `sort`, `paginate(8)`, or `withQueryString()` behavior in the Product Listing test files.

Current behavior:

- Important visual/data states are covered, but listing query controls are mostly untested.

Concrete risk:

- Future query or UI changes could break filters, query string persistence, sorting, or pagination without a focused regression test.

Recommended future action:

- Add Product Listing tests for category, destination, duration, vehicle type, IDR min/max price, each sort mode, pagination count, and query-string persistence.

#### L2. Listing empty state exists but does not expose a direct clear-filters action

Status: Potential risk.

Evidence:

- `resources/views/frontend/products/index.blade.php`: empty state renders `No products available` and `Try clearing filters or choose another destination.` at lines 151-158.
- `resources/views/frontend/products/index.blade.php`: clear filter action exists inside the filter modal as `Hapus Filter` at lines 329-331, not directly inside the empty state.

Current behavior:

- When the page is empty, users are told to clear filters but the visible empty state itself does not include a clear/reset link.

Concrete risk:

- Users may need to reopen the filter modal to recover from an empty filtered result.

Recommended future action:

- Add a direct clear-filters link in the empty state if any filters are active, preserving the current route and query parameter contract.

## 6. Confirmed Strengths

- Public route is simple and named correctly: `/products` -> `products.index`.
- Filtering, sorting, and pagination are backend-side in `ProductController@index`; no browser-side product filtering of the full listing was found.
- Pagination uses `paginate(8)->withQueryString()`, preserving filters/sort across pages.
- Listing uses shared `frontend.components.product-card` through the listing wrapper partial.
- Missing price is handled as `Price on request`; `Rp 0` is covered by tests.
- Empty video action is hidden when no product video items exist; covered by tests.
- Product card images include `alt`, `width`, `height`, `loading="lazy"`, and `decoding="async"` in listing card markup.
- Product listing page and section keys are present and covered by tests.
- Shared layout renders baseline meta description, robots, canonical, OG, Twitter, and structured data partials.

## 7. Unavailable or Unverified Behavior

- Browser/mobile visual QA was not run in this audit because the task requested read-only inspection plus report creation.
- `php artisan test` was intentionally not run because no runtime code changed and the user explicitly instructed not to run it for this task.
- Live database rows were not queried; parent visibility findings are based on source code, migrations, and documentation, not production content.
- Laravel Boost tools were not available in the current tool list, so route/model/controller/view inspection was performed from source files.
- Product Detail, Homepage, Admin Product CRUD, sitemap, and unrelated frontend sections were not audited except where directly referenced for shared listing components or documentation context.

## 8. Score Summary

Scores were assigned after completing the inspection above.

| Area | Score | Justification |
| --- | ---: | --- |
| Database | 82/100 | Product status index and unique product price currency guardrails exist. Remaining listing-relevant risks are deferred price range index and string duration sorting. |
| Backend | 76/100 | Controller prepares filters/sort/pagination server-side, but public parent visibility policy, IDR-only price semantics, broad eager loading, and duration sort need follow-up. |
| Frontend | 78/100 | Listing UI has strong card, price, empty state, modal, and pagination foundations. CMS-sync, empty-state recovery, and modal accessibility need improvement. |
| Security | 88/100 | Listing uses GET forms, bound Eloquent query builders, escaped Blade output, and no raw user SQL. Main concerns are not security-critical; no route/auth changes were in scope. |
| SEO | 68/100 | Shared layout provides baseline SEO tags and structured-data scaffolding, but `/products` lacks listing-specific metadata and Page Section-backed listing copy. |
| Performance | 70/100 | Pagination and eager loading reduce N+1 risk, but `frontendReady()` over-fetches listing data and price range queries lack a dedicated `currency, price` index. |
| Documentation | 76/100 | Product listing/page-section docs exist and filenames were resolved by content, but docs already reveal future-policy gaps that remain unimplemented. |
| Launch Readiness | 72/100 | No critical blocker found, but high-priority CMS-sync, parent visibility, price semantics, and duration sort issues should be resolved before treating listing UX/data flow as launch-ready. |

Overall score: 76/100.

## 9. Impact Assessment

Database impact:

- None from this audit report.
- Future database work may be needed only if the team approves duration normalization or a product price range index.

Route impact:

- None from this audit report.
- Current public Product Listing route remains `GET /products` named `products.index`.

Frontend impact:

- None from this audit report.
- Future frontend work should focus on Page Section-backed listing hero/copy, clear empty-state recovery, and accessible filter/sort modal state.

Backend impact:

- None from this audit report.
- Future backend work should define a public listing scope, listing-specific eager loading, listing-specific metadata, and price/duration sorting policies.

Security impact:

- None from this audit report.
- No raw user-input SQL or exposed sensitive route was confirmed in listing scope.

SEO impact:

- None from this audit report.
- Future listing-specific metadata and stable CMS-backed hero copy are recommended.

Performance impact:

- None from this audit report.
- Future work should reduce listing relation payload and evaluate price range indexing.

Documentation impact:

- This audit report was created.
- Existing documentation was not changed or moved.

Rollback note:

- Delete `ai/reports/frontend/frontend-05-public-product-listing-uiux-data-flow-audit.md` to roll back this audit-only change.

## 10. Recommended Next Implementation Step

Proceed with a focused `FRONTEND-06 Public Product Listing Data Flow Fix Plan` before runtime edits.

Recommended plan scope:

1. Define the public product visibility policy for archived/inactive Category and Destination parents.
2. Define listing price semantics for IDR-only filters/sorts versus SGD/no-price cards.
3. Define duration sorting policy before changing code.
4. Add Page Section-backed content loading for `products.index` hero/catalog copy/media without creating a page builder.
5. Add focused tests for listing filters, sorting, pagination query-string persistence, parent visibility policy, and listing-specific metadata.

## 11. Verification

Required verification for this audit-only task:

- `git diff --check`
- `git status --short`

Decision recorded:

- `php artisan test` was not run because no runtime code changed and the user explicitly instructed not to run it when only this audit report changed.

