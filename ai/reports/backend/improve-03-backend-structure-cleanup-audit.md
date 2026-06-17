# STEP IMPROVE-03 - Backend Structure Cleanup Audit

Date: 2026-06-13
Status: Completed as audit-only.

## 1. Executive Summary

Bintan Prestige CMS already has a usable Laravel MVC foundation. Admin and public routes are separated, CMS modules use Eloquent models and relationships, the main Product, Category, Destination, and Admin User flows have Form Request coverage, and security baseline work has closed the critical public-registration/admin-access gap.

The backend is not yet fully clean or scalable. The largest cleanup needs are concentrated in `SiteSettingController`, `PageSectionController`, product submodule validation, missing policy classes, duplicated query/filter patterns, and global settings loading from `AppServiceProvider`. These issues are manageable and should be improved incrementally without rewriting the CMS.

No Laravel code, database schema, migration, route, controller, model, view, config, public file, asset, or test was changed during this audit. This report is the only created file.

## 2. Current Backend Structure

Current backend surface inspected:

- Models live in `app/Models`.
- Admin controllers live in `app/Http/Controllers/Admin`.
- Frontend controllers live in `app/Http/Controllers/Frontend`.
- Admin middleware lives in `app/Http/Middleware/AdminMiddleware.php`.
- Form Requests exist in `app/Http/Requests` and `app/Http/Requests/Admin`.
- Services exist in `app/Services`.
- Support/configuration helpers exist in `app/Support`.
- Public routes are split into `routes/frontend.php`.
- Admin routes are split into `routes/admin.php`.
- Breeze-style auth routes remain in `routes/auth.php`, with public registration disabled by 404 closures.
- Command registration is in `bootstrap/app.php`.
- No `app/Policies` files are currently present.

## 3. Laravel MVC Compliance

Strengths:

- Laravel MVC structure is mostly respected.
- Admin and frontend controllers are separated.
- Eloquent relationships exist for Products, Categories, Destinations, Product Prices, Product Images, Product Features, Product FAQs, Product Itineraries, Product Notes, Page Sections, Bookings, and Admin Users.
- Heavy product CRUD work is partly delegated into services.
- Product listing and product detail frontend flows are prepared in controllers/models before rendering.
- Blade query scan did not find direct database queries in Blade files.

Gaps:

- Some controllers still contain validation, query building, persistence orchestration, and UI-support mapping in one place.
- `SiteSettingController` is too large at 904 lines and handles many separate settings domains.
- `PageSectionController` still owns sync, ordering, validation, JSON parsing, media count rules, and upload orchestration.
- Several product submodule controllers repeat inline validation and redirect patterns.
- Form Request `authorize()` methods mostly return `true`, except Admin User requests. This is acceptable behind the admin middleware for now, but it is not the final authorization shape.
- No policy classes exist yet for module-level authorization.

## 4. Route Structure Findings

Good:

- `routes/admin.php` contains the admin CMS route group with `auth` and `admin` middleware.
- `routes/frontend.php` contains public homepage and product routes.
- `routes/auth.php` keeps auth concerns separate.
- Admin user management is protected with `can:manage-users`.
- `php artisan route:list --path=admin` shows 81 admin routes under `/admin`.
- `php artisan route:list --path=register` confirms GET/POST registration routes still exist but point to 404 closures.

Needs review:

- `routes/web.php` still has a legacy `/dashboard` route rendering `backend.dashboard`. It is protected by `auth`, `verified`, and `admin`, so it is not a critical security issue, but it duplicates the newer `admin.dashboard` route concept.
- `routes/admin.php` is growing large. It is still readable, but future modules may benefit from route section extraction or clearer module grouping.
- `routes/console.php` only has the default `inspire` closure; the first-admin command is registered in `bootstrap/app.php`.

## 5. Controller Structure Findings

Strong controllers:

- `CategoryController` and `DestinationController` use Form Requests and services for core store/update flow.
- `UserManagementController` uses Admin Form Requests and includes important Super Admin safety rules.
- `ProductController` uses `StoreProductRequest`, `UpdateProductRequest`, and `ProductService` for the main product save flow.
- `DashboardController` is small, although it performs multiple count queries directly.

Controllers needing cleanup:

- `SiteSettingController` is the highest-priority cleanup candidate. It combines tab config, query loading, validation rules, settings persistence, asset upload/delete actions, and many support-domain rules.
- `PageSectionController` should eventually move validation to a Form Request and media/update orchestration into a service.
- `ProductController` still contains inline validation for search/booking settings and direct image/thumbnail mutation actions.
- Product submodule controllers repeat validation logic for store/update and should get focused Form Requests if these modules keep growing.
- `FaqController` is small, but uses an internal `validated()` method rather than Form Requests.

## 6. Model & Relationship Findings

Good:

- `Product` has relationships for category, destination, images, prices, booking items, highlights, features, itineraries, FAQs, and notes.
- Product submodule models have `belongsTo(Product::class)`.
- Category and Destination have `hasMany(Product::class)` and SoftDeletes.
- PageSection has media relationships and casts `extra_data` to array.
- User has safe helper methods: `isAdmin()`, `isSuperAdmin()`, and `canAccessAdmin()`.

Needs review:

- `Product::scopeFrontendReady()` eager loads many relationships. This is useful for frontend display, but may be too broad for pages that only need partial product data.
- Product feature accessors use loaded relationship collections. This is fine when `features` is eager loaded, but can create N+1 risks in future contexts if used without eager loading.
- `PageSection::mediaSlot()` and `galleryMedia()` operate on loaded media collection. Current controllers usually load `media`, but future usage should be careful.
- Product slugs are generated with `uniqid()` when missing. It works, but a future cleanup could centralize slug generation and collision handling.
- `User` uses PHP attributes for fillable/hidden and intentionally does not expose `is_admin`, `role`, or `is_active` for mass assignment. That is good for security.

## 7. Validation Findings

Good:

- Product create/update has Form Request coverage.
- Category and Destination create/update have Form Request coverage.
- Admin User create/update has Form Request coverage and strong password rules.
- Auth login request blocks inactive users.
- Upload validation includes image, mimes, extensions, and max-size checks in key places.

Gaps:

- `PageSectionController@update` has large inline validation.
- `SiteSettingController` has many inline validation rule blocks.
- Product submodule controllers use repeated inline validation.
- `ProductController@updateSearchBooking` uses inline validation.
- `FaqController` uses controller-level validation.
- Most non-admin-user Form Request `authorize()` methods return `true`; granular authorization should be added later via policies/gates.
- `UpdateProductRequest` requires `category_id` and `destination_id` but does not use `exists` rules like `StoreProductRequest`.

## 8. Middleware/Auth Findings

Good:

- `AdminMiddleware` checks `canAccessAdmin()`.
- `canAccessAdmin()` requires `is_admin`, active status, and role in `admin` or `super_admin`.
- `manage-users` Gate is defined for Super Admin only.
- Inactive users are blocked during login.
- Public registration remains disabled.

Gaps:

- Granular policies for CMS modules are not implemented yet.
- No `app/Policies` files currently exist.
- Most admin content/settings routes rely on the outer `admin` middleware only.
- High-risk settings, publish, delete, restore, force-delete, and media-delete actions need explicit policy/gate coverage in the next security phase.

## 9. Admin Module Findings

Products:

- Strong module foundation with Product model, controller, service, requests, prices, images, highlights, features, FAQs, itineraries, notes, and frontend rendering.
- Cleanup need: split product image/thumbnail/search-booking actions into services/Form Requests and later policies.

Categories:

- Uses SoftDeletes, Form Requests, service, archive restore, and force delete guard based on product count.
- Cleanup need: future policy for restore/force-delete.

Destinations:

- Uses SoftDeletes, Form Requests, service, image deletion, archive restore, and force delete guard.
- Cleanup need: use shared image upload validation/storage policy and future policy for restore/force-delete.

Product Prices:

- Managed through ProductService/ProductPriceService during product save.
- Cleanup need: explicit policy ability for price management later.

Product Images:

- Managed through ProductController and ProductImageService.
- Cleanup need: move remaining controller deletion/thumbnail actions into service and policy.

Product Features, FAQs, Itineraries, Notes, Highlights:

- Each has a dedicated controller and service.
- Cleanup need: repeated inline validation and redirect fragments should be standardized.

