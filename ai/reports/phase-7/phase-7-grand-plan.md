# Phase 7 — Internationalization (i18n) — Grand Plan
# Bintan Prestige CMS

> **Status:** PLANNING (not started). Awaiting owner approval before any code.
> **Author:** Architecture planning pass — 2026-07-09
> **Depends on:** Phase 6 COMPLETE (Flexible Content Modeling) — 2026-07-07,
> Phase 6.1 COMPLETE (Dashboard & Media UX + Media Library one-door standard) — 2026-07-08
> **Stack:** Laravel 13.8 | PHP 8.3 | Tailwind | Alpine.js | MySQL 8
> **Location:** `ai/reports/phase-7/phase-7-grand-plan.md`
>
> This document is the **constitution for Phase 7**. It defines *what* and
> *why*. Per-task detail will live in the Phase 7 living handoff
> (`ai/reports/phase-7/phase-7-progress-handoff.md`, created at A0 approval)
> and in the skill files.
>
> Authority order applies (AGENTS.md §2). Every schema change requires explicit
> owner approval (AGENTS.md §9). The Media Library image-input standard
> (AGENTS.md §8 patent rule) applies to any new image field this phase adds.

---

## 0. Why This Phase (One Paragraph)

Bintan is an international destination: the site already prices in **IDR and
SGD**, but every word on it exists in a single language. Domestic travelers
(Bahasa Indonesia) and international visitors (English — Singapore is a ferry
ride away) currently share one text. Phase 7 makes the CMS **multi-language
end-to-end**: the owner manages each locale's content from the same admin,
visitors get the site in their language at locale-aware URLs with correct
`hreflang`/sitemap SEO, and anything untranslated falls back gracefully instead
of breaking. This is the last "content power" phase before the platform shifts
to operational maturity (Phase 8).

**Phase 7 Goal:** From the admin dashboard, the owner can manage the complete
website — pages, content entries, products, categories, destinations, menus,
home sections, and global settings — in **two or more locales**, with
per-locale URLs, SEO, and automatic fallback. No hardcoded strings, no duplicate
site installs.

---

## 1. Scope

### 1.1 In Scope

1. **Locale foundation** — locale catalog (config-first, admin-toggleable),
   URL strategy with locale prefix, `SetLocale` middleware, locale switcher UI,
   session/URL detection, fallback chain.
2. **Translatable documents** — Pages and Content Entries as **row-per-locale**
   records linked by a translation group (each locale gets its own slug, SEO
   meta, and builder block tree).
3. **Translatable attributes on protected modules** — Products, Categories,
   Destinations, Page Sections, Menus, Terms via a **polymorphic translations
   sidecar** (extend-only; protected tables untouched — AGENTS.md §5).
4. **Global chrome** — site_settings-driven navigation, footer, booking CTA,
   business identity, SEO defaults translated per locale.
5. **Admin translation UX** — locale switcher on edit screens, "Translate to…"
   creates the linked copy, translation-status badges in list views.
6. **SEO i18n** — `hreflang` alternates, per-locale sitemap, localized canonical,
   localized JSON-LD, per-locale meta.
7. **Builder & content-query awareness** — `content_query` block filters by the
   current locale; `content_field` renders localized values.
8. **Carry-over debt** — StructuredDataBuilder JSON-LD flags fix (flagged at
   Phase 6 C1); close the TD-03 child-theme question (see §12).
9. **Release audit (Stage C)** — PHPStan level 5, performance, smoke, docs.

### 1.2 Out of Scope (explicitly NOT this phase)

- **Translating the admin dashboard UI.** Admin stays English — the owner team
  works in one language. Only *content* and the *public site* are localized.
- **Machine/auto translation** (DeepL/Google APIs). Manual translation only;
  an integration is a Phase 8+ candidate.
- **RTL languages.** Target locales are LTR (id, en, zh candidates). RTL layout
  work is out.
- **Per-locale currency/price logic.** Prices already carry IDR + SGD columns;
  currency *display* rules stay as-is. A full multi-currency engine is not i18n.
- **Translating booking/transactional data** (bookings, submissions) — data
  records are not content.
- Content REST/GraphQL API (Phase 8 candidate, unchanged).

### 1.3 Guardrails (do not break)

- Existing URLs keep working: the **default locale stays unprefixed** so every
  current route, sitemap entry, and backlink survives unchanged.
