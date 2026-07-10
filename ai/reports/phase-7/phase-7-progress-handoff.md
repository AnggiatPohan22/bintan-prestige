# Phase 7 — Progress Handoff & Living Reference
# Bintan Prestige CMS — Internationalization (i18n)

> **Living source of truth for Phase 7.** Created at A0 sign-off (2026-07-09).
> Read order every session: `AGENTS.md` → `ai/reports/phase-7/phase-7-grand-plan.md`
> (the constitution) → **this file** → task-relevant skill file(s).
> Do one `⏳ TODO` task per session; stop at `⚠️` gates for owner approval;
> run the §12 Documentation Sync Matrix; write the report in AGENTS.md §11 format
> inside `ai/reports/phase-7/`.
>
> Authority order applies (AGENTS.md §2). Schema changes require explicit owner
> approval (AGENTS.md §9). Media Library image-input standard (AGENTS.md §8)
> applies to any new image field.

---

## 0. How To Use This File

- The **grand plan** (`phase-7-grand-plan.md`) is the constitution — *what* and *why*, do not edit unless the owner asks.
- **This handoff** is the running state — locked decisions, task status, data model, sync matrix. Update it after every relevant task.
- Task reports live at `ai/reports/phase-7/[task-id]-[name].md`.

---

## 1. Phase 7 Overall Status

**Locale baseline (verified 2026-07-09):** `config/app.php` locale `en` + fallback `en`;
no `lang/` folder; **0** `__()`/`@lang`/`trans()` calls in `resources/views/frontend/`
(frontend is fully CMS-driven → sidecar approach is sufficient).

### Stage A — Foundation, Decisions & Debt Clearing
| Task | Name | Status | Gate |
|---|---|---|---|
| A0 | Architecture Decision Record | ✅ DONE (2026-07-09) | Decision set signed off |
| A1 | Carry-over debt (StructuredDataBuilder JSON-LD flags; close TD-03 child-theme) | ✅ DONE (2026-07-09) | ⚠️ edits Phase 4 code (no change needed) |
| A2 | Locale foundation (config/locales.php, SetLocale middleware, prefix route group, reserved-prefix guard, lang scaffolding, switcher chrome) | ✅ DONE (2026-07-09) | ⚠️ route registration change (shipped) |

> **C1 shipped (2026-07-10) — Static analysis + `{!! !!}` audit clean:** PHPStan
> L5 = 0 errors. 0 `dd/dump/var_dump/print_r`, 0 TODO/FIXME/HACK/XXX, 0 mass-assignment
> `->all()`, 0 hardcoded credentials. Every `{!! !!}` classified as safe (all
> translation-sourced values reach Blade via accessors → `{{ }}`; the sole richtext
> block goes through `InlineContentSanitizer::richtext`; JSON-LD uses `JSON_HEX_*`).
> Added `C1EscapeAuditTest` (3 tests) as a regression fence — malicious sidecar
> values render escaped in the chrome, home hero, AND the site-wide JSON-LD.
> Report: `ai/reports/phase-7/c1-static-analysis-code-quality.md`.

> **B10 shipped (2026-07-10) — All Stage-B tasks done:** dynamic `<html lang>`,
> `partials/site-hreflang.blade.php` (per-locale alternates + x-default; only
> published translations), OG `og:locale` (config-driven `en_US`/`id_ID`), JSON-LD
> `inLanguage` on WebPage/Article + WebSite, sitemap rewritten to group Pages +
> Content Entries by translation_group_id and emit `xhtml:link` alternates + x-default,
> localized 404 view (sets locale from URL because exception handler skips route
> middleware). Also fixed a lurking bug: two active frontend layouts had to be
> updated together. Report: `ai/reports/phase-7/b10-seo-i18n.md`.

> **B9 shipped (2026-07-10) — Milestone M4 COMPLETE:** `content_field` resolves the
> published sibling in the current locale via translation group (attribute fallback
> to referenced row when no sibling; hides when both draft — respecting A0 semantics).
> Page + entry admin preview endpoints run `app()->setLocale($record->locale)` so
> unprefixed preview URLs render translated chrome/catalog/`content_field`. Public
> routing unchanged. Report: `ai/reports/phase-7/b9-builder-bridge-locale.md`.