Page Sections:

- Strong CMS concept with registry, page keys, media slots, display options, and frontend sync.
- Cleanup need: controller complexity, inline validation, registry sync side effect in controller, and helper calls from Blade should be reduced.

Admin Users / Super Admin Management:

- Good foundation: route group behind `can:manage-users`, dedicated controller, Form Requests, no hidden superadmin, no public registration.
- Cleanup need: audit logging for admin account changes remains future work.

Global Assets / Settings:

- Functional but controller-heavy.
- Cleanup need: split by settings group, add Form Requests, add service/repository/composer layer, and add explicit gates for high-risk settings.

## 10. Frontend Data Flow Findings

Good:

- Frontend routes are read-only public routes.
- `HomeController` prepares Page Sections, FAQs, featured products, home products, categories, destinations, and site assets before rendering.
- `Frontend\ProductController@index` prepares filters, sorting, price ranges, category/destination lists, vehicle types, durations, and paginated products before rendering.
- `Frontend\ProductController@show` eager loads product relationships and prepares SEO variables.

Needs review:

- `AppServiceProvider` View composer loads global settings and assets for multiple layouts/partials. This centralizes global data, but it performs many settings queries and should become a dedicated cached composer/service.
- Some Blade files perform collection filtering/grouping. This is not a database query issue, but some repeated collection preparation could move into controller/view models for cleaner views.
- `Product::scopeFrontendReady()` loads a broad set of relationships; the frontend listing may not always need all of them.

## 11. Query & Performance Risk

Potential risks:

- `AppServiceProvider` View composer can run several settings queries for each composed view. It should be cached or moved into a dedicated global settings service.
- Dashboard counts run as separate queries. Acceptable now, but could become a dashboard stats service later.
- Frontend product index runs separate queries for product list, filter options, durations, vehicle types, and price range. Acceptable for current scale, but caching stable filter data would help.
- Product feature/category accessors can cause N+1 if used without eager loading in future views.
- `PageSectionController@sections` uses `orderByRaw` with bound internal keys. This is currently controlled and low risk, but should be wrapped in a service if section ordering grows.
- Blade collection filtering in product and page-section views is not DB querying, but could be precomputed for cleaner rendering.

Blade query scan:

- No direct `DB::`, model `::query()`, model `::where()`, or Eloquent `get()/paginate()` database query usage was found in Blade files.
- Matches were collection operations such as `where()`, `first()`, and `get()` on already-passed collections.

## 12. Duplicate Logic Findings

Detected duplication/candidates:

- Product submodule controllers repeat store/update validation and fragment redirect patterns.
- Category and Destination index/archive query patterns are nearly identical.
- CategoryService and DestinationService have similar slug/is_active assignment patterns.
- SiteSettingController repeats `Schema::hasTable`, `SiteSetting::updateOrCreate`, field mapping, boolean casting, and redirect-with-tab patterns.
- `AppServiceProvider` repeats settings group query patterns for each global settings support class.
- PageSection image and global site asset upload flows share storage safety concerns.

## 13. Hardcoded Data Findings

Acceptable fixed configuration:

- `app/Support/*Settings.php` contains field definitions, defaults, labels, and normalization helpers for global settings.
- `PageSectionRegistry` and `HomepageSectionMedia` contain page/section/media slot registry definitions.
- Product feature labels are fixed as `included`, `excluded`, `optional`, `addon`, and `important`.
- Sort options and filter labels are defined in frontend controller/views.

Needs review:

- Some support defaults represent business-facing content. They are safe as fallbacks, but canonical business content should live in database settings where the CMS owns it.
- `resources/views/welcome.blade.php` remains as Laravel scaffold output. It is not used by current public routes, but it is stale and includes auth/register UI assumptions. Do not delete without approval; mark for future cleanup if unused.

## 14. Security Impact

Current secure posture:

- Public registration disabled.
- Admin access requires active admin role.
- Super Admin user management is protected by `manage-users`.
- Upload validation includes file type and extension checks in core upload areas.
- No suspicious function hits were found for `eval`, `shell_exec`, `exec`, `system`, `passthru`, unsafe `base64_decode`, or debug routes in app/routes/resources/database scans.

