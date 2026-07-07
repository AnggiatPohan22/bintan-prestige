# Phase 6 — Progress Handoff & Living Reference
# Bintan Prestige CMS

> **Dokumen ini adalah source of truth untuk Phase 6 (Flexible Content Modeling).**
> Di-update **setiap kali task selesai**. AI agents (Claude, Codex, Qwen, Gemini,
> Cursor) **WAJIB** membaca file ini + `phase-6-grand-plan.md` sebelum menyentuh
> kode Phase 6 apapun.
>
> **Location:** `ai/reports/phase-6/phase-6-progress-handoff.md`
> **Grand Plan:** `ai/reports/phase-6/phase-6-grand-plan.md`
> **Task Reports:** `ai/reports/phase-6/[task-id]-[nama].md`
>
> Authority order tetap berlaku (AGENTS.md §2). **Setiap schema change butuh
> approval owner eksplisit** (AGENTS.md §9) — fase ini hampir seluruhnya schema
> work, jadi approval gate sering by design.
>
> **Last updated:** 2026-07-07 — **PHASE 6 COMPLETE.** Stage A (A0–A4) + Stage B
> (B1–B14) + Stage C (C1–C4) all DONE. Content types, fields, entries, per-field
> validation, sidecar index, taxonomies, relations, revisions/audit/scheduling/SEO,
> builder body, public routing, template resolution, content_query + content_field
> blocks; release audit (static/perf/smoke/docs) passed. Test suite: **845/845 green**,
> PHPStan level 5: 0 errors. Release gate: **PASS** (pending owner production pre-flight).

---

## 0. How To Use This File

1. Read `AGENTS.md` → `phase-6-grand-plan.md` → this file, in that order.
2. Find the next `⏳ TODO` task in §1. Do **only** that task.
3. Follow AGENTS.md §6 module pattern + §10 workflow.
4. On completion: write the §11 report, update this file's status table,
   AND run the **Documentation Sync Matrix (§12)** for that task.
5. Never start a `⚠️ schema` task without an owner "approved" in the chat.

Legend: `⏳ TODO` · `🔨 IN PROGRESS` · `✅ DONE` · `⛔ BLOCKED` · `⚠️ needs approval`

---

## 1. Phase 6 Overall Status

### Stage A — Foundation, Decisions & Debt Clearing

| Task | Name | Status | Approval | Report |
|------|------|--------|----------|--------|
| A0 | Architecture Decision Record | ✅ DONE | §3.2 = Option A (approved 2026-06-29) | a0-architecture-decisions.md |
| A1 | Carry-over debt: TD-04, TD-05 | ✅ DONE | — | a1-debt-clearing-td04-td05.md |
| A2 | Carry-over debt: pagination + FormRequest | ✅ DONE | — | a2-pagination-formrequest.md |
| A3 | Polymorphic `page_blocks` (§3.2 = A, dual-rail) | ✅ DONE | ⚠️ schema — APPROVED 2026-06-30 | a3-polymorphic-page-blocks.md |
| A4 | `config/field-types.php` catalog scaffold | ✅ DONE | — | a4-field-types-catalog.md |

### Stage B — Build the Engine