> **B8 shipped (2026-07-10) — M4 admin polish:** Pages + Content Entries list views
> gained a shared `translation-badges.blade.php` partial (● per active locale:
> published/draft/missing) between Status and Sort/Author, plus a locale filter
> `<select>` next to the existing status filter. Controllers accept a validated
> `locale` query param and eager-load `translationSiblings` (id, group, locale,
> status) — badges add 1 query total for the whole list (regression-tested).
> Everything is opt-in on `count(Locales::active()) > 1`. Report:
> `ai/reports/phase-7/b8-admin-translation-ux.md`.

> **B7 shipped (2026-07-10) — M4 further progress:** `MenuItem` uses `Translatable`
> (`$translatable=['label']`, A0 §3.5 Opt A: structure shared, only labels localized),
> `Term` gains name+description. `MenuService::buildMany` eager-loads translations on
> root items + children; cache is keyed per locale (`menu.tree.v2.{location}.{locale}`),
> `forget()` clears all variants + legacy key. Admin: MenuItem drawer form has per-locale
> label card (Alpine form.translations state on openEdit); Term edit form has per-locale
> name+description card. FormRequests allow-list `translations.*`. No schema change.
> Report: `ai/reports/phase-7/b7-menus-and-terms-localized.md`.

> **B6 shipped (2026-07-10) — Business milestone (M4 partial):** Product, Category,
> Destination now use `Translatable`. Product: 10 copy fields; Category/Destination:
> name+description. Locale-aware accessors → Home/Product listing+detail Blade auto-localize.
> Frontend queries add `->withTranslations()` (N+1 guard: verified 2 queries for a
> 3-product listing; default locale = 0 extra queries). Services (Category/Destination/
> Product) sync per-locale sidecar values on store/update. Admin edit views gain
> Translations cards (per active non-default locale). No schema change. Report:
> `ai/reports/phase-7/b6-catalog-localized.md`.

> **B5 shipped (2026-07-09) — Milestone M3 COMPLETE:** `content_entries` ALTER
> (owner-approved, mysqldump `storage/app/db-backups/pre-b5-entries-alter-20260709-213507.sql`,
> migrated): `+ locale + translation_group_id`, unique `(content_type_id, slug)` →
> `(content_type_id, slug, locale)`, backfill en+ULID. Model row-per-locale (boot,
> `translationIn`, `publicUrl` locale-aware). Frontend archive+single filter by
> `Locales::current()` (leading locale segment still stripped from `resolve()`), and
> `single()` emits `$localeAlternates` from published siblings for the switcher.
> Admin "Translate to…" replicates the entry into the same group as a draft, copies
> block tree (morph rail) + taxonomy pivot (idempotent). B9 partial: `content_query`
> now filters by `Locales::current()` (one line in `ContentQueryResolver`). Slug
> uniqueness per (content_type, slug, locale) in FormRequests. Report:
> `ai/reports/phase-7/b5-content-entries-row-per-locale.md`.

> **B4 shipped (2026-07-09) — Milestone M3 started:** `pages` ALTER (owner-approved,
> mysqldump `storage/app/db-backups/pre-b4-pages-alter-20260709-210206.sql`, migrated):
> `+ locale + translation_group_id`, unique `slug` → `(slug, locale)`, backfilled
> en+ULID. `Page` row-per-locale (boot group/locale, `translationIn`, `publicUrl`,
> locale-scoped `resolveRouteBinding` via `Locales::localeFromRequest()` — binding
> runs before SetLocale). `PageService::translateTo` copies blocks as a draft into the
> same group. Admin "Translate to…" action + panel + list locale badge. Frontend
> `/{locale}/pages/{slug}` (404 if untranslated), switcher lands on the published
> counterpart. Report: `ai/reports/phase-7/b4-pages-row-per-locale.md`.