- Never rebuild a protected module; the sidecar pattern is extend-only.
- Page builder shell (20:60:20), `BuilderTreeSanitizer`, block registry — untouched.
- Media Library one-door standard applies to any new image field (AGENTS.md §8).
- PHPStan level 5 / 0 errors / no baseline; full suite green at every task.
- No new Composer/NPM packages expected (§3.6) — flag at A0 if that changes.

---

## 2. Mental Model & Naming

| Concept | Our term | Where it lives |
|---|---|---|
| A language the site publishes in | **Locale** (`id`, `en`, …) | `config/locales.php` + admin toggle |
| The locale served with no URL prefix | **Default locale** | config (owner decides at A0) |
| Linked versions of one document across locales | **Translation group** | `translation_group_id` on pages / content_entries |
| Per-field translated value for a non-document model | **Translation** (sidecar row) | `translations` table (polymorphic) |
| What renders when a translation is missing | **Fallback** | default-locale value, marked with `hreflang` only where real |

> Two different shapes on purpose: **documents** (pages, entries) translate as
> *whole records* — different slug, different blocks, independently publishable.
> **Attributes** (a product name, a category description, a menu label)
> translate as *field values* on one canonical record — price, images, stock,
> relations stay shared. Forcing one shape onto both is how i18n projects fail.

---

## 3. Key Architecture Decisions (the "A0" of Phase 7)

Each has a recommendation; **owner must sign off** at A0.

### 3.1 Locale set & default — **`id` + `en`, default `en` (recommended, owner decides)**

- Ship with two locales: **Indonesian (`id`)** and **English (`en`)**.
- **Recommendation: default = `en` (unprefixed)** — the current site content is
  English, so every existing URL keeps its exact meaning and SEO history;
  Indonesian becomes `/id/...`. If the owner prefers Indonesian-first, flip it —
  but that changes the language of all existing unprefixed URLs.
- Locale catalog in `config/locales.php` (code-first, mirrors `config/media.php`
  pattern): key, native label, flag/icon, `is_active`. Adding a locale later =
  one config entry (+ translations). A future `zh` for the Chinese market slots
  in with zero schema work.

### 3.2 URL strategy — **prefix segment, default unprefixed (recommended)**

| Option | Pro | Con |
|---|---|---|
| **Prefix `/{locale}/…`, default bare (recommended)** | Zero breakage of existing URLs; standard SEO practice; one host | Route registration wrapped in a prefix group |
| Subdomain `id.domain.com` | Clean separation | Hosting/DNS/ssl churn; overkill |
| Query `?lang=id` | Trivial | Bad SEO, ugly, cache-hostile |

- Implementation: wrap `routes/frontend.php` in a locale-prefix group generated
  from active locales; `Route::fallback()` (Phase 6 B11) keeps lowest priority
  **per prefix**. Reserved-prefix guard for content-type `route_base` gains the
  locale keys (`id`, `en`, …) as reserved.
- `SetLocale` middleware: URL segment → session persist → `app()->setLocale()`.
  Locale switcher swaps the current URL to its translation-group counterpart
  (not just the homepage).

### 3.3 Document translation — **row-per-locale + translation group (recommended)**

For **pages** and **content_entries**:

| Option | Pro | Con |
|---|---|---|
| JSON-per-column (`{"en":…,"id":…}`) | No new rows | Breaks every existing query/index/sidecar; slug can't vary; builder tree can't vary |
| Translation tables per model | Queryable | One extra table per model; block tree still can't vary per locale |
| **Row-per-locale + group (recommended)** | Each locale = a full record: own slug, own SEO, own block tree; render pipeline unchanged; publish per locale | Two new columns on two tables + linking UX |

- Schema (⚠️ approval): `pages` + `content_entries` gain
  `locale` (string(10), default = default locale) and
  `translation_group_id` (uuid/ulid, indexed). Existing rows backfill
  `locale = default`, `translation_group_id = fresh id per row`.
- Unique constraints become locale-aware: pages `unique(slug, locale)`;
  entries `unique(content_type_id, slug, locale)` (⚠️ index change on existing
  tables — same approval gate).
- The Phase 5/6 builder needs **zero changes**: a translated page/entry is just
  another page/entry with its own `page_blocks` rail.
- `content_entry_index` (sidecar) gains nothing — it joins through the entry,
  and archive queries add `where locale = current`.

### 3.4 Attribute translation — **one polymorphic `translations` sidecar (recommended)**

For protected + structural models (Products, Categories, Destinations,
PageSections, MenuItems, Terms, SiteSettings values):

