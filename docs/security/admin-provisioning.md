# Admin Provisioning

Last updated: 2026-06-12
Related step: STEP SECURITY-05 first admin provisioning implementation.

## Purpose

This document explains the safe first-admin provisioning process for Bintan Prestige CMS.

Public registration remains disabled. Admin users must be created through a trusted CLI-only flow.

## Command

Use:

```bash
php artisan admin:provision-first
```

The command is interactive and asks for:

- Admin name.
- Admin email.
- Admin password.
- Admin password confirmation.

The password is entered through a secret prompt and is never accepted as a CLI argument.

## Safety Rules

- Do not hardcode admin email or password in any file.
- Do not store credentials in documentation.
- Do not create admin users through seeders automatically.
- Do not reopen public registration for admin creation.
- Do not add hidden superadmin logic.
- Run the command only from a trusted terminal session.

## Expected Behavior

- If an admin already exists, the command stops and does not create another admin.
- If the email is invalid or already used, the command stops.
- If password confirmation does not match, the command stops.
- If the password does not meet the security rule, the command stops.
- If validation passes, the command creates the first user with `is_admin = true`.
- The password is stored as a Laravel hash, never as plaintext.

## Password Rule

The first admin password must include:

- At least 12 characters.
- Letters.
- Mixed case.
- Numbers.
- Symbols.

## Verification

After provisioning:

1. Login through the normal login page.
2. Confirm the admin user redirects to `admin.dashboard`.
3. Confirm the admin user can access `/admin/dashboard`.
4. Confirm public `/register` still returns 404.
5. Confirm public homepage still works for guests.

## Rollback

If the wrong user was provisioned, do not commit or document credentials.

Recommended rollback options:

- In development or staging, reset the test data if safe.
- In production, update the specific trusted user record manually through an approved database operation after backup and operator confirmation.

Do not add a public demotion route. A future CLI demotion command requires a separate approved security step.
