# FRONTEND-25 Category & Destination Data Flow, Visibility & Experience Fix Plan

Date: 2026-06-16
Branch: `feature/ai-foundation`
Mode: planning only, read-only, report-only

## 1. Executive Summary

FRONTEND-25 converts the FRONTEND-24 audit into a focused implementation plan. No runtime code was changed.

Recommended near-term architecture:

- **Category:** keep **filter-first/hybrid-ready**. Do not create dedicated Category pages yet. First fix the shared public visibility/data-flow issues and keep Category represented by Product Listing filters, homepage search, Product Card metadata, and Product Detail badges. Revisit clean Category routes only after business confirms Category pages have enough content value without an entity image field.
- **Destination:** use **hybrid-ready** planning. First fix shared visibility/data-flow issues, then request approval for clean Destination detail routes because Destination already owns `slug`, `description`, and `image`, making it stronger than Category as a public travel-discovery page candidate.
- **FRONTEND-26 should be combined** as `FRONTEND-26: Public Category & Destination Visibility & Query Integrity Implementation`, because the immediate critical/high findings share the same implementation surface: `HomeController`, homepage Product query, homepage Category/Destination options/counts, `HomepageContent`, and focused homepage/listing regression tests.

Priority:

1. Fix homepage Product visibility leak.
2. Fix homepage Destination package-count mismatch.
3. Reduce homepage Product eager loading to listing-card relations.
4. Only after those are stable, decide whether to add clean Category/Destination routes.

## 2. Planning Scope and Method

This plan used:

- FRONTEND-24 as the primary baseline.
- Product Listing reports FRONTEND-07 through FRONTEND-14B.
- Product Detail report FRONTEND-23.
- Product, Category, Destination, Page Sections, SEO, structured-data, and database integrity documentation.
- Actual routes, controllers, models, support classes, homepage/listing/detail views, and relevant test inventory.

Read-only boundary:

- No runtime implementation.
- No route/controller/model/Blade/CSS/JS/test/database changes.
- No previous reports modified.
- Only this planning report was created.

## 3. FRONTEND-24 Findings Used

| Finding group | FRONTEND-24 evidence | Current behavior | Expected behavior | Recommended step | Risk if deferred |
|---|---|---|---|---|---|
| Already safe | `ProductController@index`, `Product::publiclyVisible()` | Product Listing hides draft and inactive-parent Products | Keep | FRONTEND-26 regression coverage | Low |
| Already safe | `ProductController::show()` | Product Detail direct slug access hides draft/inactive-parent Products | Keep | FRONTEND-26 regression coverage | Low |
| Confirmed critical | `HomeController@index` | Homepage Products use `published()->frontendReady()` | Use public visibility policy | FRONTEND-26 | Public inactive-parent Product leak |
| Confirmed high | `HomeController@index` | Destination card counts use published count only | Count public-visible Products | FRONTEND-26 | Misleading public counts |
| Medium | `HomeController@index` | Homepage Products eager load detail-only relations | Load only card relations | FRONTEND-26 | Avoidable query/memory cost |
| Content gap | `Category` model | Category description/slug not rendered on public page | Decide filter-only vs clean route | FRONTEND-27 decision | Thin Category discovery |
| Content gap | `Destination` model | Destination description/image underused beyond homepage cards | Decide clean Destination detail route | FRONTEND-27 decision | Weak Destination discovery |
| SEO gap | `listingSeoState()` | Filter URLs are `noindex, follow` | Keep unless clean routes approved | FRONTEND-29 | Non-indexable entity discovery |
| Accessibility gap | No entity page | Dedicated entity accessibility unavailable | Add tests if pages approved | FRONTEND-29 | Unknown entity-page accessibility |
| Performance gap | No query count for entity pages | No entity route measurements | Define future measurement scenarios | FRONTEND-30 | Unknown production query profile |
| Approval decision | FRONTEND-24 approval list | Dedicated pages and schema not approved | Request explicit approval | FRONTEND-27/29 | Scope creep |

## 4. Confirmed Existing Architecture

Actual public routes:

- `/` -> `home` -> `HomeController@index`
- `/products` -> `products.index` -> `ProductController@index`
- `/products/{product:slug}` -> `products.show` -> `ProductController@show`

No public Category or Destination routes exist. `php artisan route:list --path=categories -v` and `--path=destinations -v` show admin routes only.

