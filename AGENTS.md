# Bintan Prestige CMS — AI Agent Master Rules

This file is the master rule for all AI agents working on this project,
including Claude, Codex, Qwen, Gemini, Cursor, and any local AI assistant.

Read this file first. Then read **only** the skill files relevant to your task.
Do not read all skill files at once — use the Skill Map in Section 3.

---

## 1. Project Identity & Goal

**Project:** Bintan Prestige CMS
**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Architecture:** Laravel MVC with Service Layer and Support Classes

**Ultimate Goal:**
Build a WordPress-like CMS where the complete website — pages, navigation,
layout, content blocks, and appearance — is fully managed from one admin
dashboard. No hardcoded frontend content. Backend controls everything.

**Current Phase:** Phase 6 — Flexible Content Modeling **COMPLETE** — 2026-07-07.
Phase 5 (Visual Page Builder) complete — 2026-06-23.
Phase 4 (Plugin & Module System) complete at v4.0.0 — 2026-06-21.

---

## 2. Authority Order

When instructions conflict, follow this order:

1. Owner's latest explicit instruction
2. This AGENTS.md
3. `ai/guidelines/*`
4. `ai/skills/*`
5. `docs/*`
6. Existing code structure
7. Laravel official conventions

Never ignore existing working features.

---

## 3. Skill Map — Load Only What the Task Needs

Before working, read this file + the skill(s) below that match your task.
**Do not load all skills.** One or two files per task is enough.

| Task Type | Read These Skill Files |
|-----------|----------------------|
| Frontend / Blade / UI | `frontend-design-skill.md` (consolidated) |
| Backend / Controller / Service | `backend-skill.md` |
| Database / Migration / Model | `database-architecture-skill.md` |
| Security fix or audit | `security-skill.md` |
| SEO / Schema / Sitemap | `seo-ai-discovery-skill.md` |
| Page Builder / Blocks | `page-builder-skill.md` + `cms-architect-skill.md` |
| Visual Builder / Phase 5 | `phase5-visual-builder-skill.md` + `page-builder-skill.md` |
| Pages module (About, Contact, etc.) | `page-module-skill.md` + `backend-skill.md` |
| Menu / Navigation | `menu-manager-skill.md` + `backend-skill.md` |
| Media Library | `media-library-skill.md` |
| **Any image/file upload field (admin)** | `media-library-skill.md` — see its ⭐ Canonical Image Input Standard |
| Performance / Cache | `performance-skill.md` |
| Testing / QA | `testing-qa-skill.md` |
| Documentation | `documentation-skill.md` |
| Design / Brand / Tokens | `DESIGN-SYSTEM.md` (root) |
| Admin Dashboard | `admin-dashboard-skill.md` |
| Product / Tour / Activity | `product-management-skill.md` |
| Travel Business Logic | `travel-business-skill.md` |
| Component Library | `COMPONENT-LIBRARY.md` |
| Content Types / Field Groups / Fields / Entries (Phase 6) | `content-modeling-skill.md` + `field-types-skill.md` |
| **Multi-language / locale / translation (Phase 7)** | `i18n-skill.md` |

> Skill files live in: `ai/skills/`
> Guidelines live in: `ai/guidelines/`
> Detail rules are in those files. This file is the constitution, not the manual.

---

## 4. CMS Architecture Phases

**Phase 1 — COMPLETE ✅**
Backend data syncs to frontend.
Products, categories, destinations, bookings, page sections, global settings (13 modules), user roles, site assets.

**Phase 2 — COMPLETE ✅**
Full website builder from admin dashboard. Goal: WordPress-like control.
- Generic Pages module (About, Contact, Privacy, etc.)
- Block editor (Hero, Text, Image, Gallery, CTA, Products, FAQ blocks)
- Menu manager (header, footer, mobile navigation)
- Media library (central asset manager)
- Template system (page templates selectable from admin)
- Preview/draft mode

**Phase 3 — COMPLETE ✅**
Theme system — design tokens, token editor, Google Fonts integration,
theme export/import (ZIP), extended token inheritance.

