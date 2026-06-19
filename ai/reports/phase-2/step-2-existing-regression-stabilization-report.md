# Phase 2 STEP 2 — Existing Regression Stabilization Report

Date: 2026-06-19
Status: Completed and re-verified

## Scope

Stabilize inherited regressions around shared menu resolution, global navigation/footer expectations, and booking-form behavior before feature hardening.

## Stabilization Performed

- `MenuService` was consolidated around batched menu-tree resolution.
- `AppServiceProvider` sharing and cache behavior were aligned with the shared navigation contract.
- Global navigation/footer tests, Menu Manager tests, and Product booking-form regression coverage were adjusted to characterize the intended existing behavior.
- Cold menu resolution was constrained to one menu query and warm resolution to zero menu queries through per-location cache reuse.

## Historical Verification

- Focused STEP 2 regression: 43 tests passed.
- Full suite at the STEP 2 checkpoint: 260 of 263 tests passed.
- The remaining three failures were the known characterization findings assigned to STEP 3: block ownership, Page SEO propagation, and FAQ Blade querying.

## Current Verification

- Focused STEP 2–8 gate: 63 tests passed, 437 assertions.
- Full STEP 9 suite: 296 tests passed, 2,053 assertions.
- The three deferred failures are covered by the current Page Block and Generic Page tests and no longer reproduce.

## Outcome

STEP 2 is complete. Existing navigation/footer and booking regressions are stable, and the explicitly deferred Page Builder findings were closed in STEP 3 rather than hidden during stabilization.

## Remaining

- Repository-wide Pint still reports pre-existing formatting debt. Phase 2-specific runtime and regression tests are green; formatting debt should be handled in a separately approved cleanup.
