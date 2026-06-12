# Changelog

All notable project documentation and baseline improvement steps are tracked here.

## 2026-06-12

### STEP IMPROVE-02 - Documentation Sync After Security Baseline Chain

Changed:

- Synchronized security documentation after SECURITY-03, SECURITY-04, and SECURITY-05.
- Updated security checklist to mark `users.is_admin` schema and first-admin provisioning as documented.
- Updated security audit log to reflect that the SECURITY-03 `users.is_admin` migration has been applied.
- Updated `ai/reports/documentation/improve-02-documentation-sync-report.md`.

Notes:

- Documentation-only sync.
- No Laravel runtime logic was changed.
- No database, migration, route, controller, model, middleware, view, config, public, or asset files were changed.

### STEP SECURITY-05 - First Admin Provisioning Implementation

Changed:

- Added `php artisan admin:provision-first`.
- Registered the command in `bootstrap/app.php`.
- Added console tests for first-admin provisioning.
- Added `docs/security/admin-provisioning.md`.
- Updated security checklist, audit log, baseline, changelog, and SECURITY-05 report.

Security impact:

- First admin creation is now CLI-only.
- Public registration remains disabled.
- No admin email or password is hardcoded.
- No hidden superadmin logic was added.
- No password is accepted as a CLI argument.
- Passwords are stored hashed through Laravel Hash.

Verification:

- Command appears in `php artisan list --raw`.
- Command help shows no custom password argument.
- Focused SECURITY-05 suite passed with 20 tests and 62 assertions.

### STEP SECURITY-04 - First Admin Provisioning & Granular Authorization Plan

Changed:

- Created `ai/reports/security/security-04-first-admin-provisioning-granular-authorization-plan.md`.
- Updated security checklist, audit log, baseline, and changelog.

Decisions:

- Prefer a CLI-only Artisan command for first-admin provisioning.
- Promote only an existing user by email.
- Do not reopen public registration.
- Do not add hidden superadmin logic.
- Do not hardcode admin credentials.
- Do not install a role/permission package yet.
- Map granular Laravel policies/gates after first-admin provisioning is stable.

Notes:

- Planning-only step.
- No Laravel runtime code changed.
- No database, migration, route, controller, model, middleware, view, config, public, or asset files changed.

### STEP SECURITY-03 - Admin Access Fix

Changed:

- Disabled public `GET /register`.
- Disabled public `POST /register`.
- Added `users.is_admin` through a new migration.
- Added admin middleware and registered the `admin` alias.
- Applied `auth` + `admin` protection to the admin route group.
- Applied `admin` protection to the legacy `/dashboard` backend route.
- Updated login redirect behavior so only admins go to `admin.dashboard`.
- Updated security/auth/admin tests for the new admin access policy.
- Updated security documentation and created `ai/reports/security/security-03-admin-access-fix-report.md`.

Security impact:

- Public registration is closed.
- Non-admin authenticated users can no longer access `/admin`.
- Public frontend routes remain guest-accessible.

Verification:

- Runtime PHP lint passed.
- Focused auth/security tests passed with 14 tests and 24 assertions.
- Admin feature suite reported 58 passed and 4 existing page-section assertion failures unrelated to the admin gate.

### STEP IMPROVE-02 - Documentation Sync Fix

Changed:

- Created `docs/security/checklist.md`.
- Created `docs/security/audit-log.md`.
- Created `docs/security/security-baseline.md`.
- Created `docs/changelog/CHANGELOG.md`.
- Created `ai/reports/security/improve-01-security-baseline-fix-report.md`.
- Created `ai/reports/documentation/improve-02-documentation-sync-report.md`.

Notes:

- Documentation-only sync.
- No Laravel logic was changed.
- No database, migration, route, controller, model, view, config, public, or asset files were changed during this step.

### STEP IMPROVE-01 - Security Baseline Fix

Changed:

- `.env.example` now defaults `APP_DEBUG=false`.
- Upload validation now includes `extensions` checks for product, destination, page-section, and global asset upload surfaces.
- `PageSectionImageService` now validates allowed upload extensions before storing page-section and site-asset files.
- Added `tests/Feature/SecurityBaselineTest.php`.
- Added `ai/reports/security/IMPROVE-01-security-baseline-fix.md`.

Security issues fixed:

- Safer `.env.example` debug default.
- Stronger upload extension validation.
- Regression coverage for guest admin dashboard protection.

Still pending:

- Admin authorization roles/policies.
- Public registration policy.
- Raw tracking script governance.
- Production environment verification.
- Full upload negative-case test suite.

Verification:

- `php artisan test tests/Feature/SecurityBaselineTest.php` passed with 3 tests and 5 assertions.
- Changed PHP files passed `php -l`.
- `git diff --check` passed for the STEP IMPROVE-01 files.

Related reports:

- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`
- `ai/reports/security/improve-01-security-baseline-fix-report.md`
- `ai/reports/documentation/improve-02-documentation-sync-report.md`