**Phase 4 — COMPLETE ✅** (v4.0.0 — 2026-06-21)
Plugin & Module System — plugin registry/lifecycle, hook & filter event system,
content revision history, content scheduling, admin audit log, SEO manager
(sitemap/robots.txt/redirects/noindex), contact form builder plugin,
analytics dashboard plugin, plugin security & sandboxing.
- Test suite: 596 tests / 2765 assertions / 0 failures
- PHPStan: level 5 / 0 errors / no ignores / no baseline

**Phase 5 — COMPLETE ✅** (feature/phase-5-stage-b-visual-builder — 2026-06-23)
Visual Page Builder & Foundation Hardening.

- **Stage A — Foundation Hardening:** admin UX refactor (sidebar groups, shared components),
  block library expansion (8 new block types → 19 total), backend readiness audit,
  N+1 resolution + eager loading, frontend design token polish.

- **Stage B — Visual Page Builder:** full-viewport drag-and-drop builder
  (`GET /admin/pages/{page}/builder`), live iframe preview with 800ms debounce,
  schema-driven block settings panel (all 19 types), inline editing with server-side
  HTML sanitization (`InlineContentSanitizer`), reusable pattern library
  (`builder_patterns`), page template library (`builder_templates`), responsive preview
  controls (Desktop/Tablet/Mobile) with per-block hide-on-device support.

- **Stage C — Release Audit:** three automated audit stages + final documentation.
  - **C1 Static Analysis & Code Quality:** PHPStan level 5 (0 errors), full test suite
    (627 tests / 3206 assertions / 0 failures), dead code scan, `{!! !!}` safety audit.
  - **C2 Performance Audit:** all public routes ≤300ms warm-run, no N+1 queries,
    all Phase 5 DB indexes verified, asset bundle size checked.
  - **C3 Functional Smoke Test:** HTTP route checks (9 public + draft 404 guard +
    admin auth guard), block registry + view files verified (19/19), sanitizers tested
    functionally, Phase 5 migrations confirmed Ran, Phase 1–4 regression confirmed intact.
    10 manual QA items documented (require browser + admin login).
  - **C4 Architecture Documentation:** `docs/modules/visual-builder.md` created
    (developer reference), `docs/visual-builder-structure.md` updated, Phase 5 CHANGELOG
    entry added, Phase 6 preparation notes finalized.

- Test suite: 627 tests / 3206 assertions / 0 failures (+31 tests / +441 assertions over Phase 4)
- PHPStan: level 5 / 0 errors / no ignores / no baseline
- Release Gate: PASS (pending 10 manual QA items + production env pre-flight)

**Phase 6 — COMPLETE ✅** (branch: `feature/phase-6-a1-debt-clearing` — 2026-07-07)
Flexible Content Modeling — custom content types, field groups, fields, and entries from admin.

**Stage A — Foundation & Debt Clearing — COMPLETE ✅**
- A0: Architecture decision record (Option A polymorphic page_blocks + hybrid JSON storage).
- A1: Technical debt TD-04 (widget text sanitization) + TD-05 (DB query in Blade contact form) + PHPStan baseline fixes (TD-06) + test regression fix (TD-07) + admin copy consistency (TD-08).
- A2: Patterns API pagination + PageBlock FormRequest extraction.
- A3: Polymorphic `page_blocks` (dual-rail morph: `blockable_type` / `blockable_id`) — schema approved, migrated.
- A4: `config/field-types.php` catalog (18 types) + `FieldTypeRegistry` static wrapper.

