# FRONTEND-26A Category Visibility & Query Integrity Implementation Report

Date: 2026-06-16
Branch: `feature/ai-foundation`
Mode: focused implementation

## 1. Scope Confirmation

This task implemented the Category-side portion of the FRONTEND-25 visibility plan with a narrow runtime change.

Approved boundaries followed:

- No Destination route or Destination implementation was added.
- No public Category route was added.
- No schema, migration, Blade, CSS, JavaScript, Product Card, Product model policy, or global model scope change was made.
- Product visibility behavior remains opt-in through existing query scopes.
- Documentation was updated only because the runtime Product visibility contract changed for homepage Product cards.

Expected runtime change:

- Homepage Product cards now use the same public Product visibility policy as Product Listing and Product Detail.
- Homepage Product cards now use the listing-card eager-loading contract instead of the broader detail-ready relation set.

## 2. Files Changed

- `app/Http/Controllers/Frontend/HomeController.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- `ai/reports/frontend/frontend-26a-category-visibility-query-integrity-implementation-report.md`

## 3. Baseline Inspection

Pre-edit commands:

- `git branch --show-current`: `feature/ai-foundation`
- `git status --short`: clean
- `git diff --check`: passed
- `git diff --stat`: no tracked diff

Primary documents inspected:

- `AGENTS.md`
- `ai/reports/frontend/frontend-24-public-category-destination-experience-audit.md`
- `ai/reports/frontend/frontend-25-category-destination-data-flow-visibility-experience-fix-plan.md`
- `ai/guidelines/01-laravel-mvc-architecture.md`
- `ai/guidelines/03-backend-data-processing.md`
- `ai/guidelines/04-frontend-uiux-standard.md`
- `ai/guidelines/09-testing-qa-release.md`
- `ai/skills/frontend-skill.md`
- `ai/skills/testing-qa-skill.md`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`