Remaining risks:

- Granular policies/gates for CMS modules are still pending.
- Tracking integration settings can store large script/text payloads and need explicit governance.
- SVG remains allowed for favicon/site asset compatibility; this should be governed by a documented upload policy.
- Destructive admin actions need explicit policies and direct URL tests.

## 15. Testing Impact

Existing testing posture:

- Tests exist for security baseline, auth, first-admin provisioning, Super Admin user management, global settings, Page Sections, frontend product pages, and profile flows.
- SECURITY-08 report records a full `php artisan test` pass: 117 tests, 560 assertions.
- This audit did not run the full test suite because no runtime code was changed.

Testing gaps:

- Granular module policy tests are still missing.
- Direct URL mutation tests for POST/PUT/PATCH/DELETE admin content routes should be expanded.
- Backend service-level tests for ProductService, settings updates, and PageSection media behavior would improve confidence.
- Query/performance tests are not present.

## 16. Documentation Impact

Good:

- Security documentation is current through SECURITY-08.
- Admin user management docs exist.
- Dashboard access control docs exist.
- Legacy docs were mapped in `docs/DOCS-MAP.md`.

Gaps:

- Canonical module docs under `docs/modules/` are still placeholders.
- Canonical database docs under `docs/database/` are still placeholders.
- Architecture docs for MVC flow, route map, backend-to-frontend sync, and global settings flow are still missing.
- No canonical backend cleanup roadmap existed before this report.

## 17. Critical Issues

No critical backend structure issue was found during this audit.

Reason:

- Public admin bypass is closed by current middleware.
- Public registration is disabled.
- No direct database queries were found in Blade.
- No suspicious executable function usage was found in scanned application paths.

## 18. High Priority Issues

1. `SiteSettingController` is too large and mixes too many responsibilities.
2. Granular policies/gates for admin CMS actions are missing.
3. Global settings loading in `AppServiceProvider` risks repeated queries and should be cached/centralized.
4. High-risk settings actions such as tracking, structured data, SEO defaults, asset deletion, and default media updates need explicit authorization boundaries.
5. `PageSectionController@update` should move validation/orchestration into a Form Request and service.

## 19. Medium Priority Issues

1. Product submodule controllers repeat inline validation and mutation patterns.
2. `ProductController` still owns image deletion, thumbnail, status toggle, featured toggle, and search/booking settings logic.
3. `FaqController` lacks Form Request coverage.
4. `UpdateProductRequest` should align category/destination validation with `StoreProductRequest`.
5. `Product::scopeFrontendReady()` may eager load more data than needed for listing pages.
6. Category/Destination archive query patterns could be standardized later.
7. Dashboard count queries could move to a dashboard stats service.

## 20. Low Priority Issues

1. Legacy `/dashboard` route duplicates admin dashboard concept, although it is protected.
2. `resources/views/welcome.blade.php` is stale scaffold output and likely unused.
3. Some code formatting is inconsistent in older controllers/services.
4. Blade collection preparation could be moved to controllers/view models for presentation cleanliness.
5. `routes/admin.php` can remain as-is now, but may need modular grouping as route count grows.

## 21. Recommended Backend Cleanup Roadmap

Phase 1 - Authorization foundation:

- Implement minimal Laravel policies/gates for high-risk CMS actions.
- Keep `AdminMiddleware` as the outer gate.
- Start with products, categories, destinations, page sections, global settings, and force-delete/publish/media actions.

Phase 2 - Settings controller cleanup:

- Split `SiteSettingController` behavior by settings group or introduce dedicated services/Form Requests.
- Create a global settings read service/composer with caching.
- Add explicit governance for tracking scripts and SVG uploads.

Phase 3 - Page Sections backend cleanup:

- Create `UpdatePageSectionRequest`.
- Move PageSection update/media orchestration into a service.
- Prepare view data in controller/service so Blade does not need helper decisions.

Phase 4 - Product module cleanup:

- Create Form Requests for product search/booking settings and product submodule mutations.
- Move product image/thumbnail delete and status/featured toggles into service methods.
- Add policy checks for product-owned submodules.

Phase 5 - Documentation and testing:

