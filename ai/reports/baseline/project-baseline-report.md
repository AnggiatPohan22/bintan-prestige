# STEP BASELINE-01 - Main Project Baseline Report

Date: 2026-06-12
Project: Bintan Prestige CMS
Scope: Main baseline report before improvement work.

## Report Basis

This baseline is based on:

- `AGENTS.md`
- `ai/guidelines/*`
- `ai/skills/*`
- `docs/README.md`
- `docs/DOCS-MAP.md`
- `ai/reports/audit/project-existing-audit.md`

No Laravel code, database schema, migrations, routes, controllers, models, views, config, assets, public files, or packages were changed in this step.

## 1. Executive Summary

Bintan Prestige CMS is already a working Laravel MVC + TailwindCSS CMS with a clear public frontend and authenticated admin dashboard. The strongest foundation is the product CMS: product data is normalized across product child tables, managed through admin screens, rendered on public pages, and supported by SEO fields, image handling, and tests.

The project is ready for structured improvement, but not yet ready to be treated as launch-hardened. The main baseline gaps are authorization depth, documentation completeness, frontend CMS ownership, SEO discovery completeness, performance caching, and QA coverage.

The AIOS foundation is now in place: `AGENTS.md` is the master rule, AI guidelines and skills exist, documentation has an index and map, and the existing project has been audited. This report should be treated as the main pre-improvement baseline.

## 2. Current Project Status

Current status: Working existing CMS with a good module base and moderate hardening/documentation gaps.

Strengths:

- Laravel MVC structure is recognizable and mostly consistent.
- Products, categories, destinations, page sections, global settings, global assets, and FAQs are present.
- Admin dashboard is separated from public frontend.
- Public product listing/detail flows are backed by database data.
- Page Sections and Global Settings already support the future goal of backend-controlled frontend content.
- Documentation foundation has been created and mapped.
- Tests exist for several frontend/global/page-section behaviors.

Main gaps:

- Admin authorization appears mostly auth-middleware based, not policy/role based.
- Some frontend content still uses Blade/support fallbacks instead of canonical CMS data.
- Global settings/assets may create repeated query overhead without caching.
- Sitemap/robots/public discovery coverage still needs confirmation.
- Canonical docs are mostly structure/index level, not full module references yet.
- Test coverage is not complete for admin CRUD, product child modules, upload safety, authz, SEO, and booking.

## 3. Current Laravel Structure

Observed structure:

- `app/Models`
- `app/Http/Controllers`
- `app/Http/Controllers/Admin`
- `app/Http/Controllers/Frontend`
- `app/Http/Requests/Admin`
- `app/Services`
- `app/Support`
- `app/Providers/AppServiceProvider.php`
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

Baseline assessment:

- Laravel version observed in AUDIT-01: Laravel Framework 13.11.2.
- Route list in AUDIT-01 succeeded with 99 routes.
- `bootstrap/app.php` registers web, frontend, and admin route files.
- Admin views currently use `resources/views/backend`, while AGENTS.md lists `resources/views/admin` as the preferred structure. This is not a blocker because existing code convention must be preserved, but docs should clearly record the current convention.

## 4. Current Database Overview

Observed database areas:

- Laravel base tables: users, cache, jobs
- Taxonomy tables: categories, destinations
- Product tables: products, product_images, product_prices, product_highlights, product_features, product_faqs, product_itineraries, product_notes
- CMS content/settings tables: page_sections, page_section_media, site_assets, site_settings, faqs
- Booking tables: bookings, booking_items

Positive baseline:

- Product data is normalized into separate child tables.
- Page sections use stable page/section keys.
- Site settings and assets use key-based access.
- Categories and destinations support soft deletes.
- Product relationships to category/destination use deleted taxonomy records safely enough for display continuity.

Database baseline gaps:

