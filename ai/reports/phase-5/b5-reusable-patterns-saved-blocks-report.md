# B5 — Reusable Patterns & Saved Blocks

**Date:** 2026-06-22
**Branch:** `feature/phase-5-stage-b-visual-builder`
**B4 commit:** `115b006`
**Status:** IMPLEMENTATION COMPLETE — dev migration and owner manual QA pending

## Summary

B5 adds an isolated reusable-pattern library to the visual builder. An admin can
save the currently selected block or container subtree, browse saved patterns in
the left inserter, insert a deep copy at the existing container-aware insertion
position, and delete the library record without affecting page blocks already
inserted from it.

No Page/PageBlock schema, public route, public frontend, auth middleware,
dependency, or B-LAYOUT canvas behavior was changed.

## Files changed

Created:

- `database/migrations/2026_06_22_000004_create_builder_patterns_table.php`
- `app/Models/BuilderPattern.php`
- `app/Http/Requests/Admin/StoreBuilderPatternRequest.php`
- `app/Services/BuilderPatternService.php`
- `app/Http/Controllers/Admin/BuilderPatternController.php`
- `tests/Feature/Admin/BuilderPatternTest.php`
- `ai/reports/phase-5/b5-reusable-patterns-saved-blocks-report.md`

Modified:

- `routes/admin.php`
- `resources/views/backend/builder/index.blade.php`
- `resources/views/backend/builder/partials/alpine-component.blade.php`
- `resources/views/backend/builder/partials/panel-left.blade.php`
- `resources/views/backend/builder/partials/panel-right.blade.php`
- `docs/visual-builder-structure.md`

## Schema and storage decision

Patterns use the isolated `builder_patterns` table with name/slug metadata,
optional description/category/thumbnail, JSON `pattern_data`, root `block_type`,
future-facing `is_global`, creator/updater references, and timestamps. Existing
page tables remain unchanged.

The migration exists but was deliberately not run on the dev database. Current
status: `2026_06_22_000004_create_builder_patterns_table — Pending`.

## Pattern data contract

One record stores one root block or container subtree:

```text
block_type
label
data
is_visible
children[]
```

Server validation removes builder-only state such as database IDs, `_cid`,
selection, hover, drag, editing, and toolbar state. Pattern trees allow at most
200 blocks and five nesting levels. Only Group/Columns may contain children, and
Columns may contain Group children only.

Pattern insertion deep-clones `data` and recursively regenerates every `_cid`.
The original library object is not mutated. Page save-tree validation remains the
second persistence boundary after insertion.

## UI behavior

- Left panel tabs: Add Block / Patterns / Block List.
- Pattern cards show name, category or root type, optional description, Insert,
  and Delete.
- Block Settings exposes Save as Pattern for the selected block/subtree.
- Insert uses the existing selected-position/container rules.
- Inserted blocks remain editable through B3 settings and B4 inline editing.
- Deleting a pattern removes only the library record.
- The desktop `20:60:20` grid, full-height iframe, and CSS-only device max-width
  implementation remain unchanged.

## Routes

Four admin-only routes were added:

- `GET /admin/builder-patterns`
- `POST /admin/builder-patterns`
- `GET /admin/builder-patterns/{builderPattern}`
- `DELETE /admin/builder-patterns/{builderPattern}`

Admin route count changed from 155 to 159. No public route was added.

## Sanitization

`BuilderPatternService` validates every node against `config/blocks.php`, then
passes every node's data through
`PageBlockService::validateAndSanitizeData()`. B4 `InlineContentSanitizer` remains
unchanged and active. Script/style/event attributes, unsafe URLs, and unsupported
data are sanitized or rejected before pattern persistence.

## Automated verification

- B5 focused tests: 6 tests / 50 assertions / 0 failures.
- BuilderPattern + PageBlock focused suite: 26 tests / 403 assertions / 0 failures.
- Full suite: 619 tests / 3150 assertions / 0 failures.
- PHPStan level 5: 0 errors.
- Scoped Pint: PASS.
- Blade `view:cache`: PASS.
- Inline builder JavaScript syntax parse: PASS.
- Vite production build: PASS.
- `git diff --check`: PASS.
- Admin route count: 159.
- Migration status: Pending on dev DB; applied successfully by RefreshDatabase in tests.

Browser automation was not run end-to-end because no authenticated CMS tab was
available and the dev migration was intentionally not applied automatically.

## Owner manual QA checklist

First, after reviewing the migration, apply only B5 storage:

```bash
php artisan migrate --path=database/migrations/2026_06_22_000004_create_builder_patterns_table.php
```

Then:

1. Log in as an active admin and open a page in Visual Builder.
2. Confirm the layout is still 20:60:20, iframe fills canvas height, and desktop/
   tablet/mobile toggles only change max-width.
3. Select a configured Heading/Text/Hero block.
4. In Block Settings click **Save as Pattern**.
5. Enter name, optional category and description, then save.
6. Open the **Patterns** tab and confirm the pattern appears.
7. Click **Insert Pattern** and confirm the cloned block appears at the selected
   position or page end.
8. Edit the inserted block through the right B3 settings panel.
9. For Heading/Text/Hero/CTA, edit the inserted copy through B4 inline editing.
10. Save the page tree and reload the builder; confirm inserted content persists.
11. Save a Group with children as a pattern, insert it, and confirm its subtree
    and nesting are preserved.
12. Select a Columns block and confirm non-Group patterns are inserted after it,
    while Group patterns may be inserted inside it.
13. Delete the saved pattern from the Patterns tab.
14. Confirm the already inserted page block remains present and editable.
15. Confirm guest/non-admin accounts cannot access `/admin/builder-patterns`.

## Rollback

Before dev migration, rollback is code-only. After applying the isolated
migration, roll it back before removing the B5 model/controller code:

```bash
php artisan migrate:rollback --path=database/migrations/2026_06_22_000004_create_builder_patterns_table.php
```

This drops only `builder_patterns`; it does not alter Page or PageBlock data.
B4 remains restorable at commit `115b006`.

## Next recommended step

Complete owner manual QA and commit B5 as one concern. Then proceed to B6
Templates Integration; do not start repeater inline editing/B4A during B5.