**Stage B — Build the Engine — COMPLETE ✅ (B1–B14, M2–M4 done)**
- B1 ✅: `content_types` table + full admin CRUD (slug auto-gen, reserved-prefix guard, soft delete).
- B2 ✅: `field_groups` + `fields` tables + nested CRUD (compound unique key per scope, is_filterable inherits catalog, dual ownership check, reorder endpoints).
- B3 ✅: Field rendering engine — `<x-admin.field-input>` Blade component dispatches to 18 type partials. Media types use Alpine.js + hidden inputs.
- B4 ✅: `content_entries` table (FK RESTRICT, varchar status not ENUM, compound unique slug per type, author SET NULL, JSON data + seo) + admin CRUD. ContentType forceDelete guarded. B2+B3+B4 form integration complete.
- B5 ✅: `FieldValidationResolver` (no schema) — resolves dynamic `data.*` rules per ContentType: placeholder substitution from `$field->settings`, `is_required` toggle, special handling for gallery/checkbox/relationship (wildcard `.*` rules) + datetime normalisation. Integrated into Store + Update FormRequests.
- B6 ✅: `content_entry_index` table (CASCADE, no timestamps, 3 value columns) + `ContentEntryIndexService` (field projection by cast type) + `ContentEntryObserver` (saved/restored hooks). Filterable fields auto-projected on every entry save; stale rows cleaned up.
- B7 ✅: `taxonomies` + `terms` + `content_entry_term` pivot. `Taxonomy` (softDeletes, appliesToType, content_type_ids JSON restriction) + `Term` (softDeletes, self-referential parent, ordered). `TaxonomyController` + `TermController` full CRUD. ContentEntryController syncs terms on save. Term picker partial in entry form (flat tag chips + hierarchical tree).
- B8 ✅: `content_entry_relations` table (source/target FK CASCADE, field_key, sort_order; forward + reverse indexes; no timestamps). `ContentEntryRelation` model + `ContentEntryRelationService` projects relationship-type field values from data JSON on save (FK-safe filter to existing entries, no self-links, order preserved). Wired into `ContentEntryObserver`. `ContentEntry::relatedEntries()` (forward) + `relatingEntries()` (reverse).
- B9 ✅: Phase 4 reuse wiring. NEW `content_entry_revisions` table (isolated CASCADE, flexible `snapshot` JSON) + `ContentEntryRevisionService` (20-keep prune, reversible restore) + revision history UI. Audit log via `ContentEntryObserver` created/updated/deleted (reuse polymorphic `AuditLog`). `PublishScheduledContentEntries` command (reuse status/published_at) scheduled everyMinute. `ContentEntry::seoMeta()` fallback resolver (reuse `seo` JSON).
- B10 ✅: Entry body via visual builder on the A3 morph rail. `ContentEntry::blocks()` MorphMany (page_id NULL) + observer `forceDeleted()` cleanup. `ContentEntryBuilderController` (show/saveTree/previewPayload) reuses BuilderTreeSanitizer + PageBlockService + revision snapshot. Generic Phase 5 builder partials reused unchanged; entry builder view + topbar + standalone preview shell added. "Edit Body in Builder" button on entry edit for `editor`-supported types. Page builder untouched (dual-rail).
- B11 ✅: Public frontend routing via `Route::fallback()` (always lowest priority — never shadows explicit/admin routes; route_base is unique + RESERVED_PREFIXES-guarded). `Frontend\ContentEntryController` resolve→archive (`/{route_base}`, needs has_archive) / single (`/{route_base}/{slug}`). Published-only (draft/future → 404). Single renders builder block body for `editor` types. `ContentEntry::publicUrl()` + archive/single views.
- B12 ✅: Template resolution + structured data. `ContentEntryTemplateRegistry` maps per-entry `template` string → render container + schema type (default/contained → Article, full-width → WebPage; unknown → default). Single view applies resolved container width + emits JSON-LD (Article/WebPage) with headline/description/url/dates/author.
- B13 ✅: Builder bridge — `content_query` block. Queries + renders a list of published entries of a chosen public type (heading/orderby/columns/limit/show_excerpt). Registered in config/blocks.php + PageBlockService. `ContentQueryResolver` (shared, resolve-before-Blade) used by PageRenderData (pages) + Frontend\ContentEntryController (entry bodies). `content_types` option source added to both builders (page builder otherwise untouched).
- B14 ✅: Builder bridge — `content_field` block. Displays a single field value from the current entry (entry bodies) or a specific published+public entry by id. `ContentFieldResolver` (shared) formats by type — richtext sanitized, url/email href guarded against unsafe schemes. Registered in config/blocks.php + PageBlockService; resolved via PageRenderData (pages) + resolveBridgeBlocks (entry bodies).

**Stage B COMPLETE (B1–B14). Milestone M4 done.**