- **New table (⚠️ approval):**
  ```
  translations
    id, translatable_type, translatable_id, locale (string 10),
    field (string 100), value (longtext), timestamps
    unique(translatable_type, translatable_id, locale, field)
    index(translatable_type, translatable_id, locale)
  ```
- A `Translatable` trait declares `$translatable = ['name', 'description', …]`
  per model and resolves values for the current locale with fallback to the
  base column. **Base columns keep the default-locale value** — protected
  modules remain fully functional even if Phase 7 were reverted.
- Eager-loading rule (performance gate C2): list/detail queries
  `->with('translations')` scoped to the current locale — never per-attribute
  lazy queries.
- site_settings: translated via the same sidecar on the `SiteSetting` row
  (`field = 'value'`) so the 13 settings modules localize without new columns.

### 3.5 Fallback & publish semantics — **default-locale fallback, per-locale publish**

- Missing attribute translation → base column (default locale). Missing
  *document* translation → that locale simply doesn't publish the document
  (404 in that locale; switcher hides/points to fallback per owner preference —
  decide at A0: "hide" recommended).
- `hreflang` only emitted for locales where a **real** translation exists —
  never claim a language we serve as fallback.

### 3.6 Packages — **ZERO new packages (recommended)**

`spatie/laravel-translatable` (JSON approach) conflicts with §3.3;
`astrotomic/laravel-translatable` (table-per-model) conflicts with §3.4. Both
patterns are small enough to own: one trait, one middleware, one config. Lang
files (`lang/en`, `lang/id`) cover the handful of hardcoded frontend strings
(there are currently **zero `__()` calls** — the frontend is already
CMS-driven, which is exactly why the sidecar approach is enough).

---

## 4. Data Model (proposed)

> Subject to A0 approval. Architectural picture, not final DDL.

```
config/locales.php              [NEW config] key, native_label, is_active;
                                default_locale; fallback rules

pages                           [ALTER ⚠️] + locale (string 10, default 'en'),
                                + translation_group_id (ulid, index);
                                unique(slug) → unique(slug, locale)

content_entries                 [ALTER ⚠️] + locale, + translation_group_id;
                                unique(content_type_id, slug) →
                                unique(content_type_id, slug, locale)

translations                    [NEW ⚠️] polymorphic attribute sidecar (§3.4)

menus                           [ALTER ⚠️ — decide at A0]
                                Option A (recommended): menu_items labels via
                                translations sidecar (structure shared).
                                Option B: menu row per locale (location, locale).

lang/en/*.php, lang/id/*.php    [NEW] the few hardcoded UI strings (frontend only)
```

**No changes to:** products, categories, destinations, page_sections,
page_blocks, site_settings, media, taxonomies, terms **schemas** — all of them
localize through the sidecar. `media.alt/caption` localization = stretch goal
via the same sidecar (media is shared across locales by design).

---

## 5. Frontend & SEO Surface

| Surface | Localized how |
|---|---|
| Locale switcher | Nav component; links to the *translated counterpart* URL, falls back to locale home |
| `hreflang` alternates | Layout head: one `<link rel="alternate">` per locale with a real translation + `x-default` |
| Sitemap | `sitemap.xml` gains per-locale URL sets (or index + per-locale sitemaps — decide at B10) |
| Canonical | Always self-locale canonical; never cross-locale |
| JSON-LD | `inLanguage` + localized names via resolved values (uses the C1-fixed escaping) |
| OG meta | Per-locale from the document's own SEO fields |
| Content query block | Adds implicit `locale = current` filter |
| 404/empty states | Localized via lang files |

---

## 6. Work Breakdown — Stages A / B / C

Each task follows AGENTS.md §6 module pattern + §10 workflow, ends with a §11
report in `ai/reports/phase-7/`, and keeps the suite green.

### Stage A — Foundation, Decisions & Debt Clearing

| Task | Name | Output | Approval gate |
|---|---|---|---|
| **A0** | Architecture Decision Record | Lock §3.1–§3.6 (locale set, default, URL strategy, doc-vs-attribute split, menus option, fallback semantics); create `phase-7-progress-handoff.md`; confirm zero packages | ⚠️ the whole decision set |
| **A1** | Carry-over debt | Fix `StructuredDataBuilder` JSON-LD `JSON_HEX_*` flags (Phase 6 C1 follow-up); close TD-03 child-theme question (recommend: **closed — theme tokens + templates cover the need; revisit only on real demand**) | ⚠️ touches existing Phase 4 code |
| **A2** | Locale foundation | `config/locales.php`, `SetLocale` middleware, locale-prefixed route group, reserved-prefix guard update, lang file scaffolding, locale switcher component (chrome only — nothing translated yet) | ⚠️ route registration change |

