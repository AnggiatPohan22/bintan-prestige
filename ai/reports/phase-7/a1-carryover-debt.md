# Task A1 — Phase 7 Carry-over Debt Clearing

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Debt verification + disposition — **no production code change required**.

---

## Task: A1 — Clear carry-over technical debt (grand plan §11)

Two carry-over items were slated for A1: the C1 follow-up on
`StructuredDataBuilder` JSON-LD escaping, and a decision on TD-03 (child-theme
support). Investigation shows the first is already fixed and the second should be
formally closed. No new code was required.

### Item 1 — C1-FU: `StructuredDataBuilder` JSON-LD `JSON_HEX_*` flags → ALREADY RESOLVED ✅

- The grand plan §11 listed `StructuredDataBuilder::jsonLd()` as still using
  `json_encode` without the hex-escape flags (Phase 6 C1 flagged it as a
  follow-up).
- **Reality:** commit **74f1027** *"fix(security): harden site-wide JSON-LD
  against `</script>` breakout"* (2026-07-07) already added
  `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` to the `@graph`
  encode (`app/Support/StructuredDataBuilder.php:292`) **and** added a regression
  test (`tests/Feature/Security/StructuredDataEscapeTest.php`). That commit is an
  ancestor of the current HEAD (present on `develop`, `feature/phase-6.1-*`, and
  this branch). The grand plan §11 was authored without accounting for it.
- **Verified this task:** `php artisan test tests/Feature/Security/StructuredDataEscapeTest.php`
  → **1 passed / 4 assertions** (a business name containing `</script><script>`
  is hex-escaped and cannot break out of the ld+json block).
- **Disposition:** debt already cleared; no code change. No `{!! !!}` escaping
  gap remains in the site-wide structured data.

### Item 2 — TD-03: Child-theme support → CLOSED (won't-do, revisit on demand)

- **Origin:** TD-03 = Phase 4 "IMP-08 Child Theme Support", LOW priority, never
  implemented; deferred through Phase 5 and Phase 6 ("re-evaluate; defer to
  Phase 7 if not needed").
- **Assessment:** `app/Services/ThemeService.php` has no theme-inheritance concept
  (grep for `child|parent|extends|inherit` → 0 matches), and none is needed. The
  existing **theme tokens** (Phase 3 design-token system + token editor + export/
  import) and **page/entry templates** (Phase 5/6) already deliver per-site visual
  customization from the admin without a parent→child theme chain. Adding a theme
  inheritance chain + migration for a feature with no current demand would add
  schema and complexity against the "no hardcoded content / one dashboard" goal
  without user value.
- **Disposition:** **CLOSED as won't-do.** Revisit only on a concrete owner
  request for multi-theme inheritance. Recorded here and in the Phase 7 handoff
  so it stops re-appearing as open carry-over debt.

### Changed
- No application code changed.
- `ai/reports/phase-7/a1-carryover-debt.md` — created (this report).
- `ai/reports/phase-7/phase-7-progress-handoff.md` — A1 status → ✅; debt
  disposition recorded.

### Impact
- DB: none.
- Routes: none.
- Frontend: none.
- Security: none (existing `JSON_HEX_*` hardening verified green).
- Tests: no new tests; existing `StructuredDataEscapeTest` re-verified (1/4).

### Rollback
- Documentation-only task. Delete `ai/reports/phase-7/a1-carryover-debt.md` and
  revert the handoff status edit. No code/DB to revert.

### Next
- **A2 — Locale foundation** (⚠️ route registration change): `config/locales.php`
  (id/en, default en, is_active), `SetLocale` middleware (URL segment → session →
  `app()->setLocale()`), locale-prefixed route group around `routes/frontend.php`
  with `Route::fallback` priority preserved per prefix, reserved-prefix guard
  gains `id`/`en`, `lang/en` + `lang/id` scaffolding, and the locale-switcher
  chrome component (nothing translated yet). Add route-registration tests
  (fallback ordering per prefix). Reconcile the exact test baseline at the start
  of A2 before making changes.