**Stage C — Release Audit — COMPLETE ✅ (C1–C4)**
- C1 ✅: Static analysis & code quality. PHPStan level 5 / 0 errors; suite green; `{!! !!}`/debug/TODO scans clean. Security fix: single-entry JSON-LD hardened with `JSON_HEX_*` against `</script>` breakout (+ regression test). Flagged pre-existing `StructuredDataBuilder` (Phase 4) for follow-up.
- C2 ✅: Performance. Fixed two N+1s (archive `contentType` eager-load; `content_field` field-lookup memoization) → public routes O(1); hot-path indexes verified; no new asset bundle.
- C3 ✅: Functional smoke test — schema (10 tables + morph), block registry ↔ view files, admin guards, public archive/single/draft, scheduler command.
- C4 ✅: Documentation — `docs/modules/content-modeling.md`, `ai/skills/content-modeling-skill.md`, CHANGELOG Phase 6 entry, handoff finalized.

**PHASE 6 COMPLETE.** Test suite: **846/846 pass** | PHPStan level 5: 0 errors (2026-07-08).
Release gate: **PASS** (pending owner production pre-flight). App timezone: `Asia/Jakarta` (WIB).
Grand plan + progress: `ai/reports/phase-6/phase-6-progress-handoff.md`

**Post-release note (2026-07-08):** dev DB wiped by cached-config `migrate:fresh`,
restored from MySQL binlog (see `ai/reports/phase-6/post-release-db-recovery.md`).
Owner UI scheme is now **Navy + gold** (DB-stored: `site_settings` brand_colors +
`admin_dashboard_appearances` preset `navy-light`). Guards: never `config:cache`
in dev; `tests/TestCase.php` refuses non-sqlite test DB.

**Phase 6.1 — COMPLETE ✅ (2026-07-08, interim before Phase 7)**
Dashboard & Media UX: sidebar accordion + sticky fix (`overflow-x: clip`),
media collections (`media.collection` + `config/media.php`), picker modal
upload + theme parity, `<x-admin.media-image-field>` component, category image
(`categories.image` + frontend landing parity), destinations → picker,
`navy-light` as first-class admin preset (config/admin_palettes.php).
Suite **850/850** | PHPStan level 5: 0 errors.
Plan + handoff + staged follow-ups: `ai/reports/phase-6.1/`.

**Phase 7 — COMPLETE ✅ (branch: `feature/phase-7-a1-foundation` — 2026-07-10)**
Internationalization — multi-language content for Bintan tourism market.
Ship configuration: `en` (default, unprefixed) + `id` (`/id/…`). Everything localizes
end-to-end from one admin: chrome (nav/CTA/footer/SEO defaults), pages,
content entries, catalog (products/categories/destinations), menus, taxonomy
terms, plus SEO surfaces (hreflang + x-default, per-locale sitemap with
xhtml:link, canonical, OG `og:locale`, JSON-LD `inLanguage`, localized 404).

**Stage A — Foundation, decisions & debt clearing — COMPLETE ✅**
- A0 Architecture decisions: `id`+`en` (default `en` unprefixed), URL prefix
  `/{code}` non-default, documents = row-per-locale, attributes = polymorphic
  `translations` sidecar, menus = shared structure + sidecar labels, untranslated
  documents hide/404, **zero new packages**.
- A1: `StructuredDataBuilder` JSON-LD hex-escape flags (already at 74f1027);
  TD-03 child-theme CLOSED (theme tokens + templates cover the need).
- A2: `config/locales.php`, `SetLocale` middleware, per-locale route groups
  (non-default first so prefixed `Route::fallback` wins), reserved-prefix guard
  gains locale codes, lang scaffolding, header locale switcher.

**Stage B — Build the engine — COMPLETE ✅ (B1–B10)**
- B1: `translations` polymorphic sidecar + `App\Models\Concerns\Translatable`
  trait (locale resolve + base-column fallback + N+1-guard eager-load scope).
- B2: Global chrome localized via `SiteSetting.value` sidecar + dedicated admin
  translation panel (allow-list) — 8 per-group forms untouched.
- B3: `PageSection` localized (5 copy columns via sidecar; media/layout shared;
  locale-aware accessors → home + product listing sections auto-localize).
