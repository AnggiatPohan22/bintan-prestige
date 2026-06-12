# Security Baseline

Date: 2026-06-13
Status: Updated after STEP DOC-SYNC-SECURITY-08 documentation sync.

## Executive Summary

Bintan Prestige CMS has a stronger security baseline after the SECURITY-03 through SECURITY-08 chain. Public registration is disabled, admin routes now require an authenticated active admin user, first-admin provisioning is CLI-only, and Super Admin can manage admin users from the dashboard without a complex permission package.

The project is not yet fully security-hardened. SECURITY-08 adds the minimal Super Admin/Admin foundation. The next implementation work should focus on granular controller-level policies/gates for CMS content and settings actions.

## Security Fixes Already Applied

### Environment Safety

- `.env` remains local and ignored.
- `.env.example` is tracked.
- `.env.example` now defaults to `APP_DEBUG=false`.
- `.gitignore` covers `.env`, `.env.backup`, `.env.production`, `public/storage`, `public/uploads`, and `storage/*.key`.

### Admin Route Protection

- Admin routes now use both `auth` and `admin` middleware.
- The `users.is_admin` migration has been applied to the active database.
- The `users.role`, `users.is_active`, and `users.created_by` migration has been applied to the active database.
- The `admin` middleware checks `User::canAccessAdmin()`.
- `User::canAccessAdmin()` requires `is_admin = true`, `is_active = true`, and role `admin` or `super_admin`.
- `User::isSuperAdmin()` identifies active Super Admin users.
- No role/permission package is used for this phase.
- The legacy `/dashboard` backend route also requires `admin`.
- Guest access to `/admin/dashboard` redirects to `/login`.
- Non-admin authenticated access to `/admin/dashboard` returns 403.
- Admin authenticated access to `/admin/dashboard` returns 200.
- Inactive admin authenticated access to `/admin/dashboard` returns 403.

### Super Admin User Management

- Admin user management lives under `admin.users.*`.
- All admin user management routes require `auth`, `admin`, and `can:manage-users`.
- The `manage-users` Gate allows only active Super Admin users.
- Super Admin can create Admin and Super Admin users explicitly from the dashboard.
- Ordinary Admin cannot access user management routes.
- Super Admin cannot deactivate themselves.
- The only active Super Admin cannot be downgraded.
- Permanent user deletion was not added; deactivation is used instead.
- User management does not use public registration.
- User management does not use hidden superadmin logic.
- No admin credential is hardcoded in the user-management flow.

### Registration Policy

- Public `GET /register` now returns 404.
- Public `POST /register` now returns 404.
- The existing registration controller was not deleted, but public routes no longer point to it.
- Newly created users are non-admin by default because `users.is_admin` defaults to false.

### Login Redirect Policy

- Admin users continue to redirect to `admin.dashboard` after login.
- Non-admin users now redirect to the public homepage after login.
- Non-admin users do not receive an intended admin redirect from the normal login flow.

### First Admin Provisioning

- First admin provisioning is CLI-only through `php artisan admin:provision-first`.
- The command stops if any admin user already exists.
- The command asks for name, email, password, and password confirmation interactively.
- The command validates email format and uniqueness.
- The command validates password strength and confirmation.
- The command hashes the password with Laravel Hash before saving.
- The command creates the first admin with `is_admin = true`, `role = super_admin`, and `is_active = true`.
- The command does not accept a password argument and does not print the password.
- The command does not hardcode an email or password.

### Upload Validation

STEP IMPROVE-01 added `extensions` validation to upload surfaces that already used `image`, `mimes`, and `max`.

Covered upload areas:

- Product thumbnail.
- Product gallery.
- Destination image.
- Page Section image.
- Page Section mobile image.
- Page Section slot uploads.
- Page Section gallery media uploads.
- Global Asset logos.
- Favicon.
- Social share image.
- SEO default OG image.
- Default media placeholder assets.

### Upload Storage Guard

`PageSectionImageService` now validates allowed extensions before storing uploaded page-section and site-asset files.

Allowed extensions:

- `jpg`
- `jpeg`
- `png`
- `webp`
- `ico`
- `svg`

The favicon flow preserves existing support for `ico`, `png`, `svg`, `webp`, `jpg`, and `jpeg`.

### Suspicious Function Scan

