# STEP SECURITY-02 - Admin Authorization & Registration Policy Audit

Date: 2026-06-12
Project: Bintan Prestige CMS
Scope: Read-only audit of admin access, authorization, and registration policy.

## Executive Summary

The admin area is protected by Laravel `auth` middleware, and guest users are redirected away from admin routes. However, there is no granular admin authorization layer yet. Public registration is active, registered users are automatically logged in, login redirects authenticated users to `admin.dashboard`, and the `users` table/model has no `role`, `is_admin`, or permission attribute.

This means a normal registered/authenticated user can access the admin dashboard and admin CRUD/settings routes. In a production CMS, this is a critical access-control issue if public registration remains enabled.

No fixes were applied in this step. This report is audit-only and provides the recommended fix plan and file list for a future approved implementation step.

## Current Auth Structure

### Routing

- `bootstrap/app.php` registers:
  - `routes/web.php`
  - `routes/frontend.php`
  - `routes/admin.php`
- `routes/admin.php` is grouped with:
  - middleware: `auth`
  - prefix: `admin`
  - route name prefix: `admin.`
- `routes/auth.php` exposes login, registration, password reset, email verification, password confirmation, and logout routes.

### User Model

- `app/Models/User.php` contains fillable fields:
  - `name`
  - `email`
  - `password`
- `User` casts:
  - `email_verified_at`
  - `password`
- `User` does not currently expose:
  - `is_admin`
  - `role`
  - `permission`
  - admin capability method
- `MustVerifyEmail` is commented out and not implemented.

### Users Table

`database/migrations/0001_01_01_000000_create_users_table.php` defines:

- `id`
- `name`
- `email`
- `email_verified_at`
- `password`
- `remember_token`
- timestamps

No admin/role column exists in the base users table.

### Login Redirect

`app/Http/Controllers/Auth/AuthenticatedSessionController.php` redirects successful login to:

- `route('admin.dashboard')`

### Registration Redirect

`app/Http/Controllers/Auth/RegisteredUserController.php`:

- allows public registration through `routes/auth.php`
- creates a user
- logs the user in immediately
- redirects to `route('dashboard')`

`routes/web.php` defines `/dashboard` with `auth` and `verified`, but `User` does not implement `MustVerifyEmail`, so verification is not a reliable admin access boundary.

## Admin Route Protection Status

### Positive Findings

- `php artisan route:list --path=admin -v` shows all 75 admin routes use:
  - `web`
  - `auth`
- `routes/admin.php` does not expose admin routes outside the `auth` group.
- IMPROVE-01 added a regression test that guest access to `/admin/dashboard` redirects to `/login`.
- Backend forms use `@csrf` broadly.
- PUT/PATCH/DELETE actions use method spoofing via `@method`.
- AJAX product status toggle uses the admin layout CSRF token with `X-CSRF-TOKEN`.

### Gap

`auth` only proves the user is logged in. It does not prove the user is allowed to administer the CMS.

## Registration Status

Registration is active.

Observed in `routes/auth.php`:

- `GET register`
- `POST register`

Observed in `tests/Feature/Auth/RegistrationTest.php`:

- registration screen is expected to render with HTTP 200
- new users can register
- newly registered users are authenticated
- new registration redirects to `route('dashboard')`

Observed in `tests/Feature/Auth/AuthenticationTest.php`:

- a generic `User::factory()->create()` user can login
- successful login redirects to `admin.dashboard`

## Authorization Gap

### Missing Role/Admin Boundary

No current code path distinguishes:

- admin user
- regular user
- public registered user
- super admin
- editor/operator

### Missing Policy/Gate Layer

No active Laravel policy/gate usage was found for admin actions:

- no `app/Policies` files were found
- no meaningful `Gate::` usage was found
- no `authorize()` controller calls were found in admin controllers
- Form Request `authorize()` methods return `true`

### Admin Actions Affected

The following admin action groups are protected by `auth` but not by role/policy authorization:

