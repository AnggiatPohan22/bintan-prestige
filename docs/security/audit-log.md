# Security Audit Log

This log records security-relevant audits and fixes. It does not contain secrets.

## 2026-06-13 - STEP DOC-SYNC-SECURITY-08 Documentation Sync

Type: Documentation sync
Runtime behavior changed: No.
Routes changed: No.
Database changed: No.
Migrations changed: No.
Views changed: No runtime views changed.
Public assets changed: No.

### Summary

Security and admin documentation was synchronized after SECURITY-08. The sync records that public registration remains disabled, first admin provisioning remains CLI-only, Super Admin can manage admin users, ordinary Admin cannot manage users, inactive users cannot access admin, and granular module permissions remain future improvement.

### Files Changed

- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

### Files Created

- `docs/admin/user-management.md`
- `docs/admin/dashboard-access-control.md`
- `ai/reports/documentation/doc-sync-security-08-report.md`

### Validation Recorded

- Read `security-07-super-admin-user-management-plan.md`.
- Read `security-08-super-admin-user-management-implementation-report.md`.
- Read existing security docs and changelog before update.
- Read-only implementation validation checked registration route posture, admin user routes, user role helpers, `manage-users` Gate, first-admin command behavior, and absence of a role/permission package in `composer.json`.

### Testing Reference

No runtime tests were required for this documentation-only step.

Latest SECURITY-08 verification remains:

- `php artisan test`: passed, 117 tests, 560 assertions.
- `git diff --check`: passed.

### Related Reports

- `ai/reports/security/security-07-super-admin-user-management-plan.md`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`
- `ai/reports/documentation/doc-sync-security-08-report.md`

### Rollback Note

Rollback by reverting the documentation files listed in this entry. No database rollback is required for this documentation-only sync.

## 2026-06-12 - STEP SECURITY-08 Super Admin & Admin User Management Implementation

Type: Security implementation
Runtime behavior changed: Yes.
Routes changed: Yes, `admin.users.*` routes were added behind `auth`, `admin`, and `can:manage-users`.
Database changed: Yes, a new migration adds admin role/status metadata to `users`.
Migrations changed: New migration only.
Views changed: Yes, Super Admin user management views were added.
Public assets changed: No.

### Summary

SECURITY-08 implemented a minimal multi-user admin foundation without installing a role/permission package. Admin access now requires `is_admin = true`, `is_active = true`, and a valid admin role. Super Admin can manage admin users from the dashboard through the `manage-users` Gate, while ordinary Admin can continue managing CMS content but cannot manage users.

### Files Changed

- `routes/admin.php`
- `app/Models/User.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Console/Commands/ProvisionFirstAdmin.php`
- `app/Providers/AppServiceProvider.php`
- `database/factories/UserFactory.php`
- `resources/views/backend/partials/sidebar.blade.php`
- `resources/views/backend/partials/navbar.blade.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/SecurityBaselineTest.php`
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

### Files Created

- `database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Requests/Admin/StoreAdminUserRequest.php`
- `app/Http/Requests/Admin/UpdateAdminUserRequest.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `tests/Feature/Security/SuperAdminUserManagementTest.php`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`

### Issues Fixed

- Super Admin and ordinary Admin are now separated without a package.
- Admin user management is available only to Super Admin.
- Inactive users cannot login or access admin.
- First admin provisioning now creates a `super_admin` and active account.
- Super Admin cannot deactivate themselves.
- The only active Super Admin cannot be downgraded.

### Issues Still Pending

- Granular policies/gates for CMS content/settings actions remain pending.
- Admin user management audit logging remains pending.
- Tracking script governance remains pending.

### Testing Recorded

- `php artisan migrate`
- `php artisan route:list --path=admin/users`
- `php artisan test tests/Feature/Security/SuperAdminUserManagementTest.php`
- Result: passed, 10 tests, 29 assertions.
- `php artisan test tests/Feature/Console/ProvisionFirstAdminCommandTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php tests/Feature/Security/SuperAdminUserManagementTest.php`
- Result: passed, 32 tests, 98 assertions.
- `php artisan test`
- Result: passed, 117 tests, 560 assertions.

### Related Reports

- `ai/reports/security/security-07-super-admin-user-management-plan.md`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`

