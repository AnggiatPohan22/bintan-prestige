# Task C2 — Phase 7 Performance Audit

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Release gate — automated performance fences (no production code change).

---

## Task: C2 — Warm-latency + N+1 fences

Grand plan §6 sets three C2 requirements:

1. Localized routes render **≤300 ms warm**.
2. **No per-attribute translation queries** on listing/detail paths.
3. Switcher + hreflang add **no queries per row**.

Every requirement is now covered by an automated regression fence rather than a
one-shot benchmark, so a future refactor can't silently regress a gate signal.

### Changed

- `tests/Feature/Phase7/C2PerformanceAuditTest.php` — **10 tests**:
  - **Warm-latency budget (6 routes):** `/`, `/id`, `/products`,
    `/id/products`, `/pages/{slug}`, `/id/pages/{slug}`. Each seeds real
    catalog + a translated page; issues a warm-up request; then measures the
    second GET with `hrtime()`. Fails if a route exceeds **300 ms**.
  - **N+1 fence — products index scales flat:** measures translation queries
    with 6 products; scales to 24 products; asserts the count is **identical**.
    A true N+1 would multiply linearly with catalog size — this test would fail
    hard. Absolute upper bound also asserted at ≤5 (products + categories +
    destinations on products via `frontendListingReady`, + categories +
    destinations for the filter sidebar).
  - **N+1 fence — home ≤6 translation queries** (home products, categories,
    destinations, page sections, header menu items, footer menu items).
  - **Switcher + hreflang do not fanout queries per link:** total query count
    remains bounded even with multiple locales rendered.
  - **Sitemap groups siblings in PHP:** `<url>` entries for 20 translated pages
    (40 rows) fire ≤1 `from "pages"` query — no per-row alternate lookup.

### Design notes
- **Fences over benchmarks.** A one-off latency number ages badly; a fence
  runs on every push. If translation eager-loads regress, the "scales flat"
  assertion breaks explicitly with a helpful message.
- **Warm vs cold.** SQLite in-memory + a warm-up GET before measuring — same
  intent as production: hot cache is the interesting number for user-facing
  latency; cold-cache first-hit costs are already covered elsewhere.
- **Bounds tuned to eager-load groups.** The product-listing bound of 5 = one
  batch per relation cluster. This matches the intentional trade documented
  in B6 (bounded 22→25 query test), and is verified stable across catalog sizes.

### Impact
- Production code: **unchanged** — this is a regression-fence test suite only.
- Test suite: +10 tests (~3.5 s).
- CI signal: any future N+1 or slowdown on the six locale routes fails a
  named test.

### Verification
- `C2PerformanceAuditTest` — **10/10 pass**.
- Full suite — **977/977 pass** (967 + 10 C2; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <C2 commit>`. Test-only change; no code to roll back.

### Next
- **C3 — Functional smoke test:** locale route matrix (default unprefixed and
  every prefixed variant), fallback semantics (untranslated page 404 only in
  that locale), draft-in-one-locale still 404s, admin guards still gate the
  new translate/preview endpoints, and legacy URLs render byte-identical.
