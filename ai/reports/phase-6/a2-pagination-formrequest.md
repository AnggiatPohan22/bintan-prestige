# A2 — Carry-over Debt: Pagination + FormRequest

> **Task:** A2 — Patterns API pagination + `PageBlockController` FormRequest extraction
> **Status:** ✅ DONE — 2026-06-29
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** none (no schema, no renames, no packages)
> **Skills:** backend-skill

---

## Summary

Last two Phase-5 carry-over items, clearing Stage A's deck before the schema work.

- **Patterns API pagination** — `BuilderPatternController@index` loaded **all**
  patterns with `->get()`. Now `->paginate(20)`. The builder client reads
  `json.patterns` as a flat array, so `patterns` stays a flat array and
  pagination state is exposed separately under `meta` (backward-compatible — the
  Alpine client ignores unknown keys).
- **FormRequest extraction** — `PageBlockController` had three inline
  `$request->validate(...)` calls (`store`, `update`, `reorder`). Extracted to
  dedicated FormRequests (AGENTS.md §7: validation belongs in Form Requests).

---

## Report (AGENTS.md §11)

### Changed
- `app/Http/Controllers/Admin/BuilderPatternController.php` — `index()` now paginates (`paginate(20)`); response is `{ patterns: [...flat], meta: {current_page,last_page,per_page,total} }`.
- `app/Http/Requests/Admin/StorePageBlockRequest.php` — **new** (`block_type` required + `Rule::in(PageBlockController::blockTypes())`, `label` nullable/string/max:255).
- `app/Http/Requests/Admin/UpdatePageBlockRequest.php` — **new** (`label`, `data` array, `parent_block_id` integer).
- `app/Http/Requests/Admin/ReorderPageBlockRequest.php` — **new** (`ids` required array, `ids.*` integer).
- `app/Http/Controllers/Admin/PageBlockController.php` — `store`/`update`/`reorder` now type-hint the FormRequests; inline `validate()` blocks removed; dropped the now-unused `Illuminate\Http\Request` import. `blockTypes()` static kept (still used by `PageController` + `Frontend\PageController`).
- `tests/Feature/Admin/BuilderPatternTest.php` — **new** `test_pattern_index_is_paginated_at_twenty_per_page` (22 patterns → 20 on page 1 + `meta`, 2 on page 2; asserts `patterns` stays flat).

### Impact
- DB: none · Routes: none · Packages: none.
- Frontend: patterns panel response gains a `meta` key; existing flat `patterns` array unchanged → no builder JS change required. The "complete unique set" reorder rule still lives in `PageBlockService` (unchanged); FormRequest only guards shape.
- Security: validation now centralised in FormRequests; behaviour identical (existing `test_block_store_rejects_an_unknown_block_type` + reorder error tests still green, confirming the rules still fire).

### Verification
- Full suite: **634 / 634 pass**, 3274 assertions.
- PHPStan level 5: **0 errors**.

### Rollback
- `git revert <commit>`; or restore `BuilderPatternController` / `PageBlockController` and delete the three new request classes + the pagination test.

### Next
- **Milestone 1 exit pending only A3 + A4.** A3 (polymorphic `page_blocks`, §3.2 = Option A) is **⚠️ schema-gated** — needs an explicit owner "approved" before the morph migration is written. A4 (`config/field-types.php` catalog scaffold) has no gate.
