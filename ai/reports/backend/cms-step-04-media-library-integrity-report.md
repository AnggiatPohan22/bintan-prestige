# CMS STEP 4 - Media Library Integrity Report

Date: 2026-06-19
Status: Completed

## Task

Strengthen the existing Media Library and connect it to Page Builder image fields without changing database schema, existing media records, or existing direct-upload behavior.

## Baseline

- Active branch: `feature/codex-backend-cms-next`
- Restore branch: `backup/pre-codex-master-rules-20260618`
- Restore baseline: `df17e18`
- Previous completed task: STEP 3 Page Block integrity, validation, SEO, and prepared FAQ rendering.
- Initial focused Media Library result: 6 tests passed, 27 assertions.

## Audit Findings

- The Media picker view existed but was explicitly not connected to Page Builder fields.
- Hero, Image, Gallery, and shared Background fields only supported direct upload or manual paths.
- Media deletion removed records and files without checking CMS references.
- The library had no reconciliation or cleanup flow for unregistered files.
- Alt text and captions were editable in the library but were not transferred to Image or Gallery blocks.
- Standard, quick, and batch uploads used the same Media service, but upload validation did not explicitly check client extensions at every entry point.
- JPEG, PNG, and WebP used the existing WebP optimization service; GIF intentionally bypassed conversion to preserve animation.

## Changed

- `app/Http/Controllers/Admin/MediaController.php`
  - Exposes orphan counts to the library.
  - Adds an approved orphan-cleanup action.
  - Uses explicit extension validation on quick and batch uploads.
  - Includes alt text and captions in media payloads.
- `app/Http/Requests/Admin/StoreMediaRequest.php`
  - Adds explicit extension validation for normal uploads.
- `app/Services/MediaService.php`
  - Defensively validates image MIME type and client extension.
  - Removes a newly stored file if Media record creation fails.
  - Detects path usage in Page Block JSON and existing direct media columns.
  - Blocks deletion while references remain.
  - Allows stale Media records with missing files to be removed safely.
  - Detects and purges only files without a Media record and without a known CMS reference.
  - Adds usage and missing-file state to paginated media.
- `routes/admin.php`
  - Adds the approved `admin.media.orphans.destroy` route before the Media resource route.
- `resources/views/backend/media/index.blade.php`
  - Displays orphan cleanup, used-media references, missing-file warnings, and disables unsafe delete controls.
- `resources/views/backend/media/picker.blade.php`
  - Updates the existing picker as the active Page Builder selection surface.
- `resources/views/backend/media/partials/grid.blade.php`
  - Sends path, alt, caption, usage, and missing-file data to the library and picker.
- `resources/views/backend/media/partials/picker-modal.blade.php`
  - Adds one reusable, same-origin picker modal and shared image-upload state for block fields.
- `resources/views/backend/pages/partials/block-editor.blade.php`
  - Includes the shared picker once per page editor.
- `resources/views/backend/pages/partials/blocks/hero.blade.php`
  - Adds Media Library selection while preserving direct upload and manual path fallback.
- `resources/views/backend/pages/partials/blocks/image.blade.php`
  - Adds Media Library selection and transfers stored alt/caption metadata.
- `resources/views/backend/pages/partials/blocks/gallery.blade.php`
  - Adds selected media as a gallery item with stored alt/caption metadata.
- `resources/views/backend/pages/partials/blocks/partials/background.blade.php`
  - Adds Media Library selection to the shared Background field used by every block.
- `tests/Feature/Admin/MediaLibraryTest.php`
  - Adds safe-delete, missing-file, orphan cleanup, picker integration, metadata payload, spoofed-extension, invalid-file, oversized-file, batch-overflow, and shared optimization-pipeline coverage.

## Preserved

- Existing Media table and records.
- Existing Page Block JSON schemas and field names.
- Existing image paths and frontend rendering contracts.
- Direct upload and manual URL/path fallback.
- Existing `ImageOptimizationService` implementation.
- JPEG/PNG/WebP conversion to WebP.
- GIF pass-through behavior.
- Existing admin authentication and authorization middleware.

## Impact

- DB: none.
- Routes: one approved admin-only orphan cleanup route added; existing routes unchanged.
- Frontend: no public rendering changes.
- Backend: Media deletion and orphan cleanup are reference-aware.
- Admin UI: Hero, Image, Gallery, and all shared Background fields can reuse Media Library assets.
- Security: upload MIME and extension checks are consistent; used files cannot be deleted through the library.
- Accessibility/SEO: stored alt text and captions flow into Image and Gallery block data.

## Orphan Safety Contract

A file under `storage/app/public/media` is purgeable only when:

1. no Media record contains its normalized path; and
2. no known direct CMS media column contains the path; and
3. no nested Page Block JSON value contains the path.

Paths stored as raw paths, `/storage/...`, `storage/...`, or application storage URLs are normalized before comparison.

## Verification

- `php artisan route:list --name=admin.media` - seven Media routes present, including orphan cleanup before resource deletion.
- Focused STEP 4: 21 tests passed, 147 assertions.
- Focused STEP 2-4 regression: 39 tests passed, 262 assertions.
- `php artisan test` - 273 tests passed, 1,885 assertions.
- PHP syntax checks - passed.
- Laravel Pint on changed PHP files - passed.
- `git diff --check` - passed.
- Browser smoke: not executed because the local in-app browser process could not start under the Windows sandbox. Relevant admin page rendering and picker wiring were verified through feature tests; no visual-browser result is claimed.

## Schema Decision

No schema change was required for STEP 4. Usage detection remains path-based for backward compatibility.

A normalized `media_usages` relation or explicit `media_id` ownership should be evaluated as a separate architecture/schema task. It must not be added without a new audit, migration plan, backfill plan, and explicit approval.

## Rollback

Revert only the fifteen STEP 4 files listed in this report. Do not reset the whole worktree because approved STEP 3 and earlier uncommitted work are present. The restore baseline remains `backup/pre-codex-master-rules-20260618` at `df17e18`.

## Remaining

- Browser-level visual QA should be repeated when the local browser runtime is available.
- Path-based reference detection protects existing records without migration, but database-level foreign-key integrity remains intentionally deferred.
- Changes remain uncommitted and unpushed.