The STEP IMPROVE-01 scoped scan did not find:

- `eval`
- `shell_exec`
- `exec`
- `system`
- `passthru`
- `base64_decode`
- `unserialize`

## Security Issues Still Pending

### High Priority

1. Granular admin authorization implementation

   SECURITY-06 maps policies and gates for products, categories, destinations, FAQs, page sections, product submodules, dashboard, and global assets. Implementation is still pending for content/settings actions.

2. Tracking script governance

   Admin-managed custom tracking scripts remain powerful. Future work should define who can edit them, whether changes are logged, and whether they should be restricted by role or environment.

3. Production environment verification

   Local `.env` was not edited during STEP IMPROVE-01. Production must be verified separately with `APP_DEBUG=false` and no exposed secrets.

### Medium Priority

1. Upload negative-case suite

   The first baseline test covers unsafe extension rejection. More endpoint-level upload tests should be added later.

2. Storage/public exposure review

   Public storage and upload paths should be reviewed before production deployment.

3. SVG handling policy

   SVG remains allowed for favicon compatibility. Future work should decide whether this is acceptable long-term and whether sanitization is required.

4. Security audit logging

   Security-sensitive admin setting and user-management changes should eventually have an audit-log strategy.

## Security Testing Baseline

Current focused tests:

- `php artisan test tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Console/ProvisionFirstAdminCommandTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Security/SuperAdminUserManagementTest.php`
- `php artisan test tests/Feature/Admin`
- `php artisan test`

Recorded result:

- SECURITY-08 focused Super Admin suite: passed, 10 tests, 29 assertions.
- SECURITY-08 auth/security focused suite: passed, 32 tests, 98 assertions.
- Full test suite: passed, 117 tests, 560 assertions.

Covered by test:

- Guest admin dashboard access redirects to login.
- Non-admin admin dashboard access is forbidden.
- Admin admin dashboard access is allowed.
- Public registration GET and POST routes are disabled.
- Non-admin login redirects to the public homepage.
- Admin login redirects to admin dashboard.
- Public homepage remains accessible.
- First admin command creates an admin user.
- First admin command creates a Super Admin user.
- First admin command stores a hashed password.
- First admin command stops if an admin already exists.
- First admin command rejects duplicate email.
- First admin command rejects password confirmation mismatch.
- Super Admin can access admin user management.
- Ordinary Admin cannot access admin user management.
- Super Admin can create Admin and Super Admin users.
- Inactive users cannot login or access admin.
- Super Admin cannot deactivate themselves.
- The only active Super Admin cannot be downgraded.
- Image content with unsafe extension fails validation.
- `.env.example` defaults `APP_DEBUG=false`.

## Related Files

- `.env.example`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreDestinationRequest.php`
- `app/Http/Requests/UpdateDestinationRequest.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- `tests/Feature/SecurityBaselineTest.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `routes/auth.php`
- `routes/admin.php`
- `routes/web.php`
- `bootstrap/app.php`
- `database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- `database/factories/UserFactory.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `app/Console/Commands/ProvisionFirstAdmin.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Requests/Admin/StoreAdminUserRequest.php`
- `app/Http/Requests/Admin/UpdateAdminUserRequest.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `tests/Feature/Security/SuperAdminUserManagementTest.php`
- `docs/security/admin-provisioning.md`
- `docs/admin/user-management.md`
- `docs/admin/dashboard-access-control.md`

## Related Reports

- `ai/reports/baseline/project-baseline-report.md`
- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`
- `ai/reports/security/improve-01-security-baseline-fix-report.md`
- `ai/reports/documentation/improve-02-documentation-sync-report.md`
- `ai/reports/security/security-02-admin-authorization-registration-policy-audit.md`
- `ai/reports/security/security-03-admin-access-fix-report.md`
- `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`
- `ai/reports/security/security-07-super-admin-user-management-plan.md`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`
- `ai/reports/documentation/doc-sync-security-08-report.md`

## Recommended Next Step

Run the next granular authorization implementation step for CMS content and settings actions. Keep the new Super Admin/Admin foundation intact and avoid adding a complex role/permission package.

## Rollback Note

Rollback STEP SECURITY-08 by reverting the created/changed files from that step and running `php artisan migrate:rollback --path=database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php`. Do not delete user rows without separate approval and backup.
