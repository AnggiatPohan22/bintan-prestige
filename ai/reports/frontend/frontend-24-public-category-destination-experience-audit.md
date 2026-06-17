# FRONTEND-24 Public Category & Destination Experience Audit

Date: 2026-06-16
Branch: `feature/ai-foundation`
Mode: audit only, read-only, report-only

## 1. Executive Summary

Public Category and Destination experience is currently **filter-based through Product Listing**, not dedicated public Category or Destination pages.

Actual public routes are:

- `GET /products` -> `products.index` -> `App\Http\Controllers\Frontend\ProductController@index`
- `GET /products/{product:slug}` -> `products.show` -> `App\Http\Controllers\Frontend\ProductController@show`

No dedicated public `/categories`, `/categories/{slug}`, `/destinations`, or `/destinations/{slug}` route exists. Category and Destination appear through homepage search/filter controls, homepage destination cards, Product Listing filters, Product Card metadata, Product Detail badges, and Product Detail schema/category context.

Overall Category/Destination readiness score: **74/100**.

Primary confirmed issue:

- **Critical:** Homepage popular products use `Product::published()->frontendReady()` instead of `Product::publiclyVisible()`, so a published Product under an inactive or archived Category/Destination can still be publicly rendered on the homepage even though Product Listing and Product Detail hide it.

Primary high-priority risk:

- Homepage destination cards count `published` products only, not `publiclyVisible` products, so a destination card can advertise package counts that do not match the filtered Product Listing result.

No runtime implementation, route change, schema change, Blade change, CSS change, JavaScript change, test change, or previous report edit was made.

## 2. Audit Scope and Method

Inspected in order:

1. Existing documentation and previous frontend reports.
2. Public routes.
3. Product Listing and Product Detail controllers.
4. Product, Category, and Destination models.
5. Product Listing Blade, Product Detail Blade, homepage Category/Destination sections, and shared Product Card component.
6. Filter forms and query parameters.
7. Pagination.
8. Images, prices, and empty states.
9. SEO, metadata, and structured-data paths directly related to Product Listing/Product Detail.
10. Existing focused tests.

Read-only confirmation:

- This task is audit/read-only.
- Only this report file was created.
- No code, Blade, CSS, JS, test, migration, route, previous report, or documentation file was modified.

Baseline git state:

- `git branch --show-current`: `feature/ai-foundation`
- `git status --short` before FRONTEND-24 work showed one pre-existing untracked report: `?? ai/reports/frontend/frontend-23-product-detail-final-performance-regression-release-verification-report.md`
- `git diff --check`: passed
- `git diff --stat`: no tracked diff

## 3. Previous Frontend Baseline

Relevant prior baseline:

- FRONTEND-07 established Product Listing public visibility through `Product::publiclyVisible()`.
- FRONTEND-11 established Product Listing GET filters, active filter summary, empty states, and pagination behavior.
- FRONTEND-13 established Product Listing accessibility, SEO, `noindex, follow` for filter/query URLs, BreadcrumbList, and ItemList.
- FRONTEND-14 and FRONTEND-14B verified Product Listing release/performance with known browser limitations.
- FRONTEND-17A aligned Product Detail direct slug access with Product Listing visibility policy.
- FRONTEND-23 verified Product Detail final release as ready with known limitations.

Evidence:

- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`, sections describing `Product::publiclyVisible()`.
- `ai/reports/frontend/frontend-11-product-listing-filter-search-pagination-ux-implementation-report.md`, Category/Destination filter behavior.
- `ai/reports/frontend/frontend-13-product-listing-accessibility-seo-ai-discovery-rendering-report.md`, SEO/query URL policy.
- `ai/reports/frontend/frontend-23-product-detail-final-performance-regression-release-verification-report.md`, Product Detail visibility, query, schema, and release verification.

## 4. Category Route Map

No dedicated public Category route exists.

| Experience | Route | Controller | View | Visibility | Result |
|---|---|---|---|---|---|
| Category index | Not present | N/A | N/A | N/A | No dedicated public index page |
| Category detail | Not present | N/A | N/A | N/A | No slug route or 404 behavior to verify |
| Product Listing Category filter | `GET /products?category[]=id` | `ProductController@index` | `frontend.products.index` | Active categories only in filter options; products constrained by `publiclyVisible()` | Available |
| Homepage search Category select | `GET /products?category[]=id` via form | `ProductController@index` | `frontend.home` form -> listing | Active categories only | Available |
| Homepage popular product tabs | Client-side category tabs over rendered homepage products | `HomeController@index` | `frontend.sections.popular-products` | Derived from `$homeProducts`; parent visibility mismatch risk | Confirmed issue |
| Product Card category metadata | Product cards | Prepared Product relation | `frontend.components.product-card` | Displays loaded Category name if relation exists | Available |
| Product Detail Category breadcrumb link | Not present | N/A | `ProductDetailDisplayState::breadcrumbState()` links Home > Products > Product | Product Detail badge displays category name only | No Category link |

Route evidence:

- `routes/frontend.php`: only `/`, `/products`, and `/products/{product:slug}` are registered.
- `php artisan route:list --path=categories -v`: only admin Category routes exist.

## 5. Destination Route Map

No dedicated public Destination route exists.

| Experience | Route | Controller | View | Visibility | Result |
|---|---|---|---|---|---|
| Destination index | Not present | N/A | N/A | N/A | No dedicated public index page |
| Destination detail | Not present | N/A | N/A | N/A | No slug route or 404 behavior to verify |
| Product Listing Destination filter | `GET /products?destination[]=id` | `ProductController@index` | `frontend.products.index` | Active destinations only in filter options; products constrained by `publiclyVisible()` | Available |
| Homepage search Destination select | `GET /products?destination[]=id` via form | `ProductController@index` | `frontend.home` form -> listing | Active destinations only | Available |
| Homepage Destination cards | `GET /products?destination[]=id` | `HomeController@index` + `HomepageContent::destinationCards()` | `frontend.sections.categories` | Active destinations only; count mismatch risk | Available with risk |
| Product Card destination metadata | Product cards | Prepared Product relation | `frontend.components.product-card` | Displays loaded Destination name if relation exists | Available |
| Product Detail Destination breadcrumb link | Not present | N/A | `ProductDetailDisplayState::breadcrumbState()` links Home > Products > Product | Product Detail badge displays destination name only | No Destination link |

Route evidence:

- `routes/frontend.php`: no public Destination route.
- `php artisan route:list --path=destinations -v`: only admin Destination routes exist.

## 6. Category Public Architecture

Current Category experience is:

1. Product Listing filter only.
2. Homepage search select only.
3. Homepage popular product tab label only.
4. Product Card metadata only.
5. Product Detail badge/schema category text only.

There is no dedicated Category index/detail page.

Evidence:

- `ProductController@index` loads active Categories with `Category::query()->where('is_active', true)->orderBy('name')->get()`.
- `resources/views/frontend/products/index.blade.php` renders `category[]` checkboxes.
- `resources/views/frontend/home.blade.php` renders `category[]` select options.
- `resources/views/frontend/components/product-card.blade.php` renders category name as metadata.
- `resources/views/frontend/products/show.blade.php` renders category badge text.

Confirmed behavior:

- Active Category filter values can narrow Product Listing.
- Invalid Category filter values produce empty safe results instead of widening results.
- Category slugs are not used publicly because no Category detail route exists.

Unavailable/unverified behavior:

- Category direct slug 404 behavior is unavailable because no route exists.
- Category description is not rendered on a public Category page because no page exists.
- Category image behavior is unavailable because Category has no image field.

## 7. Destination Public Architecture

Current Destination experience is:

1. Product Listing filter only.
2. Homepage search select.
3. Homepage destination card grid that links to filtered Product Listing.
4. Product Card metadata.
5. Product Detail badge and WhatsApp context text.

There is no dedicated Destination index/detail page.

Evidence:

- `HomeController@index` loads active Destinations with `withCount(['products' => fn ($query) => $query->published()])`.
- `HomepageContent::destinationCard()` maps each active Destination to `route('products.index', ['destination' => [$destination->id]])`.
- `resources/views/frontend/sections/categories.blade.php` renders Destination image, name, and package label from prepared card data.
- `resources/views/frontend/products/index.blade.php` renders `destination[]` checkboxes.

Confirmed behavior:

- Active Destination filter values can narrow Product Listing.
- Homepage Destination cards are server-rendered links to filtered Product Listing.
- Destination images can render on homepage cards when `destinations.image` exists.

Unavailable/unverified behavior:

- Destination direct slug 404 behavior is unavailable because no route exists.
- Destination detail hero/content hierarchy is unavailable because no page exists.
- Destination Open Graph image usage for clean Destination pages is unavailable because no page exists.

## 8. Active/Inactive Visibility

| Entity state | Product state | Public result | Admin result |
|---|---|---|---|
| Active Category + active Destination | Published Product | Product Listing and Product Detail available | Product remains manageable |
| Active Category + active Destination | Draft Product | Hidden from Product Listing and Product Detail | Product remains manageable |
| Inactive Category + active Destination | Published Product | Hidden from Product Listing and Product Detail, but homepage popular products can still render it | Product remains manageable |
| Active Category + inactive Destination | Published Product | Hidden from Product Listing and Product Detail, but homepage popular products can still render it | Product remains manageable |
| Archived Category/Destination | Published Product | Hidden from Product Listing and Product Detail, but homepage popular products can still render if loaded by `HomeController` query | Admin/archive behavior remains |
| Active empty Category | No public Products | Filter URL can return empty listing if manually queried | Admin Category remains |
| Active empty Destination | No public Products | Homepage card may still appear if active Destination is included; package count can be zero | Admin Destination remains |

Confirmed issue F24-C01:

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: `$homeProducts` uses `Product::query()->published()->frontendReady()->with(...)->latest()->take(12)->get()` and does not call `publiclyVisible()`.
- Concrete risk: a published Product under an inactive or archived Category/Destination can still appear on the public homepage popular-products section, while Product Listing and Product Detail correctly hide it. This is a public visibility mismatch and an inactive parent leak.
- Recommended future action: in FRONTEND-25/26, plan and implement homepage Product query alignment with `Product::publiclyVisible()` or an approved shared public homepage scope, then add focused regression tests.
- Classification: **confirmed issue, critical**.

Confirmed issue F24-H01:

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: Destination `products_count` uses `withCount(['products' => fn ($query) => $query->published()])`, not the same public visibility constraints as Product Listing.
- Concrete risk: homepage Destination cards can show package counts that include Products later hidden by Product Listing because of inactive/archived Category or Destination constraints.
- Recommended future action: align destination card counts with public Product visibility policy, likely by counting products through a visibility-aware query.
- Classification: **confirmed issue, high**.

## 9. Slug and 404 Behavior

Category:

- Category model has `slug`, but no public Category slug route consumes it.
- Invalid Category slug public behavior is unavailable.
- Product Listing filters use Category IDs, not slugs.

Destination:

- Destination model has `slug`, but no public Destination slug route consumes it.
- Invalid Destination slug public behavior is unavailable.
- Homepage Destination cards include `data-destination-slug` but link by Destination ID filter.

Product:

- Product Detail uses `/products/{product:slug}` and `ProductController::show()` applies `where('slug', $slug)->publiclyVisible()->firstOrFail()`.
- Product Detail invalid slug returns 404 through `firstOrFail()`.

Evidence:

- `routes/frontend.php`, public route definitions.
- `ProductController::show()`, slug lookup and `firstOrFail()`.
- `resources/views/frontend/sections/categories.blade.php`, `data-destination-slug` on cards.

## 10. Controller/Data Flow

Product Listing:

- `ProductController@index` prepares Page Section content, active Categories/Destinations, normalized filters, product query, paginator, empty state, SEO state, and structured-data listing products.
- Blade renders prepared variables and GET forms.

Homepage:

- `HomeController@index` prepares Page Sections, products, categories, destinations, destination cards, FAQs, and global assets.
- Homepage Destination cards are prepared in `HomepageContent::destinationCards()`.

Product Detail:

- `ProductController::show()` prepares Product Detail through `ProductDetailDisplayState`.
- Breadcrumb links to Products, not Category/Destination pages.

Confirmed issue F24-C01 and F24-H01 are the main controller/data-flow gaps.

## 11. Product Query Integrity

| Page/context | Product constraint | Pagination | Eager loading | Risk |
|---|---|---|---|---|
| Product Listing base | `publiclyVisible()` + `frontendListingReady()` | `paginate(9)` | Category, Destination, Prices, Images | Low |
| Product Listing Category filter | `publiclyVisible()` + `whereIn('category_id', selected IDs)` | `paginate(9)` + normalized appends | Category, Destination, Prices, Images | Low |
| Product Listing Destination filter | `publiclyVisible()` + `whereIn('destination_id', selected IDs)` | `paginate(9)` + normalized appends | Category, Destination, Prices, Images | Low |
| Product Detail | `publiclyVisible()` + slug + detail relations | N/A | Category, Destination, Prices, Images, detail child relations | Low |
| Homepage popular products | `published()` + `frontendReady()` | `take(12)` | Category, Destination, Prices, Images, child detail relations | Critical visibility mismatch |
| Homepage Destination cards count | Active Destinations + published Product count | `take(4)` after load in support | Product count only | High count mismatch |

Confirmed safe behavior:

- Product Listing hides draft Products and inactive/archived parent Products.
- Product Listing filter values are normalized and invalid values do not widen results.
- Product Detail direct URL follows Product Listing visibility.

Confirmed gap:

- Homepage popular Product query does not reuse public visibility policy.

## 12. Eager Loading and N+1 Findings

Confirmed:

- Product Listing uses `frontendListingReady()` and eager loads card relations.
- Product Detail eagerly loads rendered detail relations.
- Homepage popular products use `frontendReady()`, which eager loads more relations than the homepage Product Card requires.

Potential risk F24-M01:

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: homepage popular products use `frontendReady()`, eager loading Product Detail-only relations (`highlights`, `features`, `faqs`, `itineraries`, `notes`) even though `frontend.components.product-card` only needs category, destination, prices, and images.
- Concrete risk: unnecessary homepage query and memory cost as Product detail child data grows.
- Recommended future action: after fixing visibility, use the narrower listing-card eager-load contract for homepage product cards unless a homepage section explicitly needs detail relations.
- Classification: **potential risk, medium**.

No fresh query-count instrumentation was committed. No N+1 query count is claimed for FRONTEND-24.

## 13. Pagination

Product Listing:

- Uses Laravel paginator with `paginate(9)`.
- Uses `appends($validQueryParameters)`.
- High-page empty state is prepared by the controller.
- Filtered Category/Destination contexts retain normalized query parameters.

Category/Destination dedicated pages:

- Not present; no page-specific pagination behavior exists.

Confirmed:

- `ProductIndexUiTest` covers nine-products-per-page, tenth product on second page, valid filter persistence, and high-page recovery.

## 14. Filter and Sorting Integration

Supported Product Listing parameters:

- `category[]`
- `destination[]`
- `duration[]`
- `vehicle_type[]`
- `min_price`
- `max_price`
- `sort`
- `page`

Sorting:

- `newest`
- `price_low`
- `price_high`

Confirmed:

- Duration sorting is not enabled.
- Invalid sort falls back to newest.
- Category/Destination filters use active entity IDs from the database.
- Filter query URLs are intentionally `noindex, follow`.

Unavailable:

- No clean Category/Destination slug routes exist, so filter URLs currently carry the Category/Destination experience.

## 15. Backend-to-Blade Contract

Confirmed safe contracts:

- Product Listing Blade receives prepared filter state, active summaries, empty state, paginator, SEO state, and listing products for schema.
- Product Card reads loaded relations and does not execute explicit queries.
- Product Detail Blade receives prepared display state.
- Homepage Destination card partial receives prepared card arrays.

Contract gap:

- `resources/views/frontend/home.blade.php` and `resources/views/frontend/sections/categories.blade.php` call static support helpers for default media/Page Section fallback rendering. This is established existing homepage behavior, but Category/Destination future pages should keep media/default resolution in controller/support state for a cleaner contract.

No Blade database query was confirmed in the inspected Product Listing, Product Detail, Product Card, or homepage Category/Destination partials.

## 16. Category Index Findings

No dedicated public Category index page exists.

Current substitute:

- Homepage search select.
- Product Listing filter modal.

Business/UX implication:

- This may be sufficient for a simple catalog discovery model.
- It is weaker for SEO/AI discovery because Category descriptions are not visible on clean Category URLs.

Classification:

- **Unavailable behavior**, not a confirmed bug.

## 17. Category Detail Findings

No dedicated public Category detail page exists.

Unavailable:

- Category H1.
- Category description.
- Category-specific image/media.
- Category breadcrumb.
- Category clean canonical URL.
- Category direct slug 404 behavior.
- Category-specific empty state.

Potential risk F24-M02:

- File path: `app/Models/Category.php`
- Class/section: Category model fields and `products()` relation.
- Current behavior: Category stores `description` and `slug`, but no public page renders them.
- Concrete risk: useful CMS-owned Category context remains invisible on the public frontend except filter labels, reducing content depth for users and crawlers.
- Recommended future action: decide in FRONTEND-25 whether Category should remain filter-only or receive approved clean route/content treatment.
- Classification: **potential risk, medium**.

## 18. Destination Index Findings

No dedicated public Destination index page exists.

Current substitute:

- Homepage Destination card grid.
- Product Listing Destination filter.

The homepage section is visually closer to a Destination index, but it is not a standalone route and only renders up to four Destination cards through `HomepageContent::destinationCards()`.

## 19. Destination Detail Findings

No dedicated public Destination detail page exists.

Unavailable:

- Destination H1.
- Destination description as full public page context.
- Destination hero image.
- Destination breadcrumb.
- Destination clean canonical URL.
- Destination direct slug 404 behavior.
- Destination-specific empty state.

Potential risk F24-M03:

- File path: `app/Models/Destination.php`
- Class/section: Destination model fields and `products()` relation.
- Current behavior: Destination stores `description`, `slug`, and `image`, but only homepage cards use image/short label behavior and Product Listing filters use ID.
- Concrete risk: destination-specific CMS data is underused for public destination discovery.
- Recommended future action: decide whether Destination remains homepage-card/filter-only or becomes a clean Destination experience with approved route, content hierarchy, and SEO policy.
- Classification: **potential risk, medium**.

## 20. Homepage Integration

Confirmed:

- Homepage search form posts GET to `products.index` with `destination[]` and `category[]`.
- Homepage Destination cards link to `products.index` with a Destination ID filter.
- Inactive and soft-deleted Destinations are excluded from homepage Destination cards by `HomeController@index`.
- Destination card image fallback exists through `default_media.destination`.

Confirmed issue:

- Homepage popular products do not use `publiclyVisible()`.

High-priority risk:

- Homepage Destination card counts do not use full public visibility constraints.

## 21. Product Listing Integration

Confirmed:

- Category filter options load only active Categories.
- Destination filter options load only active Destinations.
- Product results use `Product::publiclyVisible()`.
- Filter labels and remove URLs are backend-prepared.
- Empty states distinguish global, filtered, invalid, and high-page states.
- Product Listing uses shared Product Card partial.

Potential issue:

- Filter URLs are ID-based, not slug-based. This is acceptable for current architecture but weaker for clean public Category/Destination discovery.

## 22. Product Detail Integration

Confirmed:

- Product Detail direct URLs use `publiclyVisible()`.
- Inactive/archived parent Products return 404.
- Product Detail displays Category/Destination names as badges, not links.
- Product Detail breadcrumb links Home > Products > Product, not Category/Destination.
- Product schema includes Product category name from actual Product relation.

No broken Category/Destination detail links were found because no such links are rendered.

## 23. CMS Content Ownership

| Concern | Entity-owned | CMS-owned | Code-owned |
|---|---:|---:|---:|
| Category name/slug/description/is_active | Yes | No | No |
| Category image | No | Possible future via Page Section/global fallback | Current code no entity image |
| Destination name/slug/description/image/is_active | Yes | No | No |
| Homepage destination intro copy | No | Yes, Page Section `home.categories_intro` | Layout/order |
| Product Listing hero/catalog copy | No | Yes, Page Sections `products.index.*` | Query/filter/pagination |
| Product query visibility | No | No | Yes |
| Filter parameter handling | No | No | Yes |
| Pagination count | No | No | Yes |
| SEO policy for filter URLs | No | No | Yes |
| Structured data schema type | No | Global settings toggle | Builder code |

Finding:

- CMS content ownership is clear for Product Listing and homepage intro copy, but Category/Destination entity-owned descriptions are not yet used in a public entity page.

## 24. Category Media Findings

Category has no image field in the inspected model.

Evidence:

- `app/Models/Category.php` fillable fields: `name`, `slug`, `description`, `is_active`.
- No Category public image rendering was found.

Recommendation:

- Do not add a Category image field automatically.
- In FRONTEND-25, decide whether Category remains text/filter-only, uses a shared fallback visual, or needs a future approved schema change.

## 25. Destination Media Findings

Destination supports image.

Evidence:

- `app/Models/Destination.php` fillable includes `image`.
- `resources/views/frontend/sections/categories.blade.php` renders `destination['image_url']` with lazy loading and async decoding.
- `HomepageContent::destinationCard()` maps `image` to `asset('storage/' . $destination->image)`.
- Fallback uses `default_media.destination`.

Potential risk:

- Destination images are currently used on homepage cards only, not Product Listing metadata/Open Graph or clean Destination pages.
- No browser/image payload test was run in FRONTEND-24.

## 26. Information Hierarchy

Current hierarchy:

- Product Listing: hero H1, breadcrumb, catalog title, discovery controls, filters, grid, pagination/empty state.
- Homepage Destination card section: section heading, description, card grid.
- Product Detail: breadcrumb, product badges, product H1, summary.

Gaps:

- No Category/Destination page hierarchy exists.
- Category/Destination descriptions are not part of a dedicated information hierarchy.
- Destination homepage card section uses `home.categories_intro` legacy key while rendering Destinations; documentation acknowledges this legacy sync.

## 27. Empty-State Findings

| Context | State | Current behavior | Recommended policy |
|---|---|---|---|
| Product Listing | No public products | General empty listing | Keep |
| Product Listing Category filter | Active Category with no public products | Filtered empty state | Keep unless clean Category page policy changes |
| Product Listing Destination filter | Active Destination with no public products | Filtered empty state | Keep unless clean Destination page policy changes |
| Homepage Destination cards | No active destinations | `No destinations available yet.` | Keep |
| Dedicated Category page | Not present | N/A | Approval needed before deciding 404 vs empty page |
| Dedicated Destination page | Not present | N/A | Approval needed before deciding 404 vs empty page |

Finding:

- Empty states are safe for filter-based architecture.
- Entity-specific empty copy is not available because no dedicated entity page exists.

## 28. Breadcrumb and Internal Linking

Confirmed:

- Product Listing breadcrumb: Home > Products.
- Product Detail breadcrumb: Home > Products > Product.
- Homepage Destination cards link to Product Listing filtered by Destination ID.
- Product Card detail links are crawlable anchors.

Unavailable:

- Category/Destination breadcrumb hierarchy.
- Category/Destination breadcrumb schema.
- Clean anchor links to Category/Destination detail pages.

No broken public Category/Destination detail links were found.

## 29. Responsive Findings

Confirmed by source/tests:

- Product Listing responsive grid CSS contract is covered by `ProductIndexUiTest`.
- Homepage Destination card images use lazy loading and async decoding.
- Product Listing cards have stable image dimensions.

Unavailable:

- No live browser viewport QA was run in FRONTEND-24.
- No dedicated Category/Destination page responsive behavior exists.

Classification:

- **Unavailable/unverified behavior** for live Category/Destination responsive QA.

## 30. Accessibility Findings

Confirmed:

- Product Listing has one H1.
- Filter controls use labels/fieldsets/legends.
- Active filter remove links have descriptive aria labels.
- Product Listing pagination uses accessible navigation.
- Homepage Destination card links have `aria-label` from prepared card data.
- Product Detail renders semantic breadcrumb list.

Gaps:

- Dedicated Category/Destination page accessibility is unavailable.
- Homepage popular product category tabs are client-side controls over homepage products and inherit the `homeProducts` visibility issue.
- No real keyboard/browser or screen-reader test was run.

## 31. SEO Rendering Findings

Current policy:

- Base `/products` is indexable.
- Plain valid pagination can self-canonicalize.
- Filter/sort/price/invalid/unsupported/high-page query URLs are `noindex, follow` and canonicalize to `/products`.

Implication:

- Category/Destination filter URLs are not intended as indexable clean landing pages.
- Because no dedicated clean Category/Destination pages exist, Category/Destination entity descriptions are not currently indexable as standalone public pages.

Potential risk F24-M04:

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Method: `listingSeoState()`
- Current behavior: Category/Destination filter URLs are treated as query-index risk and get `noindex, follow`.
- Concrete risk: if business expects indexable Category/Destination experiences, current filter-only URL policy will not satisfy that objective.
- Recommended future action: in FRONTEND-25, decide whether clean Category/Destination routes are needed before changing canonical/indexability rules.
- Classification: **potential risk, medium**.

## 32. Structured-Data Readiness

Current:

- Product Listing can render BreadcrumbList and ItemList through `StructuredDataBuilder`.
- Product Detail can render BreadcrumbList, Product schema, and FAQPage when applicable.
- Product schema includes Product category name when present.

Not present:

- No CollectionPage schema for Category/Destination pages.
- No BreadcrumbList for Category/Destination entity hierarchy.
- No Place/TouristDestination schema for Destination pages.

Recommendation:

- Do not add `TouristDestination` automatically.
- Only consider CollectionPage/ItemList/BreadcrumbList after route/content policy approval.
- Only consider Place/TouristDestination if accurate location data exists.

## 33. AI Discovery Findings

Confirmed:

- Product Listing exposes Product names, category, destination, duration, price state, Product Detail links, pagination links, and empty state in server-rendered HTML.
- Homepage Destination cards expose Destination names and package labels in server-rendered HTML.
- Product Detail exposes Product destination/category context in server-rendered HTML.

Gaps:

- Category descriptions are not exposed on a public Category page.
- Destination descriptions are only used in homepage Destination card aria labels, not as visible destination detail content.
- Filter URLs are not indexable landing pages by current SEO policy.

AI discovery readiness is not a ranking guarantee.

## 34. Performance Findings

Confirmed:

- Product Listing paginates at 9 products and eager loads listing-card relations.
- Product Detail eager loads rendered relations.
- Homepage Destination card grid limits to four cards in support code.

Potential risks:

- Homepage popular products eager load more relations than the card needs.
- Homepage Destination card counts use published count only and may require a more complex visibility-aware count.
- No Lighthouse, browser network, image payload, or production MySQL query measurement was run.

## 35. Security/Data-Integrity Findings

Confirmed safe:

- Product Listing normalizes array filters and non-negative integer price filters.
- Invalid Category/Destination filter values do not widen listing results.
- Product Listing CMS copy is covered by escaping tests.
- Product Detail escapes overview rich text through `nl2br(e(...))`.
- Category/Destination delete integrity is protected by soft deletes and restricted parent FKs.

Confirmed issue:

- Homepage product visibility mismatch can expose inactive/archived parent Products publicly.

No raw SQL with user input or open redirect risk was confirmed in the inspected Category/Destination public experience.

## 36. Testing Gaps

Existing focused tests passed:

- `php artisan test --filter=ProductIndexUiTest`: passed, 32 tests, 303 assertions.
- `php artisan test --filter=HomepageCmsContentTest`: passed, 17 tests, 182 assertions.
- `php artisan test --filter=ProductDetail`: passed, 29 tests, 341 assertions.
- `php artisan test tests\Feature\Database\CategoryDestinationDeleteIntegrityTest.php`: passed, 9 tests, 64 assertions.

Future tests needed:

1. Homepage hides published Products under inactive Category.
2. Homepage hides published Products under archived Category.
3. Homepage hides published Products under inactive Destination.
4. Homepage hides published Products under archived Destination.
5. Homepage Product tabs exclude inactive/archived Category labels.
6. Homepage Destination card package counts match Product Listing public visibility.
7. Destination card with zero public Products renders approved count/empty behavior.
8. Category filter URL remains safe with inactive Category ID.
9. Destination filter URL remains safe with inactive Destination ID.
10. Future clean Category route behavior if approved.
11. Future clean Destination route behavior if approved.
12. Future Category/Destination metadata if approved.
13. Future Category/Destination one-H1 and breadcrumb tests if approved.
14. Future Category/Destination no-query-in-Blade tests if approved.
15. Future N+1/query-count tests for Category/Destination pages if approved.

## 37. Critical Issues

### F24-C01 - Homepage popular products can bypass public parent visibility

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: homepage products use `Product::published()->frontendReady()` without `publiclyVisible()`.
- Concrete risk: a published Product under inactive/archived Category or Destination can appear publicly on homepage even though listing/detail hide it.
- Recommended future action: align homepage Product query with public visibility policy in a scoped implementation step and add regression tests.
- Status: confirmed issue.

## 38. High-Priority Issues

### F24-H01 - Homepage Destination package counts can disagree with public Product Listing

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: `withCount(['products' => fn ($query) => $query->published()])` counts published Products without Category/Destination public visibility constraints.
- Concrete risk: a homepage Destination card can advertise packages that disappear after the visitor clicks the filtered listing.
- Recommended future action: count only Products that satisfy the approved public Product visibility policy.
- Status: confirmed issue.

## 39. Medium-Priority Issues

### F24-M01 - Homepage product query eager loads detail-only relations

- File path: `app/Http/Controllers/Frontend/HomeController.php`
- Class/method: `HomeController@index`
- Current behavior: `frontendReady()` loads Product Detail child relations for homepage Product Cards.
- Concrete risk: avoidable query/memory cost as Products gain more child rows.
- Recommended future action: use a listing-card/home-card eager-load scope after visibility alignment.
- Status: potential risk.

### F24-M02 - Category entity descriptions are not visible in public Category experience

- File path: `app/Models/Category.php`
- Class/section: Category fields
- Current behavior: `description` exists but no public Category page renders it.
- Concrete risk: thin Category discovery and weaker crawler/user context.
- Recommended future action: decide whether filter-only Category UX is sufficient before implementing routes.
- Status: potential risk.

### F24-M03 - Destination entity descriptions/images are underused outside homepage cards

- File path: `app/Models/Destination.php`
- Class/section: Destination fields
- Current behavior: Destination `description`, `slug`, and `image` are not used by a clean detail route.
- Concrete risk: destination-specific public content remains shallow.
- Recommended future action: decide whether clean Destination pages are needed.
- Status: potential risk.

### F24-M04 - Category/Destination filter URLs are intentionally not indexable

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Method: `listingSeoState()`
- Current behavior: active filter query URLs use `noindex, follow` and canonicalize to `/products`.
- Concrete risk: if the business expects Category/Destination landing pages to rank, current architecture does not provide indexable clean pages.
- Recommended future action: decide SEO indexability policy before route/schema implementation.
- Status: potential risk.

## 40. Low-Priority Issues

### F24-L01 - Legacy homepage section key names can confuse future Category/Destination ownership

- File path: `resources/views/frontend/sections/categories.blade.php`
- Component/section: Homepage Destination card section
- Current behavior: section id/key uses `home.categories_intro` while rendering Destination cards.
- Concrete risk: future maintainers may confuse Category and Destination content ownership.
- Recommended future action: document or rename only in a future approved compatibility step; do not rename automatically.
- Status: potential risk.

## 41. Scores

| Area | Score | Reason |
|---|---:|---|
| Category Architecture | 70 | Filter-only architecture is coherent, but Category slug/description have no public page. |
| Destination Architecture | 76 | Homepage cards plus filters are useful, but no clean Destination page exists. |
| Visibility Integrity | 62 | Listing/detail pass, but homepage Product query can leak inactive/archived parent Products. |
| Query/Data Flow | 74 | Listing/detail contracts are strong; homepage Product and count queries diverge. |
| Product Query Integrity | 82 | Product Listing and Product Detail use `publiclyVisible()`; homepage does not. |
| Pagination Readiness | 84 | Product Listing pagination is robust; no entity-page pagination exists. |
| Filter/Sorting Consistency | 86 | Normalized Category/Destination filters are solid and tested. |
| CMS Content Ownership | 78 | Listing/homepage Page Sections are clear; entity descriptions are underused publicly. |
| Media Readiness | 72 | Destination media exists with fallback; Category media does not exist and needs approval. |
| Empty-State Readiness | 78 | Listing empty states are strong; entity-specific empty states are unavailable. |
| Responsive Readiness | 72 | Source/tests support listing responsiveness; no live browser or entity page QA. |
| Accessibility Readiness | 78 | Listing controls are accessible; entity pages and real AT/browser QA unavailable. |
| SEO Rendering Readiness | 74 | Listing policy is strong; Category/Destination clean SEO pages do not exist. |
| Structured-Data Readiness | 74 | Listing/detail schema exists; Category/Destination schema requires approved pages/data. |
| AI Discovery Readiness | 76 | Server-rendered product/destination info exists; entity descriptions are not standalone. |
| Performance Readiness | 73 | Listing pagination/eager loading good; homepage eager loading/count risks remain. |
| Testing Readiness | 78 | Focused baseline tests exist; homepage parent-visibility tests are missing. |
| Overall Category/Destination Readiness | 74 | Current filter-based UX is usable, but homepage visibility mismatch blocks higher confidence. |

## 42. Recommended FRONTEND-25 to FRONTEND-30 Roadmap

| Step | Objective | Files | Tests | Rollback boundary |
|---|---|---|---|---|
| FRONTEND-25 | Public Category & Destination Data Flow, Visibility & Experience Fix Plan | Report/planning only; likely references `HomeController`, `ProductController`, models, views | No runtime tests unless verifying | Plan-only report |
| FRONTEND-26A | Public Category visibility and query integrity implementation | Likely `HomeController`, Product public scopes/support, focused tests | Homepage Category parent visibility, listing regression | Revert scoped query/test changes |
| FRONTEND-26B | Public Destination visibility and query integrity implementation | Likely `HomeController`, `HomepageContent`, focused tests | Destination count/link consistency, listing regression | Revert scoped query/test changes |
| FRONTEND-27 | Public Category & Destination layout/content hierarchy decision | If approved: views/routes/controllers/support; otherwise report only | One H1, breadcrumb, no-query Blade | Revert approved UX files |
| FRONTEND-28 | Public Category & Destination media, product grid, empty states | If approved: views/support/CSS/tests | media fallback, empty states, pagination | Revert media/layout files |
| FRONTEND-29 | Public Category & Destination accessibility, SEO, structured data | If approved: controller/support/schema/meta/tests/docs | canonical/robots/schema/accessibility tests | Revert SEO/schema files |
| FRONTEND-30 | Final performance, regression, release verification | Report only unless blockers approved | focused/full tests/build/browser if available | Report-only or revert approved fixes |

Combining 26A and 26B may be safe because Category and Destination visibility share the same `Product::publiclyVisible()` policy and both homepage risks are in `HomeController@index`. It should only be combined if FRONTEND-25 confirms the fix can remain a shared query-scope alignment without route/schema changes.

## 43. Files Inspected

- `AGENTS.md`
- `routes/frontend.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Support/HomepageContent.php`
- `app/Support/ProductListingContent.php`
- `app/Support/ProductDetailDisplayState.php`
- `app/Support/StructuredDataBuilder.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/partials/site-structured-data.blade.php`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/database/data-integrity.md`
- `docs/seo/README.md`
- `docs/global-structured-data-business-schema.md`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Database/CategoryDestinationDeleteIntegrityTest.php`
- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`
- `ai/reports/frontend/frontend-11-product-listing-filter-search-pagination-ux-implementation-report.md`
- `ai/reports/frontend/frontend-13-product-listing-accessibility-seo-ai-discovery-rendering-report.md`
- `ai/reports/frontend/frontend-14-public-product-listing-final-performance-regression-release-verification-report.md`
- `ai/reports/frontend/frontend-14b-public-product-listing-browser-qa-performance-measurement-report.md`
- `ai/reports/frontend/frontend-15-public-product-detail-uiux-data-flow-audit.md`
- `ai/reports/frontend/frontend-17a-product-detail-visibility-query-integrity-implementation-report.md`
- `ai/reports/frontend/frontend-23-product-detail-final-performance-regression-release-verification-report.md`