- B4 ⚠️: `pages` row-per-locale (mysqldump + ALTER: + locale + translation_group_id;
  unique slug → (slug, locale)); admin "Translate to…" + per-locale panel; switcher
  targets published counterparts.
- B5 ⚠️: `content_entries` row-per-locale (same ALTER pattern); archive+single
  filter by locale; `content_query` filters by locale.
- B6: Catalog localized — `Product` (10 copy fields), `Category`, `Destination`
  via sidecar + accessors; frontend queries eager-load translations.
- B7: `MenuItem.label` + `Term.name/description` via sidecar (menu tree cache
  keyed per locale).
- B8: Admin translation-status badges (published/draft/missing) + locale filter
  on Pages + Content Entries list views (N+1-fenced).
- B9: `content_field` block resolves group sibling in current locale (attribute
  fallback); admin builder preview sets `app locale = record.locale`.
- B10: SEO i18n — dynamic `<html lang>`; `partials/site-hreflang.blade.php` with
  `x-default`; OG `og:locale`; JSON-LD `inLanguage`; sitemap grouped by
  translation_group_id with `xhtml:link` alternates; localized 404 view.

**Stage C — Release audit — COMPLETE ✅ (C1–C4)**
- C1: PHPStan L5/0 errors; every `{!! !!}` classified as safe; zero raw-echo of
  translation-sourced values. Added `C1EscapeAuditTest` as regression fence
  (malicious sidecar values render escaped in chrome + hero + JSON-LD).
- C2: 10 performance fences — 6 warm-latency budgets ≤300ms, product-listing
  translation query count SCALES FLAT with catalog size (6→24 products; N+1
  would multiply), home ≤6, switcher/hreflang no fanout, sitemap ≤1 pages query.
- C3: 13 functional smoke fences — locale route matrix (default + prefixed),
  document fallback (untranslated/draft 404 in that locale only), attribute
  fallback, admin guards on all Phase 7 endpoints, legacy-URL parity
  (route('pages.show', $slug) byte-identical).
- C4: `docs/modules/internationalization.md`, `ai/skills/i18n-skill.md`,
  CHANGELOG, AGENTS/CLAUDE.md sync, Phase 8 prep notes.

Grand plan + progress: `ai/reports/phase-7/phase-7-progress-handoff.md`

**Phase 8 — FUTURE**
Operational Maturity — backup/restore, import/export, monitoring dashboard.

---

## 5. Protected Existing Modules

Do not rebuild these from zero. Extend them safely only:

- Products, Categories, Destinations (with soft delete + restore)
- Product: Prices, Images, Features, FAQs, Itineraries, Notes, Highlights
- Bookings + Booking Items
- Page Sections + Page Section Media
- General FAQs
- Global Settings (13 modules)
- Site Assets, Site Settings
- User Management (is_admin, admin_role, admin_status)

**Phase 6 modules (protect after B1–B4 ship):**
- Content Types (`content_types` table) — CRUD via `ContentTypeController`
- Field Groups + Fields (`field_groups`, `fields` tables) — CRUD via `FieldGroupController`, `FieldController`
- Content Entries (`content_entries` table) — CRUD via `ContentEntryController`
- Field Type Catalog (`config/field-types.php` + `FieldTypeRegistry`) — extend only, never remove existing types

---

## 6. CMS Module Pattern

Every new module must follow this sequence:

1. Migration (new table or column)
2. Model + relationships
3. Form Request (validation)
4. Controller — Admin CRUD
5. Service (business logic, when needed)
6. Admin Blade views
7. Frontend rendering (Blade component or section)
8. SEO handling (meta, schema)
9. Security check (auth, policy, validation)
10. Documentation update
11. Tests

---

## 7. Laravel MVC Rules

- Controllers handle request flow only
- Models handle relationships, casts, scopes, domain data
- Form Requests handle validation (when large or reusable)
- Policies handle authorization
- Views render prepared data only — no queries in Blade
- Services handle reusable business logic
- JavaScript must not process heavy business data
- Support classes prepare display-ready data for Blade

---

## 8. Critical Safety Rules

**Never do these without explicit approval:**
- Delete existing features or UI sections
- Rewrite the full project or any full module
- Change database schema or existing migrations
- Rename routes, controllers, models, tables, or columns
- Replace existing backend logic without analysis
- Remove existing documentation
- Install new packages
- Change authentication or authorization logic

