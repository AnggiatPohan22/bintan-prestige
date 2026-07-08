# A3 — Polymorphic `page_blocks` (dual-rail, Strategy 1)

> **Task:** A3 — make `page_blocks` polymorphic (§3.2 = Option A)
> **Status:** ✅ DONE — 2026-06-30
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ schema change to existing table — **APPROVED by owner 2026-06-30** (Strategy 1, dual-rail)
> **Skills:** database-architecture-skill · backend-skill

---

## Decision recap

Option A (shared `page_blocks` via morph) was locked at A0. At A3 the owner chose
**Strategy 1 — dual-rail** over a full morph refactor:

- **Pages keep `page_id` + `hasMany`** → the Phase 5 builder, `BuilderTreeSanitizer`,
  revisions, and all existing queries are **untouched** (handoff §3.7).
- **Entries (B9) use the morph** (`blockable_type`/`blockable_id`) with a NULL
  `page_id`, which is why `page_id` is relaxed to nullable.
- Existing rows are backfilled so the morph is uniform across the whole table.

## What changed in the schema

`page_blocks`:
- `+ blockable_type` (string, nullable) — full class name, matching the app's
  existing morph convention (no morph map; cf. `MenuItem::linkable`).
- `+ blockable_id` (unsigned big int, nullable).
- `+ index(blockable_type, blockable_id)`.
- `page_id` → **nullable** (FK + `cascadeOnDelete` preserved).
- **Backfill:** every existing row → `blockable_type = App\Models\Page`,
  `blockable_id = page_id`.

Additive + reversible: `down()` drops the new columns/index and restores
`page_id NOT NULL` (safe — page-owned rows always retain `page_id`).

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_06_30_000001_add_blockable_morph_to_page_blocks_table.php` — **new** migration (columns + index + nullable page_id + backfill).
- `app/Models/PageBlock.php` — added `blockable()` `MorphTo`; `blockable_type`/`blockable_id` to `$fillable`; `blockable_id` cast to integer.
- `tests/Feature/Phase6/A3PolymorphicPageBlocksTest.php` — **new**, 4 tests (columns exist; page_id nullable for morph-owned blocks; `blockable` resolves the owner; existing `Page::blocks()` hasMany rail unchanged).

### Impact
- DB: **migration added** — `page_blocks` altered (see above). Applied to dev DB; backfill verified (30 rows, 0 null types, 0 mismatches).
- Routes: none · Frontend: none · Packages: none.
- Builder: **zero change** — pages still use `page_id`/`hasMany`. Phase 5 regression suite stays green.
- Security: none (no new input surface; columns are internal).

### Verification
- A3 tests: 4/4 pass (on sqlite via `RefreshDatabase` — proves the migration runs clean).
- Dev MySQL: `migrate` ✅; backfill `total=30 null_type=0 mismatch=0`; **rollback `down()` ✅ then re-`migrate` ✅** (rollback path proven).
- Full suite: **638 / 638 pass**, 3281 assertions. PHPStan level 5: **0 errors**.

### Rollback
- `php artisan migrate:rollback --step=1` (drops the two columns + index, restores `page_id NOT NULL`); or `git revert <commit>` + rollback.

### Next
- **A4 — `config/field-types.php` catalog scaffold** (code-only, no gate) — completes Milestone 1.
- B9 (open the builder on a ContentEntry, save tree to polymorphic blocks, cascade-on-entry-delete) builds on this morph later in Stage B.