Runtime files inspected:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Support/HomepageContent.php`
- `app/Support/ProductListingContent.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`

## 4. Implementation Summary

`HomeController@index` previously loaded homepage Product cards with:

- `published()`
- `frontendReady()`
- duplicate explicit eager loading for Category, Destination, and Images

It now loads homepage Product cards with:

- `publiclyVisible()`
- `frontendListingReady()`

This aligns homepage Product card visibility with existing public Product Listing and Product Detail behavior while keeping admin queries unchanged.

## 5. Findings and Evidence

### Finding 1 - Confirmed Issue Fixed: Homepage Product cards could render Products with inactive or archived parents

Evidence:

- File: `app/Http/Controllers/Frontend/HomeController.php`
- Section: `HomeController@index`
- Previous behavior: `$homeProducts` used `Product::query()->published()->frontendReady()` and did not require active, non-archived Category and Destination parents.
- Current behavior: `$homeProducts` uses `Product::query()->publiclyVisible()->frontendListingReady()->latest()->take(12)->get()`.
- Concrete risk before fix: a published Product assigned to an inactive or archived Category could appear on the homepage even though Product Listing and Product Detail hide it.
- Recommended future action: keep homepage Product card queries on `publiclyVisible()` and add the same opt-in scope to any future public Product surfaces.

Test evidence:

- File: `tests/Feature/Frontend/HomepageCmsContentTest.php`
- Method: `test_homepage_products_use_public_visibility_and_listing_card_relations`
- Current behavior verified: visible published Product appears; draft Products and Products with inactive/archived Category or inactive Destination parents do not render on homepage.

### Finding 2 - Confirmed Issue Fixed: Homepage Product cards loaded detail-only relations

Evidence:

- File: `app/Models/Product.php`
- Sections: `scopeFrontendListingReady`, `scopeFrontendReady`
- Previous behavior: homepage Product query used `frontendReady()`, which eager loads detail relations including Highlights, Features, FAQs, Itineraries, and Notes.
- Current behavior: homepage Product query uses `frontendListingReady()`, which eager loads only Category, Destination, Prices, and Images.
- Concrete risk before fix: homepage rendered Product cards with unnecessary relation loading, increasing query/memory work for a card-only surface.
- Recommended future action: reserve `frontendReady()` for Product Detail or legacy contexts that truly render detail relations; use `frontendListingReady()` for card grids.

Test evidence:

- File: `tests/Feature/Frontend/HomepageCmsContentTest.php`
- Method: `test_homepage_products_use_public_visibility_and_listing_card_relations`
- Current behavior verified: Category, Destination, Prices, and Images are loaded; FAQs, Itineraries, Notes, and Features are not loaded for homepage card Products.

### Finding 3 - Potential Risk Deferred: Homepage Destination card counts still use published Product counts

Evidence:

- File: `app/Http/Controllers/Frontend/HomeController.php`
- Section: `HomeController@index`, `$destinations = Destination::query()->where('is_active', true)->withCount(['products' => fn ($query) => $query->published()])`
- Current behavior: Destination counts remain based on published Products, not full public Product visibility.
- Concrete risk: a Destination card can show a package count that does not fully match Product Listing visibility if Products have inactive or archived parent records.
- Recommended future action: handle in the separate Destination-focused implementation step, because FRONTEND-26A explicitly avoided Destination implementation.

### Finding 4 - Confirmed Existing Behavior Preserved: Category remains filter-first, no public Category route

Evidence:

- File: `routes/frontend.php`
- Relevant section: public route definitions
- Current behavior: public routes remain `/`, `/products`, and `/products/{product:slug}`. No `/categories` or Category detail route exists.
- Concrete risk if changed without approval: route, SEO, canonical, empty-state, and content model decisions would be introduced beyond FRONTEND-26A scope.
- Recommended future action: keep Category filter-first until a separate route/content decision is approved.

### Finding 5 - Unavailable Behavior: Category direct slug 404 and clean Category SEO cannot be verified

Evidence:

- File: `routes/frontend.php`
- Relevant section: public route definitions
- Current behavior: there is no public Category slug route or Category page controller.
- Concrete risk: no direct Category page indexability, metadata, or slug 404 policy exists to audit at runtime.
- Recommended future action: if Category pages are approved later, implement explicit active/non-archived lookup, canonical metadata, empty-state policy, and tests.

## 6. Scores

Scores were assigned after inspection and implementation.

| Area | Score | Justification |
|---|---:|---|
| Category visibility integrity | 92/100 | Homepage Product cards now use `publiclyVisible()`, matching listing/detail behavior. Remaining gap is no clean Category page, by approved scope. |
| Query integrity | 90/100 | Homepage cards now use card-only eager loading. Destination count mismatch remains deferred and outside FRONTEND-26A. |
| Route discipline | 100/100 | No new Category or Destination routes were added. Existing public route map was preserved. |
| UI stability | 100/100 | No Blade, CSS, JavaScript, or Product Card changes were made. |
| Test coverage | 92/100 | Added homepage regression coverage and ran Category, Product Listing fallback, homepage, and full tests. No browser/UI build was needed because no frontend assets changed. |
| Documentation alignment | 95/100 | Product and frontend/backend sync docs now state the homepage Product card visibility contract. |
| Overall FRONTEND-26A readiness | 94/100 | Critical Category-facing homepage visibility leak is fixed with minimal scope. Remaining high-priority Destination count issue is intentionally deferred. |

## 7. Verification

Commands run:

- `php artisan test --filter=Category`: passed, 9 tests, 64 assertions
- `php artisan test --filter=PublicProductListing`: matched 0 tests, so it was treated as unavailable for this suite name
- `php artisan test --filter=ProductIndexUiTest`: passed, 32 tests, 303 assertions
- `php artisan test --filter=HomepageCmsContentTest`: passed, 18 tests, 200 assertions
- `php artisan test`: passed, 223 tests, 1493 assertions

Build decision:

- `npm.cmd run build` was not run because no Blade, CSS, or JavaScript files changed.

Final required commands:

- To be recorded after report creation: `git diff --check`
- To be recorded after report creation: `git status --short`
- Additional requested final inventory commands: `git diff --stat`, `git diff --name-only`

## 8. Impact

Database impact:

- None. No migration or schema change.

Route impact:

- None. Public routes are unchanged.

Frontend impact:

- Homepage Product cards now hide Products that are not public-visible because of draft status or inactive/archived parent records.
- No markup, styling, JavaScript, or Product Card component behavior was changed.

Backend impact:

- `HomeController@index` now reuses existing Product public visibility and listing-card scopes.
- Admin Product queries are unchanged.

Security impact:

- Positive visibility-hardening effect: inactive or archived parent Product records are no longer exposed through homepage Product cards.

SEO impact:

- Positive consistency effect: homepage Product links now align with public Product Detail availability, reducing risk of linking users/crawlers to Products that should 404 publicly.
- No Category route or metadata behavior was introduced.

Performance impact:

- Positive query-shape effect: homepage Product card query no longer eager loads detail-only relations.

Documentation impact:

- `docs/modules/products.md` now includes homepage Product cards in the `publiclyVisible()` and `frontendListingReady()` contract.
- `docs/architecture/frontend-backend-sync.md` now describes homepage Products as public-visible Product module records.

Rollback note:

- Revert this implementation by restoring the previous `$homeProducts` query in `HomeController@index`, removing the added homepage test, and reverting the two documentation contract edits.

## 9. Recommended Next Implementation Step

Implement the separate Destination-focused follow-up for the deferred homepage Destination card count mismatch. Keep it scoped to count integrity and tests unless the user explicitly approves clean Destination routes.