| Task | Name | Status | Approval | Report |
|------|------|--------|----------|--------|
| B1 | Content Types module | ✅ DONE | ⚠️ schema — APPROVED 2026-06-30 (via "lanjut B1") | b1-content-types-module.md |
| B2 | Field Groups + Fields module | ✅ DONE | ⚠️ schema — APPROVED 2026-06-30 ("Approve B2") | b2-field-groups-fields-module.md |
| B3 | Field rendering engine (admin form) | ✅ DONE | — | b3-field-rendering-engine.md |
| B4 | Content Entries module | ✅ DONE | ⚠️ schema — APPROVED 2026-07-01 (scalability review passed) | b4-content-entries-module.md |
| B5 | Per-Field Validation Resolver | ✅ DONE | — (no schema change) | b5-field-validation-resolver.md |
| B6 | Query sidecar + indexing | ✅ DONE | ⚠️ schema — APPROVED 2026-07-01 ("lanjutkan B6") | b6-sidecar-index.md |
| B7 | Taxonomies & Terms | ✅ DONE | ⚠️ schema — APPROVED 2026-07-01 ("lanjut ke B7") | b7-taxonomies-terms.md |
| B8 | Relationships | ✅ DONE | ⚠️ schema — APPROVED 2026-07-01 ("ok move ke B8") | b8-relationships.md |
| B9 | Phase 4 reuse wiring (revisions/schedule/audit/seo) | ✅ DONE | ⚠️ schema (content_entry_revisions) — APPROVED 2026-07-02 | b9-phase4-reuse-wiring.md |
| B10 | Entry body via builder (§3.2 = A, now active) | ✅ DONE | — (reuse A3 morph rail) | b10-entry-builder.md |
| B11 | Frontend routing + controllers | ✅ DONE | ⚠️ route ordering — solved via Route::fallback() | b11-frontend-routing.md |
| B12 | Template resolution + render | ✅ DONE | — | b12-template-resolution.md |
| B13 | **Builder bridge — `content_query` block** | ✅ DONE | — | b13-content-query-block.md |
| B14 | **Builder bridge — `content_field` block** | ✅ DONE | — | b14-content-field-block.md |

### Stage C — Release Audit

| Task | Name | Status | Report |
|------|------|--------|--------|
| C1 | Static Analysis & Code Quality | ✅ DONE | c1-static-analysis-code-quality.md |
| C2 | Performance Audit | ✅ DONE | c2-performance-audit.md |
| C3 | Functional Smoke Test | ✅ DONE | c3-functional-smoke-test.md |
| C4 | Documentation | ✅ DONE | c4-documentation.md |

**PHASE 6 COMPLETE — 2026-07-07.** Release gate: **PASS** (pending owner production
pre-flight). Suite **845/845**, PHPStan level 5 / 0 errors. App tz: `Asia/Jakarta`.
Follow-up flagged (C1): align `StructuredDataBuilder` JSON-LD flags (Phase 4).

---

## 2. Milestones (demoable checkpoints)

| Milestone | Tasks | Exit criteria |
|---|---|---|
| M1 — Decks cleared & decided | A0–A4 | Architecture locked, debt gone, field catalog scaffolded, suite green |
| M2 — Authoring works | B1–B5 | Owner can define a type + fields, create entries, per-field validation enforced |
| M3 — Organize & relate | B6–B9 | Entries filterable, taxonomized, related, revisioned, schedulable |
| M4 — Public + visual | B10–B14 | Entries render on frontend + placeable via page builder |
| M5 — Ship | C1–C4 | Release gate PASS |

---

## 3. Architecture Decisions (A0)

> Locked via grand-plan approval on 2026-06-29 **except** §3.2 (entry body),
> which needs one explicit owner "approved" before A3/B9.

### 3.1 Field-value storage — **HYBRID ✅ ACCEPTED**

- Primary store: `content_entries.data` = JSON (mirrors `page_blocks.data`).
- Query sidecar: fields flagged `is_filterable` projected into
  `content_entry_index` on save → archive filter/sort hits the sidecar.
- Rationale: consistent with existing JSON-in-`data` pattern; avoids EAV join
  explosion; keeps filtering indexable. No MySQL-version lock-in.

### 3.2 Entry body model — **OPTION A (polymorphic `page_blocks`) ✅ ACCEPTED**

> Owner picked **Option A** on 2026-06-29.

- **Option A (CHOSEN):** make `page_blocks` polymorphic
  (`blockable_type` / `blockable_id`), backfill existing rows to `Page`. Content
  entries with `editor` support reuse the Phase 5 builder + `BuilderTreeSanitizer`
  unchanged. Most powerful; touches an existing table → **still requires explicit
  schema approval at A3 itself** (AGENTS.md §9) before the morph migration is written.
- ~~Option B (zero-risk): store entry block tree in `content_entries.body` JSON.~~
  **Rejected** in favour of A. The `content_entries.body` column is therefore
  **not** added (see §4).