**Image / file inputs — PATENT RULE (mandatory, no exceptions but favicon):**
- **Every** admin image/file field goes through the Media Library via
  `<x-admin.media-image-field>` (single) or `<x-admin.media-gallery-field>`
  (multiple). A raw `<input type="file">` in an admin form is not allowed.
- Store the returned **path string** (`string(500)` nullable) — no `media_id` FK,
  no schema change. FormRequest rule = `string`, never `image|mimes`.
- On replace/delete, only remove the module's own legacy files (`products/`,
  `pages/`, `site-assets/`, `page-sections/`); **never** delete a `media/…` path.
- Register the new column in `MediaService::DIRECT_REFERENCES`.
- Full pattern + checklist: `ai/skills/media-library-skill.md` (⭐ Canonical
  Image Input Standard). Only exception: favicon (raw `.ico`/`.svg` upload).

**Always do these:**
- Inspect existing files before editing
- List files that will change before changing them
- Hardcode nothing — use CMS data
- Work section by section
- Keep commits focused on one concern

**Database — non-negotiable (formalized after the Phase 6 §16 dev-DB wipe incident):**
- **NEVER** run `php artisan migrate:fresh` or `migrate:reset` against a MySQL DB —
  our test suite uses sqlite in-memory; there is no legitimate reason to wipe
  MySQL. The dev DB carries recovery data that cannot be reconstructed.
- **NEVER** run `php artisan config:cache` in dev. `.env` values leak into a
  stale cache — this is the exact mechanism that triggered the Phase 6 wipe.
  Use `php artisan optimize:clear` instead.
- **ALWAYS** take a `mysqldump` before every ALTER on an existing table
  (including `DROP INDEX`, `RENAME COLUMN`, `ALTER … DEFAULT`).
  Naming convention: `storage/app/db-backups/pre-{task-id}-{YYYYMMDD-HHMMSS}.sql`.
  This is a hard rule, not a suggestion — even for "trivial" ALTERs.
- **ALL** migrations must be additive + reversible. Test `migrate:rollback --step=1`
  on the branch before merging. If a migration drops a column, split it into a
  separate migration so the rollback point is clean.
- MySQL `log_bin` must stay ON (binlog recovery saved us in Phase 6). Retention
  ≥14 days. Never delete files in `storage/app/binlog-recovery/`.
- Every deploy (even hotfix) preceded by a `mysqldump`. Automate this in Phase 8.

**Environment isolation:**
- `.env`, `.env.testing`, `.env.production` MUST have different `DB_DATABASE`
  values. `tests/TestCase.php` refuses non-sqlite test DBs — never loosen that
  guard.
- `.env*` files never commit (only `.env.example`). Verify `.gitignore` before
  each session.
- Deploy checklist: `optimize:clear` → `mysqldump` → `migrate --pretend` (review
  SQL) → `migrate --force`. Skipping any step is a bug.

**Every task must leave a regression fence:**
- Fences over benchmarks (Phase 7 C1/C2/C3 pattern). One-shot smoke tests age
  badly; every new feature ships with named tests that fail loudly when the
  invariant regresses.
- New N+1-sensitive path → test that scales the fixture and asserts query count
  is flat (see `C2PerformanceAuditTest::test_products_index_translation_queries_do_not_grow_with_product_count`).
- New surface with escaping → test that malicious input renders escaped (see
  `C1EscapeAuditTest`).
- New behavioural rule → smoke test that fails when the rule is violated (see
  `C3FunctionalSmokeTest`).

---

## 9. Approval Required Before Acting

Ask for explicit approval before:

- Any database schema change (new table, new column, index)
- Editing existing migrations
- Renaming anything (route, controller, model, view, column)
- Removing any feature, page, or UI section
- Installing new Composer or NPM packages
- Changing auth or security-sensitive logic
- Moving or deleting existing documentation
- **Any production deploy or `migrate --force` on the dev DB after an ALTER**
- **Any change to `.env*` files** (production or otherwise)

