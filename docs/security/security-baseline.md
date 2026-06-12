# Security Baseline

Date: 2026-06-12
Status: Updated after STEP SECURITY-05 first admin provisioning implementation.

## Executive Summary

Bintan Prestige CMS has a stronger security baseline after the SECURITY-03 through SECURITY-05 chain. Public registration is disabled, admin routes now require an authenticated user with `users.is_admin = true`, non-admin login no longer redirects into the CMS, first-admin provisioning is CLI-only, and regression tests cover guest, non-admin, admin, public registration, first-admin provisioning, and public homepage access.

The project is not yet fully security-hardened. SECURITY-05 adds the CLI-only first-admin provisioning command. The next implementation work should focus on granular controller-level policies and gates.

## Security Fixes Already Applied

### Environment Safety

- `.env` remains local and ignored.
- `.env.example` is tracked.
- `.env.example` now defaults to `APP_DEBUG=false`.
- `.gitignore` covers `.env`, `.env.backup`, `.env.production`, `public/storage`, `public/uploads`, and `storage/*.key`.

### Admin Route Protection

- Admin routes now use both `auth` and `admin` middleware.
- The `users.is_admin` migration has been applied to the active database.
- The `admin` middleware checks `User::isAdmin()`.
- `User::isAdmin()` reads the boolean `users.is_admin` field.
- The legacy `/dashboard` backend route also requires `admin`.
- Guest access to `/admin/dashboard` redirects to `/login`.
- Non-admin authenticated access to `/admin/dashboard` returns 403.
- Admin authenticated access to `/admin/dashboard` returns 200.

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
- The command creates the first admin with `is_admin = true`.
- The command does not accept a password argument and does not print the password.

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

1. Granular admin authorization design

   SECURITY-04 maps policies and gates for products, categories, destinations, FAQs, page sections, product submodules, dashboard, and global assets. Implementation is still pending.

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

   Security-sensitive admin setting changes should eventually have an audit-log strategy.

## Security Testing Baseline

Current focused tests:

- `php artisan test tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Console/ProvisionFirstAdminCommandTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`
- `php artisan test tests/Feature/Admin`

Recorded result:

- Auth and security focused suite: passed, 14 tests, 24 assertions.
- SECURITY-05 focused suite: passed, 20 tests, 62 assertions.
- Admin feature suite: 58 passed, 4 failed, 352 assertions.

Covered by test:

- Guest admin dashboard access redirects to login.
- Non-admin admin dashboard access is forbidden.
- Admin admin dashboard access is allowed.
- Public registration GET and POST routes are disabled.
- Non-admin login redirects to the public homepage.
- Admin login redirects to admin dashboard.
- Public homepage remains accessible.
- First admin command creates an admin user.
- First admin command stores a hashed password.
- First admin command stops if an admin already exists.
- First admin command rejects duplicate email.
- First admin command rejects password confirmation mismatch.
- Image content with unsafe extension fails validation.
- `.env.example` defaults `APP_DEBUG=false`.

Admin suite note:

- The remaining admin failures were in `tests/Feature/Admin/PageSectionMediaSlotTest.php` and were caused by existing page-section content/order assertions expecting historical keys such as `home.hero` and `home.faq`. They were not caused by the admin middleware gate.

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
- `docs/security/admin-provisioning.md`

## Related Reports

- `ai/reports/baseline/project-baseline-report.md`
- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`
- `ai/reports/security/improve-01-security-baseline-fix-report.md`
- `ai/reports/documentation/improve-02-documentation-sync-report.md`
- `ai/reports/security/security-02-admin-authorization-registration-policy-audit.md`
- `ai/reports/security/security-03-admin-access-fix-report.md`
- `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`

## Recommended Next Step

Run STEP SECURITY-06 as a granular authorization implementation. Add Laravel-native policy/gate coverage for sensitive admin actions without introducing a complex role/permission package yet.

## Rollback Note

Rollback STEP SECURITY-05 by reverting the command, command registration, tests, docs, and report updates. If a first admin was created through the command, remove or demote that specific user through an approved database operation after backup and operator confirmation.
