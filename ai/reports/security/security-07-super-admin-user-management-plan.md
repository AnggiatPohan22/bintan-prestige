# STEP SECURITY-07 - Super Admin & Admin User Management Plan

Date: 2026-06-12
Status: Planning only. No code, database, route, controller, model, view, config, public, or asset changes were made.

## Executive Summary

The current security baseline is ready for a minimal multi-user admin foundation. Public registration is disabled, all `/admin/*` routes are protected by `auth` and `admin`, `users.is_admin` exists, and the first admin can be provisioned through a CLI-only command.

The next implementation should add a simple two-role model without installing a permission package:

- `super_admin`: full CMS access plus admin user management.
- `admin`: CMS content/settings access, but no user management.

The implementation should keep `is_admin` as the outer CMS access flag, add `role`, `is_active`, and `created_by` to `users`, update admin access checks to require active users, and add one Laravel-native Gate named `manage-users`.

## Current Auth Status

Current state from inspected files:

- Laravel version: `Laravel Framework 13.11.2`.
- `GET /register` and `POST /register` return 404 in `routes/auth.php`.
- `/admin/*` routes are grouped in `routes/admin.php` with `auth` and `admin` middleware.
- `AdminMiddleware` calls `$request->user()?->isAdmin()` and aborts 403 for non-admin users.
- `User::isAdmin()` currently returns `(bool) $this->is_admin`.
- `AuthenticatedSessionController@store` redirects admin users to `admin.dashboard` and non-admin users to `home`.
- `LoginRequest` authenticates by email/password and rate limits attempts, but does not yet block inactive users because `users.is_active` does not exist yet.
- `ProvisionFirstAdmin` creates the first admin with `is_admin = true` and no hardcoded credentials.
- No role, `is_active`, `created_by`, Gate, policy, or user-management controller exists yet.

## Proposed Role Model

Keep the role model intentionally small:

| Role | CMS access | User management | Notes |
| --- | --- | --- | --- |
| `super_admin` | Yes | Yes | Full access. Can create/manage admins. |
| `admin` | Yes | No | Can manage CMS content/settings according to current admin baseline. |
| `null` | No | No | Public/non-admin authenticated user. |

Recommended model helpers:

- `User::isAdmin()`: true only when `is_admin = true`, `is_active = true`, and role is `admin` or `super_admin`.
- `User::isSuperAdmin()`: true only when `isAdmin()` is true and `role = super_admin`.
- `User::canManageUsers()`: proxy/helper for `isSuperAdmin()`.

Compatibility note:

- Keep `users.is_admin` for the current security baseline and tests.
- Use `role` for admin hierarchy.
- A user should not be considered admin if `is_admin = false`, even if `role` accidentally contains an admin role.

## Proposed Database Changes

Create a new migration in SECURITY-08. Do not edit existing migrations.

Proposed columns on `users`:

- `role`: nullable string, recommended max length 50, indexed.
- `is_active`: boolean default `true`, indexed.
- `created_by`: nullable foreign id referencing `users.id`, null on delete.

Recommended migration strategy:

1. Add the three columns in a new migration.
2. Backfill existing `is_admin = true` users to active admin roles:
   - If exactly one existing admin exists, set that user to `role = super_admin` and `is_active = true`.
   - If multiple existing admins exist, set them to `role = admin`, `is_active = true`, and require an operator-approved promotion of one trusted account to `super_admin` before enabling dashboard user management.
   - If no admin exists, do not create any user in the migration; the updated `admin:provision-first` command will create the first `super_admin`.
3. Leave non-admin users with `role = null` and `is_active = true` unless a separate business decision says otherwise.

Rollback:

- Drop `created_by`, `is_active`, and `role`.
- Do not delete users.
- Keep `is_admin` untouched because it belongs to SECURITY-03.

## Proposed Route Map

Add routes inside the existing `Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')` group.

Apply `can:manage-users` to the user management sub-group.

Proposed routes:

| Method | URI | Name | Controller action |
| --- | --- | --- | --- |
| GET | `/admin/users` | `admin.users.index` | `UserManagementController@index` |
| GET | `/admin/users/create` | `admin.users.create` | `UserManagementController@create` |
| POST | `/admin/users` | `admin.users.store` | `UserManagementController@store` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `UserManagementController@edit` |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | `UserManagementController@update` |
| PATCH | `/admin/users/{user}/deactivate` | `admin.users.deactivate` | `UserManagementController@deactivate` |