### Rollback Note

Rollback code by reverting the files listed above. Roll back the database change with `php artisan migrate:rollback --path=database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php`.

## 2026-06-12 - STEP SECURITY-05 First Admin Provisioning Implementation

Type: Security implementation
Runtime behavior changed: Yes.
Routes changed: No.
Database changed: No schema changes. Command can create the first admin user when executed.
Migrations changed: No.
Views changed: No.
Public assets changed: No.

### Summary

SECURITY-05 implemented a CLI-only first admin provisioning command: `php artisan admin:provision-first`.

The command stops if an admin already exists, asks for admin identity and password through prompts, validates email and password requirements, hashes the password, and creates the first admin user with `is_admin = true`.

### Files Changed

- `bootstrap/app.php`
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`

### Files Created

- `app/Console/Commands/ProvisionFirstAdmin.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `docs/security/admin-provisioning.md`

### Issues Fixed

- The project now has a safe CLI-only path to create the first admin after public registration is disabled.
- No admin credentials are stored in files.
- No password is accepted through command arguments.
- No hidden superadmin or seeder-based admin was added.

### Issues Still Pending

- Granular Laravel policies/gates for individual admin actions remain pending.
- Admin audit logging remains pending.
- Tracking script governance remains pending.

### Testing Recorded

- `php -l app/Console/Commands/ProvisionFirstAdmin.php`
- `php -l tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `php -l bootstrap/app.php`
- `php artisan list --raw | Select-String -Pattern "admin:provision-first"`
- `php artisan admin:provision-first --help`
- `php artisan test tests/Feature/Console/ProvisionFirstAdminCommandTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- Result: passed, 20 tests, 62 assertions.

### Related Reports

- `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`

### Rollback Note

Rollback code by reverting the files listed above. If the command was used to create an admin, remove or demote that specific user through an approved database operation after backup and operator confirmation.

## 2026-06-12 - STEP SECURITY-04 First Admin Provisioning & Granular Authorization Plan

Type: Security planning
Runtime behavior changed: No.
Routes changed: No.
Database changed: No.
Migrations changed: No.
Views changed: No.
Public assets changed: No.

### Summary

SECURITY-04 created a planning report for first-admin provisioning and granular authorization. The preferred next implementation is a CLI-only Artisan command that promotes an existing user to admin without reopening registration, hardcoding credentials, adding hidden superadmin logic, or installing permission packages.

### Files Changed

- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

### Files Created

- `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`

### Decisions Recorded

- Use a CLI-only first-admin provisioning command in the next implementation step.
- Do not add a public web route for provisioning.
- Do not reopen public registration.
- Do not create hidden superadmin logic.
- Do not hardcode credentials or admin emails.
- Do not add a permission package yet.
- Map granular Laravel policy/gate coverage after first-admin provisioning is stable.

### Testing Recorded

- Documentation-only step.
- No runtime tests required.
- `git diff --check` should be run after documentation updates.

### Related Reports

- `ai/reports/security/security-03-admin-access-fix-report.md`
- `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`

### Rollback Note

Rollback by reverting the planning report and documentation updates listed above.

## 2026-06-12 - STEP SECURITY-03 Admin Access Fix

Type: Admin access security fix
Runtime behavior changed: Yes.
Routes changed: Yes.
Database changed: Yes, `users.is_admin` migration has been applied.
Migrations changed: New migration only.
Views changed: No.
Public assets changed: No.

### Summary

STEP SECURITY-03 implemented the approved admin access policy from SECURITY-02. Public registration is disabled, admin access now requires `users.is_admin = true`, non-admin login redirects to the public homepage, and the public frontend remains available to guests.

### Files Changed