- `product_prices` should eventually have an approved DB-level uniqueness rule for `(product_id, currency)` if not already present.
- Products do not appear to use soft deletes.
- Booking workflow needs a deeper module audit.
- Some migration filenames contain typos such as `coloumn`; this is low risk but should be documented.
- Demo seeders should be treated carefully and not assumed production-safe.

## 5. Current Backend/Admin Overview

Backend baseline:

- Controllers handle main request flow.
- Models define relationships, casts, scopes, and accessors.
- Form Requests exist for major admin validation flows.
- Services exist for products, prices, images, categories, destinations, and related reusable behavior.
- Support classes handle SEO defaults, structured data, navigation, footer, booking CTA, tracking integration, default media assets, and page-section registry behavior.

Admin baseline:

- Admin routes are in `routes/admin.php`.
- Admin routes are protected by `auth` middleware.
- Admin dashboard modules exist for dashboard, products, categories, destinations, FAQs, page sections, and site settings/global assets.
- Admin product forms include repeatable child data areas.
- Admin lists generally use filters/search/pagination patterns.

Admin/backend baseline gaps:

- Granular authorization through policies/gates/roles was not confirmed.
- Public registration exists and should be reviewed for CMS production usage.
- Some request `authorize()` methods rely on route middleware by returning `true`.
- Booking admin/public workflow is not yet clear from baseline audit.

## 6. Current Frontend Overview

Frontend baseline:

- Public frontend is under `resources/views/frontend`.
- Product index and detail pages are database-backed.
- Homepage uses controller-prepared data and Page Section records where available.
- Header/footer and layout partials receive global settings/assets through view composers.
- SEO and structured data partials exist.
- Blade/Tailwind is the main UI pattern.

Positive baseline:

- Critical product content is mostly visible in HTML.
- Product pages support prices, categories, destinations, descriptions, images, details, FAQs, itineraries, notes, and CTAs.
- The project direction fits the luxury travel CMS concept in AGENTS.md and AI skills.

Frontend baseline gaps:

- Some sections still include hardcoded fallback copy.
- Some Blade files contain `@php` presentation preparation.
- Homepage product filtering uses client-side JavaScript over a limited rendered product set.
- A text encoding issue was observed in the testimonials section scan output.
- Destination/category public discovery pages were not confirmed.

## 7. Current Backend-to-Frontend Sync Status

Sync maturity: Good foundation, not fully canonicalized.

Strong sync:

- `HomeController` prepares homepage sections, products, categories, destinations, FAQs, and site assets.
- `Frontend\ProductController` prepares published products, filters, pagination, and product SEO variables.
- `Product::frontendReady()` centralizes eager loading for product frontend rendering.
- `PageSectionRegistry` defines controlled page/section mapping.
- `AppServiceProvider` shares global CMS settings into layouts and partials.

Sync gaps:

- Some fallback content still lives in Blade/support code rather than CMS records.
- Homepage filtering is partially frontend-side.
- Global settings are powerful but may need caching.
- Page Sections and Global Settings need canonical docs to prevent future drift.

## 8. Existing CMS Modules

Active/core modules:

- Products
- Categories
- Destinations
- Product Prices
- Product Images
- Product Highlights
- Product Features
- Product FAQs
- Product Itineraries
- Product Notes
- Page Sections
- Page Section Media
- Global Site Settings
- Global Site Assets
- Global FAQs
- Bookings / Booking Items

Module maturity:

| Module | Baseline status | Notes |
| --- | --- | --- |
| Products | Strong | Core admin/frontend/SEO/data relationships exist. |
| Categories | Good | Admin/data support exists; public discovery should be reviewed. |
| Destinations | Good | Admin/data support exists; public discovery should be reviewed. |
| Product Prices | Good | Normalized; DB uniqueness should be reviewed later. |
| Product Images | Good | Stronger optimization than some other image flows. |
| Product Features | Good | Product child module exists. |
| Product FAQs | Good | Schema integration should be reviewed. |
| Product Itineraries | Good | Product child module exists. |
| Product Notes | Good | Product child module exists. |
| Page Sections | Strong | Important for CMS-controlled frontend content. |
| Global Settings/Assets | Strong | Needs caching/security/performance governance. |
| Global FAQs | Moderate | Present; schema/SEO integration needs review. |
| Bookings | Early/unclear | Data model exists; workflow needs dedicated audit. |

