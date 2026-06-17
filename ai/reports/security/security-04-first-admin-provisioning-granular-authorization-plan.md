# STEP SECURITY-04 - First Admin Provisioning & Granular Authorization Plan

Date: 2026-06-12
Status: Plan only. No Laravel runtime code changed.

## Executive Summary

SECURITY-03 closed the critical admin access gap by disabling public registration and requiring `users.is_admin = true` for `/admin/*`. SECURITY-04 defines the next safe step: create a controlled first-admin provisioning path and map granular authorization coverage for sensitive admin actions.

This report is planning-only. It does not change database schema, routes, controllers, models, middleware, views, config, assets, or public frontend behavior.

## Current Security State

- Public registration is disabled.
- Admin routes require `auth` and `admin`.
- The `admin` middleware checks `User::isAdmin()`.
- `users.is_admin` exists as a planned migration from SECURITY-03.
- Existing users become non-admin by default after migration.
- No granular policies, gates, or permissions exist yet.
- Admin controllers are protected at route-group level but not at action/policy level.

## Goal

1. Provide a safe way to promote the first trusted admin user.
2. Avoid hardcoded credentials, emails, or hidden superadmin logic.
3. Keep the current simple `is_admin` model.
4. Prepare granular Laravel-native policy/gate coverage.
5. Keep public frontend routes unaffected.
6. Avoid package installation and complex role/permission systems for now.

## First Admin Provisioning Decision

Recommended approach for SECURITY-05:

- Create a CLI-only Artisan command to promote an existing user by email.
- Command name suggestion: `user:promote-admin {email}`.
- The command must refuse to create users.
- The command must only update `is_admin` for an existing user.
- The command should ask for confirmation before changing the user.
- The command should print a safe success message without exposing sensitive data.
- The command should not hardcode any email, password, or secret.
- The command should be covered by a feature/console test.

Why this is preferred:

- No public web route is needed.
- No temporary registration reopening is needed.
- No hidden superadmin is introduced.
- No production secret is stored in Git.
- The action is explicit, auditable, and reversible.

## Provisioning Options Reviewed

### Option A - CLI Artisan Command

Status: Preferred.

Flow:

1. Confirm SECURITY-03 migration has run.
2. Confirm the target user already exists.
3. Run a CLI command with the target email.
4. Confirm the prompt.
5. Set `is_admin = true`.
6. Verify the user can access `/admin/dashboard`.

Suggested future command:

```bash
php artisan user:promote-admin admin@example.com
```

Recommended safeguards:

- Refuse if user does not exist.
- Refuse if user is already admin unless `--force` is provided.
- Ask for confirmation before update.
- Do not create passwords.
- Do not print password hashes or secrets.
- Add a matching demotion command or `--demote` option only after explicit approval.

### Option B - Manual SQL or Tinker

Status: Emergency-only fallback.

This is acceptable only when CLI command does not exist yet and the operator has trusted server access.

Risks:

- Easy to update the wrong user.
- Less testable.
- Less discoverable for future maintainers.
- Can bypass app-level audit conventions.

### Option C - Seeder-Based Admin

Status: Not recommended for this project stage.

Risks:

- Can accidentally hardcode emails or credentials.
- Can be re-run unexpectedly.
- Can create confusing environment-specific behavior.

### Option D - Web-Based Admin Invitation UI

Status: Future feature, not for the next step.

Risks:

- Requires additional UI, routes, validation, mail/invitation flow, and audit logging.
- Larger blast radius than needed for first-admin provisioning.

## Granular Authorization Strategy

Recommended strategy:

1. Keep `AdminMiddleware` as the outer CMS gate.
2. Add Laravel policies for admin-managed models.
3. Start policies with `is_admin` checks only.
4. Add controller authorization calls action by action.
5. Add tests that non-admin users are blocked for direct URLs and sensitive mutations.
6. Add capability-based rules later only when the project needs multiple admin levels.

This gives explicit controller-level authorization without building a complex role system too early.

## Policy Map

### Dashboard

Current controller:

- `DashboardController@index`

Recommended gate:

- `viewAdminDashboard`

Initial rule:

- Allow only `is_admin = true`.

### Products

Current controller:

- `ProductController`

Recommended policy:

- `ProductPolicy`

Recommended abilities:

- `viewAny`
- `create`
- `update`
- `delete`
- `manageStatus`
- `manageFeatured`
- `manageImages`
- `manageSearchBooking`

Initial rule:

- Allow only `is_admin = true`.

### Product Submodules

Current controllers:

- `ProductHighlightController`
- `ProductFeatureController`
- `ProductFaqController`
- `ProductItineraryController`
- `ProductNoteController`

Recommended policy approach:

- Use `ProductPolicy` for product-owned nested records where practical.
- Add specific policies later only if submodule ownership/rules diverge.

Recommended abilities:

- `manageProductHighlights`
- `manageProductFeatures`
- `manageProductFaqs`
- `manageProductItineraries`
- `manageProductNotes`

Initial rule:

- Allow only `is_admin = true`.

### Categories

Current controller:

- `CategoryController`

Recommended policy:

- `CategoryPolicy`

Recommended abilities:

- `viewAny`
- `create`
- `update`
- `delete`
- `restore`
- `forceDelete`

