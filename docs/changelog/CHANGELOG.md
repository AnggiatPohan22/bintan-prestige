# Changelog

All notable project documentation and baseline improvement steps are tracked here.

## 2026-06-13

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