## 44. Files Recommended for Future Changes

Planning only; do not treat as approved.

- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Support/HomepageContent.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- Potentially `app/Models/Product.php` if a reusable homepage/listing card scope is approved.
- Potentially `routes/frontend.php`, new frontend controllers, views, CSS, and docs only if clean Category/Destination pages are approved.

## 45. Approval Points

1. Whether dedicated Category pages should exist.
2. Whether dedicated Destination pages should exist.
3. Whether query-filter URLs should canonicalize to future clean routes.
4. Inactive Category public behavior outside Product Listing/Detail.
5. Inactive Destination public behavior outside Product Listing/Detail.
6. Active but empty Category behavior.
7. Active but empty Destination behavior.
8. Category image strategy.
9. Destination hero image strategy.
10. Shared versus separate layout.
11. Shared versus separate controller/query.
12. Pagination count.
13. Filter scope.
14. Related Product grid behavior.
15. CMS intro/final CTA ownership.
16. Breadcrumb hierarchy.
17. SEO indexability policy.
18. CollectionPage/ItemList schema.
19. TouristDestination schema eligibility.
20. Future location/map data.

## 46. Risks

- Homepage visibility mismatch can expose Products that listing/detail hide.
- Destination card counts can mislead users if counts include Products hidden by public listing policy.
- Clean Category/Destination SEO is unavailable until approved routes/content exist.
- Browser, keyboard, screen-reader, Lighthouse, image payload, and production MySQL query timing were not measured.
- Existing untracked FRONTEND-23 report is outside this task and was not modified.