- dashboard read access
- products create/update/delete
- product status/featured toggle
- product child data create/update/delete
- category create/update/archive/restore/force-delete
- destination create/update/archive/restore/force-delete
- FAQ create/update/delete
- page section update/media delete
- global assets update/delete
- SEO default settings update/delete
- tracking integrations update
- structured data settings update
- booking CTA settings update
- navigation/footer/business/contact/social settings update

### Direct URL Bypass Risk

Because authorization is route-level `auth` only, a logged-in user can bypass UI navigation and call admin URLs directly if they know the path.

Examples:

- `/admin/dashboard`
- `/admin/products`
- `/admin/settings/global-assets`
- `/admin/settings/global-assets/tracking-integrations`
- `/admin/categories/{id}/force-delete`
- `/admin/destinations/{id}/force-delete`

## Risk Level

Overall risk: Critical for production if public registration remains enabled.

| Finding | Risk | Reason |
| --- | --- | --- |
| Public registration is active | High | Anyone can reach the registration screen and create a user. |
| Any authenticated user can access admin | Critical | No role/policy/admin middleware separates regular users from CMS admins. |
| Login redirects generic users to admin dashboard | High | Successful login points users directly into admin. |
| No policy/gate layer for update/delete/settings actions | High | Sensitive actions rely only on authentication. |
| Raw tracking integration settings are admin-editable by any authenticated user | Critical | A normal authenticated user could inject frontend scripts if they can reach the settings endpoint. |
| Force-delete category/destination routes lack authorization | High | Destructive admin actions need stronger access control. |
| CSRF coverage | Low residual risk | Forms and AJAX show CSRF coverage; issue is authorization, not CSRF. |
| Public frontend separation | Low | Public frontend routes are separate and do not appear to require auth/admin access. |

## Files Inspected

Master/security rules:

- `AGENTS.md`
- `ai/skills/security-skill.md`

Routing and bootstrap:

- `bootstrap/app.php`
- `routes/admin.php`
- `routes/auth.php`
- `routes/web.php`
- `routes/frontend.php`

Auth and user structure:

- `app/Models/User.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/factories/UserFactory.php`

Admin controllers:

- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `app/Http/Controllers/Admin/FaqController.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Controllers/Admin/ProductFaqController.php`
- `app/Http/Controllers/Admin/ProductFeatureController.php`
- `app/Http/Controllers/Admin/ProductHighlightController.php`
- `app/Http/Controllers/Admin/ProductItineraryController.php`
- `app/Http/Controllers/Admin/ProductNoteController.php`

Backend views and forms:

- `resources/views/layouts/admin.blade.php`
- `resources/views/backend/**/*.blade.php`

Tests:

