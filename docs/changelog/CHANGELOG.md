# Changelog

All notable project documentation and baseline improvement steps are tracked here.

## 2026-06-29 — Phase 6: Flexible Content Modeling (in progress)

### Stage A — Foundation, Decisions & Debt Clearing

**A0 Architecture Decision Record**
- Locked Phase 6 architecture §3.1–§3.6 (grand plan). Entry-body model: **Option A** — polymorphic `page_blocks` (`blockable_type`/`blockable_id`), approved by owner. No `content_entries.body` column. A3/B9 now active (A3 still gated on its own schema approval).
- Confirmed **zero new packages**. Created `ai/reports/phase-6/a0-architecture-decisions.md`.

**A1 Carry-over Debt — TD-04 + TD-05**
- TD-04: `text` widget output now sanitized via `InlineContentSanitizer::richtext()` (was raw `{!! !!}`).
- TD-05: moved `FormDefinition::find()` out of `contact-form` Blade into `PageRenderData::prepareContactFormBlocks()`; partial reads `$block->resolvedFormDefinition`.
- Strengthened the Blade-query guard test to reject `::find(` / `\App\Models\`.
- Added `tests/Feature/Phase6/A1DebtClearingTest.php` (6 tests). Zero new PHPStan errors; zero new test failures.
- Flagged pre-existing baseline regressions from the `4c6cd6d` brand-identity merge (3 PHPStan errors + 1 failing admin test) as TD-06 / TD-07 — not introduced by Phase 6.

**A1+ Baseline Fix — TD-06 + TD-07** (clear pre-existing RED before A2)
- TD-06: `AdminDashboardAppearance` `static`→`self` (`getCurrent`/`makeDefault`); removed redundant `is_array` guard in `AdminAppearanceService::toCssVars`. PHPStan restored to 0 errors.
- TD-07: updated `PageBlockManagementTest` block delete-state assertions to the new `data-confirm` confirmation + `aria-label="Delete …"` markup (Command Center Dark refactor). Full suite 627/627 green.
- Hard gate restored: PHPStan level 5 / 0 errors, suite green.

**A1+ Admin Copy Consistency — TD-08**
- Translated the 3 Indonesian `data-confirm` strings to English to match the admin convention (block delete, menu-item delete, appearance reset).
- Fixed a curly-quote markup bug (`type=”submit” data-confirm=”…”`) in `menus/partials/item-row.blade.php` that silently disabled the menu-item delete confirmation.
- Synced the TD-07 assertion to the new English block-delete copy; added a menu-item delete regression test (straight quotes + English). Full suite 633 green.

## 2026-06-23 — Phase 5: Visual Page Builder

### Phase 5 Stage A — Foundation Hardening

**A1 Backend Readiness Audit**
- Audited all Phase 4 controllers, models, and services for Phase 5 readiness.
- No breaking issues found; deferred items documented.

**A2 Admin Dashboard UX Refactor**
- Refactored admin sidebar into 6 named groups (Content, Products & Tours, Forms, Appearance, Marketing, System).
- Added shared admin components: `x-admin.data-table`, `x-admin.form-shell`, `x-admin.publish-box`, `x-admin.command-palette`.

**A3 Block Library Expansion**
- Added 8 new block types: `heading`, `stats`, `button_group`, `columns`, `group`, `tour_itinerary`, `pricing_table`, `video_embed`.
- Added frontend render Blade files for all new types.
- Block registry now covers 19 types across 5 categories.

**A4 Content & Data Efficiency**
- Resolved N+1 on page block tree loading with eager loading of `children`.
- Block type config loaded from Laravel config cache (not per-block DB queries).

**A5 Frontend Polish**
- Added CSS custom property theme token system (`--color-primary`, `--font-heading`, etc.).
- `ThemeService` injects active theme tokens into `<head>` on every frontend render.

### Phase 5 Stage B — Visual Page Builder

**B0 Architecture Decision**
- Decided: iframe + `srcdoc` live preview; full tree save on each save action; Alpine.js store for all client state; `@alpinejs/sort` for drag-drop; fixed `20:60:20` panel grid.
- `builder_templates` separate from `page_templates` (layout shells).

**B1 Builder Shell & Canvas**
- Implemented `GET /admin/pages/{page}/builder` → `PageBuilderController@show`.
- 3-panel layout: left block inserter, center iframe canvas, right settings panel.
- Fixed-viewport flex grid (`20:60:20`). No JS scaling.

**B2 Block Insertion & Ordering**
- Add-block inserter with block type search/filter.
- Drag-drop reordering via `@alpinejs/sort` + manual drag-drop nesting.
- Nesting: `group` / `columns` can contain child blocks (max depth 2).

**B3 Block Settings Panel**
- Schema-driven right panel reading `config/blocks.php` fields array.
- All 19 block types fully configurable via panel.
- Field types: text, select, toggle, color, range, image, repeater, list, box, background, richtext, code, showIf conditions.
- Tabbed layout/style/advanced for Group/Columns.

**B4 Inline Editing**
- `contenteditable` in canvas iframe for heading/text/hero/cta blocks.
- On blur → writes to Alpine store → schedules 800ms debounced preview refresh.
- Server-side sanitization: `InlineContentSanitizer::plaintext()` / `::richtext()`.

**B5 Reusable Patterns**
- `builder_patterns` table and `BuilderPatternService`.
- Save block/container subtree as named pattern; insert with re-generated `_cid`s.
- `BuilderPatternController`: `GET/POST/GET/DELETE /admin/builder-patterns`.

**B6 Templates Integration**
- `builder_templates` table and `BuilderTemplateService`.
- Save full page block forest as reusable template.
- Apply template replaces in-memory canvas after user confirmation.
- `BuilderTemplateController`: `GET/GET/POST/DELETE /admin/builder-templates`.
- All tree persistence (save, patterns, templates) routes through `BuilderTreeSanitizer`.

**B7 Responsive Preview Controls**
- Device toggle buttons (Desktop / Tablet / Mobile) in topbar.
- Pure CSS `max-width` device sizing (no JS scaling).
- Per-block `hide_desktop` / `hide_tablet` / `hide_mobile` Advanced tab controls.
- Status bar below canvas shows active device label.
- Badge in block list shows when a block is hidden on any device.

### Phase 5 Stage C — Release Audit

**C1 Static Analysis & Code Quality**
- PHPStan level 5: 0 errors.
- Test suite: 627 tests / 3206 assertions / 0 failures.
- Phase 5 added: +31 tests / +441 assertions over Phase 4 baseline.

**C2 Performance Audit**
- All public routes ≤300ms warm-run.
- No N+1 queries. All Phase 5 DB indexes verified.
- Asset bundles: 212 KB CSS + 87 KB JS (uncompressed).

**C3 Functional Smoke Test**
- All 9 public routes: 200 OK.
- Draft page access correctly returns 404.
- All admin routes redirect to login (unauthenticated).
- 19/19 block types registered and view files present.
- InlineContentSanitizer, BuilderTreeSanitizer, BlockStyle: all functional.
- Phase 1–4 regression: intact.

**C4 Architecture Documentation**
- Created `docs/modules/visual-builder.md` (developer reference).
- Updated `docs/visual-builder-structure.md` with B7 section and change history.
- Added Phase 5 CHANGELOG entry.
- Completed Phase 6 Preparation Notes in handoff doc.

### Database changes (Phase 5)

| Migration | Description |
|-----------|-------------|
| `2026_06_21_000003_add_parent_block_id_to_page_blocks_table` | Adds `parent_block_id` FK for block nesting |
| `2026_06_22_000004_create_builder_patterns_table` | Reusable block patterns library |
| `2026_06_22_000005_create_builder_templates_table` | Reusable page template library |

### Test coverage (Phase 5 additions)

- `tests/Feature/Admin/BuilderPatternTest.php`
- `tests/Feature/Admin/BuilderTemplateTest.php`
- `tests/Feature/Admin/PageBlockManagementTest.php` (Phase 5 additions)
- `tests/Feature/Admin/PageManagementTest.php` (Phase 5 additions)
- `tests/Feature/Frontend/GenericPageRenderingTest.php` (Phase 5 block render additions)

---

## 2026-06-13

### STEP FRONTEND-04D - Homepage Renderer Consolidation & Tests

Changed:

- Added `App\Support\HomepageSectionData` as the homepage PageSection display-data map.
- Consolidated homepage section label/title/description/CTA fallback reads through prepared backend data.
- Moved Destination card and FAQ item preparation into `App\Support\HomepageContent`.
- Removed the unused featured Products homepage query from `HomeController`.
- Added focused homepage renderer regression tests for full data, missing/inactive section fallback, Product price empty state, Destination visibility, testimonial fallback, default media fallback, route contract, and Blade query safety.
- Added canonical homepage and Page Sections documentation.
- Created `ai/reports/frontend/frontend-04d-homepage-renderer-consolidation-tests-report.md`.

Notes:

- No database schema, migration, route, model, package, page builder, homepage redesign, header/footer layout, Product Listing, Product Detail, Review CRUD, or Admin redesign work was added.
- Testimonials remain a documented static fallback until a dedicated CMS strategy is approved.

### STEP FRONTEND-04C - Destination, Review & Final CTA Sync

Changed:

- Synced the homepage destination card section with the existing Destination module collection.
- Kept the legacy `home.categories_intro` PageSection key while rendering active Destination records for the homepage card grid.
- Moved testimonial fallback items out of Blade into `App\Support\HomepageContent`; no review table, model, migration, or CRUD was added.
- Confirmed Footer CTA continues to use `home.footer_cta` copy with Global Settings WhatsApp fallback when CMS URL is empty or invalid.
- Added focused homepage regression tests for active/inactive/soft-deleted Destinations, missing Destination images, testimonial fallback, and Footer CTA Global Settings fallback.
- Created `ai/reports/frontend/frontend-04c-destination-review-final-cta-sync-report.md`.

Notes:

- No database schema, migration, route, model, package, homepage redesign, review module, page builder, header, footer layout, product listing, or product detail flow was changed.
- Granular testimonial/review CMS management remains a future planning item.

### STEP FRONTEND-04B - Homepage Hardcoded Copy Cleanup, No Schema

Changed:

- Added `App\Support\HomepageContent` to prepare editable homepage copy from PageSection fields and controlled `extra_data`.
- Moved supported homepage copy sources out of Blade conditionals for search softcopy/labels, product section CTA/empty state, journey feature cards, category empty state, and FAQ fallback items.
- Added controlled default `extra_data` values to canonical homepage PageSection registry defaults.
- Kept Products, Categories, Destinations, Testimonials, and FAQs module-driven where applicable.
- Added focused homepage copy cleanup tests.
- Created `ai/reports/frontend/frontend-04b-homepage-hardcoded-copy-cleanup-report.md`.

Notes:

- No database schema, migration, route, model, package, homepage redesign, or page builder was added.
- Remaining hardcoded testimonial cards, Why Choose Us cards, placeholder labels, newsletter placeholder behavior, and footer utility placeholder links are intentionally deferred.

### STEP FRONTEND-04A - Homepage Section Key and CTA CMS Sync

Changed:

- Registered homepage PageSection keys in the canonical PageSection registry.
- Scoped homepage PageSection loading to the registered active homepage keys.
- Added `data-section-key` markers for FAQ preview and Footer CTA.
- Added safe PageSection CTA URL handling for homepage CTA links.
- Added Hero CTA rendering when CMS button text and a safe URL are provided.
- Allowed Footer CTA button text/URL to use `home.footer_cta` CMS values while preserving global WhatsApp CTA fallback.
- Added focused homepage CMS/CTA tests.
- Created `ai/reports/frontend/frontend-04a-homepage-section-key-cta-cms-sync-report.md`.

Notes:

- No database schema, migration, route, model, package, or homepage redesign was changed.
- Product, category, destination, FAQ, and global settings module data remain module-driven.
- Existing fixed luxury layout, responsive structure, Tailwind classes, and animations were preserved.
- Full `php artisan test` passed with 153 tests and 735 assertions.

### STEP DOC-SYNC-DB - Database Improvement Documentation Sync

Changed:

- Consolidated DB-01 through DB-09 database improvement outcomes into canonical documentation.
- Added `docs/database/data-integrity.md`.
- Updated database schema, relationship, index, module, performance, admin settings, frontend-backend sync, changelog, and docs index references.
- Created `ai/reports/documentation/doc-sync-db-database-improvement-report.md`.

Notes:

- Documentation-only sync.
- No Laravel runtime logic was changed.
- No database, migration, model, controller, route, service, middleware, view, config, asset, public file, test, or package was changed.
- Historical reports remain in `ai/reports/database/` and `ai/reports/performance/`.

### STEP DB-09 - Category & Destination Foreign Key Restriction Implementation

Changed:

- Added a new migration changing `products.category_id` and `products.destination_id` parent delete behavior from cascade to restrict.
- Added focused tests for Category/Destination soft delete, permanent delete restrictions, controller force-delete guards, empty parent permanent delete, and orphan checks.
- Updated database relationship/module documentation for Category, Destination, and Product delete integrity.
- Created `ai/reports/database/db-09-category-destination-fk-restriction-implementation-report.md`.

Notes:

- No old migration was changed.
- No existing product, category, destination, or product child data was deleted or modified.
- Product child foreign keys were not changed.
- Admin/frontend layout was not changed.
- Soft delete remains the normal Category/Destination archive flow.

### STEP DB-07 - Product Query Index Implementation

Changed:

- Added a new migration for `products(status, created_at)`.
- Added explicit index name `products_status_created_at_index`.
- Updated database, product module, performance, and changelog documentation.
- Created `ai/reports/database/db-07-product-query-index-implementation-report.md`.

Notes:

- No existing migration was changed.
- No product data was changed or deleted.
- No product query behavior, layout, route, controller, or model was changed.
- `product_prices(currency, price)` remains deferred.

### STEP DB-06 - Global Settings Service & Cache Implementation

Changed:

- Added `App\Services\GlobalSettingsService` for public global settings and active site assets.
- Replaced repeated global settings queries in `AppServiceProvider` with cached service reads.
- Added scoped cache invalidation for `SiteSetting` and `SiteAsset` saves/deletes.
- Updated cache payload format to primitive arrays so stale object payloads cannot break typed service properties.
- Added regression coverage for stale invalid cache payloads on `/dashboard`.
- Updated homepage site asset loading to use the global settings service.
- Added focused cache/invalidation tests.
- Added performance/admin/architecture documentation for global settings cache.
- Created `ai/reports/performance/db-06-global-settings-cache-implementation-report.md`.

Notes:

- No database schema, migration, route, layout, package, or product index was changed.
- Cache keys are `global_settings.public.v1` and `global_assets.public.v1`.
- Cache TTL is 30 minutes.
- Legacy/invalid cache payloads are forgotten and rebuilt from database data.
- Public registration and admin authorization were not changed.
- Full `php artisan test` passed with 136 tests and 603 assertions after regression coverage was added.

### STEP DB-04 - Product Price Unique Index & Safe Update Flow Implementation

Changed:

- Added a new migration for unique `product_prices(product_id, currency)`.
- Added ProductPrice currency constants and supported currency helper.
- Updated ProductPriceService to sync prices through a single currency-aware method.
- Added non-negative validation for IDR and SGD product price fields.
- Added focused ProductPrice integrity tests.
- Added database/module documentation for product price integrity.
- Created `ai/reports/database/db-04-product-price-unique-index-implementation-report.md`.

Notes:

- No old migration was changed.
- No existing product price rows were deleted or modified.
- No frontend layout was changed.
- Global settings cache, additional product indexes, and soft-delete/cascade policy were not changed in this step.

### STEP DOC-SYNC-SECURITY-08 - Documentation Sync for Super Admin & Admin User Management

Changed:

- Added `docs/admin/user-management.md`.
- Added `docs/admin/dashboard-access-control.md`.
- Updated security checklist with SECURITY-08 documentation sync status.
- Updated security audit log with documentation-only sync entry.
- Updated security baseline references for Super Admin/Admin access control.
- Created `ai/reports/documentation/doc-sync-security-08-report.md`.

Notes:

- Documentation-only sync.
- No Laravel runtime logic was changed.
- No database, migration, route, controller, model, middleware, runtime view, config, public, or asset files were changed.
- Public registration remains disabled.
- First admin provisioning remains CLI-only.
- No hidden superadmin, hardcoded credential, or role/permission package was introduced.
- Granular module authorization remains future work.

## 2026-06-12

### STEP SECURITY-08 - Super Admin & Admin User Management Implementation

Changed:

- Added `users.role`, `users.is_active`, and `users.created_by` through a new migration.
- Added Super Admin and Admin role helpers to the User model.
- Updated admin middleware to require active admin access.
- Added inactive user login blocking.
- Added `manage-users` Gate for Super Admin only.
- Updated first admin provisioning to create an active `super_admin`.
- Added Super Admin-only admin user management routes, controller, requests, and views.
- Added dashboard navigation for Admin Users visible only to Super Admin.
- Added focused Super Admin user management tests.
- Updated security checklist, audit log, baseline, changelog, and SECURITY-08 report.

Security impact:

- Ordinary Admin can manage CMS content but cannot manage users.
- Super Admin can create Admin and Super Admin accounts explicitly.
- Inactive users cannot login or access admin.
- Super Admin cannot deactivate themselves.
- The only active Super Admin cannot be downgraded.
- Public registration remains disabled.

Verification:

- `php artisan migrate` applied the SECURITY-08 migration.
- Focused Super Admin suite passed with 10 tests and 29 assertions.
- Focused auth/security suite passed with 32 tests and 98 assertions.
- Full `php artisan test` passed with 117 tests and 560 assertions.

### STEP IMPROVE-02 - Documentation Sync After Security Baseline Chain

Changed:

- Synchronized security documentation after SECURITY-03, SECURITY-04, and SECURITY-05.
- Updated security checklist to mark `users.is_admin` schema and first-admin provisioning as documented.
- Updated security audit log to reflect that the SECURITY-03 `users.is_admin` migration has been applied.
- Updated `ai/reports/documentation/improve-02-documentation-sync-report.md`.

Notes:

- Documentation-only sync.
- No Laravel runtime logic was changed.
- No database, migration, route, controller, model, middleware, view, config, public, or asset files were changed.

### STEP SECURITY-05 - First Admin Provisioning Implementation

Changed:

- Added `php artisan admin:provision-first`.
- Registered the command in `bootstrap/app.php`.
- Added console tests for first-admin provisioning.
- Added `docs/security/admin-provisioning.md`.
- Updated security checklist, audit log, baseline, changelog, and SECURITY-05 report.

Security impact:

- First admin creation is now CLI-only.
- Public registration remains disabled.
- No admin email or password is hardcoded.
- No hidden superadmin logic was added.
- No password is accepted as a CLI argument.
- Passwords are stored hashed through Laravel Hash.

Verification:

- Command appears in `php artisan list --raw`.
- Command help shows no custom password argument.
- Focused SECURITY-05 suite passed with 20 tests and 62 assertions.

### STEP SECURITY-04 - First Admin Provisioning & Granular Authorization Plan

Changed:

- Created `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`.
- Updated security checklist, audit log, baseline, and changelog.

Decisions:

- Prefer a CLI-only Artisan command for first-admin provisioning.
- Promote only an existing user by email.
- Do not reopen public registration.
- Do not add hidden superadmin logic.
- Do not hardcode admin credentials.
- Do not install a role/permission package yet.
- Map granular Laravel policies/gates after first-admin provisioning is stable.

Notes:

- Planning-only step.
- No Laravel runtime code changed.
- No database, migration, route, controller, model, middleware, view, config, public, or asset files changed.

### STEP SECURITY-03 - Admin Access Fix

Changed:

- Disabled public `GET /register`.
- Disabled public `POST /register`.
- Added `users.is_admin` through a new migration.
- Added admin middleware and registered the `admin` alias.
- Applied `auth` + `admin` protection to the admin route group.
- Applied `admin` protection to the legacy `/dashboard` backend route.
- Updated login redirect behavior so only admins go to `admin.dashboard`.
- Updated security/auth/admin tests for the new admin access policy.
- Updated security documentation and created `ai/reports/security/security-03-admin-access-fix-report.md`.

Security impact:

- Public registration is closed.
- Non-admin authenticated users can no longer access `/admin`.
- Public frontend routes remain guest-accessible.

Verification:

- Runtime PHP lint passed.
- Focused auth/security tests passed with 14 tests and 24 assertions.
- Admin feature suite reported 58 passed and 4 existing page-section assertion failures unrelated to the admin gate.

### STEP IMPROVE-02 - Documentation Sync Fix

Changed:

- Created `docs/security/checklist.md`.
- Created `docs/security/audit-log.md`.
- Created `docs/security/security-baseline.md`.
- Created `docs/changelog/CHANGELOG.md`.
- Created `ai/reports/security/improve-01-security-baseline-fix-report.md`.
- Created `ai/reports/documentation/improve-02-documentation-sync-report.md`.

Notes:

- Documentation-only sync.
- No Laravel logic was changed.
- No database, migration, route, controller, model, view, config, public, or asset files were changed during this step.

### STEP IMPROVE-01 - Security Baseline Fix

Changed:

- `.env.example` now defaults `APP_DEBUG=false`.
- Upload validation now includes `extensions` checks for product, destination, page-section, and global asset upload surfaces.
- `PageSectionImageService` now validates allowed upload extensions before storing page-section and site-asset files.
- Added `tests/Feature/SecurityBaselineTest.php`.
- Added `ai/reports/security/IMPROVE-01-security-baseline-fix.md`.

Security issues fixed:

- Safer `.env.example` debug default.
- Stronger upload extension validation.
- Regression coverage for guest admin dashboard protection.

Still pending:

- Admin authorization roles/policies.
- Public registration policy.
- Raw tracking script governance.
- Production environment verification.
- Full upload negative-case test suite.

Verification:

- `php artisan test tests/Feature/SecurityBaselineTest.php` passed with 3 tests and 5 assertions.
- Changed PHP files passed `php -l`.
- `git diff --check` passed for the STEP IMPROVE-01 files.

Related reports:

- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`
- `ai/reports/security/improve-01-security-baseline-fix-report.md`
- `ai/reports/documentation/improve-02-documentation-sync-report.md`
