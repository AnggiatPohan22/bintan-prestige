# FRONTEND-09 Public Product Listing CMS Content Sync Implementation Report

Date: 2026-06-14

## 1. Executive Summary

FRONTEND-09 connected public Product Listing business copy, optional hero media, and optional catalog CTA to existing Page Sections while preserving the FRONTEND-07/08 query, visibility, price, sorting, pagination, and Product card behavior.

No schema, migration, route, Product Detail, Product card redesign, filter redesign, SEO implementation, page builder, package, or admin Page Sections redesign was introduced.

## 2. Previous Listing Content Behavior

Before this step:

- `/products` rendered hardcoded hero title and intro copy in Blade.
- The listing toolbar title was hardcoded as `Available Products`.
- The hero background came from the first listed Product thumbnail or global default hero media.
- Product listing Page Section keys existed but public listing did not consume their content.

## 3. FRONTEND-06 CMS Decisions Applied

Applied decisions:

- Reuse existing `products.index.*` Page Section keys.
- Keep CMS-owned content separate from code-owned listing behavior.
- Do not let Page Sections control filters, sorting, query parameters, pagination, or Product cards.
- Keep fixed layout and fallbacks.

## 4. FRONTEND-07/08 Baseline Confirmation

FRONTEND-07 report exists and records passed focused/full tests.

FRONTEND-08 report exists and records passed focused/full tests and build.

This step preserved:

- `Product::publiclyVisible()`
- `Product::frontendListingReady()`
- IDR price filter/sort semantics
- duration sort fallback
- normalized query persistence

## 5. Listing Routes and Views Covered

| Route | Controller | View | Covered |
| --- | --- | --- | --- |
| `products.index` `/products` | `Frontend\ProductController@index` | `frontend.products.index` | Yes |

No Taxi, Activity, Hotel, Tour Package, category landing, or destination landing route exists as a separate listing page in the inspected route file.

## 6. Existing Page Section Keys

Used existing keys:

- `products.index.hero`
- `products.index.catalog`
- `products.index.filter_modal`
- `products.index.sort_modal`

No key was renamed.

## 7. Key Reuse or New-Key Decision

Decision: reuse existing keys.

No new Page Section key, seeder mutation, or admin content overwrite was needed.

## 8. Shared vs Per-Listing Content Decision

Decision: shared base listing content.

Reason:

- The current public route map exposes one Product Listing route: `/products`.
- Category and Destination states are query filters, not dedicated listing routes.
- Page Sections should not infer content keys from raw request filters.

## 9. CMS/Application Responsibility Boundary

| Listing concern | CMS-owned | Code-owned | Implementation |
| --- | ---: | ---: | --- |
| Hero label/title/subtitle/description | Yes | No | `ProductListingContent` from `products.index.hero` |
| Optional hero image | Yes | No | `PageSection.image` legacy image URL |
| Catalog heading/supporting paragraph | Yes | No | `products.index.catalog` |
| Optional catalog CTA | Yes | No | Safe `button_text` + `button_url` |
| Product query and visibility | No | Yes | Existing FRONTEND-07 query |
| Filters, sorting, pagination | No | Yes | Existing controller logic |
| Price/currency semantics | No | Yes | Existing FRONTEND-08 logic |
| Product cards | No | Yes | Existing shared card component |

## 10. Listing Content Data Contract

`ProductListingContent::fromSections()` returns:

- `hero.label`
- `hero.title`
- `hero.subtitle`
- `hero.description`
- `hero.image_url`
- `hero.mobile_image_url`
- `hero.image_alt`
- `catalog.title`
- `catalog.description`
- `catalog.subtitle`
- `catalog.cta_text`
- `catalog.cta_url`
- `catalog.has_cta`

## 11. Content Resolver Changes

Added:

- `app/Support/ProductListingContent.php`

The resolver:

- accepts an already-loaded active Page Section collection;
- prepares display-ready hero/catalog arrays;
- preserves code fallbacks;
- validates CTA URLs through `PageSectionCta`.

## 12. Controller Integration

`ProductController@index` now loads active registered `products.index.*` Page Sections once:

- `where('page_key', 'products.index')`
- `whereIn('section_key', registered products.index keys)`
- `where('is_active', true)`
- `with('media')`

The controller passes `$listingContent` to Blade.

## 13. Blade Integration

`resources/views/frontend/products/index.blade.php` now renders:

- CMS hero label when present.
- CMS hero title.
- CMS hero intro description.
- CMS hero subtitle when present.
- CMS hero image as first hero background choice.
- CMS catalog heading.
- CMS catalog supporting paragraph when present.
- Optional catalog CTA when valid.

Blade still does not query Page Sections.

## 14. Hardcoded Copy Removed

Moved behind backend fallbacks:

- Hero H1.
- Hero intro paragraph.
- Catalog heading.

## 15. Hardcoded Fallbacks Retained

Fallbacks retained in `ProductListingContent`:

- `Explore Tours, Taxi & Activities in Bintan`
- `Choose curated island tours, private transfers, and activities with easy WhatsApp booking support.`
- `Available Products`

Functional UI copy remains code-owned, including filter/sort labels, result count, empty state, and pagination.

## 16. Dynamic Heading Behavior

Current behavior: shared base listing heading.

Filtered requests do not choose Page Section keys from request values. The product count and active filters continue to communicate listing state without changing the CMS H1.

## 17. Image/Media Behavior

Hero background priority:

1. Active `products.index.hero.image`.
2. First listed Product thumbnail.
3. Global default hero media.

No broken image wrapper is rendered when CMS image is empty.

## 18. CTA Behavior

`products.index.catalog` can render an optional CTA only when:

- `button_text` is filled.
- `button_url` is safe and filled.

Unsafe schemes such as `javascript:` are rejected by `PageSectionCta`.