- Create canonical docs for products, categories, destinations, page sections, global settings, and admin users.
- Add database relationship docs.
- Add direct URL policy tests for admin mutation routes.
- Add service-level tests for product/page-section/settings flows.

## 22. Files Inspected

AI rules and skills:

- `AGENTS.md`
- `ai/skills/backend-skill.md`
- `ai/skills/cms-architect-skill.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/security-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/skills/documentation-skill.md`

Documentation and prior reports:

- `docs/README.md`
- `docs/DOCS-MAP.md`
- `docs/admin/README.md`
- `docs/admin/dashboard-access-control.md`
- `docs/admin/user-management.md`
- `docs/security/security-baseline.md`
- `ai/reports/security/security-06-granular-authorization-audit.md`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`

Routes and bootstrap:

- `routes/web.php`
- `routes/auth.php`
- `routes/admin.php`
- `routes/frontend.php`
- `routes/console.php`
- `bootstrap/app.php`
- `app/Providers/AppServiceProvider.php`

Controllers:

- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `app/Http/Controllers/Admin/FaqController.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/ProductHighlightController.php`
- `app/Http/Controllers/Admin/ProductFeatureController.php`
- `app/Http/Controllers/Admin/ProductFaqController.php`
- `app/Http/Controllers/Admin/ProductItineraryController.php`
- `app/Http/Controllers/Admin/ProductNoteController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/BookingController.php`
- `app/Http/Controllers/Auth/*`

Middleware, requests, services, models, support:

- `app/Http/Middleware/AdminMiddleware.php`
- `app/Http/Requests/*`
- `app/Http/Requests/Admin/*`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Services/*`
- `app/Models/*`
- `app/Support/*`
- `app/Policies` path check

Database and tests:

- `database/migrations/*`
- `database/seeders/*`
- `tests/*`

Views scanned:

- `resources/views/**/*.blade.php` query scan

Commands run:

- `php artisan route:list --path=admin`
- `php artisan route:list --path=products`
- `php artisan route:list --path=register`
- `rg` scans for Blade queries and suspicious/debug functions

## 23. Files Recommended for Future Changes

Do not change all of these at once. Use scoped future steps.

High-priority future files:

- `app/Policies/ProductPolicy.php`
- `app/Policies/CategoryPolicy.php`
- `app/Policies/DestinationPolicy.php`
- `app/Policies/FaqPolicy.php`
- `app/Policies/PageSectionPolicy.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreProductRequest.php`
- New Form Requests for Page Sections, settings groups, FAQ, product submodules, and product search/booking settings.
- New settings/global composer or service class.
- Focused tests under `tests/Feature/Security` and `tests/Feature/Admin`.

Documentation future files:

- `docs/architecture/backend-mvc-flow.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/database/schema-overview.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/modules/page-sections.md`
- `docs/admin/global-settings.md`
- `docs/qa/backend-test-plan.md`

## 24. Safe Next Step

Recommended next step:

STEP SECURITY-06B or STEP BACKEND-01, depending on priority.

Preferred path:

1. Implement minimal granular policies/gates first, because the Super Admin/Admin foundation is already in place.
2. Then clean `SiteSettingController` and global settings loading.
3. Then clean `PageSectionController`.
4. Then standardize product submodule requests/services.

Do not start broad backend refactor in one pass. Keep each improvement small, tested, documented, and reversible.

## Backend Scores

| Area | Score |
| --- | ---: |
| Laravel MVC Structure | 78/100 |
| Controller Cleanliness | 60/100 |
| Model Relationship Quality | 82/100 |
| Validation Structure | 70/100 |
| Route Organization | 80/100 |
| Backend Data Processing | 76/100 |
| Security Alignment | 78/100 |
| Testing Readiness | 75/100 |
| Backend Scalability | 68/100 |

Overall backend readiness: 74/100.

## Audit Notes

- Files changed: only this report.
- Files created: `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`.
- Files deleted: none.
- Database impact: none.
- Route impact: none.
- Frontend impact: none.
- Backend impact: none.
- Security impact: audit-only; no security behavior changed.
- Testing performed: route list and static scans only; full test suite was not run because runtime code was not changed.
- Rollback note: delete this report file only if this audit document needs to be removed; no Laravel runtime rollback is needed.