- A3 and B9 are now active tasks (no longer conditional). **A3 ✅ DONE** —
  schema approved 2026-06-30, implemented as **Strategy 1 (dual-rail)**:
  `page_blocks` gained nullable `blockable_type`/`blockable_id` + index, `page_id`
  relaxed to nullable, existing rows backfilled to `Page`. Pages keep
  `page_id`/`hasMany` (builder untouched); entries (B9) use the morph with NULL
  `page_id`. `PageBlock::blockable()` morphTo added.

### 3.3 Field type registry — **CODE catalog + DB instances ✅**

- `config/field-types.php` = code catalog (mirror of `config/blocks.php`):
  `label`, `icon`, `category`, `settings_schema`, `cast`, `validation_rules`,
  `admin_partial`, `render_partial` per type.
- Field *instances* live in DB (`fields` table). New field type = catalog entry
  + 2 Blade partials + cast/validator (see §5 + field authoring pattern in
  `field-types-skill.md` once created).

### 3.4 Routing — **explicit per-type `route_base`, NO global catch-all ✅**

- Public types declare `route_base` (e.g. `blog`, `hotels`). Routes:
  - Archive: `GET /{route_base}` → `ContentArchiveController@index`
  - Single: `GET /{route_base}/{entry:slug}` → `ContentSingleController@show`
- Reserved-prefix guard in the Content Type FormRequest rejects collisions with
  `pages`, `products`, `admin`, `sitemap.xml`, etc.
- Non-public types (`is_public=false`) register no routes.

### 3.5 Template resolution — **theme hierarchy, reuse render path ✅**

`content/{type}/single.blade.php` → `content/single-{type}.blade.php` →
`content/single.blade.php` (generic fallback). Same for archive. Fallback renders
title + featured image + fields + optional block body via existing block-render
partials + active theme tokens (Phase 3/5).

### 3.6 Packages — **ZERO new packages ✅ CONFIRMED**

Repeater/relationship/conditional logic built on Alpine.js + `@alpinejs/sort`
(already installed Phase 5). If this ever changes → stop and get approval (§9).

### 3.7 Preserved constraints (do NOT change without owner approval)

1. Products / Bookings / Page Sections / Global Settings = **protected** (AGENTS.md §5). Never rebuilt. May be *linked* via `relationship` fields only.
2. `BuilderTreeSanitizer` reused unchanged for any entry block tree. Never forked.
3. Fixed 20:60:20 builder layout untouched. New blocks plug into the existing registry.
4. PHPStan level 5 / 0 errors / no baseline stays a hard gate.
5. No queries in Blade — entry/field/loop data prepared in `PageRenderData` / services.

---

## 4. Data Model (proposed — confirm per-table at its task)

```
page_blocks         [EXISTING table, altered at A3 ✅] + blockable_type(nullable),
                    blockable_id(nullable)  [index(blockable_type, blockable_id)];
                    page_id relaxed to NULLABLE. Dual-rail: pages keep page_id +
                    hasMany (builder untouched); entries use the morph (page_id
                    NULL). Existing rows backfilled to blockable=Page/page_id.
content_types       [NEW table ✅ B1] slug(unique), label_singular, label_plural,
                    icon, description, is_public, has_archive,
                    route_base(unique nullable), supports(json: title|slug|editor|
                    excerpt|featured_image|revisions|scheduling|seo),
                    menu_position, is_active, timestamps, softDeletes.
                    Reserved-prefix guard: admin/api/pages/products/preview/etc.
field_groups        [NEW table ✅ B2] content_type_id (FK→content_types CASCADE),
                    label, key, description, sort_order, timestamps.
                    UNIQUE(content_type_id, key) — namespace per tipe.
                    NOTE: `location_rules` dari grand plan dihapus — tidak diperlukan
                    di implementasi aktual; conditional_logic ada di level `fields`.
fields              [NEW table ✅ B2] field_group_id (FK→field_groups CASCADE),
                    type (varchar validated via FieldTypeRegistry — bukan FK DB),
                    key, label, instructions, is_required(bool), is_filterable(bool),
                    default_value(json nullable), settings(json nullable),
                    conditional_logic(json nullable), sort_order, timestamps.
                    UNIQUE(field_group_id, key) — namespace per group.
content_entries     [NEW table ✅ B4] content_type_id (FK→content_types RESTRICT —
                    bukan CASCADE; proteksi data), title, slug, excerpt, status(varchar
                    bukan ENUM), published_at, author_id (FK→users SET NULL), template,
                    sort_order, data(json), seo(json), timestamps, softDeletes.
                    UNIQUE(content_type_id, slug) — slug namespace per tipe.
                    NOTE: no `body` column — §3.2 = Option A (block tree lives in
                    polymorphic page_blocks via blockable_type/id, added at A3)
content_entry_index content_entry_id, field_key, value_string, value_number,
                    value_date  [indexed per value type]
taxonomies          slug, label_singular, label_plural, is_hierarchical,
                    content_type_ids(json), timestamps
terms               taxonomy_id, parent_id, name, slug, description, sort_order
                    [unique(taxonomy_id, slug)]
content_entry_term  content_entry_id, term_id  [composite pk]
content_entry_relations  source_entry_id, target_entry_id, field_key, sort_order
```