Do not add public registration routes.

Do not add user deletion in this phase. Deactivation is safer than delete.

## Proposed Controller Map

Create:

- `app/Http/Controllers/Admin/UserManagementController.php`

Recommended actions:

- `index`: list admin-capable users with search, role filter, active filter, and pagination.
- `create`: show create form for Super Admin only.
- `store`: create admin user with role, hashed password, `is_admin = true`, `is_active`, and `created_by = auth()->id()`.
- `edit`: edit admin user metadata/status/role.
- `update`: update name, email, role, status, and optional password.
- `deactivate`: set `is_active = false` with self-protection rules.

Controller authorization:

- Call `Gate::authorize('manage-users')` or use route middleware `can:manage-users`.
- Add explicit self-protection checks in `update` and `deactivate`.
- Never allow ordinary admins to reach these actions.

## Proposed View Map

Create admin views using existing admin dashboard layout/classes:

- `resources/views/backend/users/index.blade.php`
- `resources/views/backend/users/create.blade.php`
- `resources/views/backend/users/edit.blade.php`

Index UI:

- Search by name/email.
- Filter by role.
- Filter by active/inactive.
- Status badges for active/inactive.
- Role badges for `super_admin` and `admin`.
- Deactivate confirmation for destructive state change.
- No delete button in this phase.

Create UI:

- Name.
- Email.
- Password.
- Password confirmation.
- Role select.
- Active toggle.

Edit UI:

- Name.
- Email.
- Optional new password.
- Optional password confirmation.
- Role select with self/single-super-admin guard messaging.
- Active toggle with self-deactivation guard messaging.

## Proposed Gate/Middleware Rules

### Admin Middleware

Update the existing `admin` middleware behavior in SECURITY-08:

- Require authenticated user.
- Require `is_admin = true`.
- Require `is_active = true`.
- Require role in `admin` or `super_admin`.

Recommended behavior:

- Abort 403 for inactive/non-admin access.
- Do not redirect inactive users to admin.
- Do not expose whether an account is inactive through public pages beyond normal auth error messaging.

### Gate

Register Laravel Gate:

- `manage-users`: returns true only for `User::isSuperAdmin()`.

Recommended location:

- `AppServiceProvider::boot()` is acceptable for minimal implementation because no dedicated AuthServiceProvider currently exists.
- A dedicated provider can be introduced later if authorization grows.

### Login Request

Add inactive-user handling in SECURITY-08:

- After successful password authentication, if the user has `is_active = false`, immediately logout/invalidate auth attempt and return a generic auth failure message.
- This prevents inactive users from logging in, not only from accessing admin.

## First Admin Command Impact

Update `app/Console/Commands/ProvisionFirstAdmin.php` in SECURITY-08:

- Existing guard remains: if an admin already exists, stop safely.
- Created user should have:
  - `is_admin = true`
  - `role = super_admin`
  - `is_active = true`
  - `created_by = null`
- Continue to collect name/email/password only through interactive prompts.
- Continue to reject password arguments.
- Continue to hash password with Laravel Hash.
- Continue to avoid printing password or storing credentials in files.

Existing deployment note:

- If a first admin was already provisioned before this role model, the migration/backfill or an explicitly approved operator action must ensure one trusted account becomes `super_admin`.

## Security Rules

Required SECURITY-08 rules:

- Public registration remains disabled.
- Only Super Admin can access `admin.users.*`.
- Ordinary Admin cannot list, create, edit, deactivate, or promote users.
- Inactive users cannot login/access admin.
- Super Admin cannot deactivate themselves.
- Super Admin cannot downgrade themselves if they are the only active Super Admin.
- Super Admin cannot deactivate the only active Super Admin account.
- Creating/updating role must use an allowlist: `admin`, `super_admin`.
- Do not accept arbitrary roles from request data.
- Password is required on create.
- Password is optional on update, but if present must be confirmed and strong.
- Admin users are created only from dashboard by Super Admin.
- Do not create hidden superadmin accounts.
- Do not add seeder-created admin credentials.
- Do not store credentials in documentation or config.

## Validation Plan

Recommended Form Requests:

- `app/Http/Requests/Admin/StoreAdminUserRequest.php`
- `app/Http/Requests/Admin/UpdateAdminUserRequest.php`

Validation rules:

- `name`: required, string, max 255.
- `email`: required, lowercase/email, max 255, unique users email.
- `password` on create: required, confirmed, strong password rule.
- `password` on update: nullable, confirmed, strong password rule.
- `role`: required, in `admin,super_admin`.
- `is_active`: boolean.

Controller-level business rules:

- Block self-deactivation.
- Block self role downgrade if the actor is the only active Super Admin.
- Block deactivating or downgrading the last active Super Admin.

## Testing Plan

Focused tests for SECURITY-08:

1. Guest cannot access `admin.users.index`.
2. Non-admin authenticated user cannot access `admin.users.index`.
3. Ordinary Admin cannot access user management routes.
4. Super Admin can access user management index/create/edit.
5. Super Admin can create an Admin user.
6. Super Admin can create another Super Admin only through allowed role list.
7. Created admin has hashed password, not plaintext.
8. Created admin has `is_admin = true`, selected `role`, `is_active`, and `created_by`.
9. Ordinary Admin can login and access CMS content routes.
10. Ordinary Admin cannot access `admin.users.*`.
11. Inactive Admin cannot login.
12. Inactive Admin cannot access `/admin/dashboard` if already authenticated.
13. Super Admin cannot deactivate themselves.
14. Super Admin cannot downgrade themselves when they are the only active Super Admin.
15. Super Admin cannot deactivate the only active Super Admin.
16. Public registration GET/POST still returns 404.
17. Public frontend homepage still works for guests.
18. Existing admin CMS CRUD tests still pass.
19. Updated first admin command creates `role = super_admin` and `is_active = true`.
20. First admin command still stops if an admin already exists.

Final verification:

- `php artisan test`
- `git diff --check`

## Files That Will Be Created In SECURITY-08

Expected created files:

- `database/migrations/xxxx_xx_xx_xxxxxx_add_admin_role_status_to_users_table.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Requests/Admin/StoreAdminUserRequest.php`
- `app/Http/Requests/Admin/UpdateAdminUserRequest.php`
- `resources/views/backend/users/index.blade.php`
- `resources/views/backend/users/create.blade.php`
- `resources/views/backend/users/edit.blade.php`
- `tests/Feature/Security/SuperAdminUserManagementTest.php`
- `ai/reports/security/security-08-super-admin-user-management-implementation-report.md`

Optional if documentation sync is included in SECURITY-08:

- `docs/security/admin-user-management.md`

## Files That Will Be Changed In SECURITY-08

Expected changed files:

- `routes/admin.php`
- `app/Models/User.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Console/Commands/ProvisionFirstAdmin.php`
- `app/Providers/AppServiceProvider.php`
- `database/factories/UserFactory.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/SecurityBaselineTest.php`
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`

Do not change:

- Existing migrations.
- Public registration routes except to keep them disabled.
- Public frontend routes/views.
- Existing admin CMS route names.
- Existing admin CMS controller method names.

## Risks

- Existing admin lockout risk if role backfill is not handled before `isAdmin()` requires a role.
- Last-super-admin lockout risk if downgrade/deactivate rules are incomplete.
- User management can become a high-risk area because it can create privileged accounts.
- Role drift risk if `is_admin` and `role` become inconsistent.
- Tests must be updated carefully so ordinary Admin still has CMS access but not user management access.
- Adding user management UI to navigation should be visible only to Super Admin.

## Rollback Plan

Rollback SECURITY-08 code changes by reverting created/changed files from that step.

Database rollback:

```bash
php artisan migrate:rollback --path=database/migrations/xxxx_xx_xx_xxxxxx_add_admin_role_status_to_users_table.php
```

Rollback effects:

- Removes `role`, `is_active`, and `created_by`.
- Leaves `is_admin` intact.
- Admin access returns to the SECURITY-03/05 baseline.
- User management dashboard routes/views/controllers should be removed/reverted with the code rollback.

Operational rollback note:

- Do not delete user rows during rollback unless separately approved after backup.

## Recommended Next Step

Proceed to STEP SECURITY-08: Super Admin & Admin User Management Implementation.

Recommended implementation order:

1. Add migration and model casts/helpers with safe existing-admin backfill strategy.
2. Update first admin command for `super_admin` and `is_active`.
3. Update admin middleware and login inactive handling.
4. Add `manage-users` Gate.
5. Add controller, requests, routes, and views for user management.
6. Add tests for Super Admin, Admin, inactive users, public registration disabled, and public frontend unaffected.
7. Run `php artisan test` and `git diff --check`.
