# Phase 6 — Flexible Content Modeling — Grand Plan
# Bintan Prestige CMS

> **Status:** PLANNING (not started). Awaiting owner approval before any code.
> **Author:** Architecture planning pass — 2026-06-29
> **Depends on:** Phase 5 COMPLETE (Visual Page Builder) — 2026-06-23
> **Stack:** Laravel 13.8 | PHP 8.3 | Tailwind | Alpine.js | MySQL 8
> **Location:** `ai/reports/phase-6/phase-6-grand-plan.md`
>
> This document is the **constitution for Phase 6**. It defines *what* and
> *why*. The per-task detail lives in the Phase 6 living handoff
> (`ai/reports/phase-6/phase-6-progress-handoff.md`) and in the skill files.
>
> Authority order still applies (AGENTS.md §2). Every schema change in this
> phase requires explicit owner approval (AGENTS.md §9) — and this phase is
> **almost entirely schema work**, so approval gates are frequent by design.

---

## 0. Why This Phase (One Paragraph)

Today the CMS can build *pages* from blocks, but every "thing" the site talks
about — a tour, a hotel, a review, a blog post, an event — either lives in a
hardcoded module (Products) or has nowhere to live at all. Phase 6 gives the
owner the power to **define new content structures from the admin dashboard**,
without a developer writing a migration. This is the single feature that
separates a "page builder" from a real CMS. WordPress calls it Custom Post
Types + Advanced Custom Fields + Taxonomies + the Query Loop. We will build the
Laravel equivalent.

**Phase 6 Goal:** From the admin dashboard, the owner can create a new content
type (e.g. "Hotel"), define its custom fields (star rating, price range,
location, gallery, amenities), create entries of that type, organize them with
custom taxonomies, link them to other content, and render both archive (list)
and single (detail) pages on the frontend — all without touching code.

---

## 1. Scope

### 1.1 In Scope

1. **Content Type Builder** — define content types (label, slug, icon, supports, routing) from admin.
2. **Custom Fields engine** — field groups + fields with ~15 field types, validation, conditional logic.
3. **Content Entries** — CRUD for records of any content type, with status/draft/scheduling.
4. **Taxonomies & Terms** — custom categories/tags (hierarchical or flat) attachable to content types.
5. **Relationships** — link entries to entries (Tour → Destination, Hotel → Reviews).
6. **Frontend rendering & routing** — archive + single views, template resolution.
7. **Visual Builder bridge** — new dynamic blocks: **Query Loop** (`content_query`) and **Field Binding** (`content_field`) so Phase 5 builder can display Phase 6 data.
8. **Reuse of Phase 4 infrastructure** — revisions, scheduling, audit log, SEO meta apply to content entries.
9. **Release audit (Stage C)** — PHPStan level 5, performance, smoke test, docs.
10. **Carry-over technical debt** — clear TD-03, TD-04, TD-05 + pagination + FormRequest refactor (see §12).

### 1.2 Out of Scope (explicitly NOT this phase)

- **Migrating Products/Tours/Bookings into the new engine.** These are protected
  modules (AGENTS.md §5). They stay as-is. Phase 6 is for *new* content types.
  We may *link* to Products via relationship fields, never rebuild them.
- Multi-language content (that is Phase 7 — Internationalization).
- Drag-and-drop *field builder* UI polish — a functional admin form is enough;
  fancy UX is a follow-up, not a blocker. This phase is engine-first.
- A public REST/GraphQL API for content (candidate for Phase 8).

### 1.3 Guardrails (do not break)

- No hardcoded frontend content — everything renders from CMS data.
- Never rebuild a protected module; extend only.
- PHPStan level 5 / 0 errors / no baseline stays a hard gate.
- No `min-h-0` / layout regressions in the existing builder.
- No new package without approval (§9). See §3.6 for the one likely candidate.

---

## 2. Mental Model & Naming (avoid collisions)

We already have `pages`, `page_sections`, `page_blocks`. Phase 6 introduces a
*parallel* concept. Keep names distinct to avoid confusion:

| WordPress term | Our term | Table |
|---|---|---|
| Custom Post Type | **Content Type** | `content_types` |
| Field Group (ACF) | **Field Group** | `field_groups` |
| Custom Field (ACF) | **Field** | `fields` |
| Post / record | **Content Entry** | `content_entries` |
| Taxonomy | **Taxonomy** | `taxonomies` |
| Term | **Term** | `terms` |
| Query Loop block | **Content Query block** | block type `content_query` |

> "Page" stays the singleton, builder-driven document. "Content Entry" is a
> typed, repeatable record. A blog with 200 posts = 200 content entries of type
> `post`. An About page = 1 page. This distinction must be clear in docs.

---

## 3. Key Architecture Decisions (the "B0" of Phase 6)

These are the decisions that must be locked **before** any table is created.
Each has a recommendation; **owner must sign off** (this is Stage A0's only job).

### 3.1 Field-value storage model — **Hybrid (recommended)**

Three options were considered:

| Option | Pro | Con |
|---|---|---|
| Pure EAV (`field_values` row per field) | Fully queryable | Many joins, N+1 risk, slow hydration |
| Pure JSON (`content_entries.data`) | Fast whole-entry read, consistent with `page_blocks.data` | Hard to filter/sort by a single field in SQL |
| **Hybrid (recommended)** | Fast read **and** queryable | Slightly more write logic |

**Recommendation — Hybrid:**
- **Primary store:** `content_entries.data` = JSON (mirrors `page_blocks.data`,
  one read hydrates the whole entry).
- **Query sidecar:** fields flagged `is_filterable` get projected into a thin
  `content_entry_index` table (`entry_id`, `field_key`, `value_string`,
  `value_number`, `value_date`) on save. Archive filtering/sorting hits the
  sidecar; full render reads the JSON. Best of both, no MySQL-version lock-in.
- Rationale: consistent with the codebase's existing JSON-in-`data` pattern,
  avoids EAV join explosion, keeps filtering fast and indexable.

### 3.2 Entry body — **opt-in block tree via polymorphic `blockable`**

Should a content entry have a builder block tree as its "body" (Gutenberg-style),
or only custom fields?

**Recommendation:** Support **both**, per content type, via a `supports` flag.
A content type can opt into `editor` support. When enabled, the entry's body is a
block tree **reusing the Phase 5 builder** — no second builder.

- This requires `page_blocks` to become **polymorphic**: add `blockable_type` /
  `blockable_id` (morph) so blocks can belong to a `Page` **or** a
  `ContentEntry`. ⚠️ **This is a schema change to an existing table — explicit
  owner approval required (§9).** Migration must backfill existing rows to
  `blockable_type = Page`. `BuilderTreeSanitizer` is reused unchanged.
- If the owner rejects the morph (to keep `page_blocks` untouched), fallback:
  store the entry body tree in `content_entries.body` JSON and render via the
  same block-render partials. Less elegant (two storage paths) but zero risk to
  the page builder. **Owner picks A (morph) or B (JSON body) at A0.**

### 3.3 Field type registry — **code-defined catalog, DB-defined instances**

Mirror the block pattern: field *types* are a code catalog (extensible by devs),
field *instances* are user-defined in the DB.

- **`config/field-types.php`** — catalog keyed by type slug, each with
  `label`, `icon`, `category`, `settings_schema`, `cast`, `validation_rules`,
  `admin_partial`, `render_partial`. Same spirit as `config/blocks.php`.
- New field type = add a catalog entry + 2 Blade partials (admin input + frontend
  output) + a cast/validator. Documented as the "field authoring pattern" (like
  the block authoring pattern in handoff §3.3).

### 3.4 Routing — **explicit per-type base slugs, no global catch-all**

A global `/{slug}` catch-all would shadow existing `/pages/{slug}`,
`/products/...`, sitemap, etc. **Do not do that.**

**Recommendation:**
- Each public content type declares `has_archive` + `route_base` (e.g. `blog`,
  `hotels`). Routes registered explicitly:
  - Archive: `GET /{route_base}` → `ContentArchiveController@index`
  - Single: `GET /{route_base}/{entry:slug}` → `ContentSingleController@show`
- A startup guard validates `route_base` does not collide with reserved
  prefixes (`pages`, `products`, `admin`, `sitemap.xml`, etc.) at save time in
  the Content Type form request.
- Non-public content types (e.g. `review` used only inside other pages) have
  `is_public = false` and register no routes.

### 3.5 Template resolution — **theme hierarchy, reuse existing render path**

Single/archive rendering resolves a Blade in this order (WordPress-like):
`content/{type}/single.blade.php` → `content/single-{type}.blade.php` →
`content/single.blade.php` (generic fallback). Same for archive. The generic
fallback renders title + featured image + fields + optional block body using
existing block-render partials and the active theme tokens (Phase 3/5). No new
theming system.

### 3.6 Likely new package (needs approval at A0)

- None strictly required. Repeater/flexible-content, relationship pickers, and
  conditional logic can all be built on **Alpine.js + existing stack**.
- *Optional* nicety: a drag-sort for repeater rows — already covered by
  `@alpinejs/sort` (already installed in Phase 5). **No new package expected.**
  Flag explicitly at A0 so the owner confirms "zero new dependencies."

---

## 4. Data Model (proposed tables)

> All subject to A0 approval. Indexes and FKs detailed in the migration plan
> per task. Shown here as the architectural picture, not final DDL.

```
content_types
  id, slug (unique), label_singular, label_plural, icon, description,
  is_public (bool), has_archive (bool), route_base (nullable, unique when public),
  supports (json: [title, slug, editor, excerpt, featured_image, revisions,
             scheduling, seo, comments]),
  menu_position (int), is_active (bool), timestamps, soft deletes

field_groups
  id, content_type_id (fk), label, key (unique per type), description,
  location_rules (json — when to show), sort_order, timestamps

fields
  id, field_group_id (fk), type (matches config/field-types.php key),
  key (unique per group), label, instructions, is_required (bool),
  is_filterable (bool), default_value (json), settings (json — per-type config:
  options, min/max, relationship target type, repeater sub-fields, etc.),
  conditional_logic (json), sort_order, timestamps

content_entries
  id, content_type_id (fk), title, slug (unique per type), status
  (draft|published|scheduled|archived), data (json — field values),
  body (json, nullable — block tree if §3.2 option B), author_id (fk users),
  published_at (nullable), seo (json — meta title/description/og),
  timestamps, soft deletes
  [unique index on (content_type_id, slug)]

content_entry_index            ← query sidecar (§3.1)
  id, content_entry_id (fk), field_key, value_string (nullable, indexed),
  value_number (nullable, indexed), value_date (nullable, indexed)
  [index on (field_key, value_string), (field_key, value_number), (field_key, value_date)]

taxonomies
  id, slug (unique), label_singular, label_plural, is_hierarchical (bool),
  content_type_ids (json — which types it applies to), timestamps

terms
  id, taxonomy_id (fk), parent_id (nullable, self fk), name, slug, description,
  sort_order, timestamps
  [unique (taxonomy_id, slug)]

content_entry_term            ← pivot
  content_entry_id (fk), term_id (fk)  [composite pk]

content_entry_relations       ← relationship field storage (§ field type "relationship")
  id, source_entry_id (fk), target_entry_id (fk), field_key, sort_order
  [index on (source_entry_id, field_key)]
```

**Reuse from earlier phases (no new tables):**
- Revisions → existing Phase 4 revision system (make it polymorphic if not already).
- Scheduling → existing Phase 4 content scheduler.
- Audit log → existing Phase 4 audit log.
- Media → existing Media Library (image/gallery/file fields store media ids).
- SEO meta → existing Phase 4 SEO manager patterns.

---

## 5. Field Type Catalog (initial 15)

`config/field-types.php` ships with these. Each = 1 admin partial + 1 render
partial + cast + validation.

| Type | Stored as | Filterable | Notes |
|---|---|---|---|
| `text` | string | ✅ | maxlength, placeholder |
| `textarea` | string | ✅ | rows |
| `richtext` | html | — | sanitized via `InlineContentSanitizer::richtext()` (reuse) |
| `number` | number | ✅ | min/max/step, prefix/suffix |
| `toggle` | bool | ✅ | |
| `select` | string/array | ✅ | options, multiple |
| `radio` | string | ✅ | options |
| `checkbox` | array | — | options |
| `date` / `datetime` | date | ✅ | |
| `email` / `url` | string | ✅ | validated by type |
| `color` | string | — | hex, reuse token palette |
| `image` | media id | — | Media Library ref |
| `gallery` | media id[] | — | Media Library multi |
| `file` | media id | — | Media Library ref |
| `relationship` | entry id[] | ✅ (by id) | target content type(s), min/max, bi-directional optional |
| `repeater` | json rows | — | sub-fields (subset of above, max 1 nest level) |

> `flexible_content` (ACF-style mixed blocks) is a **stretch goal** — only if
> Stage B finishes early. Repeater covers 90% of needs.

---

## 6. Work Breakdown — Stages A / B / C

Mirrors the Phase 5 cadence. Each task follows AGENTS.md §6 module pattern
(migration → model → form request → controller → service → views → frontend →
SEO → security → docs → tests) and §10 workflow, and ends with the §11 report.

### Stage A — Foundation, Decisions & Debt Clearing

| Task | Name | Output | Approval gate |
|---|---|---|---|
| **A0** | Architecture Decision Record | Lock §3.1–§3.6; create `phase-6-progress-handoff.md`; confirm "zero new packages" | ⚠️ schema strategy + `page_blocks` morph decision |
| **A1** | Carry-over debt: TD-04, TD-05 | Sanitize widget text; move `FormDefinition::find()` out of Blade | — |
| **A2** | Carry-over debt: pagination + FormRequest | Paginate `BuilderPatternController@index`; FormRequest for `PageBlockController` | — |
| **A3** | Polymorphic `page_blocks` (if §3.2-A chosen) | Migration `blockable_type/id` + backfill `Page`; reuse `BuilderTreeSanitizer` | ⚠️ schema change to existing table |
| **A4** | `config/field-types.php` catalog scaffold | Catalog + casts + validation registry (no UI yet) | — |

> A1–A2 clear the deck (Phase 5 left these as Phase 6 items). A0/A3 are the
> risky schema decisions — done first, with the owner in the loop.

### Stage B — Build the Engine

| Task | Name | Notes |
|---|---|---|
| **B1** | Content Types module | `content_types` table, model, FormRequest (route_base collision guard §3.4), admin CRUD, `supports` flags |
| **B2** | Field Groups + Fields module | `field_groups`, `fields` tables, models, admin form to define fields; conditional logic stored, validated |
| **B3** | Field rendering engine (admin) | Render the create/edit entry form **dynamically** from field definitions; one partial per field type |
| **B4** | Content Entries module | `content_entries` table, model with dynamic field accessors, list view with dynamic columns, create/edit, status workflow |
| **B5** | Query sidecar + indexing | `content_entry_index` populated on save; filter/sort service for archives |
| **B6** | Taxonomies & Terms | `taxonomies`, `terms`, `content_entry_term`; admin CRUD; attach to entries |
| **B7** | Relationships | `relationship` field type wiring + `content_entry_relations`; optional bi-directional |
| **B8** | Phase 4 reuse wiring | Hook content entries into revisions, scheduling, audit log, SEO meta |
| **B9** | Entry body via builder (if §3.2-A) | Open builder on a content entry; save tree to polymorphic blocks |
| **B10** | Frontend routing + controllers | `route_base` registration, `ContentArchiveController`, `ContentSingleController`, collision guard |
| **B11** | Template resolution + render | `content/{type}/single|archive` hierarchy + generic fallback; theme tokens |
| **B12** | **Visual Builder bridge — Content Query block** | `content_query` block: pick type, filter by taxonomy/field, sort, paginate, choose card layout → renders entry loop inside any page |
| **B13** | **Visual Builder bridge — Field Binding block** | `content_field` block: output a field of the *current* entry inside single templates |

> **B12 is the headline feature.** It is the WordPress "Query Loop": it makes
> all this data usable inside the existing page builder. B1–B11 are plumbing;
> B12–B13 are what the owner will actually *see and use* daily.

### Stage C — Release Audit (same gate as Phase 5)

| Task | Name | Pass criteria |
|---|---|---|
| **C1** | Static Analysis & Code Quality | PHPStan level 5 / 0 errors; no dead code; `{!! !!}` audit clean (incl. dynamic field output) |
| **C2** | Performance Audit | Archive/single routes ≤300ms warm; no N+1 on entries+fields+relations+terms; sidecar indexes verified; entry-list pagination |
| **C3** | Functional Smoke Test | Full suite green; HTTP checks for new public routes; draft/unpublished entry 404 guard; admin auth guard; field-type round-trip tests |
| **C4** | Documentation | `docs/modules/content-modeling.md`; field-type authoring guide; CHANGELOG; Phase 7 prep notes; update AGENTS.md §4 + Claude.md |

---

## 7. Integration With Phase 5 (the bridge)

The two new blocks are the whole point of connecting Phase 6 to Phase 5:

- **`content_query`** (Content Query / loop block)
  - Settings: content type, filter rules (taxonomy term, field value via
    sidecar), order by, limit, pagination on/off, layout (grid/list/carousel),
    card template (which fields to show).
  - Render: queries entries, loops, renders each via a card partial. Built
    using existing block authoring pattern (handoff §3.3). No queries in Blade —
    data prepared in `PageRenderData`.
- **`content_field`** (Field binding block)
  - Only meaningful inside a single-entry template context. Outputs one field of
    the current entry (title, a custom field, featured image, a term list).
  - Enables building single templates visually instead of hardcoding Blade.

Both respect `BuilderTreeSanitizer`, the 19→21+ block registry pattern, and the
fixed 20:60:20 builder layout. No builder-shell changes needed.

---

## 8. New Skill Files & Docs (Phase 6)

Add to `ai/skills/` and wire into AGENTS.md §3 Skill Map + Claude.md:

| File | Purpose |
|---|---|
| `content-modeling-skill.md` | Content types, fields, entries — the engine rules |
| `field-types-skill.md` | Field type catalog + authoring pattern (config + 2 partials + cast/validator) |
| `taxonomy-skill.md` | Taxonomies, terms, attachment rules |
| `dynamic-blocks-skill.md` | `content_query` + `content_field` authoring & render-data rules |
| `phase-6-progress-handoff.md` (`ai/reports/phase-6/`) | Living source-of-truth, updated after every task (like Phase 5) |
| `docs/modules/content-modeling.md` | Developer reference (created at C4) |

New Skill Map rows to add:

```
| Content Types / Fields / Entries | content-modeling-skill.md + field-types-skill.md |
| Taxonomies / Terms               | taxonomy-skill.md |
| Dynamic Builder Blocks           | dynamic-blocks-skill.md + page-builder-skill.md |
```

---

## 9. Approval Gates (schema-heavy phase)

Per AGENTS.md §9, write plan → wait → implement. The gates that **must** be
explicitly approved before code:

1. **A0** — storage model (Hybrid §3.1) and the `page_blocks` morph vs JSON-body
   decision (§3.2). This is the single most important sign-off.
2. **A3** — migration altering existing `page_blocks` (if morph chosen) + backfill.
3. Each new table at its task (B1, B2, B4, B5, B6, B7) — new tables are schema
   changes; batch-approve the set at A0 if the owner prefers, or per task.
4. Any `route_base` reserved-prefix logic that touches `routes/web.php` ordering.
5. Confirm **zero new Composer/NPM packages** (expected; flag if that changes).

No protected module (Products, Bookings, Page Sections, Global Settings) is
renamed, deleted, or rebuilt at any point.

---

## 10. Risks & Mitigations

| Risk | Likelihood | Mitigation |
|---|---|---|
| Field-value queries get slow at scale | Med | Hybrid sidecar (§3.1) + indexes; paginate archives from day one |
| `page_blocks` morph breaks page builder | Med | Backfill migration + full Phase 5 regression suite must stay green in C3; JSON-body fallback (§3.2-B) if owner prefers zero risk |
| Route collisions (new `route_base` vs existing) | Med | Reserved-prefix guard in Content Type FormRequest (§3.4) |
| Dynamic field output XSS | Med | Reuse `InlineContentSanitizer`; richtext fields sanitized on save; `content_field` render escapes by type |
| Scope creep (flexible content, REST API) | High | Explicitly out of scope (§1.2); repeater covers most needs; API → Phase 8 |
| N+1 on entries + relations + terms | Med | Eager-load rules documented in handoff; C2 performance gate blocks merge |

---

## 11. Suggested Sequencing & Milestones

Realistic order (engine-first, UI-minimal):

1. **Milestone 1 — Decks cleared & decided:** A0 → A1 → A2 → A3 → A4.
   *Exit:* architecture locked, debt gone, field catalog scaffolded, suite green.
2. **Milestone 2 — Authoring works:** B1 → B2 → B3 → B4.
   *Exit:* owner can define a "Hotel" type with fields and create entries in admin.
3. **Milestone 3 — Organize & relate:** B5 → B6 → B7 → B8.
   *Exit:* entries are filterable, taxonomized, related, revisioned, schedulable.
4. **Milestone 4 — Public + visual:** B9 → B10 → B11 → B12 → B13.
   *Exit:* entries render on the frontend and can be placed via the page builder.
5. **Milestone 5 — Ship:** C1 → C2 → C3 → C4. *Exit:* release gate PASS.

Each milestone is independently demoable. If time is tight, Milestones 1–2 alone
already deliver real value (admin-defined content + entries), with public
rendering as a fast follow.

---

## 12. Carry-over Technical Debt to Clear This Phase

From Phase 5 handoff §10 / §12 — fold into Stage A so the deck is clean:

| # | Item | Where | Action |
|---|---|---|---|
| TD-03 | Child theme support | `ThemeService` | Re-evaluate; defer to Phase 7 if not needed for content templates |
| TD-04 | Widget text not sanitized | `components/widgets/text.blade.php` | Wrap through `InlineContentSanitizer::richtext()` (A1) |
| TD-05 | DB query in Blade | `frontend/blocks/contact-form.blade.php:3` | Move `FormDefinition::find()` to `PageRenderData` (A1) |
| — | Patterns API unpaginated | `BuilderPatternController@index` | `paginate(20)` (A2) |
| — | Inline validate in controller | `PageBlockController` | Extract to FormRequest (A2) |

---

## 13. Definition of Done (Phase 6 Release Gate)

Phase 6 is COMPLETE only when **all** of:

- [ ] Owner can create a content type, fields, entries, taxonomies, and relations entirely from admin — zero code.
- [ ] At least one real Bintan content type shipped end-to-end as proof (suggest **`review`** to retire the static testimonials fallback noted in Phase 5).
- [ ] Public archive + single render for at least one public type, with SEO meta.
- [ ] `content_query` + `content_field` blocks usable inside the Phase 5 builder.
- [ ] Phase 4 revisions / scheduling / audit log work on content entries.
- [ ] PHPStan level 5 / 0 errors / no baseline.
- [ ] Full test suite green (Phase 5 baseline 627 tests must not regress; new tests added for every new module).
- [ ] All public routes ≤300ms warm, no N+1, archives paginated, sidecar indexed.
- [ ] All carry-over debt (§12) cleared.
- [ ] Docs: `content-modeling.md`, field-type authoring guide, CHANGELOG, AGENTS.md §4 + Claude.md updated, Phase 7 prep notes.

---

## 14. First Concrete Step

Approve (or amend) **A0 decisions in §3** — specifically:
1. Hybrid storage (§3.1): yes / no.
2. Entry body: **A** polymorphic `page_blocks` morph, or **B** JSON `body` column.
3. Confirm zero new packages.

Once A0 is signed off, I create `ai/reports/phase-6/phase-6-progress-handoff.md`, branch
`feature/phase-6-a1-debt-clearing` off `develop`, and start Stage A — debt first,
schema decisions locked, suite green before any new table. All task reports go into
`ai/reports/phase-6/[task-id]-[nama].md`.

**Kickoff prompt** saved at `ai/prompts/phase-6-kickoff.md` — paste at session start.

---

*End of Phase 6 Grand Plan. This is the constitution; task detail lives in the
Phase 6 handoff and skill files once A0 is approved.*