**Reused, no new tables:** revisions, scheduler, audit log (Phase 4); media
(Media Library); SEO meta (Phase 4 SEO manager).

---

## 5. Field Type Catalog (initial 15 — `config/field-types.php`)

| Type | Stored | Filterable | Notes |
|---|---|---|---|
| text | string | ✅ | maxlength, placeholder |
| textarea | string | ✅ | rows |
| richtext | html | — | `InlineContentSanitizer::richtext()` |
| number | number | ✅ | min/max/step |
| toggle | bool | ✅ | |
| select | string/array | ✅ | options, multiple |
| radio | string | ✅ | options |
| checkbox | array | — | options |
| date / datetime | date | ✅ | |
| email / url | string | ✅ | type-validated |
| color | string | — | hex, token palette |
| image | media id | — | Media Library ref |
| gallery | media id[] | — | Media Library multi |
| file | media id | — | Media Library ref |
| relationship | entry id[] | ✅ (by id) | target type(s), min/max |
| repeater | json rows | — | sub-fields, max 1 nest |

> `flexible_content` = stretch goal only if Stage B finishes early.

---

## 6. Module / File Map  *(fill in as built)*

```
Config:
  config/field-types.php                              ✅ A4 — 18 types, read via FieldTypeRegistry

Support:
  app/Support/FieldTypeRegistry.php                  ✅ A4 — thin static wrapper atas config
  app/Support/FieldValidationResolver.php             ✅ B5 — dynamic data.* rules per ContentType
  app/Support/ContentEntryIndexService.php            ✅ B6 — project filterable fields to sidecar

Migrations:
  2026_06_30_000002_create_content_types_table        ✅ B1
  2026_06_30_000003_create_field_groups_table         ✅ B2
  2026_06_30_000004_create_fields_table               ✅ B2
  2026_07_01_000001_create_content_entries_table      ✅ B4
  content_entry_index     [NEW table ✅ B6] CASCADE on content_entry_id; 3 value columns
                          (value_string, value_number, value_date); no timestamps.
  (taxonomies, terms, pivot, relations — B7–B8)

Models:
  app/Models/ContentType.php                          ✅ B1 — supports(), fieldGroups(), entries()
  app/Models/FieldGroup.php                           ✅ B2 — belongsTo ContentType, hasMany Field
  app/Models/Field.php                                ✅ B2 — typeDefinition(), typeLabel()
  app/Models/ContentEntry.php                         ✅ B4 — fieldValue(), statusBadgeClass(), scopes
  app/Models/Taxonomy.php, Term.php                   ⏳ B6

FormRequests:
  StoreContentTypeRequest, UpdateContentTypeRequest   ✅ B1
  StoreFieldGroupRequest, UpdateFieldGroupRequest     ✅ B2
  StoreFieldRequest, UpdateFieldRequest               ✅ B2
  StoreContentEntryRequest, UpdateContentEntryRequest ✅ B4 + B5 (per-field rules via resolver)
  (Taxonomy/Term FormRequests — B7)

Controllers:
  Admin/ContentTypeController.php                     ✅ B1 — incl. forceDelete guard (B4)
  Admin/FieldGroupController.php                      ✅ B2 — reorder + ownership check
  Admin/FieldController.php                           ✅ B2 — dual ownership check
  Admin/ContentEntryController.php                    ✅ B4 — index filter + auth entry
  Frontend/ContentArchiveController.php               ⏳ B10
  Frontend/ContentSingleController.php                ⏳ B10

Blade Component:
  resources/views/components/admin/field-input.blade.php  ✅ B3 — dispatcher ke type partials

Field Type Partials (resources/views/fields/):
  text, textarea, richtext, number, email, url            ✅ B3
  toggle, select, radio, checkbox                         ✅ B3
  date, datetime                                          ✅ B3
  image, gallery, file, relationship, color, repeater     ✅ B3

Admin Views:
  resources/views/backend/content-types/                  ✅ B1 (index, create, edit, form) + B4 (Entries btn)
  resources/views/backend/field-groups/                   ✅ B2 (index, create, edit, form)
  resources/views/backend/fields/                         ✅ B2 (create, edit, form)
  resources/views/backend/content-entries/                ✅ B4 (index, create, edit, form)
  resources/views/content/**                              ⏳ B11

Services:
  ContentQueryService.php, ContentEntryService.php        ⏳ B12–B13

Blocks:
  config/blocks.php (+content_query, +content_field)      ⏳ B12–B13
  resources/views/frontend/blocks/content-query.blade.php ⏳ B12
  resources/views/frontend/blocks/content-field.blade.php ⏳ B13

Tests:
  tests/Feature/Admin/ContentTypeTest.php                 ✅ B1 (11 tests)
  tests/Feature/Admin/FieldGroupTest.php                  ✅ B2 (10 tests)
  tests/Feature/Admin/FieldTest.php                       ✅ B2 (10 tests)
  tests/Feature/Phase6/B3FieldRenderingTest.php           ✅ B3 (14 tests)
  tests/Feature/Admin/ContentEntryTest.php                ✅ B4 (14 tests)
  tests/Feature/Phase6/B5FieldValidationResolverTest.php  ✅ B5 (14 tests)
  tests/Feature/Phase6/B6SidecarIndexTest.php             ✅ B6 (13 tests)
  tests/Feature/Phase6/A4FieldTypesCatalogTest.php        ✅ A4 (4 tests)
```

