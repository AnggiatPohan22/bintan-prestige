# B-LAYOUT — Builder Layout & Canvas Fix

**Date:** 2026-06-22
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Commits:** `3529e15` (partials + grid) · `62085f4` (device mode) · `1f5489e` (docs) · (+ `sandbox` follow-up)
**Status:** COMPLETE ✓
**Supersedes structure in:** B1 (file layout) · B2 (DnD file path)

---

## Problem (owner-reported)

1. A **white frame** appeared below the canvas; all three panels shrank and the
   layout tracked the browser instead of filling the viewport.
2. The **block canvas** did not render correctly across desktop / tablet / mobile.

## Root causes (verified)

| # | Cause | Evidence (pre-fix) |
|---|---|---|
| 1 | Panels were `position:absolute` drawers → `lg:static` with hardcoded px widths (`w-72` / `w-80`), not a proportional grid | `index.blade.php` (75f5d02) lines 462, 733 |
| 2 | Canvas rendered into a JS-measured `bg-white` "stage" card (`stageStyle()` ← `canvasH` via ResizeObserver) → the **white frame** when measurement was stale | lines 266-278, 57-62 |
| 3 | Iframe sized by `transform:scale()` where `scale = availW / baseWidth` → the rendered site **shrank with panel width** (violated "frame size must not affect the site") | `frameStyle()` lines 282-286 |

## Fix

- **Layout:** full-viewport flex column; 3-panel row is `flex flex-1 min-h-0 overflow-hidden`. Panels are a proportional grid **LEFT 20% : CANVAS 60% (flex-1) : RIGHT 20%** (`lg:w-[20%]`). Mobile keeps the overlay-drawer behaviour.
- **Canvas / device mode:** removed the white stage card, the `transform:scale`, and all JS measurement (`measureCanvas`, `availW/H`, `scale`, `stageStyle`, `frameStyle`, `canvasW/H`, ResizeObserver). The iframe now fills the visible area via CSS `h-full`; device width is a plain `max-width` on a centered wrapper. Added `deviceMaxWidth()` → `{ desktop:'100%', tablet:'768px', mobile:'375px' }`. The iframe is its own scroll container (hero → footer). Added `sandbox="allow-same-origin allow-scripts allow-forms"`.
- **Structure:** split the 765-line `index.blade.php` into a thin wrapper + 5 partials.

## Files

| File | Change |
|---|---|
| `resources/views/backend/builder/index.blade.php` | Reduced to wrapper (grid + `@include`s) |
| `resources/views/backend/builder/partials/topbar.blade.php` | New — top bar |
| `resources/views/backend/builder/partials/panel-left.blade.php` | New — inserter + tree (`lg:w-[20%]`) |
| `resources/views/backend/builder/partials/canvas.blade.php` | New — `max-width` device iframe + overlays + `sandbox` |
| `resources/views/backend/builder/partials/panel-right.blade.php` | New — settings placeholder (`lg:w-[20%]`) |
| `resources/views/backend/builder/partials/alpine-component.blade.php` | New — Alpine store (sizing fns removed, `deviceMaxWidth` added) |
| `docs/visual-builder-structure.md` | New — core structure reference |

No controller, route, schema, auth, or package changes.

## Key CSS contract (must hold)

`body h-screen overflow-hidden` → root `flex h-screen flex-col` → row `flex flex-1 min-h-0 overflow-hidden` → panels `lg:flex-none lg:w-[20%]` + canvas `flex-1`; each scroll region owns its `overflow`. **Never** reintroduce a panel-width-driven `transform:scale` on the iframe. Full detail in `docs/visual-builder-structure.md`.

## Grid ratio decision

Owner chose **20 : 60 : 20** (over 15:70:15 and the original 25:55:20).

## Verification

- `php artisan view:cache` → all partials compile, 0 errors
- `lg:w-[20%]` present in built CSS bundle (`npm run build`)
- Builder route intact; isolation throwaway route/view removed
- No dangling references to removed sizing functions
- Visual checklist (no white frame, 20:60:20 at 1280/1440/1920, device toggles width-only, independent scroll) — owner manual verification

## Known follow-ups

- B3 — right-panel block settings (placeholder now cleanly scoped in `panel-right.blade.php`)
- B7 — per-breakpoint render verification (device toggles already implemented here)
