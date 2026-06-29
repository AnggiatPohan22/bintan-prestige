# A1+ — Baseline Fix (TD-06 + TD-07)

> **Task:** Clear pre-existing baseline RED before Stage A2 (owner-requested)
> **Status:** ✅ DONE — 2026-06-29
> **Branch:** `feature/phase-6-a1-debt-clearing` (grouped with A1 — both are debt clearing)
> **Approval gate:** none (no schema, no renames, no packages)
> **Origin:** regressions introduced by commit `4c6cd6d` "feat(admin-ui): brand identity customization + appearance polish" — **not** Phase 6 work. Surfaced during A1 verification; confirmed reproducible on clean `develop`.

---

## Why

The Phase 6 hard gate (PHPStan level 5 / 0 errors, full suite green) was **already
red on `develop`** before Phase 6 started. A1 verification caught it. Owner asked
to clear it before A2 so the "no regression vs 627" gate is actually enforceable.

---

## TD-06 — PHPStan (3 errors → 0)

`app/Models/AdminDashboardAppearance.php`
- `getCurrent(): static` returning `static::first() ?? static::makeDefault()` and
  `makeDefault(): static` with `new static([...])` triggered `return.type` +
  unsafe `new.static` on a non-`final` model.
- **Fix:** changed both methods to `self` and `new self([...])`. No behaviour
  change (the class is not extended); resolves both errors safely.

`app/Services/AdminAppearanceService.php:158`
- `if (! empty($a->custom_vars) && is_array($a->custom_vars))` — `custom_vars` is
  cast to `array`, so PHPStan saw `is_array(...)` as always-true (`booleanAnd.rightAlwaysTrue`).
- **Fix:** dropped the redundant `is_array` term; `! empty(...)` already guards
  null/empty. No behaviour change.

## TD-07 — failing feature test

`tests/Feature/Admin/PageBlockManagementTest.php:443`
`test_block_editor_exposes_phase_two_controls_media_preview_and_accessible_save_delete_states`
- The block-editor delete control was refactored (Command Center Dark) from an
  inline English warning (`"This permanently removes its content and cannot be
  undone."`) to a JS `data-confirm` modal. The test still asserted the removed
  string. The **view change was intentional**; the **test was the stale artifact**.
- **Fix:** updated the final assertions to verify the same intent against current
  markup — accessible `aria-label="Delete About image"` + the per-block
  `data-confirm` confirmation copy. Test now passes (7 assertions).

---

## Report (AGENTS.md §11)

### Changed
- `app/Models/AdminDashboardAppearance.php` — `static` → `self` in `getCurrent()` / `makeDefault()`.
- `app/Services/AdminAppearanceService.php` — removed redundant `&& is_array($a->custom_vars)`.
- `tests/Feature/Admin/PageBlockManagementTest.php` — delete-state assertions updated to the `data-confirm` + `aria-label` markup.

### Impact
- DB: none · Routes: none · Frontend: none (admin render unchanged) · Packages: none.
- Security: none (the `data-confirm` sanitization in the service is untouched).

### Verification
- PHPStan: **0 errors** (was 3).
- Full suite: **627 / 627 pass, 3207 assertions** (was 626/627).
- Baseline gate restored — Phase 6 "no regression vs 627" is now measurable.

### Rollback
- `git revert <commit>`; or restore the three files from `develop`.

### Next
- **A2 — Carry-over debt: pagination + FormRequest** (`BuilderPatternController@index` → `paginate(20)`; `PageBlockController` inline validation → FormRequest). No gate.

### Follow-up flagged (not actioned)
- EN/ID copy inconsistency in the page-edit delete section (block-delete confirm is Indonesian, page-delete confirm is English). User-facing copy → owner's call. Noted in handoff §11.
