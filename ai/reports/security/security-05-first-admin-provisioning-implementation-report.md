# STEP SECURITY-05 - First Admin Provisioning Implementation Report

Date: 2026-06-12
Status: Completed.

## Summary

SECURITY-05 implemented the CLI-only first admin provisioning command:

```bash
php artisan admin:provision-first
```

The command creates the first admin user only when no admin already exists. It asks for name, email, password, and password confirmation through interactive prompts, validates the inputs, hashes the password with Laravel Hash, and stores the new user with `is_admin = true`.

No public registration route was reopened. No public frontend file was changed. No package was installed. No admin credential was hardcoded or stored in files. No hidden superadmin or seeder-based admin was added.

## Pre-Check Result

Passed:

- SECURITY-03 migration is applied.
- `users.is_admin` exists in the active database.
- `User` model has `is_admin` boolean cast.
- Public registration remains disabled.
- Admin middleware remains active on admin routes.

Evidence:

- `php artisan migrate:status --path=database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- Result: migration is `Ran`.
- Schema check result: `IS_ADMIN_EXISTS`.
- `php artisan route:list --path=admin/dashboard -v` shows `web`, `auth`, and `admin`.

## Files Changed

- `bootstrap/app.php`
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/security-baseline.md`
- `docs/changelog/CHANGELOG.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`

## Files Created

- `app/Console/Commands/ProvisionFirstAdmin.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `docs/security/admin-provisioning.md`

## Database Impact

No schema changes were made during SECURITY-05.

Runtime command impact:

- When executed successfully, `php artisan admin:provision-first` creates one row in `users`.
- The created user has `is_admin = true`.
- The password is stored hashed, not plaintext.
- If any admin already exists, the command stops and creates no user.

## Auth Impact

- Admin authentication flow remains unchanged.
- Admin users can still login through the existing login page.
- Admin users redirect to `admin.dashboard`.
- Non-admin users remain forbidden from `/admin/dashboard`.
- Public registration remains disabled.

## Security Impact

Fixed:

- First admin can now be provisioned safely after registration is disabled.
- First admin provisioning is CLI-only.
- Password is collected through a secret prompt.
- Password is not accepted as an argument.
- Password is not displayed in command output.
- Password is hashed with Laravel Hash.
- Command stops if an admin already exists.
- Duplicate email is rejected.
- Password confirmation mismatch is rejected.

Not introduced:

- No hardcoded email.
- No hardcoded password.
- No hidden superadmin.
- No seeder-created admin.
- No public provisioning route.
- No role/permission package.

## Testing Performed

Lint:

- `php -l app/Console/Commands/ProvisionFirstAdmin.php`
- `php -l tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `php -l bootstrap/app.php`

Command registration:

- `php artisan list --raw | Select-String -Pattern "admin:provision-first"`
- `php artisan admin:provision-first --help`

Focused tests:

```bash
php artisan test tests/Feature/Console/ProvisionFirstAdminCommandTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/SecurityBaselineTest.php
```

Result:

- Passed: 20 tests, 62 assertions.

Covered:

- Command creates first admin user.
- Password is stored hashed.
- Command stops if an admin already exists.
- Command rejects duplicate email.
- Command rejects password confirmation mismatch.
- Provisioned admin can login and access `/admin/dashboard`.
- Non-admin remains forbidden from `/admin/dashboard`.
- Public registration remains disabled.
- Public homepage remains accessible.

## Remaining Risks

- Granular Laravel policies/gates for individual admin actions are still pending.
- Admin audit logging is still pending.
- Tracking script governance is still pending.
- A safe demotion command does not exist yet and should not be added without a separate approved security step.

## Rollback Note

Rollback code by reverting:

- `app/Console/Commands/ProvisionFirstAdmin.php`
- `tests/Feature/Console/ProvisionFirstAdminCommandTest.php`
- `docs/security/admin-provisioning.md`
- `bootstrap/app.php`
- the documentation/report updates listed above.

If the command was used to create an admin, remove or demote that specific user through an approved database operation after backup and operator confirmation.

## Recommended Next Step

Run STEP SECURITY-06: Granular Authorization Policy/Gate Implementation.

Scope recommendation:

- Keep `AdminMiddleware` as the outer CMS gate.
- Add Laravel-native policies/gates for sensitive admin actions.
- Do not introduce a complex role/permission package yet.
- Do not change public frontend behavior.