Actual public Category/Destination surfaces:

- Homepage search selects.
- Homepage popular-product tabs.
- Homepage Destination cards.
- Product Listing filters.
- Product Card metadata.
- Product Detail badges/schema category text.

## 5. Category Current Experience

Category is currently filter-first:

- Loaded as active records for homepage search and Product Listing filters.
- Used as homepage Product tab labels derived from rendered Products.
- Displayed as Product Card and Product Detail metadata.
- Not linked as a clean public route.
- `slug` and `description` exist but are not public page content.
- No entity image field exists.

## 6. Destination Current Experience

Destination is currently filter-first with stronger public card treatment:

- Loaded as active records for homepage search and Product Listing filters.
- Rendered as homepage Destination cards through `HomepageContent::destinationCards()`.
- Cards link to `/products?destination[]=id`.
- `slug`, `description`, and `image` exist.
- No clean Destination public route exists.

## 7. Category Architecture Options

| Entity | Option | Benefits | Risks | Recommendation |
|---|---|---|---|---|
| Category | Filter-only | Minimal, reuses Product Listing, no route/controller/page work | Category descriptions remain invisible; query URLs stay `noindex` | Keep as near-term fallback |
| Category | Dedicated route/page | Clean URL, entity-specific copy, stronger internal linking | New route/controller/view; no image field; duplicate query risk | Defer pending approval |
| Category | Hybrid clean route + shared listing query | Clean URL while reusing Product Card/query/pagination | Needs careful route context and filter reset behavior | Recommended future option only if approved |

## 8. Destination Architecture Options

| Entity | Option | Benefits | Risks | Recommendation |
|---|---|---|---|---|
| Destination | Filter-only | Minimal, current homepage cards already link to Product Listing | Destination description/image underused; query URLs non-indexable | Keep until visibility fixed |
| Destination | Dedicated route/page | Clean travel-discovery URL; can use existing image/description | New route/controller/view; SEO/schema decisions needed | Strong future candidate |
| Destination | Hybrid clean route + shared listing query | Clean URL plus reused Product grid/filter/pagination | More planning needed for query canonical/reset | Recommended future architecture if approved |

## 9. Recommended Category Architecture

Recommendation: **filter-first, hybrid-ready**.

Reasoning:

- Current code already supports Category filters safely.
- Category has no image field, so a full visual destination-style page may be thin.
- Category `description` can support future clean pages, but the immediate risk is homepage visibility, not Category route absence.
- A future hybrid route can reuse the Product Listing query/card grid if business wants crawlable Category pages.

Approval required before route creation:

- `/categories/{category:slug}` or `/products/categories/{category:slug}`.
- Category empty-page policy.
- Category image strategy.
- Category indexability.

## 10. Recommended Destination Architecture

Recommendation: **hybrid-ready clean Destination detail route after data-flow fixes**.

Reasoning:

- Destination already owns `slug`, `description`, and `image`.
- Destination is travel-discovery oriented and already presented visually on homepage.
- A clean Destination page can provide better user context, SEO, AI discovery, and internal linking than a query URL.
- The Product grid should still reuse shared Product Listing query logic and Product Card components.

Approval required before route creation:

- `/destinations/{destination:slug}` or equivalent.
- Destination active-empty policy.
- Destination hero/image strategy.
- Destination canonical/indexability.

## 11. Route Strategy

No route changes in FRONTEND-25.

Future route candidates:

| Route name | URI | Parameter | Controller/method | Model binding | Visibility | Canonical intent | Breadcrumb intent |
|---|---|---|---|---|---|---|---|
| `categories.show` | `/categories/{category:slug}` | `category:slug` | Future `CategoryController@show` | Explicit active lookup preferred | Active + not soft-deleted only | Self canonical if approved | Home > Products/Categories > Category |
| `destinations.show` | `/destinations/{destination:slug}` | `destination:slug` | Future `DestinationController@show` | Explicit active lookup preferred | Active + not soft-deleted only | Self canonical if approved | Home > Destinations > Destination |

Do not change:

- `products.index`
- `products.show`
- Existing Product Detail URL shape

## 12. Visibility Policy

