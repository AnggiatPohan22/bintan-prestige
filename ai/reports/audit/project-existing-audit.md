# STEP AUDIT-01 - Project Existing Audit

Date: 2026-06-11
Project: Bintan Prestige CMS
Scope: Read-only baseline audit for applying the AIOS workflow to the existing Laravel CMS.

## Executive Summary

The project is an existing Laravel MVC CMS with a clear split between public frontend travel pages and an authenticated admin dashboard. The main CMS modules for products, categories, destinations, product child data, page sections, global site settings, global assets, FAQs, and bookings already exist in the codebase.

The strongest existing foundation is the product CMS. Products already connect backend data to frontend rendering through Eloquent relationships, admin CRUD, frontend controllers, Blade views, SEO fields, image handling, and tests. Page sections and global settings also provide a strong base for CMS-controlled frontend content.

The main gaps are not project-breaking. They are governance and maturity gaps: some frontend copy still uses hardcoded fallbacks, authorization is mostly route-auth level instead of policy/role based, global settings are loaded repeatedly through view composers, sitemap/robots coverage was not confirmed in routed application structure, and documentation is still mostly mapped rather than fully canonicalized.

No Laravel code was changed during this audit. The only write performed for this step is this requested report file.

## Current Project Structure

Observed Laravel structure:

- `app/Models`
- `app/Http/Controllers`
- `app/Http/Controllers/Admin`
- `app/Http/Controllers/Frontend`
- `app/Http/Requests/Admin`
- `app/Services`
- `app/Support`
- `routes/web.php`
- `routes/frontend.php`
- `routes/admin.php`
- `resources/views/frontend`
- `resources/views/backend`
- `resources/views/layouts`
- `resources/views/components`
- `database/migrations`
- `database/seeders`
- `tests/Feature`
- `tests/Unit`

Laravel version observed through Artisan:

- Laravel Framework 13.11.2

Routing structure:

- `bootstrap/app.php` registers `routes/web.php`, then `routes/frontend.php`, then `routes/admin.php`.
- `routes/frontend.php` holds public routes.
- `routes/admin.php` holds authenticated CMS routes.
- `routes/web.php` still contains Laravel default/authenticated dashboard/profile style routes.

Route audit command result:

- `php artisan route:list` succeeded and returned 99 routes.
- `php artisan route:list --compact` failed because the `--compact` option is not available in this Laravel version.

## Existing Modules

### Products

Status: Active / core module.

Observed files and behavior:

- Model: `app/Models/Product.php`
- Admin controller: `app/Http/Controllers/Admin/ProductController.php`
- Frontend controller: `app/Http/Controllers/Frontend/ProductController.php`
- Requests: `StoreProductRequest`, `UpdateProductRequest`
- Services: `ProductService`, `ProductImageService`, `ProductPriceService`
- Frontend views: product index, product show, product card
- Backend views: product index, create, edit, form, partials
- Tests: frontend product tests exist

The product module already supports category, destination, images, prices, features, highlights, FAQs, itineraries, notes, SEO fields, pickup details, published status, featured/home flags, ordering, and CTA/contact information.

### Categories

Status: Active module.

Observed files and behavior:

- Model: `app/Models/Category.php`
- Admin controller: `app/Http/Controllers/Admin/CategoryController.php`
- Requests: `StoreCategoryRequest`, `UpdateCategoryRequest`
- Service: `CategoryService`
- Backend views under `resources/views/backend/categories`

Categories support active status, featured/home flags, sorting, SEO fields, and soft deletes.

### Destinations

Status: Active module.

Observed files and behavior:

- Model: `app/Models/Destination.php`
- Admin controller: `app/Http/Controllers/Admin/DestinationController.php`
- Requests: `StoreDestinationRequest`, `UpdateDestinationRequest`
- Service: `DestinationService`
- Backend views under `resources/views/backend/destinations`

Destinations support active status, featured/home flags, sorting, SEO fields, images, and soft deletes.

### Product Prices

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductPrice.php`
- Migration: `product_prices`
- Admin child controller: `ProductPriceController`
- Service: `ProductPriceService`
- Backend partials under product edit form

Prices are normalized in `product_prices` and consumed through product accessors/fallbacks. The service uses `updateOrCreate` for currencies.

### Product Images

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductImage.php`
- Migration: `product_images`
- Admin child controller: `ProductImageController`
- Service: `ProductImageService`
- Backend partials under product edit form

Product image uploads are validated and processed through an image optimization service.

