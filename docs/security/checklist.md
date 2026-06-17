# Security Checklist

Last updated: 2026-06-13
Related step: STEP DOC-SYNC-SECURITY-08 documentation sync.

## Purpose

This checklist tracks the current security baseline for Bintan Prestige CMS. It is documentation-only and should be updated after every security-relevant change.

## Current Baseline Status

| Area | Status | Notes |
| --- | --- | --- |
| `.env` tracked in Git | Passed | `.env` is local and ignored. `.env.example` is tracked. |
| `.env.example` debug default | Fixed | `APP_DEBUG=false` was set in STEP IMPROVE-01. |
| Exposed key scan | Passed with expected matches | No raw secret was reported. Matches were config placeholders, docs, or expected key names. |
| Admin route guest protection | Fixed / covered | `/admin/dashboard` guest access redirects guests to `/login`. |
| Admin authorization roles/policies | Improved | Admin routes now require `auth` and `admin`; admin access requires `is_admin`, active status, and an admin role. User management uses the `manage-users` Gate for Super Admin only. |
| Public registration policy | Fixed | Public `GET /register` and `POST /register` return 404. |
| First admin provisioning | Fixed / covered | `php artisan admin:provision-first` provisions the first admin through a CLI-only interactive flow. |
| `users.is_admin` schema | Applied | SECURITY-03 migration has been run and `users.is_admin` exists in the active database. |
| Admin role/status schema | Applied | SECURITY-08 migration adds `users.role`, `users.is_active`, and `users.created_by`. |
| Super Admin user management | Fixed / covered | Super Admin can manage admin users from the dashboard; ordinary Admin cannot access user management. |
| Hidden superadmin policy | Passed | No hidden superadmin or hardcoded admin credential is documented or used. |
| Role/permission package policy | Passed | No role/permission package is used for SECURITY-08; granular module permissions remain future work. |
| Upload MIME validation | Improved | Existing `image`, `mimes`, and `max` rules remain in place. |
| Upload extension validation | Fixed | STEP IMPROVE-01 added Laravel `extensions` rules to key upload surfaces. |
| Upload storage filename guard | Fixed | `PageSectionImageService` now validates allowed extensions before storing page-section and site-asset uploads. |
| Suspicious function scan | Passed | Scoped scan found no `eval`, `shell_exec`, `exec`, `system`, `passthru`, `base64_decode`, or `unserialize`. |
| Raw tracking scripts | Pending | Admin-managed custom tracking scripts remain powerful and need governance in a future step. |
| Production `APP_DEBUG` | Pending deployment check | Local `.env` was not edited. Production environment must use `APP_DEBUG=false`. |
| Storage/public upload exposure | Needs review | Public upload exposure should be reviewed during a later security audit. |

## STEP IMPROVE-01 Fix Checklist

- [x] Set `.env.example` default `APP_DEBUG=false`.
- [x] Confirm `.env` is ignored and not tracked.
- [x] Confirm `.gitignore` protects `.env`, `.env.production`, `.env.backup`, `public/storage`, `public/uploads`, and `storage/*.key`.
- [x] Confirm admin route group uses `auth`.
- [x] Add regression test for guest access to `/admin/dashboard`.
- [x] Add `extensions` validation to product thumbnail upload.
- [x] Add `extensions` validation to product gallery upload.
- [x] Add `extensions` validation to destination image upload.
- [x] Add `extensions` validation to page-section image, mobile image, slot upload, and gallery upload.
- [x] Add `extensions` validation to global assets upload fields.
- [x] Preserve existing favicon formats: `ico`, `png`, `svg`, `webp`, `jpg`, and `jpeg`.
- [x] Add defensive extension allowlist to `PageSectionImageService`.
- [x] Add focused security baseline tests.
- [x] Run focused security tests.

## STEP SECURITY-03 Fix Checklist

- [x] Disable public registration page route.
- [x] Disable public registration POST route.
- [x] Add `users.is_admin` boolean column through a new migration.
- [x] Keep `users.is_admin` defaulted to false.
- [x] Add `is_admin` boolean cast to `User`.
- [x] Add `User::isAdmin()` helper.
- [x] Add admin middleware for CMS access.
- [x] Register admin middleware alias in Laravel 11 bootstrap middleware config.
- [x] Apply admin middleware to `/admin/*` route group.
- [x] Apply admin middleware to legacy `/dashboard` backend route.
- [x] Redirect non-admin logins to the public homepage.
- [x] Keep admin logins redirecting to `admin.dashboard`.
- [x] Keep public frontend routes accessible without login.
- [x] Add regression coverage for guest, non-admin, admin, registration, and public homepage access.

