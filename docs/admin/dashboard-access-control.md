# Admin Dashboard Access Control

Last updated: 2026-06-13
Related implementation: STEP SECURITY-08 Super Admin & Admin User Management.

## Purpose

This document records how Bintan Prestige CMS currently protects dashboard access.

The goal is to keep the public frontend open to visitors while keeping CMS access limited to trusted active admin users.

## Current Access Model

Admin dashboard access requires:

1. Authenticated user.
2. `users.is_admin = true`.
3. `users.is_active = true`.
4. `users.role` is `admin` or `super_admin`.

The `admin` middleware checks `User::canAccessAdmin()`.

## Role Rules

| Role | Dashboard access | User management |
| --- | --- | --- |
| `super_admin` | Yes | Yes |
| `admin` | Yes | No |
| inactive admin | No | No |
| non-admin user | No | No |
| guest | No | No |

## Login Behavior

- Super Admin and Admin users redirect to `admin.dashboard` after login.
- Non-admin users redirect to the public homepage after login.
- Inactive users cannot login.
- Guest access to `/admin/dashboard` redirects to `/login`.
- Non-admin or inactive authenticated access to `/admin/dashboard` returns 403.

## Registration Policy

Public registration remains disabled:

- `GET /register` returns 404.
- `POST /register` returns 404.

The existing registration controller/view may remain in the codebase from auth scaffolding, but public routes do not use it for account creation.

## User Management Gate

The `manage-users` Gate allows only Super Admin users.

Protected user management routes:

- `admin.users.index`
- `admin.users.create`
- `admin.users.store`
- `admin.users.edit`
- `admin.users.update`
- `admin.users.deactivate`

Ordinary Admin users cannot access these routes.

## First Admin Provisioning

The first admin remains CLI-only:

```bash
php artisan admin:provision-first
```

The command:

- Prompts for name, email, password, and password confirmation.
- Does not accept password arguments.
- Does not hardcode credentials.
- Does not create a hidden superadmin.
- Creates the first admin as `role = super_admin`, `is_admin = true`, and `is_active = true`.
- Stops if an admin already exists.

## Package Policy

No role/permission package is used for this phase.

The current access model uses:

- Laravel middleware.
- Laravel Gate.
- User model helpers.
- Database fields on `users`.

## Future Improvement

Granular module permissions are still future work. The next security phase should add policy/gate coverage for sensitive CMS content and settings actions without weakening the current Super Admin/Admin foundation.

## Rollback Note

Rollback access-control documentation by reverting this file. Runtime rollback must follow the SECURITY-08 report and migration rollback instructions.