> **B3 shipped (2026-07-09) — Milestone M2 COMPLETE:** `PageSection` (protected)
> now uses `Translatable` (`label/title/subtitle/description/button_text`) with
> locale-aware accessors, so `HomepageSectionData` + section Blade localize
> transparently (default locale = zero query overhead). Home + product-listing
> section queries eager-load `withTranslations()`. Admin: per-locale translation
> card on the PageSection edit form. Trait `translate()` now reads the raw base
> attribute (accessor-safe). Report: `ai/reports/phase-7/b3-page-sections-localized.md`.

> **B2 shipped (2026-07-09):** `GlobalSettingsService` payload now cached per locale
> and resolves each `value` via the B1 sidecar (fallback to base; N+1-guarded eager
> load) — header/footer/CTA/identity/SEO defaults localize with zero Blade/Support
> changes. Owner chose a **dedicated admin translation panel** (`TranslatableSettings`
> allow-list + `settings/global-assets/translations` GET/PUT) over per-field tabs;
> the 8 per-group forms are untouched. Report: `ai/reports/phase-7/b2-global-chrome-localized.md`.

> **B1 shipped (2026-07-09):** `translations` polymorphic sidecar (new table, A0 §3.4
> shape, migrated additive/reversible) + `App\Models\Concerns\Translatable` trait
> (current-locale resolution, base-column fallback, `withTranslations` eager-load N+1
> guard, soft-delete-aware cleanup). First consumer: `SiteSetting` (`$translatable=['value']`,
> extend-only, inert until B2). Trait unit-tested in isolation (12/12). Report:
> `ai/reports/phase-7/b1-translations-sidecar.md`.

> **A2 shipped (2026-07-09):** `config/locales.php` (en default + id), `app/Support/Locales.php`,
> `SetLocale` middleware, per-locale route groups in `bootstrap/app.php` (non-default
> prefixed first so its `Route::fallback` wins per prefix; default bare + canonical
> names), `ContentType::reservedPrefixes()` blocks locale codes as route_base,
> `ContentEntryController::resolve()` strips leading locale segment, `lang/en|id/frontend.php`
> scaffolding, and the header locale switcher. Suite **877/877**, PHPStan 0.
> Report: `ai/reports/phase-7/a2-locale-foundation.md`. **Test baseline reconciled = 866**
> (grand plan value confirmed; 877 = 866 + 11 core A2 tests, +2 switcher-render = 13 total).