Initial rule:

- Allow only `is_admin = true`.

### Destinations

Current controller:

- `DestinationController`

Recommended policy:

- `DestinationPolicy`

Recommended abilities:

- `viewAny`
- `create`
- `update`
- `delete`
- `restore`
- `forceDelete`

Initial rule:

- Allow only `is_admin = true`.

### FAQs

Current controller:

- `FaqController`

Recommended policy:

- `FaqPolicy`

Recommended abilities:

- `viewAny`
- `create`
- `update`
- `delete`

Initial rule:

- Allow only `is_admin = true`.

### Page Sections

Current controller:

- `PageSectionController`

Recommended policy:

- `PageSectionPolicy`

Recommended abilities:

- `viewAny`
- `view`
- `update`
- `manageMedia`
- `deleteMedia`

Initial rule:

- Allow only `is_admin = true`.

### Global Assets and Site Settings

Current controller:

- `SiteSettingController`

Recommended gate or policy:

- `manageSiteSettings`
- `manageBrandAssets`
- `manageSeoDefaults`
- `manageTrackingIntegrations`
- `manageStructuredData`

Initial rule:

- Allow only `is_admin = true`.

Special note:

- `manageTrackingIntegrations` should be treated as high-risk because admin-managed scripts can affect public frontend security, privacy, analytics, and SEO behavior.

## Suggested SECURITY-05 Implementation Scope

Only after approval, implement:

1. CLI first-admin promotion command.
2. Console tests for the command.
3. Minimal documentation for operating the command.
4. No web UI.
5. No role table.
6. No permission package.
7. No registration reopening.

Suggested files for SECURITY-05:

- `app/Console/Commands/PromoteAdminUser.php`
- `tests/Feature/Security/AdminProvisioningCommandTest.php`
- `docs/security/admin-provisioning.md`
- `docs/security/audit-log.md`
- `docs/changelog/CHANGELOG.md`
- `ai/reports/security/security-05-first-admin-provisioning-implementation-report.md`

Potential Laravel 11 registration note:

- If commands are not auto-discovered in this project, register the command using the project's existing Laravel 11 console command pattern.

## Suggested SECURITY-06 Implementation Scope

Only after SECURITY-05 is stable, implement:

1. Laravel policies/gates for admin modules.
2. Controller-level authorization calls.
3. Non-admin direct URL tests for high-risk actions.
4. Admin allow tests for the same actions.
5. Documentation updates.

Suggested files may include:

- `app/Policies/ProductPolicy.php`
- `app/Policies/CategoryPolicy.php`
- `app/Policies/DestinationPolicy.php`
- `app/Policies/FaqPolicy.php`
- `app/Policies/PageSectionPolicy.php`
- `app/Providers/AppServiceProvider.php` or equivalent gate registration location if needed.
- Selected admin controllers in `app/Http/Controllers/Admin/`
- Selected tests under `tests/Feature/Security/` or `tests/Feature/Admin/`
- `docs/security/authorization-policy.md`
- `ai/reports/security/security-06-granular-authorization-implementation-report.md`

## Testing Plan

For first-admin provisioning:

- Command refuses an unknown email.
- Command promotes an existing non-admin user.
- Command does not alter password, email, or other user fields.
- Command handles already-admin users safely.
- Promoted user can access `/admin/dashboard`.
- Non-promoted user remains forbidden.

For granular authorization:

- Guest users redirect to login for admin GET routes.
- Non-admin users get 403 for admin GET routes.
- Non-admin users get 403 for admin POST, PUT, PATCH, and DELETE routes.
- Admin users can perform existing CRUD actions.
- Public frontend homepage and product routes remain accessible.

## Route Impact

No route changes in SECURITY-04.

Future SECURITY-05 should not add public routes.

Future SECURITY-06 should keep existing admin route names stable and add authorization checks inside controllers or policies.

## Database Impact

No database changes in SECURITY-04.

Future SECURITY-05 should not require schema changes because `users.is_admin` already exists from SECURITY-03.

Future complex role/permission systems would require explicit approval and should not be introduced yet.

## Frontend Impact

No frontend changes in SECURITY-04.

Public frontend should remain accessible to guests in all future admin authorization steps.

## Backend Impact

No backend runtime changes in SECURITY-04.

Future implementation should stay Laravel-native:

- Artisan command for first-admin promotion.
- Policies/gates for action-level authorization.
- Tests for direct URL bypass attempts.

## Security Impact

SECURITY-04 reduces implementation risk by defining safe next steps before adding more access-control code.

Primary security decisions:

- Do not reopen public registration.
- Do not create hidden superadmin logic.
- Do not hardcode admin credentials.
- Do not install permission packages yet.
- Do not add a web-based admin invitation UI yet.
- Prefer CLI-only first-admin provisioning.
- Add granular policies after the first admin path is stable.

## Rollback Note

This step is documentation and planning only. Rollback by reverting this report and related documentation updates.

## Recommended Next Step

Run STEP SECURITY-05: First Admin Provisioning Implementation.

Recommended approval scope:

- Add a CLI-only Artisan command to promote an existing user to admin.
- Add tests for the command.
- Add operating documentation.
- Do not add public routes.
- Do not add role/permission packages.