| Entity | State | Public route | Filter/Homepage | Admin |
|---|---|---|---|---|
| Category | Active, not soft-deleted | Available if clean route approved | Visible in filters/homepage controls | Visible/manageable |
| Category | Inactive | 404 if clean route exists | Excluded from filters/homepage | Visible/manageable |
| Category | Soft-deleted | 404 if clean route exists | Excluded from filters/homepage | Archived/manageable |
| Category | Invalid slug | 404 | N/A | N/A |
| Destination | Active, not soft-deleted | Available if clean route approved | Visible in filters/homepage cards | Visible/manageable |
| Destination | Inactive | 404 if clean route exists | Excluded from filters/homepage | Visible/manageable |
| Destination | Soft-deleted | 404 if clean route exists | Excluded from filters/homepage | Archived/manageable |
| Destination | Invalid slug | 404 | N/A | N/A |

Do not introduce global model scopes. Use explicit public query scopes/lookups so admin behavior remains unaffected.

## 13. Active/Inactive Entity Policy

Public entity availability should require:

- `is_active = true`
- `deleted_at IS NULL`

Inactive entities:

- Remain in admin/database.
- Do not appear in public filters.
- Do not appear on homepage search/card/tab surfaces.
- Return 404 on clean routes if routes are approved.
- Do not keep public Products discoverable through homepage, listing, detail, or entity pages.

## 14. Invalid Slug and 404 Policy

If clean routes are approved:

- Use explicit active lookup by slug and `firstOrFail()` or route binding constrained in controller.
- Invalid slug returns 404.
- Inactive/soft-deleted entity slug returns 404.
- No redirect from invalid slug to `/products`.
- No fallback to ID access.

## 15. Active Empty Category Policy

Recommendation: **200 with tailored empty state** if a clean Category route is approved.

Reason:

- Active Category can have descriptive value even before Products are published.
- URLs remain stable.
- Product availability can change without route churn.

Policy:

- Show Category name and description.
- Show "No packages are currently available in this category."
- Provide recovery actions to all Products and related Destination filters if available.
- Use `noindex, follow` if the page has weak/empty content and no Product list, unless business approves indexability.

## 16. Active Empty Destination Policy

Recommendation: **200 with tailored empty state** if a clean Destination route is approved.

Reason:

- Destination image/description can still help visitors.
- Destination pages are stronger candidates for travel discovery than Category pages.

Policy:

- Show Destination name, description, image/fallback.
- Show "No packages are currently available for this destination."
- Provide all Products and WhatsApp/contact recovery.
- Consider `noindex, follow` for empty pages until useful content/Product links exist.

## 17. Product Query Reuse Strategy

Target flow:

```text
entity context or filter context
-> active entity lookup
-> Product::publiclyVisible()
-> fixed category/destination constraint
-> allowed filters/sort
-> frontendListingReady()
-> paginate(9)
-> shared Product Card/grid
```

Smallest safe approach:

- FRONTEND-26: directly align homepage queries with `publiclyVisible()` and `frontendListingReady()` without building a broad repository.
- Future clean entity pages: extract narrow Product Listing query preparation only if duplication becomes real.

## 18. Public Visibility Scope Reuse

Reuse `Product::publiclyVisible()` for:

- Homepage popular Products.
- Homepage Product tab source.
- Homepage Category/Destination counts.
- Future entity-page Product grids.
- Future entity-page filter option constraints.

Do not create a global model scope. Admin must still access draft/inactive-parent Products.

## 19. Filter and Sorting Policy

| Context | Fixed filter | Available filters | Reset target |
|---|---|---|---|
| Product Listing | None | Category, Destination, duration, vehicle, IDR price, sort | `/products` |
| Future Category page | Category fixed by route | Destination, duration, vehicle, IDR price, sort | Category clean route |
| Future Destination page | Destination fixed by route | Category, duration, vehicle, IDR price, sort | Destination clean route |
| Homepage search | None | Category and Destination selects only | `/products` |

No free-text search is planned here because current Product Listing does not support it.

## 20. Pagination Policy

Recommendation:

- Reuse 9 Products per page for any future entity Product grid.
- Keep normalized valid query parameters.
- High-page behavior should render a tailored page-empty state with recovery to page 1 of the same route context.
- Plain entity page 2 can self-canonicalize only if route is clean, valid, and has results.
- Filter/sort/price query permutations should canonicalize to the clean entity route and use `noindex, follow`.

## 21. Backend-to-Blade Contract

Future Category view state:

- `category`
- `title`
- `description`
- `products`
- `publicProductCount`
- `filterState`
- `resetUrl`
- `breadcrumbState`
- `metadataState`
- `emptyState`
- optional generic CMS CTA/content

Future Destination view state:

- `destination`
- `title`
- `description`
- `mediaState`
- `products`
- `publicProductCount`
- `filterState`
- `resetUrl`
- `breadcrumbState`
- `metadataState`
- `emptyState`
- optional generic CMS CTA/content

Blade must not query Products, parse raw request input, build canonical URLs, determine active status, load relations, sort collections, or query Page Sections.

## 22. Category Content Ownership

Category-owned:

- `name`
- `slug`
- `description`
- `is_active`

Code-owned:

- visibility
- route behavior
- query filters
- sorting
- pagination
- metadata policy
- empty-state behavior

CMS/Page Sections:

- optional generic intro/final CTA only, not per-Category content.

## 23. Destination Content Ownership

Destination-owned:

- `name`
- `slug`
- `description`
- `image`
- `is_active`

Code-owned:

- visibility
- route behavior
- Product query
- filters
- pagination
- metadata/canonical/robots
- media fallback rules

CMS/Page Sections:

- optional generic Destination index/detail intro/final CTA only.

Do not move Destination-specific description/image into Page Sections.

## 24. Page Sections/CMS Integration

No Page Section key should be added in FRONTEND-25 or FRONTEND-26.

Future possible keys, approval required:

- `categories.show.hero`
- `categories.show.catalog`
- `destinations.show.hero`
- `destinations.show.catalog`

Rules:

- Do not reuse `home.categories_intro` for clean Destination pages.
- Do not store query/filter configuration in CMS.
- Use `firstOrCreate`-style seed/sync only; do not overwrite admin-edited content.
- Generic section copy only; entity content remains on Category/Destination records.

## 25. Category Media Strategy

Recommendation: **Text-first Category**.

Options:

- A: text-first Category page with no entity image. Recommended if Category route is approved.
- B: shared generic Category header image from global/Page Section settings. Optional later.
- C: future Category image field. Requires migration and explicit approval; not recommended until business value is clear.

## 26. Destination Media Strategy

Recommendation: use Destination-owned image as primary media if clean Destination routes are approved.

Media state:

- primary image: `destinations.image`
- fallback: `default_media.destination`
- alt: Destination name or fallback asset alt
- loading: hero image may be eager/priority if first viewport; below-grid images lazy
- Open Graph: Destination image first, fallback image second
- object-fit/aspect ratio: code-owned display decision

## 27. Category Layout Plan

Future Category page hierarchy:

1. Breadcrumb
2. Category H1
3. Category description
4. Product count/context
5. Optional Destination/duration/vehicle/price/sort filters
6. Product grid
7. Pagination
8. Empty state
9. Optional generic final CTA

No hero image required for text-first Category.

## 28. Destination Layout Plan

Future Destination page hierarchy:

1. Breadcrumb
2. Destination image/hero
3. Destination H1
4. Destination description
5. Product count/context
6. Optional Category/duration/vehicle/price/sort filters
7. Product grid
8. Pagination
9. Empty state
10. Optional generic final CTA

Destination image should be useful, inspectable, and not purely decorative.

## 29. Product Card/Grid Reuse

Reuse:

- `frontend.components.product-card`
- existing price display component
- existing listing grid CSS where possible
- existing Product Detail links
- Product image fallback behavior

Avoid:

- Category-specific Product Card copy.
- Destination-specific Product Card duplicate markup.
- Loading Product Detail child relations for cards.

## 30. Empty-State Plan

Category:

- Title: `No packages are currently available in this category.`
- Text: explain that packages will appear when published.
- Recovery: View all Products; optionally browse Destinations.

Destination:

- Title: `No packages are currently available for this destination.`
- Text: explain that packages will appear when available.
- Recovery: View all Products; optionally contact via existing CTA if global WhatsApp is available.

No fake Products, filler Product cards, or misleading pagination.

## 31. Breadcrumb/Internal-Link Plan

Near term:

- Keep Product Detail breadcrumb as Home > Products > Product.
- Keep Product Listing breadcrumb as Home > Products.

If clean routes approved:

- Category: Home > Products or Categories > Current Category.
- Destination: Home > Destinations > Current Destination.
- Product Detail can later include Destination/Category only after clean routes are stable and approved.