## 9. Documentation Status

Documentation foundation:

- `AGENTS.md` is the master project rule.
- `ai/guidelines/*` exists and covers architecture, Boost workflow, backend processing, frontend UI/UX, admin CMS, security, SEO, performance, QA, and documentation.
- `ai/skills/*` exists and covers backend, frontend, UI/UX, admin, CMS architecture, database, security, SEO, performance, QA, product management, travel business, and documentation.
- `docs/README.md` exists as the main index.
- `docs/DOCS-MAP.md` maps existing docs to the canonical structure.
- `ai/reports/audit/project-existing-audit.md` exists as AUDIT-01 baseline.

Documentation gaps:

- Canonical module docs are not yet fully written.
- Architecture, database, security, performance, SEO, and QA docs are mostly placeholders/indexes.
- Old root-level global docs are still active implementation references and should be consolidated carefully.
- Page Section and Product docs are spread across old branch/step folders.
- Changelog is not yet canonicalized.

## 10. Security Baseline

Security score is limited mainly by authorization and governance gaps, not by confirmed active compromise.

Positive baseline:

- `.env` was not observed as tracked in AUDIT-01.
- Admin routes use `auth` middleware.
- CSRF-protected forms are used in admin views.
- Upload validation exists for major image flows.
- Product image upload includes MIME checks and optimization.
- AUDIT-01 did not find suspicious use of `eval`, `shell_exec`, `exec`, `system`, `passthru`, unsafe `base64_decode`, or `unserialize` in scoped search.

Security gaps:

- Admin role/permission/policy layer was not confirmed.
- Public registration should be reviewed for CMS use.
- Admin-managed tracking scripts use raw output and need strict governance.
- Storage/public upload exposure should be reviewed before production.
- Upload rules should be standardized across all image modules.
- Authorization tests were not confirmed.

## 11. Performance Baseline

Positive baseline:

- Product listing/detail uses eager loading patterns.
- Public product lists use pagination.
- Admin lists generally use pagination and counts.
- Product image processing converts/optimizes images.
- Frontend avoids a large SPA architecture and keeps content server-rendered.

Performance gaps:

- Global view composers may repeat settings/assets queries per request.
- Product price accessors depend on relationships being eager-loaded.
- Homepage client-side filtering should stay limited and not become the main large-data pattern.
- Image optimization appears stronger for product images than for all other upload types.
- Third-party custom tracking scripts can hurt performance if not controlled.
- No Lighthouse or runtime performance baseline has been captured yet.

## 12. SEO & AI Discovery Baseline

Positive baseline:

- SEO defaults exist.
- Product SEO fields exist.
- Structured data support exists through support classes and Blade partials.
- Canonical, Open Graph, Twitter/X card, and JSON-LD concepts exist.
- Product detail content is mostly visible in HTML.
- FAQ content exists at product and global levels.

SEO and AI discovery gaps:

- Sitemap and robots coverage were not confirmed in AUDIT-01 route inspection.
- Category and destination public detail/discovery pages were not confirmed.
- FAQPage schema output should be reviewed.
- Homepage/product listing per-page SEO governance should be expanded.
- Important fallback copy should move toward CMS-managed canonical content.
- AI discovery docs and crawler policy still need project-specific decisions.

## 13. Testing & QA Baseline

Observed test foundation:

- Auth/profile tests
- Global asset tests
- Page section media slot tests
- Frontend product tests
- Structured data builder tests
- Unit example test

QA gaps:

- Category CRUD tests not confirmed.
- Destination CRUD tests not confirmed.
- Full product admin CRUD tests not confirmed.
- Product child controller tests not confirmed.
- Authorization/role tests not confirmed.
- Upload negative-case tests not confirmed.
- SEO/sitemap/robots tests not confirmed.
- Performance/N+1 regression tests not confirmed.
- Booking workflow tests not confirmed.
- Full launch QA scoring checklist is not yet implemented as canonical docs.

