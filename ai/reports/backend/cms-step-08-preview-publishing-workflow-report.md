# CMS STEP 8 - Preview and Publishing Workflow Report

Date: 2026-06-19
Status: Completed

## Task

Clarify the Generic Page draft/published workflow, preserve admin-only draft preview, verify public
Menu Manager visibility, connect the preview ribbon back to the editor, define the current slug
change contract, and audit revision/history as a future phase without building it now.

## Baseline

- Active branch: `feature/codex-backend-cms-next`
- Restore branch: `backup/pre-codex-master-rules-20260618`
- Restore baseline: `df17e18`
- Previous completed task: STEP 7 Frontend Page, Template, and SEO.
- Initial focused Page/Menu workflow result: 29 passed, 215 assertions.
- Existing uncommitted STEP 1-7 work was preserved.

## Changed

- `resources/views/backend/pages/index.blade.php`
  - Explains the public consequence of Draft and Published status.
  - Exposes admin Preview for every Page, labels draft previews explicitly, and keeps View Live only
    for published Pages.
  - Keeps the action group responsive when Preview, View Live, Edit, and Delete are present.
- `resources/views/backend/pages/edit.blade.php`
  - Labels draft preview and live-view actions explicitly.
  - Explains that Draft is admin-only and excluded from public URLs/managed menus, while Published
    is live and menu-eligible.
- `resources/views/backend/pages/form.blade.php`
  - Adds the same publishing consequences to the Page creation form.
- `resources/views/frontend/pages/show.blade.php`
  - Adds an accessible Back to editor link to the admin-only preview ribbon.
  - Preserves the current preview status message and STEP 7 `noindex, nofollow` metadata.
- `tests/Feature/Admin/PageManagementTest.php`
  - Covers clear Draft/Published copy and Preview Draft/View Live admin actions.
- `tests/Feature/Frontend/GenericPageRenderingTest.php`
  - Covers preview authorization for guests, non-admin users, and admins.
  - Covers preview ribbon editor navigation.
  - Covers the complete slug-change contract: new URL and canonical become active while the old URL
    returns 404.
- `tests/Feature/Admin/MenuManagementTest.php`
  - Covers Page menu links following slug changes.
  - Covers links disappearing on draft and returning on republish after cache invalidation.
- `ai/reports/backend/cms-step-08-preview-publishing-workflow-report.md`
  - Records the current publishing contract and separates revision/history into a future phase.

## Publishing Contract

### Draft

- Public `/pages/{slug}` returns 404.
- Admin preview requires both authentication and admin authorization.
- Preview uses the public canonical URL but is marked `noindex, nofollow`.
- Draft Pages cannot be selected as new internal Menu Manager targets.
- Existing Page menu links disappear immediately when their Page becomes draft.

### Published

- Public `/pages/{slug}` renders normally.
- Page is eligible for Menu Manager linking.
- Republishing restores an existing Page menu link after cache invalidation.
- Admin can use both Preview and View Live.

### Slug Changes

- Menu items store the Page relationship, not a copied slug, so their URL follows the Page's current
  slug after cache invalidation.
- The new public URL and canonical become active immediately.
- The old URL returns 404.
- No redirect is created because old slugs are not stored in the current schema.

## Revision and History Audit - Future Phase Only

The current system intentionally remains an in-place editor:

- Page updates overwrite the current Page row.
- Page Block updates overwrite the current JSON payload.
- Page Block deletion is permanent.
- There is no `published_at`, revision model, snapshot, author attribution, diff, restore action,
  slug redirect history, or revision-aware media retention.

A future revision/history architecture task should separately design and approve:

1. `page_revisions` snapshots containing Page fields and an ordered Page Block snapshot.
2. Author, action, reason, status, and timestamp metadata.
3. Transactional restore semantics and authorization.
4. `page_slug_redirects` if old public URLs should return 301 instead of 404.
5. Media reference retention so assets used by historical revisions are not purged.
6. Revision pruning/retention, storage growth, diff UI, backfill, rollback, and test plans.

No revision/history schema, model, route, controller, UI, or background job was added in STEP 8.

## Preserved

- Existing Page status values and database schema.
- Existing public and admin routes.
- Existing controllers, models, Form Requests, and services.
- STEP 5 Menu Manager public-target filtering and cache invalidation.
- STEP 7 canonical, Open Graph, structured-data, template, and preview robots behavior.
- Existing Page and Block editing behavior.

## Impact

- DB/schema/migrations: none.
- Routes: none.
- Controllers: none.
- Models/services: none.
- Packages: none.
- Frontend: preview ribbon now links back to the editor; public draft behavior remains 404.
- Admin UI: publishing consequences and Preview Draft/View Live actions are explicit.
- Security: preview access is regression-tested as admin-only.

## Verification

- Initial focused baseline: 29 passed, 215 assertions.
- Focused STEP 8: 33 passed, 240 assertions.
- Focused STEP 2-8 regression: 63 passed, 437 assertions.
- `php artisan test`: 296 passed, 2,053 assertions.
- Laravel Pint on changed test files: passed.
- Blade view compilation and compiled-view cleanup: passed.
- `git diff --check`: passed.
- Browser QA: not executed because the in-app browser process could not start under the Windows
  sandbox. No visual result is claimed.

## Rollback

Revert only the eight STEP 8 files listed in this report. Do not reset or clean the worktree because
approved uncommitted STEP 1-7 work remains present. The pre-Codex restore point remains
`backup/pre-codex-master-rules-20260618` at `df17e18`; restoration requires explicit owner
instruction and a non-destructive plan.

## Remaining

- Repeat Page index, edit form, and preview ribbon browser QA when runtime is available.
- Treat revision/history and old-slug redirects as a separately approved schema task.
- No known STEP 8 automated test failure remains.
- Changes remain uncommitted and unpushed.
