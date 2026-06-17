# Admin User Management

Last updated: 2026-06-13
Related implementation: STEP SECURITY-08 Super Admin & Admin User Management.

## Purpose

This document describes the current admin user management foundation for Bintan Prestige CMS.

Admin accounts are not created through public registration. Public registration remains disabled, and admin users are managed from the dashboard by a Super Admin only.

## Current Status

Admin user management is implemented as a minimal Laravel-native foundation without a role/permission package.

Current admin roles:

| Role | Access | Notes |
| --- | --- | --- |
| `super_admin` | Full CMS access and admin user management | Can create and manage admin users. |
| `admin` | CMS dashboard and content management access | Cannot access admin user management. |

The project still uses `users.is_admin` as the outer CMS access flag. The role field adds admin hierarchy, not a complex permission system.

## Account Creation Policy

Allowed:

- First admin provisioning through the CLI-only command: `php artisan admin:provision-first`.
- Admin account creation from the dashboard by a Super Admin.
- Super Admin creating another Super Admin only by explicitly selecting the `super_admin` role.

Not allowed:

- Public self-registration.
- Public admin invitation or provisioning route.
- Hidden superadmin account.
- Hardcoded admin email or password.
- Seeder-created admin credentials.
- Role/permission package for this phase.

## Dashboard User Management

Super Admin can manage users through:

- `admin.users.index`
- `admin.users.create`
- `admin.users.store`
- `admin.users.edit`
- `admin.users.update`
- `admin.users.deactivate`

All routes are protected by:

- `auth`
- `admin`
- `can:manage-users`

Ordinary Admin users receive 403 if they try to access these routes directly.

## User Status Rules

- Active Admin and Super Admin users can access the admin dashboard.
- Inactive users cannot login.
- Inactive users cannot access admin routes if already authenticated.
- User management does not permanently delete users in this phase.
- Deactivation is used instead of delete.

## Super Admin Safety Rules

- Super Admin cannot deactivate themselves.
- The only active Super Admin cannot be downgraded.
- At least one active Super Admin must remain.
- Ordinary Admin cannot manage users.

## Validation Rules

Create admin user:

- `name`: required.
- `email`: required, email, unique.
- `password`: required, confirmed, strong.
- `role`: required, allowlisted to `admin` or `super_admin`.
- `is_active`: boolean.

Update admin user:

- `name`: required.
- `email`: required, email, unique except current user.
- `password`: optional, confirmed, strong when provided.
- `role`: required, allowlisted to `admin` or `super_admin`.
- `is_active`: boolean.

## Testing Reference

Latest recorded verification:

- `php artisan test tests/Feature/Security/SuperAdminUserManagementTest.php`
- Result: passed, 10 tests, 29 assertions.
- `php artisan test`
- Result: passed, 117 tests, 560 assertions.

## Remaining Work

- Add granular authorization policies/gates for CMS content and settings actions.
- Add audit logging for admin user management changes.
- Define governance for tracking script settings.

## Rollback Note

Rollback the SECURITY-08 implementation by reverting its files and rolling back:

```bash
php artisan migrate:rollback --path=database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php
```

Do not delete user rows without backup and separate approval.
