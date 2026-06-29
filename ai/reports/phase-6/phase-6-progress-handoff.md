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
> **Last updated:** 2026-06-29 — Planning complete, A0 decisions locked except
> entry-body model (PENDING owner confirm). **Phase 6 NOT STARTED.**

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
| A2 | Carry-over debt: pagination + FormRequest | ⏳ TODO | — | — |
| A3 | Polymorphic `page_blocks` (§3.2 = A, now active) | ⏳ TODO | ⚠️ schema (existing table) | — |
| A4 | `config/field-types.php` catalog scaffold | ⏳ TODO | — | — |

### Stage B — Build the Engine

| Task | Name | Status | Approval | Report |
|------|------|--------|----------|--------|
| B1 | Content Types module | ⏳ TODO | ⚠️ new table `content_types` | — |
| B2 | Field Groups + Fields module | ⏳ TODO | ⚠️ new tables `field_groups`, `fields` | — |
| B3 | Field rendering engine (admin form) | ⏳ TODO | — | — |
| B4 | Content Entries module | ⏳ TODO | ⚠️ new table `content_entries` | — |
| B5 | Query sidecar + indexing | ⏳ TODO | ⚠️ new table `content_entry_index` | — |
| B6 | Taxonomies & Terms | ⏳ TODO | ⚠️ new tables `taxonomies`, `terms`, pivot | — |
| B7 | Relationships | ⏳ TODO | ⚠️ new table `content_entry_relations` | — |
| B8 | Phase 4 reuse wiring (revisions/schedule/audit/seo) | ⏳ TODO | — | — |
| B9 | Entry body via builder (§3.2 = A, now active) | ⏳ TODO | — | — |
| B10 | Frontend routing + controllers | ⏳ TODO | ⚠️ route ordering | — |
| B11 | Template resolution + render | ⏳ TODO | — | — |
| B12 | **Builder bridge — `content_query` block** | ⏳ TODO | — | — |
| B13 | **Builder bridge — `content_field` block** | ⏳ TODO | — | — |

### Stage C — Release Audit

| Task | Name | Status | Report |
|------|------|--------|--------|
| C1 | Static Analysis & Code Quality | ⏳ TODO | — |
| C2 | Performance Audit | ⏳ TODO | — |
| C3 | Functional Smoke Test | ⏳ TODO | — |
| C4 | Documentation | ⏳ TODO | — |

---

## 2. Milestones (demoable checkpoints)

| Milestone | Tasks | Exit criteria |
|---|---|---|
| M1 — Decks cleared & decided | A0–A4 | Architecture locked, debt gone, field catalog scaffolded, suite green |
| M2 — Authoring works | B1–B4 | Owner can define a type + fields and create entries in admin |
| M3 — Organize & relate | B5–B8 | Entries filterable, taxonomized, related, revisioned, schedulable |
| M4 — Public + visual | B9–B13 | Entries render on frontend + placeable via page builder |
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
- A3 and B9 are now active tasks (no longer conditional). A3 stays gated on its
  own schema approval; the morph migration is not written until owner says
  "approved" at A3.

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
content_types       slug, label_singular, label_plural, icon, description,
                    is_public, has_archive, route_base, supports(json),
                    menu_position, is_active, timestamps, softDeletes
field_groups        content_type_id, label, key, description,
                    location_rules(json), sort_order, timestamps
fields              field_group_id, type, key, label, instructions, is_required,
                    is_filterable, default_value(json), settings(json),
                    conditional_logic(json), sort_order, timestamps
content_entries     content_type_id, title, slug, status, data(json),
                    author_id, published_at, seo(json), timestamps, softDeletes
                    [unique(content_type_id, slug)]
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
  config/field-types.php                         (A4)

Migrations:                                       (per task, post-approval)
Models:        app/Models/ContentType.php, FieldGroup.php, Field.php,
               ContentEntry.php, Taxonomy.php, Term.php                  (B1–B7)
FormRequests:  app/Http/Requests/Admin/...                              (B1–B7)
Controllers:   app/Http/Controllers/Admin/ContentType/Field/Entry...    (B1–B7)
               app/Http/Controllers/Frontend/ContentArchiveController.php (B10)
               app/Http/Controllers/Frontend/ContentSingleController.php  (B10)
Services:      app/Services/ContentTypeService.php, FieldRenderService.php,
               ContentEntryService.php, ContentQueryService.php          (B1–B12)
Support:       app/Support/PageRenderData.php (extend for entries/blocks) (B11–B13)
Admin views:   resources/views/backend/content/**                       (B1–B4)
Frontend:      resources/views/content/**                               (B11)
Blocks:        config/blocks.php (+content_query, +content_field)        (B12–B13)
               resources/views/frontend/blocks/content-query.blade.php    (B12)
               resources/views/frontend/blocks/content-field.blade.php    (B13)
Tests:         tests/Feature/Admin/Content*Test.php, Frontend/Content*    (each task)
```

---

## 7. Routes  *(fill in as built — B1, B10)*

```
(admin CRUD routes added at B1–B7)
(public: GET /{route_base}, GET /{route_base}/{entry:slug} added at B10)
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
| — | Patterns API unpaginated | `BuilderPatternController@index` | `paginate(20)` | A2 |
| — | Inline validate in controller | `PageBlockController` | Extract to FormRequest | A2 |
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
| Ships Taxonomies (B6) | create `ai/skills/taxonomy-skill.md` |
| Ships builder bridge blocks (B12–B13) | create `ai/skills/dynamic-blocks-skill.md`; add rows to block inventory; update `docs/modules/visual-builder.md` |
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
| Test suite (≥627 baseline, no regression) | ⏳ | — |
| PHPStan level 5 / 0 errors | ⏳ | — |
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