- `routes/auth.php`
- `routes/admin.php`
- `routes/web.php`
- `bootstrap/app.php`
- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `database/factories/UserFactory.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/SecurityBaselineTest.php`
- `tests/Feature/Admin/*.php`
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

### Files Created

- `app/Http/Middleware/AdminMiddleware.php`
- `database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- `ai/reports/security/security-03-admin-access-fix-report.md`

### Issues Fixed

- `users.is_admin` migration is available and has been applied.
- Public registration page is no longer accessible.
- Public registration POST route is no longer accessible.
- Admin route group now requires `auth` and `admin`.
- Legacy `/dashboard` backend route now requires `admin`.
- Non-admin authenticated users receive 403 for admin dashboard access.
- Admin users can still access the admin dashboard.
- Non-admin login no longer redirects to `admin.dashboard`.

### Issues Still Pending

- First trusted admin user still needs a safe provisioning procedure.
- Granular policies/gates for individual admin actions are not implemented yet.
- Tracking script governance remains pending.
- Production environment verification remains pending.

### Testing Recorded

- `php -l` passed for changed runtime PHP files.
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- Result: passed, 14 tests, 24 assertions.
- `php artisan test tests/Feature/Admin`
- Result: 58 passed, 4 failed, 352 assertions.
- Admin test failures were existing page-section content/order assertion drift in `PageSectionMediaSlotTest`, not admin authorization failures.

### Related Reports

- `ai/reports/security/security-02-admin-authorization-registration-policy-audit.md`
- `ai/reports/security/security-03-admin-access-fix-report.md`

### Rollback Note

Rollback by reverting the files listed above. If the migration has been run, roll it back with `php artisan migrate:rollback --path=database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`.

## 2026-06-12 - STEP IMPROVE-01 Security Baseline Fix

Type: Safe security baseline fix
Runtime behavior changed: Upload validation became stricter.
Routes changed: No.
Database changed: No.
Migrations changed: No.
Views changed: No.
Public assets changed: No.

### Summary

STEP IMPROVE-01 applied a small hardening pass based on `ai/reports/baseline/project-baseline-report.md`.

### Files Changed

- `.env.example`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreDestinationRequest.php`
- `app/Http/Requests/UpdateDestinationRequest.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- `tests/Feature/SecurityBaselineTest.php`
- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`

### Issues Fixed

- `.env.example` now defaults to `APP_DEBUG=false`.
- Product upload rules now include extension validation.
- Destination upload rules now include extension validation.
- Page Section upload rules now include extension validation.
- Global Asset upload rules now include extension validation.
- `PageSectionImageService` validates allowed file extensions before storing page-section and site-asset files.
- Guest access to `/admin/dashboard` is covered by a focused regression test.

### Issues Still Pending

- Admin role/policy authorization is not implemented yet.
- Public registration remains enabled.
- Raw admin-managed tracking scripts still need governance and access-control decisions.
- Production `.env` posture must be verified outside Git.
- Upload negative-case tests should be expanded beyond the first focused baseline test.
- Public storage/upload exposure needs a dedicated review.

### Testing Recorded

- `php artisan test tests/Feature/SecurityBaselineTest.php`
- Result: passed, 3 tests, 5 assertions.
- Changed PHP files passed `php -l`.
- `git diff --check` passed for the files changed in STEP IMPROVE-01.

### Related Reports

- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`
- `ai/reports/security/improve-01-security-baseline-fix-report.md`
- `ai/reports/documentation/improve-02-documentation-sync-report.md`

### Rollback Note

Rollback STEP IMPROVE-01 by reverting the files listed above. No database rollback is required.

## 2026-06-12 - STEP IMPROVE-02 Documentation Sync Fix

Type: Documentation sync
Runtime behavior changed: No.
Routes changed: No.
Database changed: No.
Migrations changed: No.
Views changed: No.
Public assets changed: No.

### Summary

Security documentation was synchronized after STEP IMPROVE-01. This entry records that security checklist, baseline, changelog, and report files were added without applying new security fixes.

### Related Report

- `ai/reports/documentation/improve-02-documentation-sync-report.md`