---

## 7. Routes  *(fill in as built — B1, B10)*

```
Admin routes (all under middleware ['auth','admin'], prefix 'admin', name 'admin.'):

[B1] Content Types:
  GET    admin/content-types                        content-types.index
  GET    admin/content-types/create                 content-types.create
  POST   admin/content-types                        content-types.store
  GET    admin/content-types/{ct}/edit              content-types.edit
  PUT    admin/content-types/{ct}                   content-types.update
  DELETE admin/content-types/{ct}                   content-types.destroy
  PATCH  admin/content-types/{id}/restore           content-types.restore
  DELETE admin/content-types/{id}/force-delete      content-types.force-delete

[B2] Field Groups (nested under content-types/{ct}):
  GET    .../field-groups                           content-types.field-groups.index
  GET    .../field-groups/create                    content-types.field-groups.create
  POST   .../field-groups                           content-types.field-groups.store
  GET    .../field-groups/{fg}/edit                 content-types.field-groups.edit
  PUT    .../field-groups/{fg}                      content-types.field-groups.update
  DELETE .../field-groups/{fg}                      content-types.field-groups.destroy
  POST   .../field-groups/reorder                   content-types.field-groups.reorder

[B2] Fields (nested under .../field-groups/{fg}):
  GET    .../fields/create                          content-types.field-groups.fields.create
  POST   .../fields                                 content-types.field-groups.fields.store
  GET    .../fields/{f}/edit                        content-types.field-groups.fields.edit
  PUT    .../fields/{f}                             content-types.field-groups.fields.update
  DELETE .../fields/{f}                             content-types.field-groups.fields.destroy
  POST   .../fields/reorder                         content-types.field-groups.fields.reorder

[B4] Content Entries (nested under content-types/{ct}):
  GET    .../entries                                content-types.entries.index
  GET    .../entries/create                         content-types.entries.create
  POST   .../entries                                content-types.entries.store
  GET    .../entries/{entry}/edit                   content-types.entries.edit
  PUT    .../entries/{entry}                        content-types.entries.update
  DELETE .../entries/{entry}                        content-types.entries.destroy
  PATCH  .../entries/{id}/restore                   content-types.entries.restore
  DELETE .../entries/{id}/force-delete              content-types.entries.force-delete

[B10 — TODO] Public routes:
  GET    /{route_base}                              content.archive (ContentArchiveController)
  GET    /{route_base}/{entry:slug}                 content.single  (ContentSingleController)
```