### Stage B — Build the Engine

| Task | Name | Notes |
|---|---|---|
| **B1** | `translations` sidecar + `Translatable` trait | Table (⚠️), trait with current-locale resolution + fallback + eager-load scope; unit-tested in isolation |
| **B2** | Global chrome localized | site_settings values (navigation, footer, CTA, identity, SEO defaults) via sidecar; admin: per-locale tabs on Global Assets text fields |
| **B3** | Page Sections localized | Protected module — sidecar for label/title/subtitle/description/button_text; home page fully bilingual; media slots stay shared |
| **B4** | Pages row-per-locale | Migration (⚠️ §3.3), backfill, locale-aware unique slug; admin: locale badge + "Translate to…" action creating the linked copy; builder opens per locale unchanged |
| **B5** | Content Entries row-per-locale | Same as B4 for entries (⚠️); archive/single controllers filter by locale; sidecar index queries locale-aware; scheduling/revisions keep working per record |
| **B6** | Catalog localized | Products, Categories, Destinations via sidecar (name, descriptions, meeting_point, CTA texts, meta) — **extend-only**, prices/images/relations shared; frontend listing + detail consume resolved values |
| **B7** | Menus & Terms localized | Menus per A0 option (labels via sidecar recommended); taxonomy terms name/description via sidecar |
| **B8** | Admin translation UX pass | Translation-status column in list views (●/○ per locale), locale filter, switcher on edit screens; consistent across modules |
| **B9** | Builder bridge locale-aware | `content_query` implicit locale filter; `content_field` resolves localized values; page-builder preview renders in the page's locale |
| **B10** | SEO i18n | `hreflang` + `x-default`, per-locale sitemap, localized canonical/OG/JSON-LD (`inLanguage`), localized 404 |

> **The demo milestone is B4:** the owner switches the site to `/id` and edits
> the Indonesian home page + an Indonesian page from the same admin. B6 is the
> business milestone: the catalog (tours) fully bilingual.

### Stage C — Release Audit

| Task | Name | Pass criteria |
|---|---|---|
| **C1** | Static Analysis & Code Quality | PHPStan level 5 / 0 errors; `{!! !!}` audit incl. translated output (sidecar values escape like base values) |
| **C2** | Performance Audit | Localized routes ≤300ms warm; **no per-attribute translation queries** (eager-load verified); switcher/hreflang add no N+1 |
| **C3** | Functional Smoke Test | Suite green; per-locale route checks; fallback correctness; draft-in-one-locale 404 guard; existing unprefixed URLs unchanged |
| **C4** | Documentation | `docs/modules/internationalization.md`; `ai/skills/i18n-skill.md` (+ Skill Map rows); CHANGELOG; AGENTS.md §4 + Claude.md phase lines; Phase 8 prep notes in handoff |

---

## 7. New Skill Files & Docs (Phase 7)

| File | Purpose |
|---|---|
| `ai/skills/i18n-skill.md` | Locale rules: when row-per-locale vs sidecar, Translatable trait usage, eager-load rule, fallback + hreflang rules — **the canonical i18n standard**, mirroring the Media Library standard pattern |
| `ai/reports/phase-7/phase-7-progress-handoff.md` | Living source of truth (created at A0) |
| `docs/modules/internationalization.md` | Developer reference (C4) |

Skill Map rows to add (AGENTS.md §3 + Claude.md):
```
| Multi-language / locale / translation | i18n-skill.md |
```

---

## 8. Approval Gates

1. **A0** — the full decision set (§3.1–§3.6), especially default locale and
   the document/attribute split. Single most important sign-off.
2. **A1** — edit to existing Phase 4 `StructuredDataBuilder`.
3. **A2** — frontend route registration wrapped in locale prefix group.
4. **B1** — new `translations` table.
5. **B4/B5** — ALTERs + unique-index changes on `pages` / `content_entries`
   (existing tables). Backfill plan reviewed before running.
6. Zero new packages confirmation (expected).

> ⚠️ Dev DB carries recovered production-like data (see Phase 6 §16). **No
> `migrate:fresh`** — all Phase 7 migrations must be additive + reversible, and
> a `mysqldump` backup is taken before every ALTER on existing tables (this
> becomes automatic in Phase 8).

---

## 9. Risks & Mitigations