Do not update Product Detail breadcrumb in FRONTEND-26.

## 32. Metadata Plan

Category clean route:

- Title: `{Category Name} Packages in Bintan`
- Description: Category description, fallback to safe generic category package copy.
- Image: global fallback unless future Category media approved.

Destination clean route:

- Title: `{Destination Name} Travel Packages`
- Description: Destination description, fallback to safe generic destination travel copy.
- Image: Destination image, fallback to `default_media.destination` or global default OG image.

No new SEO fields are required.

## 33. Canonical/Robots Policy

| URL context | Robots | Canonical | Indexability |
|---|---|---|---|
| `/products` | `index, follow` | `/products` | Indexable |
| `/products?page=2` with results and no filters | `index, follow` | self | Indexable |
| `/products?category[]=id` | `noindex, follow` | `/products` or future clean Category route if approved | Non-indexable query |
| `/products?destination[]=id` | `noindex, follow` | `/products` or future clean Destination route if approved | Non-indexable query |
| Future active Category clean route | `index, follow` if useful content/products exist | self | Candidate indexable |
| Future active Destination clean route | `index, follow` if useful content/products exist | self | Candidate indexable |
| Future entity route with sort/price/filter query | `noindex, follow` | clean entity route | Non-indexable query |
| Inactive/invalid entity | 404 | N/A | Not indexable |

## 34. Structured-Data Plan

| Schema | Category | Destination | Decision/reason |
|---|---:|---:|---|
| BreadcrumbList | Yes if clean route approved | Yes if clean route approved | Must match visible breadcrumb |
| ItemList | Yes if Product grid visible | Yes if Product grid visible | Reuse existing minimal listing pattern |
| CollectionPage | Possible | Possible | Defer until clean route/page exists |
| TouristDestination | No | Deferred | Current data lacks geo/address/rich destination facts |
| Product | No on entity pages | No on entity pages | Product schema remains Product Detail concern |

Do not invent coordinates, ratings, reviews, address, opening hours, or tourist-type data.

## 35. Accessibility Plan

Future implementation must verify:

- one H1
- breadcrumb nav
- labeled filters
- visible focus
- Product Card headings
- image alt text
- result count context
- empty-state heading
- pagination nav
- no nested interactive elements
- touch targets
- server-rendered links
- no hidden duplicate H1

Do not claim full WCAG compliance without browser/AT verification.

## 36. AI Discovery Plan

Category visible HTML should include:

- Category name
- Category description
- Product count/context
- Product names and crawlable links
- Product metadata already shown by cards

Destination visible HTML should include:

- Destination name
- Destination description
- Destination image alt
- Product count/context
- Product names and crawlable links

No crawler-only text. Schema must match visible content.

## 37. Query/Performance Plan

Expected query groups for future entity page:

- entity lookup
- Page Section query if generic content is used
- Product paginator with `publiclyVisible()` and fixed entity constraint
- eager-loaded card relations
- filter option queries
- global settings/media fallback query if CTA/media needs it

Prevent:

- Product N+1
- price N+1
- Category/Destination N+1
- repeated CMS queries
- loading all Products before pagination
- Product Detail child relation loading on Product Cards

Future measurement scenarios:

1. Category with 0 public Products.
2. Category with 1 public Product.
3. Category with 9 public Products.
4. Destination with 0 public Products.
5. Destination with 1 public Product.
6. Destination with 9 public Products.
7. Page 2.
8. Filtered/sorted entity page.

Do not set arbitrary hard query thresholds before measurement.

## 38. Security/Data-Safety Notes

Plan guardrails:

- Use active entity lookups; no direct ID public entity access.
- Normalize filters using existing patterns.
- Escape entity descriptions in Blade unless explicitly approved rich text exists.
- Do not render unsafe CMS URLs.
- Do not expose inactive/archived entity links.
- Keep admin access unaffected.
- Do not introduce raw SQL with request input.

## 39. Focused Test Plan

Future tests:

1. Homepage hides published Product under inactive Category.
2. Homepage hides published Product under archived Category.
3. Homepage hides published Product under inactive Destination.
4. Homepage hides published Product under archived Destination.
5. Homepage Product tabs exclude inactive/archived Category labels.
6. Homepage Destination card counts match public-visible Product count.
7. Product Listing regression for active Category filter.
8. Product Listing regression for active Destination filter.
9. Product Detail regression for inactive parent 404.
10. Future active Category clean route returns 200 if approved.
11. Future inactive Category clean route returns 404 if approved.
12. Future invalid Category slug returns 404 if approved.
13. Future active Destination clean route returns 200 if approved.
14. Future inactive Destination clean route returns 404 if approved.
15. Future invalid Destination slug returns 404 if approved.
16. Active empty Category safe state.
17. Active empty Destination safe state.
18. Entity page pagination.
19. Entity page filter reset.
20. Entity page metadata/canonical/robots.
21. Entity page BreadcrumbList.
22. Entity page ItemList.
23. No fake schema data.
24. No query in Blade.
25. No obvious N+1.
26. Full `php artisan test` before release implementation completes.

## 40. FRONTEND-26A Scope

If kept separate, FRONTEND-26A should handle Category visibility:

- Align homepage Product query with `publiclyVisible()`.
- Ensure homepage Product category tabs derive only from public-visible Products.
- Ensure active Category search/filter options remain active-only.
- Add focused homepage Category parent visibility tests.
- Run Product Listing and Product Detail regressions.

Likely runtime files:

- `app/Http/Controllers/Frontend/HomeController.php`
- possibly `app/Models/Product.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`

## 41. FRONTEND-26B Scope

If kept separate, FRONTEND-26B should handle Destination visibility:

- Align homepage Destination counts with public-visible Products.
- Preserve active-only Destination cards.
- Ensure card URLs remain valid Product Listing filters.
- Add focused Destination count/visibility tests.
- Run Product Listing and homepage regressions.

Likely runtime files:

- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Support/HomepageContent.php` only if card label/count preparation changes.
- `tests/Feature/Frontend/HomepageCmsContentTest.php`

## 42. Combined FRONTEND-26 Decision

Recommendation: **combine 26A and 26B** into:

`FRONTEND-26: Public Category & Destination Visibility & Query Integrity Implementation`

Why combining is safe:

- The confirmed critical and high issues share `HomeController@index`.
- Both depend on `Product::publiclyVisible()`.
- Both are homepage/listing visibility consistency fixes, not new routes or layouts.
- The rollback boundary remains understandable: revert homepage query/count/test changes.
- Tests can stay clear by grouping Category and Destination scenarios separately inside focused test methods.

Do not include:

- Clean route creation.
- Entity page layout.
- SEO/schema implementation.
- Product Detail breadcrumb changes.
- Category image schema.

## 43. FRONTEND-27 Scope

Objective: Category & Destination public experience decision and display-state preparation.

Scope:

- Decide whether Category stays filter-only or gets clean route.
- Decide whether Destination gets clean route.
- If approved, prepare controller/support contracts and route plan.
- Define display-state arrays, not full visual polish.

Likely files if approved:

- `routes/frontend.php`
- new frontend controller(s) or narrow methods
- support class for entity listing state
- tests for route/visibility contracts

Rollback boundary:

- Revert new route/controller/support/test files.

## 44. FRONTEND-28 Scope

Objective: Category & Destination layout, Product grid, media, and empty states.

Scope:

- Implement approved entity page Blade layout(s).
- Reuse Product Card/grid.
- Add Category text-first layout if approved.
- Add Destination media/hero if approved.
- Add active-empty entity states.

Likely files:

- future `resources/views/frontend/categories/show.blade.php`
- future `resources/views/frontend/destinations/show.blade.php`
- `resources/css/frontend-products.css` or a scoped frontend stylesheet
- focused frontend tests

Rollback boundary:

- Revert new views/CSS/tests.

## 45. FRONTEND-29 Scope

Objective: Accessibility, metadata, canonical/robots, and structured data.

Scope:

- One H1 and breadcrumb.
- Entity metadata state.
- Canonical and robots policy.
- BreadcrumbList and ItemList if pages exist.
- Defer TouristDestination unless accurate data is added/approved.

Likely files:

- entity controller/support state
- `app/Support/StructuredDataBuilder.php`
- `resources/views/partials/site-structured-data.blade.php`
- tests and docs

Rollback boundary:

- Revert SEO/schema support/test/doc changes.

## 46. FRONTEND-30 Scope

Objective: final performance, regression, and release verification.

Scope:

- Focused entity tests.
- Product Listing regression.
- Product Detail regression.
- Homepage regression.
- Full `php artisan test`.
- `npm.cmd run build` if frontend runtime files changed.
- Browser/Lighthouse only if tooling is actually available.
- Final report and release decision.

Rollback boundary:

- Report-only unless approved blockers are fixed.

## 47. Files Inspected

- `AGENTS.md`
- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Support/HomepageContent.php`
- `app/Support/ProductListingContent.php`
- `app/Support/PageSectionRegistry.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/seo/README.md`
- `docs/database/data-integrity.md`
- `docs/global-structured-data-business-schema.md`
- `ai/reports/frontend/frontend-24-public-category-destination-experience-audit.md`
- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`
- `ai/reports/frontend/frontend-08-public-product-listing-price-sorting-semantics-implementation-report.md`
- `ai/reports/frontend/frontend-09-public-product-listing-cms-content-sync-implementation-report.md`
- `ai/reports/frontend/frontend-10-product-card-listing-ux-consolidation-implementation-report.md`
- `ai/reports/frontend/frontend-10b-product-card-visual-pagination-alignment-report.md`
- `ai/reports/frontend/frontend-11-product-listing-filter-search-pagination-ux-implementation-report.md`
- `ai/reports/frontend/frontend-12-product-listing-responsive-image-empty-state-refinement-report.md`
- `ai/reports/frontend/frontend-13-product-listing-accessibility-seo-ai-discovery-rendering-report.md`
- `ai/reports/frontend/frontend-14-public-product-listing-final-performance-regression-release-verification-report.md`
- `ai/reports/frontend/frontend-14b-public-product-listing-browser-qa-performance-measurement-report.md`
- `ai/reports/frontend/frontend-23-product-detail-final-performance-regression-release-verification-report.md`

## 48. Files Recommended for Future Changes

Planning only; not approved yet.

Near-term FRONTEND-26:

- `app/Http/Controllers/Frontend/HomeController.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- possibly `app/Models/Product.php` for a narrow reusable public card scope if needed
- possibly `app/Support/HomepageContent.php` if count label behavior needs adjustment

