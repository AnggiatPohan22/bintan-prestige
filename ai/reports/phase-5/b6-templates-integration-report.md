# Phase 5 Stage B6 - Templates Integration Report

Date: 2026-06-22  
Branch: `feature/phase-5-stage-b-visual-builder`  
Baseline: `09a23cd feat(builder): B5 reusable patterns and saved blocks`

## Outcome

B6 adds a scalable, Elementor-style template foundation while preserving the
existing Page, PageBlock, public template hierarchy, and visual-builder layout.
Reusable full-page designs are versioned records separate from frontend layout
shells. Applying a design changes only the in-memory canvas until the existing
Save action is used.

## Architecture

- `page_templates` remains the allowlisted frontend shell registry.
- `builder_templates` stores reusable, versioned block forests with a future-ready
  `template_type`, category, thumbnail slot, schema version, and base shell link.
- `BuilderTreeSanitizer` is shared by page saves, patterns, and templates so node
  limits, nesting, registry checks, labels, and block data cannot drift.
- List API responses contain summaries only; full template JSON is lazy-loaded
  from the show endpoint when Apply is selected.
- Applying a layout shell preserves blocks.
- Applying a saved page template confirms replacement, regenerates all client
  IDs, adopts the base shell, refreshes preview, and stays dirty until Save.
- Deleting a library template never deletes or changes pages/page blocks.

## Changed

- `database/migrations/2026_06_22_000005_create_builder_templates_table.php` -
  isolated, versioned builder-template persistence.
- `app/Models/BuilderTemplate.php` - template model and extension constants.
- `app/Services/BuilderTreeSanitizer.php` - shared server tree contract.
- `app/Services/BuilderTemplateService.php` - sanitized creation and unique slugs.
- `app/Services/BuilderPatternService.php` - adopts shared sanitizer.
- `app/Http/Requests/Admin/StoreBuilderTemplateRequest.php` - store validation.
- `app/Http/Controllers/Admin/BuilderTemplateController.php` - lazy JSON library.
- `app/Http/Controllers/Admin/PageBuilderController.php` - layout choices and
  atomic layout/tree save.
- `app/Http/Controllers/Frontend/PageController.php` - transient shell preview.
- `routes/admin.php` - admin-only template endpoints.
- `resources/views/backend/builder/index.blade.php` - template configuration.
- `resources/views/backend/builder/partials/topbar.blade.php` - Templates control.
- `resources/views/backend/builder/partials/template-library.blade.php` - modal.
- `resources/views/backend/builder/partials/alpine-component.blade.php` - lazy
  library, save/apply/delete, preview, and persist-on-save state.
- `docs/visual-builder-structure.md` - B6 maintenance contract.
- `tests/Feature/Admin/BuilderTemplateTest.php` - B6 feature coverage.

## Preserved

- Existing routes and public page rendering behavior.
- `PageTemplateRegistry` allowlist and theme hierarchy.
- Page/PageBlock schema and nested `parent_block_id` persistence.
- B5 pattern behavior and existing block sanitization.
- Visual builder `20:60:20` grid and CSS-only device sizing.
- No dependency, auth, authorization, or package changes.

## Impact

- DB: one approved additive `builder_templates` table; development migration is
  intentionally still pending.
- Routes: four new admin-protected JSON routes.
- Frontend: public output unchanged; selected layout can be previewed transiently.
- Backend: reusable page-template library and atomic layout/tree persistence.
- Security: admin middleware, allowlisted base shells, sanitized metadata and
  block forests, bounded tree depth/count.

## Verification

- `php artisan test tests/Feature/Admin/BuilderTemplateTest.php` - 8 tests,
  56 assertions passed.
- B5/page/render/theme focused regression - 54 tests, 621 assertions passed.
- `vendor\\bin\\phpunit --colors=never` - 627 tests, 3206 assertions passed.
- `composer analyse` - PHPStan passed with 0 errors.
- scoped `vendor\\bin\\pint --test ...` - passed.
- Alpine component parsed with Node `new Function(...)` - passed.
- `php artisan view:cache` - passed.
- `npm.cmd run build` - passed.
- `git diff --check` - passed.
- `php artisan migrate:status` - B5 is ran; B6 migration is pending as intended.
- Browser automation could not reach the local server from the in-app browser
  surface, so authenticated interaction remains a manual double-check below.

## Manual QA Checklist

1. Run `php artisan migrate` after reviewing the pending B6 migration.
2. Sign in as admin and open a page's Visual Builder.
3. Click **Templates** and confirm the modal opens without changing panel widths.
4. Select each Layout Shell and confirm preview changes while blocks remain.
5. Reload without Save and confirm the page's original layout is still persisted.
6. Save the current page as a template; include category and description.
7. Search for it, apply it, accept the replacement confirmation, and confirm the
   canvas changes but remains marked dirty.
8. Click normal **Save**, reload, and confirm both layout and blocks persist.
9. Apply the same template twice and confirm selecting/editing blocks still works
   (fresh client IDs; no duplicate-ID behavior).
10. Delete the saved template and confirm the current page remains unchanged.
11. Recheck desktop/tablet/mobile preview and the fixed `20:60:20` desktop grid.

## Rollback

Before migration: discard only the B6 files/diff. After migration: run
`php artisan migrate:rollback --step=1` only after verifying B6 is the latest
batch and no production template records need preservation. Git baseline remains
commit `09a23cd`.

## Remaining

- Development migration must be explicitly applied by the owner.
- Authenticated manual browser QA is required before commit.
- B6 is implemented and test-green but intentionally uncommitted.
