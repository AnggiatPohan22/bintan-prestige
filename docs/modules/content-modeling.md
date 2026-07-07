# Module: Flexible Content Modeling (Phase 6)

> Developer reference for the custom content-type engine: content types, field
> groups + fields, entries, taxonomies, relationships, revisions, public routing,
> and the builder bridge. Built in Phase 6 (B1–B14). Full step reports live in
> `ai/reports/phase-6/`.

---

## 1. What it does

Lets an admin define arbitrary **content types** (e.g. Hotel, Tour, Article) with
custom **fields**, create **entries**, classify them with **taxonomies**, **relate**
entries to each other, version them with **revisions**, compose a block **body** with
the Phase 5 visual builder, and publish them on the public site — all without code.

WordPress-like: the whole content structure is managed from the admin dashboard.

---

## 2. Data model

```
content_types ──1:N── field_groups ──1:N── fields
      │                                        (type from config/field-types.php)
      │
      └──1:N── content_entries ──┬── data (JSON: field values, keyed by field.key)
                                  ├── seo (JSON)
                                  ├──1:N── content_entry_index      (B6 sidecar projection)
                                  ├──1:N── content_entry_revisions  (B9 snapshots)
                                  ├──M:N── terms (via content_entry_term)          (B7)
                                  ├──self M:N── content_entries (content_entry_relations) (B8)
                                  └──morph 1:N── page_blocks (blockable_*, page_id NULL) (B10)

taxonomies ──1:N── terms (self-referential parent_id for hierarchy)
```

### Key tables

| Table | Purpose | Notes |
|-------|---------|-------|
| `content_types` | type definition | slug, labels, `supports` JSON, `route_base` (unique), soft delete |
| `field_groups` | groups of fields per type | compound-unique `key` per type |
| `fields` | field definitions | `type` ∈ `config/field-types.php`; `is_filterable`, `settings` JSON |
| `content_entries` | the content | `data`/`seo` JSON; `status` varchar; FK RESTRICT to type; soft delete |
| `content_entry_index` | B6 filter sidecar | projection of filterable fields; CASCADE; no timestamps |
| `taxonomies` / `terms` | B7 classification | `content_type_ids` JSON restriction; hierarchical terms |
| `content_entry_term` | B7 pivot | composite PK, CASCADE both sides |
| `content_entry_relations` | B8 entry↔entry | source/target CASCADE, `field_key`, `sort_order` |
| `content_entry_revisions` | B9 versions | flexible `snapshot` JSON, 20-keep prune |

---

## 3. Storage strategy — hybrid (A0 §3.1)

- **Source of truth:** `content_entries.data` JSON (mirrors `page_blocks.data`).
- **Query sidecar:** fields flagged `is_filterable` are projected into
  `content_entry_index` on every save (`ContentEntryIndexService` via
  `ContentEntryObserver`) → filtering/sorting hits indexed columns, not JSON scans.
- Rationale: consistent with the existing JSON-in-`data` pattern, no EAV join
  explosion, still indexable.

## 4. Entry body — dual-rail morph (A0 §3.2, A3, B10)

`page_blocks` gained `blockable_type` / `blockable_id` (nullable) at A3:
- **Pages** keep `page_id` + `hasMany` — the Phase 5 builder is **untouched**.
- **Entries** with `editor` support use the morph (`page_id` NULL) via
  `ContentEntry::blocks()` (MorphMany).

The same visual builder renders on the entry via `ContentEntryBuilderController`
(show / saveTree / previewPayload / updateStatus), reusing `BuilderTreeSanitizer` +
`PageBlockService`. Hard delete cleans up morph blocks in `ContentEntryObserver::forceDeleted()`.

---

## 5. Services (`app/Support/`)

| Class | Role |
|-------|------|
| `FieldTypeRegistry` | static wrapper over `config/field-types.php` (18 types) |
| `FieldValidationResolver` | builds per-entry `data.*` validation rules from field defs (B5) |
| `ContentEntryIndexService` | projects filterable fields → sidecar on save (B6) |
| `ContentEntryRelationService` | projects relationship fields → relations table (B8) |
| `ContentEntryRevisionService` | snapshot + prune (20) + reversible restore (B9) |
| `ContentEntryTemplateRegistry` | entry `template` → render container + schema type (B12) |
| `ContentQueryResolver` | resolves `content_query` block → published entries (B13) |
| `ContentFieldResolver` | resolves `content_field` block → one field value, cached (B14) |

All relation-backed rendering is resolved **before Blade** (no queries in views),
following the Phase 5 `PageRenderData` pattern.

---

## 6. Public routing (B11) — route ordering

Registered as `Route::fallback()` in `routes/frontend.php` — always the lowest
priority match, so it never shadows explicit or admin routes:

- `/{route_base}` → archive (requires `has_archive`)
- `/{route_base}/{slug}` → single

Safety: `route_base` is UNIQUE + guarded by `ContentType::RESERVED_PREFIXES`.
Only **published** entries are public (draft/future → 404). `Frontend\ContentEntryController`
resolves by path segments.

## 7. Template resolution (B12)

`ContentEntryTemplateRegistry::keyFor($entry->template)` → container width +
schema type (default/contained → Article, full-width → WebPage). Single view emits
hardened JSON-LD (`JSON_HEX_*` flags, see C1).

## 8. Phase 4 reuse (B9)

- **Revisions:** isolated `content_entry_revisions` table.
- **Audit log:** `ContentEntryObserver` created/updated/deleted → polymorphic `AuditLog`.
- **Scheduling:** `content-entries:publish-scheduled` command (every minute).
- **SEO meta:** `ContentEntry::seoMeta()` fallback resolver.

## 9. Builder bridge (B13 + B14)

Two blocks connect Phase 6 back into the visual builder (usable on pages + entry bodies):
- **`content_query`** — dynamic list of published entries of a chosen type.
- **`content_field`** — one field value from the current (or a specific) entry;
  field chosen via dropdown (`entry_fields` option source); value shows in the
  builder preview before save.

Both registered in `config/blocks.php` + `PageBlockService` (defaults + rules).

---

## 10. Admin surface

| Area | Controller | Route prefix |
|------|-----------|--------------|
| Content types | `ContentTypeController` | `admin.content-types.*` |
| Field groups / fields | `FieldGroupController` / `FieldController` | nested under type |
| Entries | `ContentEntryController` | nested under type |
| Entry builder | `ContentEntryBuilderController` | `.../entries/{entry}/builder*` |
| Taxonomies / terms | `TaxonomyController` / `TermController` | `admin.taxonomies.*` |

Field input rendering: `<x-admin.field-input>` dispatches to 18 type partials (B3).

---

## 11. Safety invariants

- Type `forceDelete` blocked while entries exist (FK RESTRICT + app guard).
- Reserved-prefix guard on slug + route_base.
- Per-field validation built dynamically (B5); unknown data keys pass through.
- Relations filter to existing entries (no dangling FK, no self-links).
- content_field: richtext sanitized; url/email href validated (no `javascript:`).
- JSON-LD hex-escaped against `</script>` breakout (C1).

---

## 12. Tests

Per-step suites in `tests/Feature/Phase6/` (A3, B5–B14) + Stage C audits
(C2 performance, C3 smoke) + admin CRUD in `tests/Feature/Admin/`. As of Phase 6
close: **845 tests / 0 failures**, PHPStan level 5 / 0 errors.

Grand plan + progress: `ai/reports/phase-6/phase-6-progress-handoff.md`.