---

## 8. Caching & Performance Rules

- Archive queries paginated from day one (`paginate()`), never unbounded.
- Eager-load: `ContentEntry::with(['terms','relations','contentType'])` for lists.
- Filter/sort via `content_entry_index`, not JSON `WHERE` scans.
- No N+1 on entries + fields + relations + terms — verified at C2.
- Field catalog read from `config/field-types.php` (config-cached), never queried.

---

## 9. Sanitization Rules (reuse Phase 5 — do not fork)

- richtext fields → `App\Support\InlineContentSanitizer::richtext()` on save.
- plaintext fields → `InlineContentSanitizer::plaintext()`.
- `content_field` block render escapes by field type; never raw `{!! !!}` on
  unsanitized field values.
- Entry block tree (if §3.2=A) → `BuilderTreeSanitizer` (unchanged).

---

## 10. Phase 5 Bridge (the headline)

- **`content_query`** (B12): pick content type → filter (taxonomy term / field
  via sidecar) → order → limit → paginate → layout (grid/list/carousel) → card
  fields. Data prepared in `ContentQueryService` + `PageRenderData`. No queries
  in Blade. Built via the standard block authoring pattern (handoff Phase 5 §3.3).
- **`content_field`** (B13): output one field of the *current* entry inside a
  single template (title / custom field / featured image / term list).

Both respect the block registry, `BuilderTreeSanitizer`, and the 20:60:20 shell.

---

## 11. Carry-over Technical Debt (clear in Stage A)

| # | Item | File | Action | Task |
|---|------|------|--------|------|
| TD-03 | Child theme support | `ThemeService` | Re-evaluate; defer to Phase 7 if not needed | A0 note |
| TD-04 | Widget text not sanitized | `frontend/widgets/text.blade.php` | ✅ DONE — wrapped via `InlineContentSanitizer::richtext()` (A1) | A1 |
| TD-05 | DB query in Blade | `frontend/blocks/contact-form.blade.php:3` | ✅ DONE — moved `FormDefinition::find()` to `PageRenderData::prepareContactFormBlocks()` (A1) | A1 |
| — | Patterns API unpaginated | `BuilderPatternController@index` | ✅ DONE — `paginate(20)`; `patterns` kept flat for the builder client, pagination under `meta` | A2 |
| — | Inline validate in controller | `PageBlockController` | ✅ DONE — extracted to `StorePageBlockRequest`, `UpdatePageBlockRequest`, `ReorderPageBlockRequest` | A2 |
| TD-06 | Pre-existing baseline RED (from `4c6cd6d` brand-identity merge, NOT Phase 6): 3 PHPStan errors in `AdminDashboardAppearance.php` (return.type, new.static) + `AdminAppearanceService.php` (booleanAnd.rightAlwaysTrue) | `app/Models/AdminDashboardAppearance.php`, `app/Services/AdminAppearanceService.php` | ✅ DONE — `static`→`self` (model getCurrent/makeDefault), dropped redundant `is_array` guard (service). PHPStan 0 errors restored. | A1+ |
| TD-07 | Pre-existing test failure (baseline, NOT Phase 6): `PageBlockManagementTest::…accessible_save_delete_states` asserted inline block-delete copy removed by the refactor | `tests/Feature/Admin/PageBlockManagementTest.php:443` | ✅ DONE — test updated to assert the new per-block `data-confirm` confirmation + `aria-label="Delete …"` (Command Center Dark UX). Suite 627 green. | A1+ |