### Product Features

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductFeature.php`
- Migration: `product_features`
- Admin child controller: `ProductFeatureController`
- Backend partials under product edit form

Features support label/group style organization and are rendered in product views.

### Product FAQs

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductFaq.php`
- Migration: `product_faqs`
- Admin child controller: `ProductFaqController`
- Backend partials under product edit form

Product FAQs are available as structured product content. FAQ schema output should be reviewed in a future SEO pass.

### Product Itineraries

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductItinerary.php`
- Migration: `product_itineraries`
- Admin child controller: `ProductItineraryController`
- Backend partials under product edit form

Itineraries are managed as product child records.

### Product Notes

Status: Active product child module.

Observed files and behavior:

- Model: `app/Models/ProductNote.php`
- Migration: `product_notes`
- Admin child controller: `ProductNoteController`
- Backend partials under product edit form

Notes are managed as product child records and can support inclusions, exclusions, or operational details depending on labels/types.

### Page Sections

Status: Active frontend CMS module.

Observed files and behavior:

- Model: `app/Models/PageSection.php`
- Model: `app/Models/PageSectionMedia.php`
- Admin controller: `app/Http/Controllers/Admin/PageSectionController.php`
- Support: `PageSectionRegistry`, `HomepageSectionMedia`
- Migration: `page_sections`
- Migration: `page_section_media`
- Backend views under `resources/views/backend/page-sections`
- Frontend section views under `resources/views/frontend/sections`
- Tests exist for page section media slot behavior

Page sections are one of the most important foundations for converting hardcoded frontend content into CMS-controlled frontend content.

### Global Settings and Site Assets

Status: Active support module.

Observed files and behavior:

- Models: `SiteSetting`, `SiteAsset`
- Admin controller: `SiteSettingController`
- Support classes for SEO defaults, structured data, tracking integrations, booking CTA, navigation, footer, and media defaults
- Tests exist for global asset behavior and structured data

This module already supports central CMS control over important global frontend behavior.

### Global FAQs

Status: Active module.

Observed files and behavior:

- Model: `Faq`
- Admin controller: `FaqController`
- Frontend homepage uses published/global FAQs
- Seeder exists

Global FAQs should be considered for FAQPage structured data in a future SEO pass.

### Bookings

Status: Present but not fully audited as a complete public/admin workflow.

Observed files and behavior:

- Models: `Booking`, `BookingItem`
- Migrations and seeder records exist
- Dashboard counts bookings

No complete booking CRUD or public booking flow was deeply audited in this read-only pass.

## Backend Structure

The backend follows Laravel MVC conventions in most important areas:

- Controllers manage request flow.
- Eloquent models define relationships, scopes, casts, and accessors.
- Form Requests are used for larger admin validation flows.
- Services exist for reusable product, category, destination, image, and pricing logic.
- Support classes hold global settings and frontend configuration helpers.

Positive findings:

- Product queries generally use eager loading for frontend rendering.
- Admin product lists use `with`, `withCount`, filtering, and pagination.
- Category and destination lists use pagination and product counts.
- Product child records are separated into their own models/controllers/migrations.
- Page section registry provides a controlled mapping of frontend section keys.

Gaps:

- Authorization is mostly route-auth based. No strong policy/role permission layer was observed.
- Some Form Request `authorize()` methods return `true`, relying on route middleware.
- Some backend presentation decisions are still prepared in Blade through `@php` blocks instead of controllers/view models/support classes.
- Booking module structure needs a dedicated future audit if it becomes a public or admin workflow.

## Frontend Structure

Observed frontend structure:

- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- `resources/views/frontend/partials/site-structured-data.blade.php`
- `resources/views/frontend/partials/tracking-head.blade.php`
- `resources/views/frontend/partials/tracking-body-start.blade.php`
- `resources/views/frontend/partials/tracking-body-end.blade.php`
- `resources/views/frontend/sections/*`
- `resources/views/components/frontend/*`

Positive findings:

- Frontend pages receive prepared data from frontend controllers and global view composers.
- Products are rendered with database-backed details.
- Homepage sections use database-backed page section records where available.
- Site settings and global assets are shared into frontend layouts.
- SEO and structured data partials already exist.

Gaps:

- Several frontend sections still include hardcoded fallback copy.
- Some frontend views contain non-query `@php` blocks for presentation preparation.
- Homepage product filtering uses client-side JavaScript over the rendered home product set. This is acceptable for the current limited list but should not become the full product filtering pattern.
- A text encoding issue was observed in the testimonials section where star symbols appear as mojibake (`â˜…`) in file scan output.

## Admin Dashboard Structure

Observed admin structure:

- Admin routes live in `routes/admin.php`.
- Admin routes are protected by `auth` middleware.
- Admin views live under `resources/views/backend`, not `resources/views/admin`.
- Admin layout and navigation exist under backend layouts/partials.
- Admin modules exist for dashboard, products, categories, destinations, FAQs, page sections, and site settings.

Positive findings:

- Admin dashboard is separated from public frontend.
- Product edit screens include child data management areas.
- Page sections and global settings allow CMS control over frontend content.
- Destructive UI actions generally use forms and CSRF-protected requests.

Gaps:

- No granular authorization layer was confirmed for admin actions.
- If public registration remains enabled, newly registered users may gain access depending on application assumptions and middleware. This should be reviewed before production.
- Admin view folder name `backend` is acceptable as existing convention, but docs should document this convention to avoid confusion with AGENTS.md preferred `resources/views/admin`.

## Database Structure

Observed database areas:

- Core Laravel tables: users, cache, jobs
- CMS taxonomy: categories, destinations
- Product data: products, product_images, product_prices, product_highlights, product_features, product_faqs, product_itineraries, product_notes
- Booking data: bookings, booking_items
- Content/settings: faqs, page_sections, page_section_media, site_assets, site_settings

Positive findings:

- Products are normalized into child tables for prices, images, features, FAQs, itineraries, notes, and highlights.
- Page sections have a unique `(page_key, section_key)` style constraint.
- Site settings and site assets use unique keys.
- Categories and destinations use soft deletes.
- Product relationships to category/destination include `withTrashed`, helping preserve display continuity when taxonomy records are soft-deleted.

Gaps and watch points:

- `product_prices` appears to rely on service-level `updateOrCreate` for currency uniqueness. A future approved migration could add a database-level unique constraint on `(product_id, currency)`.
- Products do not appear to use soft deletes, while categories and destinations do.
- Some migration filenames contain typo text such as `coloumn`. This does not need immediate action, but should be documented to avoid confusion.
- `TravelSeeder` clears/recreates some demo data and should be treated as development/demo-only, not production-safe.
- Booking code generation through random fake data has a theoretical unique collision risk.

## Backend to Frontend Sync

Strong sync areas:

- `HomeController` prepares homepage sections, site assets, FAQs, featured products, home products, categories, and destinations.
- `Frontend\ProductController` uses published product filtering, eager loading, pagination, category/destination filters, and SEO variables.
- `Product::frontendReady()` centralizes product eager-loading needs for frontend rendering.
- `AppServiceProvider` view composers share global assets, settings, SEO defaults, tracking settings, booking CTA, navigation, footer settings, media defaults, and structured data inputs into layouts and partials.
- `PageSectionRegistry` maps known page/section keys and supports admin synchronization of section records.

Sync gaps:

- Some frontend content still depends on fallback copy in Blade/support classes when database settings are absent.
- Homepage product filtering is partly frontend-side over a limited rendered collection.
- Destination and category content exist as admin/data concepts, but public destination/category detail pages were not confirmed in route list.
- Global settings are powerful, but repeated view-composer loading can become a performance issue if not cached.

## Documentation Status

Observed documentation foundation:

- `AGENTS.md` exists and defines the project master rule.
- `ai/guidelines` exists.
- `ai/skills` exists.
- `docs/README.md` exists as the documentation index.
- `docs/DOCS-MAP.md` exists as the mapping from old docs to the new documentation structure.
- Canonical docs folders exist for architecture, database, modules, frontend, admin, security, performance, SEO, QA, changelog, and legacy references.

Documentation gaps:

- Canonical docs are still mostly index/mapping level, not complete implementation references.
- Product module deserves a dedicated canonical module doc.
- Page Sections and Global Settings deserve dedicated canonical module docs.
- Database relationship documentation is still needed.
- Admin dashboard conventions should document that current views live under `resources/views/backend`.
- Security, performance, SEO, and QA docs should be upgraded from checklist placeholders into project-specific baselines.
- Booking module status should be documented as present but requiring deeper workflow audit.

## Security Findings

### Positive Findings

- `.env` was not observed as a tracked file during this audit; `.env.example` is tracked as expected.
- Admin routes are protected with `auth` middleware.
- CSRF-protected forms are used across admin views.
- Upload validation exists for product/category/destination/page-section style image flows.
- Product image processing includes MIME checks and image optimization.
- No suspicious use of `eval`, `shell_exec`, `exec`, `system`, `passthru`, unsafe `base64_decode`, or `unserialize` was found in the scoped search.

### Findings

1. Admin authorization is broad.

   Admin routes are authenticated, but granular role, policy, or permission checks were not confirmed. This is important before production or multi-admin use.

2. Public registration should be reviewed.

   `/register` routes exist through auth scaffolding. If this CMS should only have invited/admin users, registration should be disabled or restricted in a future approved step.

3. Raw tracking script output is powerful and risky.

   Tracking partials output admin-managed custom scripts using raw Blade output. This is expected for analytics/script integrations, but it must be restricted to trusted super-admin users and documented clearly.

4. Local storage route exposure should be reviewed before production.

   Route list includes local storage serving routes. This may be normal for local development, but production storage exposure and upload paths should be reviewed.

5. Upload governance is good but should be standardized.

   Product images are optimized heavily, while some destination/page-section/site asset flows may store original extensions. This should be standardized in a future image security/performance step.

## Performance Findings

### Positive Findings

- Product frontend listing and detail pages use eager loading patterns.
- Product listing uses pagination.
- Admin lists generally use pagination and counts.
- Product image upload has WebP conversion and max width handling.
- Page section admin lists are paginated.

### Findings

1. Global view composers may repeat queries per request.

   `AppServiceProvider` shares multiple setting groups and assets into several views. This is clean architecturally, but it should eventually be cached or grouped to reduce repeated database work.

2. Product price accessors depend on loaded relationships.

   `idr_price` and `sgd_price` read from the prices collection. This is fine when eager loaded, but can create hidden query risks if used outside the `frontendReady`/admin eager-loading paths.

3. Homepage product filtering is client-side over rendered data.

   This is acceptable for the current limited product set, but backend filtering should remain the canonical pattern for large lists.

4. Image optimization is uneven across upload types.

   Product uploads appear stronger than destination/page-section/site asset uploads. A future asset pipeline standard should define format, dimensions, lazy loading, alt text, and compression for all image types.

5. Custom scripts can affect performance.

   Admin-managed head/body scripts need performance governance to avoid blocking mobile page load.

## SEO Findings

### Positive Findings

- SEO default support exists.
- Product pages have SEO fields and fallback meta handling.
- Structured data support exists through `StructuredDataBuilder`.
- Canonical, Open Graph, Twitter/X card, and structured data partials exist.
- Product content is mostly visible in HTML, which helps AI discovery.

### Findings

1. Sitemap and robots coverage was not confirmed.

   No sitemap route was observed in the route list. Public `robots.txt`/`sitemap.xml` was not deeply audited because this step avoided touching public assets.

2. Category and destination discovery needs review.

   Categories and destinations exist as CMS data, but public category/destination detail pages were not confirmed.

3. FAQ schema should be reviewed.

   Product FAQs and global FAQs exist, but FAQPage schema output was not confirmed in this pass.

4. Per-page SEO governance should be expanded.

   Product detail SEO is strong. Homepage, product listing, and other public pages should have documented admin-controlled SEO settings.

5. Hardcoded fallback content should be reduced over time.

   AI discovery improves when important copy is canonical, database-managed, and documented instead of scattered across Blade fallbacks.

## Testing/QA Gap

Observed tests:

- Auth/profile tests
- Global asset tests
- Page section media slot tests
- Frontend product tests
- Structured data builder tests
- Unit example test

QA gaps:

- Category CRUD tests were not confirmed.
- Destination CRUD tests were not confirmed.
- Full product admin CRUD tests were not confirmed.
- Product child controller tests were not confirmed for prices/images/features/FAQs/itineraries/notes.
- Admin authorization/role tests were not confirmed.
- Upload negative-case tests were not confirmed.
- Sitemap/robots/SEO route tests were not confirmed.
- Performance/N+1 regression tests were not confirmed.
- Booking workflow tests were not confirmed.
- End-to-end admin dashboard QA checklist is not yet canonicalized.

Commands run for verification during audit:

- `php artisan --version` succeeded.
- `php artisan route:list` succeeded.
- Full test suite was not run because this step was requested as read-only baseline audit and tests/builds may write runtime artifacts.

## Risks

1. Authorization risk

   If multiple users can access admin, route-level auth alone may not be enough.

2. CMS content drift risk

   Some important frontend copy still lives in Blade/support fallback code instead of canonical CMS data.

3. Performance drift risk

   Global settings and assets are convenient but should be cached before high traffic.

4. SEO completeness risk

   Product SEO is present, but sitemap/robots/category/destination/FAQ schema coverage needs follow-up.

5. Documentation drift risk

   Docs mapping exists, but canonical docs are not yet detailed enough to prevent future AI or developer confusion.

6. Upload/media consistency risk

   Product images have stronger processing than some other image flows.

7. Public registration risk

   If open registration is not intended, it should be restricted before production.

## Recommended Next Steps

1. Create canonical module docs for Products, Page Sections, Global Settings, Categories, Destinations, FAQs, and Bookings.
2. Run a dedicated security hardening audit focused on auth, registration, admin authorization, uploads, tracking scripts, and storage exposure.
3. Run a backend-to-frontend sync pass to identify which hardcoded frontend content should become Page Sections or Site Settings.
4. Create a performance baseline doc covering eager loading, global setting cache, image optimization, frontend assets, and third-party scripts.
5. Create an SEO + AI discovery baseline covering sitemap, robots, schema, metadata, breadcrumbs, internal links, and destination/category discovery.
6. Build a QA checklist and test coverage map for admin CRUD, product child modules, page sections, global settings, uploads, and frontend rendering.
7. Audit the Booking module separately before exposing or expanding it.

## Safe Improvement Roadmap

### Phase 1 - Documentation Canonicalization

- Convert this report into docs references.
- Create `docs/modules/products.md`.
- Create `docs/modules/page-sections.md`.
- Create `docs/modules/global-settings.md`.
- Create `docs/database/relationships.md`.
- Create `docs/admin/dashboard-structure.md`.
- Keep old docs untouched and linked from `docs/DOCS-MAP.md`.

### Phase 2 - Security Baseline

- Review public registration.
- Define admin roles/permissions/policies.
- Document safe usage of custom tracking scripts.
- Review upload validation and storage exposure.
- Add security test checklist.

### Phase 3 - CMS Sync Cleanup

- Map hardcoded frontend fallbacks into Page Sections or Site Settings.
- Keep safe fallbacks, but document their source and priority.
- Move repeated Blade presentation preparation into controllers/support classes where appropriate.
- Preserve existing frontend layout and content while improving data ownership.

### Phase 4 - Performance and SEO Baseline

- Cache global settings/assets where appropriate.
- Standardize image optimization across all upload types.
- Confirm sitemap and robots strategy.
- Add FAQ schema where relevant.
- Review category and destination public discovery routes.

### Phase 5 - Testing and QA

- Add admin CRUD tests for categories, destinations, products, and product child modules.
- Add authorization and upload negative-case tests.
- Add frontend SEO/rendering tests.
- Add documentation-driven launch checklist with scoring aligned to AGENTS.md.

## Files Read

AI foundation:

- `AGENTS.md`
- `ai/guidelines/*`
- `ai/skills/*`
- `docs/README.md`
- `docs/DOCS-MAP.md`

Laravel structure:

- `bootstrap/app.php`
- `routes/web.php`
- `routes/frontend.php`
- `routes/admin.php`
- `app/Models/*`
- `app/Http/Controllers/*`
- `app/Http/Requests/*`
- `app/Services/*`
- `app/Support/*`
- `app/Providers/AppServiceProvider.php`
- `resources/views/frontend/*`
- `resources/views/backend/*`
- `resources/views/layouts/*`
- `resources/views/components/*`
- `database/migrations/*`
- `database/seeders/*`
- `tests/Feature/*`
- `tests/Unit/*`

## Files Changed

- `ai/reports/audit/project-existing-audit.md`

## Files Created

- `ai/reports/audit/project-existing-audit.md`

## Files Deleted

- None.

## Database Impact

- None. No database changes were made.

## Route Impact

- None. No route files were changed.

## Frontend Impact

- None. No frontend views or assets were changed.

## Backend Impact

- None. No backend code was changed.

## Security Impact

- No security-sensitive files were changed.
- Security risks were documented for future approved hardening steps.

## Performance Impact

- None. No runtime code was changed.

## SEO Impact

- None. SEO gaps were documented for future approved work.

## Documentation Updated

- This audit report was created as the AUDIT-01 baseline.

## Testing Performed

- `php artisan --version`
- `php artisan route:list`
- Static file inspection using read-only shell commands.

Full automated tests were not run because this step was a read-only baseline audit and full test/build commands may create runtime artifacts.

## Rollback Note

To rollback this step, remove only this report file:

- `ai/reports/audit/project-existing-audit.md`

