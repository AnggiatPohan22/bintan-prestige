# Task B8 — Phase 7 Admin Translation UX Polish

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Admin UX polish — per-locale translation-status badges + locale filter (no schema).

---

## Task: B8 — Translation status & locale filter on list views

Give the owner an at-a-glance view of what's translated where. Every row-per-locale
list (Pages, Content Entries) shows a per-locale status pill (published / draft /
missing) and a locale filter dropdown next to the existing search/status controls.
Reuses the eager-loaded `translationSiblings` so the pills add **no per-row
queries** (C2 gate honoured — verified by test).

### Changed

**New**
- `resources/views/backend/_partials/translation-badges.blade.php` — shared
  partial: one pill per active locale, colour + dot state
  (● green = published sibling, ● amber = draft/scheduled/archived sibling,
  ○ slate = no sibling). Accepts a `$record` (Page or ContentEntry) plus an
  optional pre-loaded `$siblings` collection so the caller can avoid extra
  queries.

**Edited**
- `app/Http/Controllers/Admin/PageController.php` — `index()` accepts `locale`
  query param (validated against active codes), eager-loads
  `translationSiblings` selecting `id, translation_group_id, locale, status`.
- `app/Http/Controllers/Admin/ContentEntryController.php` — same pattern:
  `locale` filter + eager-loaded siblings on `index()`.
- `resources/views/backend/pages/index.blade.php` — locale filter `<select>`
  next to the status filter; new `Translations` column between Status and Sort;
  empty-row `colspan` bumps 5 → 6 when multiple locales are active.
- `resources/views/backend/content-entries/index.blade.php` — same locale
  filter + Translations column between Status and Author; entry title gains the
  locale pill (mirrors the Pages list added at B4).

**Tests**
- `tests/Feature/Phase7/B8AdminTranslationUxTest.php` — 6 tests.

### Design notes
- **Single-partial approach.** One `translation-badges.blade.php` used by both
  Pages and Content Entries — Term/MenuItem lists don't need it (structure-shared
  per A0 §3.5 Option A), so the partial's contract is limited to row-per-locale
  documents.
- **N+1 guard.** Sibling status is fetched via a **single** WHERE-IN query per
  page load (Eloquent's morph-free `HasMany` eager-load with a whittled-down
  select). Explicit regression test asserts exactly **1** sibling-load query for
  a 5-row list.
- **UI is opt-in.** Every element ("Translations" column, locale filter, entry
  locale pill) is wrapped in `if (count(Locales::active()) > 1)` — the admin
  looks identical to before when only the default locale is active.

### Impact
- DB: **none**.
- Routes: none (extends existing list controllers).
- Frontend: **admin-only** UX; public output unchanged.
- Security: filter param validated against active locale codes; unknown values
  are ignored.

### Verification
- `B8AdminTranslationUxTest` — **6/6 pass** (Pages badges render published +
  draft dots + missing dot; Pages locale filter narrows list; **badges don't
  N+1** — 1 sibling-load query for 5 rows; Content Entries badges + column;
  Entries locale filter narrows list).
- Full suite — **949/949 pass** (943 + 6 B8; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B8 commit>`. No schema or data change.

### Next
- **B9 — Builder bridge locale-aware (finish):** `content_field` block already
  reads through localized accessors on B6/B3 models; needs an explicit test +
  per-locale builder preview (open the builder in the target locale so the owner
  sees translated content as it will render). Then **B10 — SEO i18n**
  (hreflang, per-locale sitemap, localized canonical/OG/JSON-LD).