## 14. Main Risks

1. Admin authorization risk

   The admin is protected by authentication, but granular authorization was not confirmed.

2. Frontend CMS ownership risk

   Some content still depends on Blade/support fallbacks instead of database-managed CMS records.

3. SEO discovery risk

   Sitemap, robots, category/destination discovery, and FAQ schema need confirmation.

4. Performance scaling risk

   Global settings/assets and relationship accessors need caching/eager-loading discipline as traffic and content grow.

5. Documentation drift risk

   Docs are mapped but not yet canonical enough to prevent future AI/developer confusion.

6. Testing confidence risk

   Existing tests cover useful areas, but not enough high-risk admin/security/SEO paths.

7. Booking workflow risk

   Booking models/tables exist, but workflow maturity was not fully established in AUDIT-01.

## 15. Critical Issues

No confirmed critical exploit or active breakage was identified in AUDIT-01.

Critical production gates before launch:

- Confirm no secrets are tracked or exposed.
- Confirm production `APP_DEBUG=false`.
- Confirm admin access cannot be reached by unintended public users.
- Confirm public upload/storage paths cannot execute unsafe files.
- Confirm admin/custom tracking script access is restricted to trusted users only.

## 16. High Priority Issues

1. Add or confirm granular admin authorization.

   Policies/gates/roles should protect sensitive admin actions, especially update/delete/settings/tracking/upload operations.

2. Review public registration.

   If this is a closed CMS, public registration should be disabled or restricted in an approved security step.

3. Govern raw tracking script output.

   Admin-managed custom scripts are useful but high risk. Restrict access, document usage, and consider audit logging.

4. Confirm sitemap and robots strategy.

   Public SEO discovery and private/admin exclusion must be verified before launch.

5. Create canonical module documentation.

   Products, Page Sections, Global Settings, Categories, Destinations, FAQs, and Bookings need canonical docs.

## 17. Medium Priority Issues

1. Reduce hardcoded frontend fallback content.

   Move important content into Page Sections or Global Settings while preserving safe fallbacks.

2. Cache or group global setting queries.

   The current view composer approach is convenient, but it should be optimized before heavy traffic.

3. Standardize image optimization.

   Product image handling is stronger than other image flows. Define one project-wide image policy.

4. Expand admin CRUD tests.

   Products, product child modules, categories, destinations, FAQs, and page sections need stronger coverage.

5. Review destination/category public discovery.

   These modules are important for travel SEO and should have clear public discovery strategy.

6. Audit booking workflow.

   Booking models and seeders exist, but workflow ownership should be clarified.

## 18. Low Priority Issues

1. Document current admin view folder convention.

   Current admin views live under `resources/views/backend`; AGENTS.md preferred structure mentions `resources/views/admin`.

2. Document migration filename typos.

   Typos like `coloumn` are low runtime risk but can confuse future maintainers.

3. Fix observed frontend text encoding issue.

   AUDIT-01 observed mojibake in testimonial star output.

4. Consolidate old branch-scoped docs.

   Existing docs should remain untouched until canonical replacements are ready.

5. Decide long-term fate of UI inspiration docs.

   `docs/ai-ui-reference/*` should stay reference-only unless explicitly promoted or moved after approval.

## 19. Safe Improvement Roadmap

### Phase 1 - Baseline Documentation

- Keep this baseline as the main pre-improvement checkpoint.
- Create canonical module docs for products, page sections, global settings/assets, categories, destinations, FAQs, and bookings.
- Create database relationship and table map docs.
- Create admin dashboard structure docs.

### Phase 2 - Security Hardening Audit

- Audit admin auth, public registration, policies/gates, upload handling, storage exposure, raw tracking scripts, and production environment safety.
- Produce a dedicated security report before making code changes.