Write the plan first. Wait for approval. Then implement. The owner must write
"approved" explicitly in the conversation before the task starts — a thumbs-up
emoji or "ok" is not enough for schema/security/package changes.

**Zero new packages by default.** Every Composer/NPM addition needs a specific
justification ("no clean way to build X in ≤50 LOC") AND a 24-hour review window.
Phase 7 shipped a full multi-language stack with zero new packages — the same
bar applies going forward.

---

## 10. Required Workflow Before Every Edit

1. Read AGENTS.md (this file) ← already done
2. Read the relevant skill file(s) from the Skill Map above
3. Inspect existing files affected by the task
4. List files that will change
5. Describe the plan briefly
6. Confirm with owner if schema or breaking change is involved
7. Implement in small steps
8. Report using the format below

---

## 11. Report Format

Use this after every completed task. Keep it short — use the template:

```
## Task: [name]

### Changed
- `path/to/file.php` — what changed

### Impact
- DB: none | migration added: [name]
- Routes: none | added: [route]
- Frontend: none | [section] now shows [X]
- Security: none | validated via [FormRequest/Policy]

### Rollback
`git revert [hash]` or [manual step]

### Next
[recommended next task]
```

---

## 12. Git Workflow

- Check current branch before starting
- Create a feature branch for each task
- Keep commits focused and small
- Do not mix unrelated changes in one commit
- Always document rollback steps

**Branch naming:**
- `feature/[feature-name]`
- `docs/[doc-update-name]`
- `fix/[issue-description]`
- `refactor/[area-name]`

---

## 13. Data-Safety Runbook (mandatory quick-reference)

> Full playbooks live in `docs/runbooks/` (`db-backup.md`, `rollback.md`,
> `deploy-checklist.md`). Read the relevant one BEFORE the crisis.

**Before ANY task that touches an existing table:**

```bash
# 1. Backup FIRST — enforced naming convention, size + header verified,
#    partial dumps auto-deleted on failure. Non-zero exit on error so CI /
#    deploy scripts can fail loudly. See docs/runbooks/db-backup.md.
php artisan db:backup <task-id> --purpose="short desc of the ALTER"

# 2. Review the SQL Laravel will run BEFORE running it
php artisan migrate --pretend

# 3. Only then apply
php artisan migrate --force

# 4. Verify rollback path works on the same branch (before merging)
php artisan migrate:rollback --step=1
php artisan migrate --force
```

**If a migration fails mid-run:**

```bash
# a. Do NOT panic-run migrate:fresh — that will complete the wipe.
php artisan migrate:status                     # see which ran
php artisan migrate:rollback --step=<n>        # roll back the failed one
# b. If down() itself fails (FK/index conflicts):
mysql bintan_prestige < storage/app/db-backups/pre-<task>-<ts>.sql
```

**If data gets corrupted or accidentally deleted:**

1. **Do not run any writes** to bintan_prestige. Screenshot the error.
2. Restore into a SEPARATE DB first — never into the live DB:
   ```bash
   mysql -e "CREATE DATABASE bintan_prestige_recovery"
   mysql bintan_prestige_recovery < storage/app/db-backups/<newest-backup>.sql
   ```
3. Selectively import back what you need (mirror Phase 6 §16 recovery pattern).
4. Only after verification, swap DBs or replay binlogs.

**Guards that must NEVER be loosened:**

- `tests/TestCase.php` refuses non-sqlite test DBs.
- `.gitignore` excludes `.env*` (except `.env.example`).
- MySQL `log_bin` stays ON with ≥14 day retention.
- `storage/app/binlog-recovery/` files are never deleted.
- `storage/app/db-backups/` is gitignored but kept on disk indefinitely for
  any backup < 90 days old.

**Every feature branch must include, in its final commit:**

- A regression fence test (see §8 "Every task must leave a regression fence").
- A one-line rollback command in the report `### Rollback` section.

---

## 14. Final Principle

Improve this project safely, incrementally, and transparently.

**The goal is to build a complete WordPress-like CMS where any part of
the website — pages, blocks, navigation, layout, content, and appearance —
can be created and managed entirely from the admin dashboard.**

Build toward this goal section by section, without breaking what already works.
Every task should leave the project in a better state than it was found.