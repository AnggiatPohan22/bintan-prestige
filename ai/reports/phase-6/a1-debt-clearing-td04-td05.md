# A1 — Carry-over Debt Clearing (TD-04 + TD-05)

> **Task:** A1 — Carry-over debt: TD-04, TD-05
> **Status:** ✅ DONE — 2026-06-29
> **Branch:** `feature/phase-6-a1-debt-clearing` (off `develop`)
> **Approval gate:** none (no schema, no renames, no packages)
> **Skills:** frontend-design-skill · backend-skill · security-skill

---

## Summary

Cleared the two Phase-5 carry-over debt items folded into Stage A:

- **TD-04** — the `text` widget rendered its `content` with raw `{!! $content !!}`,
  an unsanitized HTML sink (stored-XSS risk). Now routed through the existing
  `InlineContentSanitizer::richtext()` (reused, not forked — handoff §9).
- **TD-05** — the `contact_form` block ran `FormDefinition::find()` **inside Blade**,
  violating "no queries in Blade" (AGENTS.md §7). Resolution moved to
  `PageRenderData::prepareContactFormBlocks()`, mirroring the existing
  `resolvedFaqItems` / `resolvedProducts` pattern. The partial now reads
  `$block->resolvedFormDefinition`.

Both fixes reuse existing infrastructure; no new files beyond tests.

---

## Report (AGENTS.md §11)

### Changed
- `resources/views/frontend/widgets/text.blade.php` — `{!! $content !!}` → `{!! \App\Support\InlineContentSanitizer::richtext($content) !!}` (TD-04).
- `app/Support/PageRenderData.php` — added `use App\Models\FormDefinition;`, new `prepareContactFormBlocks()` (batched single query for all contact_form blocks, attaches `$block->resolvedFormDefinition`), called from `prepare()` (TD-05).
- `resources/views/frontend/blocks/contact-form.blade.php` — removed the `FormDefinition::find()` query; reads the pre-resolved `$block->resolvedFormDefinition` (TD-05).
- `tests/Feature/Frontend/GenericPageRenderingTest.php` — strengthened the Blade-query guard to also reject `::find(` and `\App\Models\` (the hole that let TD-05 pass undetected).
- `tests/Feature/Phase6/A1DebtClearingTest.php` — **new**, 6 tests: widget sanitization (render + source), contact_form resolved render, single-query resolution for N blocks, blade-no-model guard.

### Impact
- DB: none.
- Routes: none.
- Frontend: widget text output now sanitized; contact_form renders identically (no behaviour change), query moved server-side.
- Security: TD-04 closes an unsanitized HTML sink via `InlineContentSanitizer::richtext()`.

### Verification
- New A1 tests: **6/6 pass** (129 assertions).
- Strengthened guard `test_frontend_block_blades_do_not_query_models_directly`: pass.
- Full suite: **631/632 pass**. The 1 failure (`PageBlockManagementTest::test_block_editor_exposes_phase_two_controls_…`) and **3 PHPStan errors** (`AdminDashboardAppearance.php`, `AdminAppearanceService.php`) were confirmed **pre-existing on clean `develop`** (reproduced with A1 changes stashed) — they originate from the `4c6cd6d` brand-identity merge, **not** from A1. Logged as **TD-06 / TD-07** in handoff §11.
- A1 introduces **zero** new PHPStan errors and **zero** new test failures.

### Rollback
- `git revert <commit>` for the A1 commit, or manually restore the three source files; delete `tests/Feature/Phase6/A1DebtClearingTest.php`.

### Next
- **A2 — Carry-over debt: pagination + FormRequest** (`BuilderPatternController@index` → `paginate(20)`; extract `PageBlockController` inline validation to a FormRequest). No gate.
- ⚠️ **Recommend clearing TD-06/TD-07 (pre-existing baseline RED) before continuing** so the Phase-6 hard gate (PHPStan 0 / suite green) is actually enforceable.