## STEP SECURITY-04 Plan Checklist

- [x] Document first-admin provisioning options.
- [x] Select CLI-only Artisan command as the preferred first-admin provisioning approach.
- [x] Reject hardcoded credentials, hidden superadmin logic, public admin invitation UI, and permission packages for the next step.
- [x] Map granular authorization targets across existing admin modules.
- [x] Define recommended SECURITY-05 implementation scope.
- [x] Define recommended SECURITY-06 granular authorization scope.
- [x] Keep SECURITY-04 documentation-only with no Laravel runtime changes.

## STEP SECURITY-05 Fix Checklist

- [x] Verify `users.is_admin` exists before implementation.
- [x] Add CLI-only `admin:provision-first` command.
- [x] Keep command interactive with no password argument.
- [x] Stop safely when an admin already exists.
- [x] Prompt for admin name and email.
- [x] Validate email format.
- [x] Validate email uniqueness.
- [x] Prompt for password and confirmation through secret prompts.
- [x] Validate password strength.
- [x] Hash password with Laravel Hash facade.
- [x] Create the first admin with `is_admin = true`.
- [x] Keep public registration disabled.
- [x] Add command tests.
- [x] Add admin provisioning documentation.

## STEP SECURITY-08 Fix Checklist

- [x] Add `users.role` through a new migration.
- [x] Add `users.is_active` through a new migration.
- [x] Add `users.created_by` through a new migration.
- [x] Backfill the first existing admin to `super_admin` safely by user id.
- [x] Keep `users.is_admin` as the outer CMS access flag.
- [x] Add `User::canAccessAdmin()`.
- [x] Add `User::isSuperAdmin()`.
- [x] Require active admin status in admin middleware.
- [x] Block inactive users during login.
- [x] Add `manage-users` Gate for Super Admin only.
- [x] Update first admin provisioning to create a `super_admin` with `is_active = true`.
- [x] Add Super Admin-only admin user management routes.
- [x] Add admin user management controller, validation, and views.
- [x] Prevent ordinary Admin from accessing user management.
- [x] Prevent Super Admin self-deactivation.
- [x] Prevent downgrading the only active Super Admin.
- [x] Keep public registration disabled.
- [x] Add focused Super Admin user management tests.
- [x] Run full test suite.

## STEP DOC-SYNC-SECURITY-08 Checklist

- [x] Document public registration remains disabled.
- [x] Document first admin remains CLI-only.
- [x] Document Super Admin full access and user-management capability.
- [x] Document ordinary Admin dashboard/content access without user-management access.
- [x] Document inactive users cannot access admin.
- [x] Document no hidden superadmin.
- [x] Document no hardcoded credentials.
- [x] Document no role/permission package.
- [x] Document granular module permissions remain future work.
- [x] Add admin user management documentation.
- [x] Add dashboard access-control documentation.

## Pending Security Checklist

- [ ] Implement granular Laravel policies/gates for CMS content/settings actions after user management is stable.
- [ ] Add authorization checks for sensitive admin actions.
- [ ] Add role/policy tests for create/update/delete/settings actions.
- [ ] Add audit logging for admin user management actions.
- [ ] Create governance for admin-managed tracking scripts.
- [ ] Add audit logging plan for security-sensitive admin settings.
- [ ] Review production `.env` posture without committing secrets.
- [ ] Review public upload/storage exposure.
- [ ] Expand upload negative-case tests across all upload endpoints.
- [ ] Review SVG/favicon handling policy.
- [ ] Review Content Security Policy strategy for public frontend and admin.
- [ ] Review rate limits for login and sensitive endpoints.

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
- `docs/admin/user-management.md`
- `docs/admin/dashboard-access-control.md`
- `docs/security/admin-provisioning.md`

## Rollback Note

This checklist is documentation-only. Roll back by reverting this file if the documentation sync is no longer desired.
