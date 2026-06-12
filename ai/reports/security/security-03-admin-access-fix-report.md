# STEP SECURITY-03 - Admin Access Fix Report

Date: 2026-06-12
Status: Implemented with focused tests passed.

## Summary

STEP SECURITY-03 implemented the approved admin access policy from SECURITY-02 with a small Laravel-native fix. Public registration now returns 404, admin routes require authenticated users with `users.is_admin = true`, non-admin logins redirect to the public homepage, and public frontend routes remain guest-accessible.

No existing Laravel feature was deleted. No package was installed. No old migration was edited.

## Files Changed

Runtime:

- `routes/auth.php`
- `routes/admin.php`
- `routes/web.php`
- `bootstrap/app.php`
- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `database/factories/UserFactory.php`

Tests:

- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/SecurityBaselineTest.php`
- `tests/Feature/Admin/GlobalBookingCtaSettingsTest.php`
- `tests/Feature/Admin/GlobalBrandColorsSettingsTest.php`
- `tests/Feature/Admin/GlobalBusinessIdentitySettingsTest.php`
- `tests/Feature/Admin/GlobalContactInformationSettingsTest.php`
- `tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php`
- `tests/Feature/Admin/GlobalFaviconSettingsTest.php`
- `tests/Feature/Admin/GlobalFooterSettingsTest.php`
- `tests/Feature/Admin/GlobalNavigationSettingsTest.php`
- `tests/Feature/Admin/GlobalSeoDefaultSettingsTest.php`
- `tests/Feature/Admin/GlobalSocialMediaLinksSettingsTest.php`
- `tests/Feature/Admin/GlobalSocialShareImageSettingsTest.php`
- `tests/Feature/Admin/GlobalStructuredDataSettingsTest.php`
- `tests/Feature/Admin/GlobalTrackingIntegrationsSettingsTest.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php`

Documentation:

- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

## Files Created

- `app/Http/Middleware/AdminMiddleware.php`
- `database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- `ai/reports/security/security-03-admin-access-fix-report.md`

## Migration Created

- `database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`

The migration adds `users.is_admin` as a boolean column with default `false`. The rollback drops the column.

## Database Impact

- Adds one new column to the existing `users` table.
- Existing users become non-admin by default after migration.
- No database data is deleted.
- No old migration is modified.

Operational note: at least one trusted existing user must be promoted to `is_admin = true` before that user can access the CMS after the migration and code are deployed together.

## Route Impact

- `GET /register` returns 404.
- `POST /register` returns 404.
- `/admin/*` routes keep their names/controllers but now require `auth` and `admin`.
- `/dashboard` keeps its route name but now requires `auth`, `verified`, and `admin`.
- Public frontend routes in `routes/frontend.php` were not changed.

## Auth Impact

- Admin access is determined by `User::isAdmin()`.
- `User::isAdmin()` reads the casted `is_admin` boolean.
- Non-admin authenticated users receive 403 on admin routes.
- Admin authenticated users can access the admin dashboard.
- Non-admin login redirects to the public homepage.
- Admin login redirects to `admin.dashboard`.

## Frontend Impact

- Public frontend visitor access remains unchanged.
- Homepage access is covered by `tests/Feature/SecurityBaselineTest.php`.
- No frontend Blade view, public asset, or UI styling file was changed.

## Security Impact

Fixed:

- Public self-registration is disabled.
- Admin access is no longer available to every authenticated user.
- Legacy `/dashboard` backend route is no longer a bypass.
- Regression tests now cover guest, non-admin, admin, registration, and public homepage behavior.

Still pending:

- Safe first-admin provisioning workflow.
- Granular policies/gates for sensitive admin actions.
- Audit logging for high-risk admin settings.
- Tracking script governance.
- Production environment verification.

## Testing Performed

Passed:

- `php -l app/Http/Middleware/AdminMiddleware.php`
- `php -l app/Models/User.php`
- `php -l app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `php -l database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- `php -l database/factories/UserFactory.php`
- `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php`

Focused test result:

- Passed: 14 tests, 24 assertions.

Admin suite smoke test:

- Command: `php artisan test tests/Feature/Admin`
- Result: 58 passed, 4 failed, 352 assertions.
- Failure source: `tests/Feature/Admin/PageSectionMediaSlotTest.php` content/order assertions expecting historical page-section keys such as `home.hero` and `home.faq`.
- Assessment: the admin middleware gate allowed admin users through; the remaining failures are existing page-section documentation/test drift and were not fixed in this security step.

Route inspection:

- `php artisan route:list --path=admin/dashboard -v` shows `web`, `auth`, `admin`.
- `php artisan route:list --path=dashboard -v` shows `/dashboard` with `web`, `auth`, `verified`, `admin`.
- `php artisan route:list --path=register` shows registration routes still exist but now point to 404 closures.

## Remaining Risks

- Existing users are non-admin after migration until explicitly promoted.
- No role hierarchy or permissions model exists yet.
- Admin controllers still do not use per-action policies.
- Registration controller still exists, although public routes no longer point to it.
- Admin page-section tests have existing content/order drift that should be handled in a dedicated QA/docs step.

## Rollback Note

Rollback code by reverting the files listed in this report.

If the migration has been run, rollback the database change with:

```bash
php artisan migrate:rollback --path=database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php
```

After rollback, public registration and generic authenticated admin access would return to the previous behavior unless separate controls are added.

## Recommended Next Step

Run STEP SECURITY-04: First Admin Provisioning & Granular Authorization Plan. The next step should define a safe way to promote the first admin user, then map policies/gates for high-risk admin create/update/delete/settings actions.