| Risk | Likelihood | Mitigation |
|---|---|---|
| Sidecar N+1 (per-attribute lazy queries) | High | Trait ships with eager-load scope; C2 hard gate greps for lazy translation access |
| Slug uniqueness collisions after locale-aware index change | Med | Backfill audit before index swap; FormRequests updated same task |
| SEO regression on existing URLs | Med | Default locale unprefixed (§3.2); C3 asserts legacy URLs byte-identical routes |
| Fallback leaking half-translated pages | Med | Documents publish per locale (no field-level fallback for documents); hreflang only on real translations |
| Route cache / fallback ordering breaks with prefix groups | Med | A2 adds route-registration tests incl. `Route::fallback` priority per prefix |
| Scope creep (admin UI translation, auto-translate, RTL) | High | Explicitly out of scope (§1.2) |
| Translation drift (en updated, id stale) | Low | Status badges (B8); "stale" indicator = updated_at comparison, stretch |

---

## 10. Suggested Sequencing & Milestones

1. **M1 — Foundation:** A0 → A1 → A2. *Exit:* locale switcher live, `/id` serves
   the site (untranslated), zero URL breakage, debt cleared.
2. **M2 — Chrome speaks:** B1 → B2 → B3. *Exit:* nav/footer/home sections
   bilingual; a visitor can use the whole home page in Indonesian.
3. **M3 — Documents:** B4 → B5. *Exit:* pages + content entries independently
   authored per locale, builder included.
4. **M4 — Catalog & polish:** B6 → B7 → B8 → B9. *Exit:* tours/categories/
   destinations/menus bilingual; admin shows translation status everywhere.
5. **M5 — SEO + Ship:** B10 → C1 → C4. *Exit:* hreflang/sitemap correct,
   release gate PASS.

Each milestone independently demoable. M1+M2 alone already ship visible value.

---

## 11. Carry-over Technical Debt to Clear This Phase

| # | Item | Where | Action |
|---|---|---|---|
| C1-FU | `StructuredDataBuilder` JSON-LD flags not aligned with `JSON_HEX_*` set | `app/Support/StructuredDataBuilder.php` (Phase 4) | Align flags (A1) — Phase 6 C1 flagged it |
| TD-03 | Child theme support | `ThemeService` | Decide & close at A1 (recommend: closed, revisit on demand) |
| 6.1-S | Staged media follow-ups (orphan-purge safety, SVG sanitizer, media re-organize) | `ai/reports/media/media-library-integration-plan.md` §5 | **Not this phase** → scheduled into Phase 8 (ops) |

---

## 12. Definition of Done (Phase 7 Release Gate)

- [ ] Site serves ≥2 locales at locale-aware URLs; default locale URLs unchanged.
- [ ] Owner can translate pages, entries (incl. builder bodies), products,
      categories, destinations, menus, home sections, and global settings from
      the same admin — zero code.
- [ ] Fallback works: nothing renders blank; untranslated documents 404 politely
      in that locale only.
- [ ] `hreflang`, per-locale sitemap, localized canonical/OG/JSON-LD verified.
- [ ] `content_query`/`content_field` locale-aware.
- [ ] At least one real proof shipped end-to-end: **home + one tour + one page
      fully bilingual (en/id)**.
- [ ] PHPStan level 5 / 0 errors; full suite green (866 baseline must not regress).
- [ ] Localized routes ≤300ms warm; no translation N+1.
- [ ] Carry-over debt (§11) cleared or explicitly re-dispositioned.
- [ ] Docs: i18n module doc + skill file + CHANGELOG + AGENTS/Claude sync +
      Phase 8 prep notes.

---

## 13. First Concrete Step

Approve (or amend) **A0 decisions in §3** — specifically:
1. Locale set + **default locale** (`en` unprefixed recommended): yes / flip.
2. URL prefix strategy (§3.2): yes / no.
3. Document = row-per-locale, attributes = sidecar (§3.3/§3.4): yes / no.
4. Menus: sidecar labels (A) or menu-per-locale (B).
5. Untranslated document in a locale: hide (recommended) or fallback-render.
6. Confirm zero new packages.

Once A0 is signed off: create `phase-7-progress-handoff.md`, continue on branch
`feature/phase-7-planning` (rename to `feature/phase-7-a1-foundation` at first
code task), start Stage A. All task reports go to
`ai/reports/phase-7/[task-id]-[name].md`.

---

*End of Phase 7 Grand Plan. This is the constitution; task detail lives in the
Phase 7 handoff and skill files once A0 is approved.*
