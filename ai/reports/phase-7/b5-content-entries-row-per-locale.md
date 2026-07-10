# Task B5 — Phase 7 Content Entries Row-Per-Locale

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** ⚠️ Schema ALTER on existing `content_entries` (owner-approved; mysqldump taken).

---

## Task: B5 — Content Entries row-per-locale + translation group

Same pattern as B4 (pages), applied to `content_entries`: each locale is a full
entry linked by `translation_group_id`; the default locale keeps its unprefixed
URL and SEO. Archive + single are locale-aware; builder body is copied on
"Translate to…"; the `content_query` block filters by the current locale.
**Completes Milestone M3.**

### Pre-flight
- **mysqldump backup** →
  `storage/app/db-backups/pre-b5-entries-alter-20260709-213507.sql` (255 KB).
- Migration applied on dev DB: 4 entries backfilled `en`, 4 unique ULID groups,
  0 nulls.

### Changed

**Schema**
- `database/migrations/2026_07_09_000003_add_locale_to_content_entries_table.php`
  (⚠️ additive + reversible): `+ locale string(10) default 'en'`,
  `+ translation_group_id char(26)` (indexed). Backfill: `en` + one ULID per row.
  Unique index swapped `(content_type_id, slug)` →
  `(content_type_id, slug, locale)`. `down()` restores the original.

**Model / services**
- `app/Models/ContentEntry.php` — locale/group fillable; `booted()` assigns
  locale (default) + ULID on create; `translationSiblings()` /
  `translationIn($locale)`; `scopeForLocale`; `publicUrl()` locale-aware (default
  unprefixed, others `/{locale}/{route_base}/{slug}`).
- `app/Support/ContentQueryResolver.php` — `content_query` block adds
  `->forLocale(Locales::current())` (B9 locale-aware bridge — one line, applies
  to pages **and** entry bodies).

**Frontend**
- `app/Http/Controllers/Frontend/ContentEntryController.php` — archive + single
  filter by `Locales::current()`; single computes `$localeAlternates` from
  published sibling entries so the switcher lands on the counterpart and hides
  untranslated locales (A0 fallback).

**Requests**
- `StoreContentEntryRequest` — slug unique per
  `(content_type_id, slug, locale=default)`.
- `UpdateContentEntryRequest` — slug unique per
  `(content_type_id, slug, entry_locale)`.

**Admin**
- `ContentEntryController::translate()` + route
  `content-types/{content_type}/entries/{entry}/translate`: replicates the entry
  into the SAME translation group as a **draft**, in the target locale; also
  duplicates the block tree (blockable morph rail) for `editor` types, and
  copies the taxonomy pivots (structure shared). Idempotent — a second call
  reopens the existing sibling.
- `resources/views/backend/content-entries/edit.blade.php` — Translations panel
  (Edit {LOCALE} / + Translate to {LOCALE}).

**Tests**
- `tests/Feature/Phase7/B5ContentEntriesRowPerLocaleTest.php` — 11 tests.

### Design notes
- **A2 prefix strip stays in place.** `resolve()` still drops the leading locale
  segment before route_base/slug resolution; the locale filter now applies inside
  `archive()`/`single()` using the app locale that `SetLocale` just set.
- **No sidecar-index changes required.** `content_entry_index` joins through
  `content_entry_id` which is already locale-scoped by row; downstream archive
  queries filter by the entry's locale via `forLocale()`.
- **Builder + revisions + scheduling unchanged.** They operate per-record — each
  locale's entry keeps its own block tree, revisions, and `published_at`.

### Impact
- DB: **ALTER `content_entries`** (2 columns + index swap; migrated, reversible;
  backup taken).
- Routes: +1 admin (`content-types.entries.translate`); public `/{route_base}/…`
  now resolves per locale (and `/{locale}/{route_base}/…` too).
- Frontend: default-locale URLs byte-identical; `/{locale}/{route_base}[/{slug}]`
  serves that locale (404 if untranslated); switcher lands on the counterpart.
- Security: admin-guarded; slug uniqueness enforced per locale in DB + FormRequests.

### Verification
- `B5ContentEntriesRowPerLocaleTest` — **11/11 pass** (model defaults; slug unique
  per locale + cross-locale allowed; archive/single locale-filtered; untranslated
  404; publicUrl per locale; content_query locale-filtered; admin translate action
  copies blocks as draft; idempotent; default locale rejected).
- Full suite — **931/931 pass** (920 + 11 B5; one A2 test updated to the new
  B5 semantics — untranslated entries 404 in that locale, so the A2 fixture now
  creates both en+id rows).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B5 commit>` then `php artisan migrate:rollback --step=1` (restores
original unique index + drops the two columns). Backup at
`storage/app/db-backups/pre-b5-entries-alter-20260709-213507.sql` if needed.

### Milestone
**M3 (Documents) COMPLETE** — pages (B4) + content entries (B5) independently
authored per locale, builder included. Demo: author an Indonesian home page + a
tour post from the same admin.

### Next
- **B6 — Catalog localized** (Products / Categories / Destinations via the B1
  sidecar): extend-only translation of `name`, descriptions, meeting_point, CTA
  texts, meta — prices/images/relations stay shared. The **business milestone**.