> **A1 disposition (2026-07-09):** C1-FU `StructuredDataBuilder` JSON-LD escaping
> was **already fixed** by commit `74f1027` (2026-07-07, ancestor of HEAD) with a
> passing regression test — no code change. **TD-03 child-theme = CLOSED (won't-do)**:
> theme tokens + templates already cover per-site customization; revisit only on a
> concrete owner request. Report: `ai/reports/phase-7/a1-carryover-debt.md`.

### Stage B — Build the Engine
| Task | Name | Status | Gate |
|---|---|---|---|
| B1 | `translations` sidecar + `Translatable` trait | ✅ DONE (2026-07-09) | ⚠️ new table (shipped, migrated) |
| B2 | Global chrome localized (site_settings via sidecar; dedicated translation panel) | ✅ DONE (2026-07-09) | — |
| B3 | Page Sections localized (sidecar; home bilingual; media shared) | ✅ DONE (2026-07-09) | — |
| B4 | Pages row-per-locale (migration, backfill, locale-aware unique slug, "Translate to…") | ✅ DONE (2026-07-09) | ⚠️ ALTER on existing `pages` (approved, dumped, migrated) |
| B5 | Content Entries row-per-locale (same as B4; locale-aware controllers + index) | ✅ DONE (2026-07-09) | ⚠️ ALTER on existing `content_entries` (approved, dumped, migrated) |
| B6 | Catalog localized (Products/Categories/Destinations via sidecar) | ✅ DONE (2026-07-10) | — (business milestone shipped) |
| B7 | Menus (sidecar labels) & Terms localized | ✅ DONE (2026-07-10) | — |
| B8 | Admin translation UX pass (status column, locale filter, edit-screen switcher) | ✅ DONE (2026-07-10) | — |
| B9 | Builder bridge locale-aware (`content_query` + `content_field`; per-locale preview) | ✅ DONE (2026-07-10) | — |
| B10 | SEO i18n (hreflang + x-default, per-locale sitemap, canonical/OG/JSON-LD inLanguage, localized 404) | ✅ DONE (2026-07-10) | — |

### Stage C — Release Audit
| Task | Name | Status |
|---|---|---|
| C1 | Static analysis & code quality (PHPStan L5/0; `{!! !!}` audit incl. translated output) | ✅ DONE (2026-07-10) |
| C2 | Performance audit (localized routes ≤300ms warm; **no per-attribute translation N+1**) | ⏳ TODO |
| C3 | Functional smoke test (per-locale routes; fallback; draft-in-one-locale 404; legacy URLs unchanged) | ⏳ TODO |
| C4 | Documentation (i18n module doc + skill file + CHANGELOG + AGENTS/Claude sync + Phase 8 prep) | ⏳ TODO |

---

## 2. Milestones (demoable checkpoints)

1. **M1 — Foundation:** A0 → A1 → A2. *Exit:* switcher live, `/id` serves the site (untranslated), zero URL breakage, debt cleared.
2. **M2 — Chrome speaks:** B1 → B2 → B3. *Exit:* nav/footer/home sections bilingual; visitor can use the whole home page in Indonesian.
3. **M3 — Documents:** B4 → B5. *Exit:* pages + entries authored per locale, builder included. **B4 = demo milestone.**
4. **M4 — Catalog & polish:** B6 → B7 → B8 → B9. *Exit:* tours/categories/destinations/menus bilingual; admin shows translation status. **B6 = business milestone.**
5. **M5 — SEO + Ship:** B10 → C1 → C4. *Exit:* hreflang/sitemap correct, release gate PASS.

---

## 3. Architecture Decisions (A0) — LOCKED ✅ (owner sign-off 2026-07-09)

Owner approved all six via the A0 decision brief. Approval wording:
*"en unprefixed", "Sidecar labels (Opt A)", "Hide / 404", "Approve all three
[URL prefix / doc-vs-attribute split / zero packages]"* — 2026-07-09.

### 3.1 Locale set & default — ✅ `id` + `en`, **default `en` (unprefixed)**
- Ship Indonesian (`id`) + English (`en`). `en` is served at bare URLs; `id` at `/id/...`.
- Rationale: current content is English → all existing URLs + SEO history preserved.
- Catalog lives in `config/locales.php` (code-first, mirrors `config/media.php`); adding `zh` later = one config entry + translations, zero schema work.

### 3.2 URL strategy — ✅ prefix `/{locale}/…`, default bare
- Wrap `routes/frontend.php` in a locale-prefix group generated from active locales.
- `Route::fallback()` (Phase 6 B11) stays lowest priority **per prefix**.
- Reserved-prefix guard for content-type `route_base` gains locale keys (`id`, `en`) as reserved.
- `SetLocale` middleware: URL segment → session persist → `app()->setLocale()`.
- Switcher swaps current URL to its translation-group counterpart (not just homepage).

### 3.3 Document translation — ✅ row-per-locale + `translation_group_id`
- **Pages** and **content_entries**: each locale = a full record (own slug, own SEO, own builder tree; publish per locale independently).
- ⚠️ Schema (gated at B4/B5): `+ locale string(10) default 'en'`, `+ translation_group_id (ulid, indexed)`. Backfill existing rows `locale='en'`, fresh `translation_group_id` per row.
- Unique constraints become locale-aware: pages `unique(slug, locale)`; entries `unique(content_type_id, slug, locale)`.
- Phase 5/6 builder needs **zero changes** — a translated document is just another record with its own `page_blocks` rail.

### 3.4 Attribute translation — ✅ one polymorphic `translations` sidecar
- Protected + structural models (Products, Categories, Destinations, PageSections, MenuItems, Terms, SiteSettings values) translate as field values on one canonical record; price/images/relations/structure stay shared.
- ⚠️ New table (gated at B1):
  ```
  translations
    id, translatable_type, translatable_id, locale string(10),
    field string(100), value longtext, timestamps
    unique(translatable_type, translatable_id, locale, field)
    index(translatable_type, translatable_id, locale)
  ```
- `Translatable` trait declares `$translatable = [...]` per model, resolves current-locale value with **fallback to the base column**. Base columns keep the default-locale value → protected modules keep working even if Phase 7 is reverted.
- Eager-load rule (C2 hard gate): list/detail queries `->with('translations')` scoped to current locale — never per-attribute lazy queries.

### 3.5 Menus — ✅ Option A (sidecar labels)
- Menu structure shared across locales; `menu_items` labels translate via the `translations` sidecar. No `menus`/`menu_items` schema change for i18n.

### 3.6 Fallback & publish semantics — ✅ hide / 404 for untranslated documents
- Missing **attribute** translation → base column (default locale).
- Missing **document** translation → does not publish in that locale (polite 404); switcher hides that locale option.
- `hreflang` emitted **only** for locales with a real translation + `x-default`. Never claim a language served purely as fallback.

### 3.7 Packages — ✅ ZERO new packages
- One trait + one middleware + one config + lang files. No `spatie/*` or `astrotomic/*` (each conflicts with 3.3/3.4). Confirmed at A0.

---

## 4. Data Model (final plan — confirm DDL at each task's gate)

```
config/locales.php          [NEW config]  keys id/en, native_label, is_active,
                            default_locale='en', fallback rules

pages                       [ALTER ⚠️ B4]  + locale string(10) default 'en',
                            + translation_group_id (ulid, index);
                            unique(slug) → unique(slug, locale)

content_entries             [ALTER ⚠️ B5]  + locale, + translation_group_id;
                            unique(content_type_id, slug) →
                            unique(content_type_id, slug, locale)

translations                [NEW ⚠️ B1]    polymorphic attribute sidecar (§3.4)

lang/en/*.php, lang/id/*.php [NEW A2]       the few hardcoded frontend UI strings
```

**No schema change to:** products, categories, destinations, page_sections,
page_blocks, site_settings, media, taxonomies, terms, menus, menu_items —
all localize through the sidecar. `media.alt/caption` localization = stretch via
same sidecar (media shared across locales by design).

**Backfill/DB safety:** dev DB carries recovery data (Phase 6 §16). **No
`migrate:fresh`/`migrate:reset`.** All migrations additive + reversible;
`mysqldump` before every ALTER on existing tables (B4/B5).

---

## 5. Reuse Ledger (don't fork these)

- `Route::fallback()` (Phase 6 B11) — keep lowest priority per prefix.
- `content_entry_index` sidecar — archive queries add `where locale = current`.
- Phase 4 revisions / scheduling / audit / SEO manager — per-record, keep working.
- `BuilderTreeSanitizer`, `PageBlockService`, block registry, 20:60:20 builder shell — untouched.
- Theme tokens + templates (Phase 3/5) — cover TD-03 (child theme) per A1 recommendation.
- `ContentQueryResolver` / `ContentFieldResolver` (Phase 6 B13/B14) — gain implicit `locale = current` at B9.

---

## 6. Caching & Performance Rules

- List/detail queries eager-load `translations` scoped to current locale (no N+1).
- Localized routes ≤300ms warm (C2 gate). Switcher/hreflang add no queries per row.
- Locale caches keyed by locale where applicable (avoid cross-locale bleed).

---

## 7. Sanitization / Security Rules

- Translated output escapes exactly like base values; sidecar `value` rendered through the same `{{ }}` / sanitizer path — never raw `{!! !!}` on untrusted translation text (C1 audit).
- FormRequest rules for translation fields = `string` (not `image|mimes`); image fields go through Media Library (AGENTS.md §8).

---

## 8. Skill Map rows to add (at C4 → AGENTS.md §3 + CLAUDE.md)

```
| Multi-language / locale / translation | i18n-skill.md |
```

New files this phase:
- `ai/skills/i18n-skill.md` — canonical i18n standard (row-per-locale vs sidecar, Translatable trait, eager-load rule, fallback + hreflang). Mirrors the Media Library standard pattern.
- `docs/modules/internationalization.md` — developer reference (C4).

---

## 9. Approval Gates (this phase)

1. **A0** — full decision set ✅ signed off 2026-07-09.
2. **A1** — edit Phase 4 `StructuredDataBuilder`.
3. **A2** — frontend route registration wrapped in locale prefix group.
4. **B1** — new `translations` table.
5. **B4 / B5** — ALTERs + unique-index changes on existing `pages` / `content_entries`; backfill plan reviewed + `mysqldump` before running.
6. Zero new packages — confirmed at A0; re-flag if it ever changes.

---

## 10. Risks (grand plan §9 — watch list)

- Sidecar N+1 → trait ships eager-load scope; C2 greps for lazy access.
- Slug collision after locale-aware index → backfill audit before index swap; FormRequests updated same task.
- SEO regression on legacy URLs → default locale unprefixed; C3 asserts legacy routes unchanged.
- Half-translated document leak → documents publish per locale; hreflang only on real translations.
- Route cache / fallback ordering with prefix groups → A2 adds route-registration tests incl. `Route::fallback` priority per prefix.
- Scope creep (admin UI translation, auto-translate, RTL) → explicitly out of scope (grand plan §1.2).

---

## 11. Definition of Done (Release Gate — grand plan §12)

- [ ] ≥2 locales at locale-aware URLs; default-locale URLs unchanged.
- [ ] Owner translates pages, entries (incl. builder bodies), products, categories, destinations, menus, home sections, global settings from one admin — zero code.
- [ ] Fallback works: nothing blank; untranslated documents 404 politely per locale.
- [ ] hreflang, per-locale sitemap, localized canonical/OG/JSON-LD verified.
- [ ] `content_query`/`content_field` locale-aware.
- [ ] End-to-end proof: home + one tour + one page fully bilingual (en/id).
- [ ] PHPStan level 5 / 0 errors; full suite green (no regression — **baseline reconciled = 866** at A2; now 877 with A2 tests).
- [ ] Localized routes ≤300ms warm; no translation N+1.
- [ ] Carry-over debt (grand plan §11) cleared or re-dispositioned.
- [ ] Docs: i18n module doc + skill file + CHANGELOG + AGENTS/Claude sync + Phase 8 prep notes.

---

## 12. Documentation Sync Matrix (run after EVERY relevant task)

| Trigger | Update |
|---|---|
| New table / column | this handoff §4 + task report Impact + (C4) module doc |
| New route / middleware | this handoff §1 status + AGENTS/Claude phase line at C4 |
| New skill/standard | `ai/skills/i18n-skill.md` + Skill Map rows (§8) |
| Decision changed | §3 here + grand plan (only if owner asks) |
| Task complete | flip status ⏳→✅ in §1 + write `ai/reports/phase-7/[task-id]-*.md` |
| Release gate item met | §11 checkbox |

---

## 13. Phase 8 Preparation Notes (finalize at C4)

- Staged media follow-ups (orphan-purge safety, SVG sanitizer, media re-organize) — Phase 6.1 §5 → Phase 8 (ops).
- Automatic `mysqldump` before ALTERs → make a first-class ops feature in Phase 8.
- Machine/auto-translation (DeepL/Google) integration — Phase 8+ candidate.
- Content REST/GraphQL API — Phase 8 candidate (unchanged).

---

## 14. Kickoff & Report Location

- **Kickoff prompt:** `ai/promt/phase7/phase-7-a0-kickoff-prompt.md`.
- **All task reports:** `ai/reports/phase-7/[task-id]-[name].md` (e.g. `a0-architecture-decisions.md`, `a1-carryover-debt.md`).
- **Branch:** continue on `feature/phase-7-planning`; rename/branch to `feature/phase-7-a1-foundation` at first code task (A1/A2).