## 47. Rollback Notes

Only this report was created. Rollback for FRONTEND-24 is to remove:

- `ai/reports/frontend/frontend-24-public-category-destination-experience-audit.md`

No runtime rollback is required because no runtime code changed.

## 48. Verification Result

Commands run:

- `git branch --show-current`: `feature/ai-foundation`
- `git status --short`: before report creation showed pre-existing `?? ai/reports/frontend/frontend-23-product-detail-final-performance-regression-release-verification-report.md`
- `git diff --check`: passed before report creation
- `git diff --stat`: no tracked diff before report creation
- `php artisan route:list --path=products`: confirmed public Product routes
- `php artisan route:list --path=categories -v`: confirmed only admin Category routes
- `php artisan route:list --path=destinations -v`: confirmed only admin Destination routes
- `php artisan test --filter=ProductIndexUiTest`: passed, 32 tests, 303 assertions
- `php artisan test --filter=HomepageCmsContentTest`: passed, 17 tests, 182 assertions
- `php artisan test --filter=ProductDetail`: passed, 29 tests, 341 assertions
- `php artisan test tests\Feature\Database\CategoryDestinationDeleteIntegrityTest.php`: passed, 9 tests, 64 assertions

Not run:

- Full `php artisan test`, because only an audit report changed.
- `npm run build`, because no runtime frontend files changed.
- Browser/Lighthouse QA, because this step is audit/read-only and no available browser tooling was used.

Final git verification is recorded after report creation in the final response.

## 49. Recommended Next Step

FRONTEND-25: Public Category & Destination Data Flow, Visibility & Experience Fix Plan.

The first implementation plan should prioritize F24-C01 and F24-H01 before any route, layout, media, SEO, or structured-data expansion.