### Phase 3 - Backend-to-Frontend Sync Plan

- Map all hardcoded frontend fallback content.
- Decide what belongs in Page Sections, Global Settings, module data, or safe fallback code.
- Preserve existing frontend while moving ownership into CMS data step by step.

### Phase 4 - Performance and SEO Baselines

- Create runtime performance baseline.
- Review global settings caching.
- Standardize image optimization.
- Confirm sitemap/robots.
- Review structured data and FAQ schema.
- Define AI discovery crawler policy.

### Phase 5 - Testing and Launch QA

- Build admin CRUD tests.
- Add authorization and upload negative-case tests.
- Add SEO/rendering tests.
- Create canonical launch checklist with scoring.
- Run full QA before launch or major merge.

## 20. Recommended Next Steps

Recommended immediate next step:

1. STEP SECURITY-01: Security Hardening Baseline Audit, read-only first.

Alternative safe next steps:

1. STEP DOC-03: Create Canonical Module Docs for Products, Page Sections, and Global Settings.
2. STEP SYNC-01: Backend-to-Frontend Content Ownership Mapping.
3. STEP SEO-01: SEO and AI Discovery Baseline Audit.
4. STEP PERF-01: Performance Baseline Audit.
5. STEP QA-01: Testing and QA Coverage Map.

## Baseline Scoring

| Area | Score | Reason |
| --- | ---: | --- |
| Database | 78/100 | Good normalized CMS structure, but needs relationship docs, price uniqueness review, booking audit, and production-safe seeder clarity. |
| Backend | 76/100 | Laravel MVC/service/request structure is solid, but authorization and some presentation-prep boundaries need improvement. |
| Frontend | 72/100 | Public pages are functional and CMS-fed in key areas, but hardcoded fallbacks, client-side filtering, and discovery gaps remain. |
| Admin Dashboard | 74/100 | Admin modules and lists exist, but role governance, canonical docs, and some workflow QA are incomplete. |
| Security | 58/100 | No confirmed critical exploit, but admin authorization, public registration, raw scripts, uploads, and production posture need hardening. |
| Performance | 68/100 | Pagination/eager loading exists, but global settings caching, image consistency, third-party scripts, and runtime metrics need work. |
| SEO | 64/100 | Product SEO and structured data foundation exist, but sitemap/robots, FAQ schema, category/destination discovery, and AI crawler policy need confirmation. |
| Documentation | 62/100 | AI/docs foundation and mapping exist, but canonical module/security/performance/SEO/QA docs are still incomplete. |
| Testing/QA | 55/100 | Some valuable tests exist, but admin CRUD, authz, uploads, SEO, performance, and launch QA are under-covered. |
| Overall Project Readiness | 68/100 | Strong improvement-ready CMS foundation, but not yet launch-hardened or fully documented/tested. |

## Files Read

- `AGENTS.md`
- `ai/guidelines/*`
- `ai/skills/*`
- `docs/README.md`
- `docs/DOCS-MAP.md`
- `ai/reports/audit/project-existing-audit.md`

## Files Changed

- `ai/reports/baseline/project-baseline-report.md`

## Files Created

- `ai/reports/baseline/project-baseline-report.md`

## Folders Created

- `ai/reports/baseline/`

## Files Deleted

- None.

## Database Impact

- None.

## Route Impact

- None.

## Frontend Impact

- None.

## Backend Impact

- None.

## Security Impact

- Documentation-only baseline. No security-sensitive files were changed.

## Performance Impact

- None.

## SEO Impact

- Documentation-only baseline. No SEO runtime files were changed.

## Testing Performed

- Verified required foundation files were readable.
- Verified `ai/reports/baseline/` did not exist before creation.
- No automated tests were run because this step is documentation-only and must not touch Laravel runtime code or database.

## Rollback Note

To rollback this step, remove only:

- `ai/reports/baseline/project-baseline-report.md`
- `ai/reports/baseline/` if it is empty