Future approved route/page work:

- `routes/frontend.php`
- future frontend Category/Destination controller(s)
- future entity views
- future CSS/tests/docs
- `app/Support/StructuredDataBuilder.php` only when schema work is approved

## 49. Risks

- Deferring FRONTEND-26 leaves the confirmed homepage public visibility leak unresolved.
- Creating clean routes before shared query extraction can duplicate Product Listing logic.
- Category pages may be thin without an image/media strategy.
- Destination pages can overclaim SEO/schema if TouristDestination or rich Place data is invented.
- Query URL canonical changes can create duplicate-content risk if clean route policy is not approved first.

## 50. Rollback Strategy

FRONTEND-25 rollback:

- Remove this report only.

Future FRONTEND-26 rollback:

- Revert homepage query/count/test changes.
- No route/schema/database rollback expected.

Future route/page rollback:

- Revert new routes/controllers/views/support/tests/docs for the approved step.
- Keep Product Listing and Product Detail routes unchanged.

## 51. Approval Points

1. Category architecture: filter-only, dedicated route, or hybrid.
2. Destination architecture: filter-only, dedicated route, or hybrid.
3. New route creation.
4. Active empty Category policy.
5. Active empty Destination policy.
6. Category image strategy.
7. Destination hero/media strategy.
8. Product per-page count.
9. Filters retained on Category page.
10. Filters retained on Destination page.
11. Shared controller/query architecture.
12. Page Section keys.
13. CMS intro/final CTA ownership.
14. Breadcrumb path.
15. Clean-route indexability.
16. Query URL canonical/noindex policy.
17. ItemList implementation.
18. CollectionPage implementation.
19. TouristDestination deferral or implementation.
20. Product Detail breadcrumb updates after clean entity routes exist.

## 52. Recommended Immediate Next Step

`FRONTEND-26: Public Category & Destination Visibility & Query Integrity Implementation`

Definition of done for the next step:

- Homepage popular Products use the same public visibility policy as Product Listing and Product Detail.
- Homepage Product tabs no longer expose inactive/archived parent Category labels through leaked Products.
- Homepage Destination package counts match public-visible Products.
- Homepage Product Card eager loading is narrowed to the card contract where safe.
- Focused homepage visibility/count tests pass.
- Product Listing and Product Detail focused regressions pass.
- No routes, schema, layouts, SEO, or structured-data changes are included.
