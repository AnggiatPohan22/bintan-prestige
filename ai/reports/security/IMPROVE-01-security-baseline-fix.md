# STEP IMPROVE-01 - Security Baseline Fix

Date: 2026-06-12
Project: Bintan Prestige CMS
Scope: Safe security baseline fixes based on `ai/reports/baseline/project-baseline-report.md`.

## Summary

This step applied a small security baseline hardening pass without changing existing CMS features, routes, database schema, migrations, public assets, or authentication behavior.

The fix focused on:

- `.env` safety and `APP_DEBUG` default posture.
- Exposed key checks without printing secrets.
- Admin route protection verification.
- Upload validation hardening.
- Suspicious function scan.

## Files Read

- `AGENTS.md`
- `ai/guidelines/06-security-hardening.md`
- `ai/skills/security-skill.md`
- `ai/reports/baseline/project-baseline-report.md`
- `.gitignore`
- `.env` metadata and selected masked environment keys
- `.env.example`
- `routes/admin.php`
- `routes/auth.php`
- `app/Models/User.php`
- `app/Http/Requests/*`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- `app/Services/ImageOptimizationService.php`
- `tests/*`

## Files Changed

- `.env.example`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreDestinationRequest.php`
- `app/Http/Requests/UpdateDestinationRequest.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Services/PageSectionImageService.php`
- `tests/Feature/SecurityBaselineTest.php`
- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`

## Files Created

- `tests/Feature/SecurityBaselineTest.php`
- `ai/reports/security/IMPROVE-01-security-baseline-fix.md`

## Files Deleted

- None.

## Security Fixes Applied

### Environment Safety

- Changed `.env.example` default from `APP_DEBUG=true` to `APP_DEBUG=false`.
- Did not edit local `.env` because it is ignored and environment-specific.
- Confirmed `.gitignore` includes `.env`, `.env.backup`, `.env.production`, `public/storage`, `public/uploads`, and `storage/*.key`.

### Exposed Key Check

- Confirmed `.env` is local and not tracked.
- Confirmed `.env.example` is tracked and contains placeholder/empty environment values only.
- Scanned tracked files for sensitive key names. Matches were expected config placeholders, docs/checklists, CSS tokens, and framework config references. No raw secret value was printed in this report.

### Admin Route Protection

- Confirmed `routes/admin.php` is wrapped in `auth` middleware.
- Added a regression test that unauthenticated access to `/admin/dashboard` redirects to `/login`.
- Did not add role/policy middleware in this step because that would change authorization behavior and needs a dedicated approved design.
- Did not disable public registration in this step because that would change an existing auth feature.

### Upload Validation

- Added Laravel `extensions` validation to image upload rules that already used `image`, `mimes`, and `max`.
- Hardened product thumbnail/gallery uploads.
- Hardened destination image uploads.
- Hardened page section image, mobile image, slot upload, and gallery media uploads.
- Hardened global asset uploads for logos, favicon, social share image, SEO OG image, and default media.
- Preserved existing favicon support for `ico`, `png`, `svg`, `webp`, `jpg`, and `jpeg`.
- Added a defensive allowlist in `PageSectionImageService` before storing page-section and site-asset upload filenames.

### Suspicious Function Scan

Scoped scan did not find usage of:

- `eval`
- `shell_exec`
- `exec`
- `system`
- `passthru`
- `base64_decode`
- `unserialize`

## Database Impact

- None.

## Migration Impact

- None.

## Route Impact

- None.

## Frontend Impact

- None.

## Backend Impact

- Upload validation now rejects image content submitted with unsafe/disallowed file extensions.
- Admin route behavior is unchanged.
- Public registration behavior is unchanged.

## Security Impact

- Safer production default in `.env.example`.
- Stronger upload extension checks.
- Regression coverage for admin guest protection.
- Defensive upload extension allowlist added before storing site asset and page section media files.

## Performance Impact

- None expected.

## SEO Impact

- None.

## Testing Performed

- Added `tests/Feature/SecurityBaselineTest.php`.
- Focused test command:
  - `php artisan test tests/Feature/SecurityBaselineTest.php`
- Result:
  - Passed: 3 tests, 5 assertions.
- PHP syntax checks:
  - All changed PHP files passed `php -l`.
- Diff hygiene:
  - `git diff --check` passed for the files changed in this step.

## Remaining Risks

- Local `.env` still has `APP_DEBUG=true`; this is acceptable for local development but must be false in production.
- Admin authorization is still broad and should be handled by a future role/policy hardening step.
- Public registration remains enabled.
- Raw admin-managed tracking scripts remain powerful and should be governed by role/policy/audit logging later.
- Upload hardening is improved, but a full negative-case upload test suite is still recommended.

## Recommended Next Step

Run STEP SECURITY-02 as a read-only authorization design audit before changing routes, policies, roles, registration behavior, or admin permissions.

## Rollback Note

Rollback this step by reverting only the files listed in "Files Changed". No database rollback is required.
