# A0 — Architecture Decision Record (Phase 6)

> **Task:** A0 — Architecture Decision Record
> **Status:** ✅ DONE — 2026-06-29
> **Branch:** `feature/phase-6-a1-debt-clearing` (docs committed alongside A1 setup)
> **Authority:** AGENTS.md §2 / §9 · Grand Plan §3 · Handoff §3

---

## Purpose

A0's only job is to **lock the architecture decisions §3.1–§3.6** of the Phase 6
Grand Plan, create the living handoff, and confirm "zero new packages" — before a
single table is created. No code beyond documentation is produced in A0.

---

## Decisions (locked)

| # | Decision | Outcome | When |
|---|----------|---------|------|
| §3.1 | Field-value storage model | **HYBRID** — primary JSON in `content_entries.data` + `content_entry_index` query sidecar for `is_filterable` fields | Locked via grand-plan approval 2026-06-29 |
| §3.2 | Entry body model | **OPTION A** — polymorphic `page_blocks` (`blockable_type`/`blockable_id`), backfill existing rows to `Page`; entries reuse the Phase 5 builder + `BuilderTreeSanitizer` unchanged. No `content_entries.body` column. | **Owner approved 2026-06-29** |
| §3.3 | Field type registry | **CODE catalog + DB instances** — `config/field-types.php` mirrors `config/blocks.php` | Locked |
| §3.4 | Routing | **Explicit per-type `route_base`, NO global catch-all** — reserved-prefix guard in Content Type FormRequest | Locked |
| §3.5 | Template resolution | **Theme hierarchy** `content/{type}/single` → `content/single-{type}` → `content/single` generic fallback; reuse existing render path | Locked |
| §3.6 | Packages | **ZERO new packages** — repeater/relationship/conditional logic on Alpine.js + `@alpinejs/sort` (already installed Phase 5) | Confirmed |

### Consequences of §3.2 = Option A

- **A3** (polymorphic `page_blocks` migration + backfill) and **B9** (entry body
  via builder) are now **active** tasks, no longer conditional.
- **A3 still requires explicit schema approval at its own task** (AGENTS.md §9) —
  it alters an existing, protected-adjacent table. The morph migration is **not**
  written until the owner says "approved" at A3.
- `content_entries` will **not** have a `body` JSON column (Option B rejected).
  Handoff §4 data model updated accordingly.
- `BuilderTreeSanitizer` reused unchanged; the fixed 20:60:20 builder shell is untouched.

---

## Preserved constraints (handoff §3.7)

1. Products / Bookings / Page Sections / Global Settings stay **protected** — linked via `relationship` fields only, never rebuilt.
2. `BuilderTreeSanitizer` reused unchanged.
3. Fixed 20:60:20 builder layout untouched.
4. PHPStan level 5 / 0 errors / no baseline = hard gate.
5. No queries in Blade — entry/field/loop data prepared in `PageRenderData` / services.

---

## Report (AGENTS.md §11)

### Changed
- `ai/reports/phase-6/phase-6-progress-handoff.md` — §3.2 marked Option A ✅ ACCEPTED; §1 A0 → ✅ DONE, A1 → 🔨 IN PROGRESS; §4 removed `body` column note; A3/B9 relabelled "now active".
- `ai/reports/phase-6/a0-architecture-decisions.md` — this report (new).

### Impact
- DB: none (A0 is decisions/docs only). §3.2 = A defers a `page_blocks` schema change to A3 (gated).
- Routes: none.
- Frontend: none.
- Security: none.

### Rollback
- Revert the handoff edits + delete this file: `git checkout -- ai/reports/phase-6/phase-6-progress-handoff.md && rm ai/reports/phase-6/a0-architecture-decisions.md`

### Next
- **A1 — Carry-over debt: TD-04 (sanitize widget text) + TD-05 (move `FormDefinition::find()` out of Blade).** No gate.
