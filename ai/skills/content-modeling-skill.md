# Skill: Content Modeling (Phase 6)

> For tasks on content types, field groups, fields, entries, taxonomies,
> relationships, revisions, entry builder body, or public entry routing.
> Pair with `field-types-skill.md` for field-type internals.

## Read first
- **Architecture:** `docs/modules/content-modeling.md` (canonical developer reference).
- **Detail per step:** `ai/reports/phase-6/*.md` (B1–B14, C1–C4).
- **Progress + grand plan:** `ai/reports/phase-6/phase-6-progress-handoff.md`.

## Golden rules
1. **Storage is hybrid:** truth in `content_entries.data` JSON; filterable fields
   are projected to `content_entry_index` on save — never hand-write the sidecar.
   All projections run via `ContentEntryObserver` (index + relations).
2. **No queries in Blade.** Relation-backed blocks resolve before render
   (`PageRenderData` for pages, `Frontend\ContentEntryController` for entries) and
   set `$block->resolved*`. Follow this for any new dynamic block.
3. **Dual-rail morph:** entries use `page_blocks` via `blockable_*` (page_id NULL).
   Never touch the Page builder's `page_id` rail — dual-rail keeps Phase 5 intact.
4. **Public routing is a fallback** (`Route::fallback`). Do not add explicit
   `/{route_base}` routes. `route_base` is unique + reserved-prefix guarded.
   Only published entries are public.
5. **Registering a new block** requires: `config/blocks.php` entry +
   `PageBlockService::defaultDataFor()` + `rulesFor()` + a `frontend.blocks.{kebab}`
   view. C3 smoke test enforces the view exists.
6. **Publishing = live now.** "Published" status clamps a blank/future
   `published_at` to now() (controller + builder). Future scheduling uses the
   "Scheduled" status. App timezone is `Asia/Jakarta` (WIB).
7. **Security:** per-field validation via `FieldValidationResolver`; richtext
   sanitized; url/email href validated; JSON-LD hex-escaped. Keep these when editing
   render paths.

## Schema gate
Every new table/column needs explicit owner approval (AGENTS.md §9). Phase 6 tables
are already migrated — extend only.
