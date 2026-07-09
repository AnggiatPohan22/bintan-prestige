# Task A0 — Phase 7 Architecture Decision Record

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-planning`
**Type:** Decision + documentation only — **no code, no migrations** (AGENTS.md §9 gate).

---

## Task: A0 — Architecture Decision Record

Lock the Phase 7 architecture (grand plan §3.1–§3.6), obtain owner sign-off on
all six decisions, and create the living handoff. This task deliberately touches
no code or schema.

### Startup verification (grand plan baseline confirmed)
- `config/app.php` → `locale = en`, `fallback_locale = en` (env-driven).
- `lang/` folder → **does not exist**.
- `__()` / `@lang` / `trans()` in `resources/views/frontend/` → **0** occurrences.
- Confirms the sidecar + row-per-locale approach is sufficient; the frontend is
  already 100% CMS-driven, so there are almost no hardcoded strings to localize.

### Decisions — LOCKED (owner sign-off 2026-07-09)

| # | Decision | Outcome |
|---|---|---|
| a | Locale set + default | **`id` + `en`; default `en` unprefixed** (existing URLs + SEO preserved; `id` → `/id/...`) |
| b | URL strategy | **Prefix `/{locale}/…`, default bare** (`SetLocale` middleware; `Route::fallback` per prefix; reserved-prefix guard gains `id`/`en`) |
| c | Document translation | **Row-per-locale + `translation_group_id`** on `pages` + `content_entries` (own slug/SEO/builder tree; publish per locale) |
| c | Attribute translation | **One polymorphic `translations` sidecar** for protected/structural models (extend-only; base column = default locale) |
| d | Menus | **Option A — sidecar labels** (shared structure, translated labels; no menu schema change) |
| e | Untranslated document | **Hide / 404 in that locale**; `hreflang` only where a real translation exists |
| f | Packages | **ZERO new packages** (one trait + one middleware + one config + lang files) |

Owner approval wording: *"en unprefixed", "Sidecar labels (Opt A)", "Hide / 404",
"Approve all three [URL prefix / doc-vs-attribute split / zero packages]"*.

### Changed
- `ai/reports/phase-7/phase-7-progress-handoff.md` — **created** (living source of
  truth: locked A0 decisions, task-status table A0–C4, milestones, final data
  model, reuse ledger, approval gates, DoD, Documentation Sync Matrix, Phase 8 prep).
- `ai/reports/phase-7/a0-architecture-decisions.md` — **created** (this report).

### Impact
- DB: **none** (A0 is decisions only; all Phase 7 migrations deferred to their gated tasks).
- Routes: **none** (locale-prefix group lands at A2).
- Frontend: **none**.
- Security: **none**.
- Packages: **none** (zero-package decision confirmed).

### Rollback
- Delete the two created files:
  `ai/reports/phase-7/phase-7-progress-handoff.md` and
  `ai/reports/phase-7/a0-architecture-decisions.md`. No code/DB to revert.

### Next
- **A1 — Carry-over debt** (⚠️ edits Phase 4 code): align
  `app/Support/StructuredDataBuilder.php` JSON-LD flags with the `JSON_HEX_*` set
  (Phase 6 C1 follow-up), and formally close **TD-03 child-theme**
  (recommend: *closed — theme tokens + templates cover the need; revisit on real
  demand*). Reconcile the exact test baseline at the start of A1. Rename/branch to
  `feature/phase-7-a1-foundation` for the first code task.