## 19. Section Status Behavior

Only active Page Sections are loaded for public listing content.

Inactive sections are treated like missing sections and use code fallbacks.

## 20. Missing Section Behavior

Missing sections use `ProductListingContent` code fallbacks.

Empty optional fields are omitted instead of rendering empty wrappers/buttons.

## 21. Cache and Query Impact

- One Page Section query is added to the Product Listing request.
- Page Sections are not queried per Product.
- Product cards do not query Page Sections.
- No new cache layer was added.
- Global Settings cache behavior was not changed.

## 22. Admin Compatibility

Product Listing Page Sections already appear in admin via the registry.

Added product listing hero legacy-image support through existing `HomepageSectionMedia` configuration so admins can manage the optional listing hero image without a schema or admin redesign.

## 23. Homepage Regression Impact

Homepage renderer code paths are not changed.

The added media config is scoped to `products.index.hero`.

Focused admin Page Section media tests pass.

## 24. Tests Added or Updated

Updated:

- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php`

Coverage added:

- Active listing CMS content renders.
- CMS hero image renders.
- CMS catalog CTA renders only when valid.
- CMS copy is escaped.
- Inactive/missing/empty sections fall back safely.
- Unsafe CTA URLs are rejected.
- Listing Blade does not query Page Sections.
- Admin can see legacy image upload for `products.index.hero`.

## 25. Focused Test Result

```bash
php artisan test --filter=ProductIndexUiTest
```

Result: Passed, 19 tests, 154 assertions.

```bash
php artisan test --filter=PageSectionMediaSlotTest
```

Result: Passed, 14 tests, 77 assertions.

## 26. Full Test Result

```bash
php artisan test
```

Result: Passed, 182 tests, 1002 assertions.

## 27. Frontend Build Result

```bash
npm.cmd run build
```

Result: Passed.

`npm.cmd` was used because plain `npm run build` is blocked by PowerShell script execution policy in this Windows environment.

## 28. Files Changed

| File | Reason | Runtime impact |
| --- | --- | --- |
| `app/Support/ProductListingContent.php` | New listing content resolver | Prepares display-ready listing CMS content |
| `app/Http/Controllers/Frontend/ProductController.php` | Load active listing Page Sections | Adds one CMS content query; Product query unchanged |
| `app/Support/HomepageSectionMedia.php` | Enable product listing hero legacy image support | Existing admin image UI works for listing hero |
| `resources/views/frontend/products/index.blade.php` | Render prepared CMS content | Business copy/media/CTA now CMS-syncs |
| `tests/Feature/Frontend/ProductIndexUiTest.php` | Focused listing CMS tests | Protects content/fallback/regression behavior |
| `tests/Feature/Admin/PageSectionMediaSlotTest.php` | Admin image support regression | Protects listing hero image editability |
| `docs/modules/products.md` | Product module docs | Documents listing CMS boundary |
| `docs/modules/page-sections.md` | Page Sections docs | Documents listing keys/resolver |
| `docs/architecture/frontend-backend-sync.md` | Architecture docs | Documents listing content flow |
| `ai/reports/frontend/frontend-09-public-product-listing-cms-content-sync-implementation-report.md` | Implementation report | Documents FRONTEND-09 |

## 29. Deferred Items

- Per-category/per-destination listing CMS content.
- Product Listing SEO metadata.
- Product Detail CMS content sync.
- Product card/listing UX consolidation.
- Dedicated final CTA design polish.
- Page Section media slot upgrade for product listing beyond legacy hero image.

## 30. Risks

- Catalog CTA uses existing layout classes and may need visual polish in a future UX step.
- Shared listing content may be too generic if dedicated listing routes are added later.
- Legacy image support is sufficient for hero media, but not as configurable as homepage media slots.

## 31. Rollback Procedure

Revert the files listed in section 28.

No database rollback is required.

## 32. Verification Result

Completed before final report closeout:

- Focused Product Listing tests: passed.
- Focused Page Section admin media tests: passed.
- Full Laravel test suite: passed.
- Frontend build: passed.

Final commands to run after report creation:

```bash
git diff --check
git status --short
```

## 33. Definition of Done

Done:

- Listing business copy connected to CMS.
- Page Sections control only content/media/CTA.
- Filters, query, sort, pagination, and Product cards remain code-owned.
- CMS data is prepared in backend.
- No Page Section query exists in Blade.
- Fallback content is preserved.
- Inactive/missing section behavior is safe.
- Optional image is safe.
- CTA renders only when valid.
- Homepage renderer behavior is preserved.
- FRONTEND-07 visibility still works.
- FRONTEND-08 price/sort behavior still works.
- Focused tests passed.
- Full tests passed.
- Build passed.

## 34. Recommended Next Step

Proceed to:

```text
FRONTEND-10: Public Product Card and Listing UX Consolidation
```

## Required Tables

### Key Mapping

| Listing context | Section key | Fallback key | Code fallback |
| --- | --- | --- | --- |
| Base Product Listing hero | `products.index.hero` | None | Hero title/intro in `ProductListingContent` |
| Base Product Listing catalog | `products.index.catalog` | None | `Available Products` |
| Filter modal identity | `products.index.filter_modal` | None | Code-owned functional modal copy |
| Sort modal identity | `products.index.sort_modal` | None | Code-owned functional modal copy |

### Content State

| CMS state | Rendered behavior |
| --- | --- |
| Section active and filled | Render CMS content |
| Section active with optional empty fields | Render available fields only |
| Section inactive | Use code fallback |
| Section missing | Use code fallback |
| Hero image empty | Fall back to product/default hero image |
| CTA label without URL | Hide CTA |
| CTA URL without label | Hide CTA |
| Unsafe CTA URL | Hide CTA |