| TD-08 | EN/ID admin copy inconsistency (brand-identity refactor): 3 Indonesian `data-confirm` strings amid an otherwise English admin, **plus** a curly-quote markup bug (`type=”submit” data-confirm=”…”`) that silently disabled the menu-item delete confirmation | `pages/partials/block-editor.blade.php`, `menus/partials/item-row.blade.php`, `settings/appearance/index.blade.php` | ✅ DONE — all three translated to English; smart-quote bug fixed; TD-07 test synced; added menu-item delete regression test. Suite 633 green. | A1+ |

---

## 12. Documentation Sync Matrix ⭐ (run after EVERY relevant task)

**This is mandatory. A task is not "done" until its doc updates are made.**
When a task does X, update Y in the same PR/commit:

| When a task… | Update these files |
|---|---|
| Adds/changes a phase status | `AGENTS.md` §4 (phase list), `Claude.md` header (Phase line) |
| Adds a new module/skill area | `AGENTS.md` §3 Skill Map row, `Claude.md` Skill Map row, create the skill file in `ai/skills/` |
| Creates `config/field-types.php` (A4) | create `ai/skills/field-types-skill.md` (authoring pattern) |
| Ships Content Types/Fields/Entries (B1–B4) | create `ai/skills/content-modeling-skill.md`; update this handoff §6/§7 |
| Ships Taxonomies (B7) | create `ai/skills/taxonomy-skill.md` |
| Ships builder bridge blocks (B13–B14) | create `ai/skills/dynamic-blocks-skill.md`; add rows to block inventory; update `docs/modules/visual-builder.md` |
| Adds any route | update this handoff §7 + `docs/` route reference |
| Adds any DB table/column | update this handoff §4 + `AGENTS.md` §5 if it becomes a protected module |
| Completes any task | update §1 status table here + write §11-format report + append `docs/changelog/CHANGELOG.md` |
| Completes Phase 6 (C4) | flip `AGENTS.md` §4 Phase 6 → COMPLETE + Phase 7 → next; update `Claude.md` Phase line; finalize `docs/modules/content-modeling.md`; write Phase 7 prep notes here §14 |

**New Skill Map rows to add (AGENTS.md §3 + Claude.md):**
```
| Content Types / Fields / Entries | content-modeling-skill.md + field-types-skill.md |
| Taxonomies / Terms               | taxonomy-skill.md |
| Dynamic Builder Blocks           | dynamic-blocks-skill.md + page-builder-skill.md |
```

---

## 13. Release Gate (fill at Stage C)

| Gate | Status | Evidence |
|------|--------|----------|
| Test suite (≥627 baseline, no regression) | 🔨 IN PROGRESS | 728/728 pass (2026-07-01 after B6) |
| PHPStan level 5 / 0 errors | 🔨 IN PROGRESS | 0 errors (2026-07-01 after B6) |
| Performance (archives ≤300ms, paginated, no N+1) | ⏳ | — |
| Smoke test (public routes, draft 404, admin guard) | ⏳ | — |
| Docs (content-modeling.md + CHANGELOG + AGENTS/Claude synced) | ⏳ | — |
| Owner proof type shipped end-to-end (suggest `review`) | ⏳ | — |
| **Release Gate** | ⏳ | — |

---

## 14. Phase 7 Preparation Notes  *(finalize at C4)*

> To be filled when Phase 6 closes. Candidate carry-overs: child theme (TD-03),
> flexible_content field type, content REST/GraphQL API (Phase 8),
> internationalization hooks on `content_entries` (Phase 7).

---

## 15. Kickoff Prompt & Report Location

- **Kickoff prompt:** `ai/prompts/phase-6-kickoff.md` (paste at start of each session)
- **All task reports go in:** `ai/reports/phase-6/[task-id]-[nama].md`
  (e.g. `a1-debt-clearing-td04-td05.md`, `b1-content-types-module.md`)
- Always: read AGENTS.md → grand plan → this handoff; do one `⏳ TODO` task;
  stop at `⚠️` gates for owner approval; run the §12 Sync Matrix; write report
  in §11 format inside this folder.