- `tests/Feature/SecurityBaselineTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- selected `tests/Feature/Admin/*`

Commands:

- `php artisan route:list --path=admin`
- `php artisan route:list --path=admin -v`
- `php artisan route:list --name=register`
- `php artisan route:list --path=dashboard`
- `php artisan route:list --path=products`

## Findings

### Finding 1 - Public Registration Is Active

Severity: High

`routes/auth.php` exposes `GET register` and `POST register`. Current registration tests expect public registration to work.

Impact:

- If the CMS should be closed/private, anyone can create an account.
- Because admin only requires `auth`, registration becomes an indirect path into admin.

### Finding 2 - Generic Authenticated Users Can Access Admin

Severity: Critical

`routes/admin.php` uses only `auth`. No role, `is_admin`, policy, or gate check was found.

Evidence:

- `php artisan route:list --path=admin -v` shows all admin routes use `web` and `auth`.
- `app/Models/User.php` has no admin field/capability.
- `database/migrations/0001_01_01_000000_create_users_table.php` has no admin/role column.
- `tests/Feature/Auth/AuthenticationTest.php` logs in a generic factory user and expects redirect to `admin.dashboard`.

Impact:

- A normal authenticated user can access admin URLs directly.
- Sensitive CMS content/settings can be changed by any authenticated user.

### Finding 3 - Login Redirects to Admin Dashboard

Severity: High

Successful login redirects to `admin.dashboard`.

Impact:

- The app treats all authenticated users as admin users.
- If public registration remains enabled, new users can be guided toward admin access after subsequent login.

### Finding 4 - No Policy/Gate Authorization for Admin Actions

Severity: High

Admin controllers perform validation but do not call authorization checks.

Affected areas include:

- products and child modules
- page sections
- site/global settings
- tracking integrations
- SEO defaults
- categories/destinations force-delete

Impact:

- No separation between read/write/delete/settings permissions.
- No future-proof path for editor/operator/superadmin roles.

### Finding 5 - Tracking Integrations Need Special Protection

Severity: Critical

`SiteSettingController::updateTrackingIntegrations()` allows saving tracking/custom script settings behind only `auth`.

Impact:

- If any regular authenticated user reaches the endpoint, they may be able to persist custom frontend scripts.
- This should be restricted to a high-trust admin role or superadmin capability.

### Finding 6 - CSRF Is Present, But Does Not Replace Authorization

Severity: Low residual risk

Backend forms include `@csrf`, destructive forms use method spoofing, and AJAX toggle uses the CSRF token.

Impact:

- CSRF protection appears healthy.
- Authorization remains the real gap.

### Finding 7 - Public Frontend Appears Separated

Severity: Low

`routes/frontend.php` defines public home and product routes separately from admin. Frontend views extend `layouts.frontend`. No public frontend route was found inside the admin route group.

Impact:

- Adding admin authorization middleware later should not affect public frontend if applied only to the admin route group.

## Recommended Fix Plan

No fix was applied in this audit. Recommended implementation should happen in a separate approved step.

### Phase 1 - Decide Registration Policy

Choose one:

1. Closed CMS: disable public registration routes.
2. Invite-only CMS: keep registration only behind invitation/token logic.
3. Public accounts allowed: keep registration, but regular users must never access `/admin`.

Recommended for current CMS: closed or invite-only.

### Phase 2 - Add Admin Authorization Boundary

Choose one:

1. No-schema quick hardening:
   - add admin allowlist middleware using configured trusted email addresses
   - apply it to `routes/admin.php`
   - redirect unauthorized users away from admin

2. Schema-backed hardening:
   - add approved migration for `users.is_admin` or `users.role`
   - add helper method on `User`
   - add `EnsureAdmin` middleware or policy/gate layer
   - apply middleware to admin route group
   - update factories/tests/seeders

Recommended long-term: schema-backed role/admin flag with a dedicated admin middleware, then policies/gates for sensitive actions.

### Phase 3 - Protect Sensitive Settings First

Prioritize:

1. tracking integrations
2. global assets/settings
3. product publish/status toggles
4. force-delete actions
5. page section updates

### Phase 4 - Add Tests

Add tests for:

- guest cannot access admin
- registered non-admin cannot access admin
- admin can access admin
- non-admin cannot update products/settings/page sections/tracking
- admin can update allowed CMS areas
- registration behavior follows chosen policy
- public frontend remains accessible to guests

### Phase 5 - Documentation Sync

Update:

- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`
- relevant AI report file

## Files That May Need Changes

Only for a future approved fix step:

### If Choosing No-Schema Quick Hardening

- `routes/admin.php`
- `bootstrap/app.php`
- new middleware file such as `app/Http/Middleware/EnsureAdmin.php`
- `.env.example` or docs for admin allowlist configuration
- `tests/Feature/Admin/AdminAuthorizationTest.php`
- `docs/security/*`
- `docs/changelog/CHANGELOG.md`

### If Choosing Schema-Backed Admin Roles

- new migration for `users.is_admin` or `users.role`
- `app/Models/User.php`
- `database/factories/UserFactory.php`
- optional seeder/admin setup doc
- `routes/admin.php`
- `bootstrap/app.php`
- new middleware file such as `app/Http/Middleware/EnsureAdmin.php`
- optional policies under `app/Policies`
- admin/security tests
- security docs and changelog

### If Disabling Public Registration

- `routes/auth.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `tests/Feature/Auth/RegistrationTest.php`
- login/guest docs if needed
- security docs and changelog

## Rollback Note

This step created only an audit report. Roll back SECURITY-02 by deleting:

- `ai/reports/security/security-02-admin-authorization-registration-policy-audit.md`

No database rollback is required because no schema or data was changed.

