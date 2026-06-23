# B7 — Responsive & Preview Controls

**Date:** 2026-06-23
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Status:** COMPLETE ✓

---

## Deliverable

B7 completes the responsive preview loop: device toggles already existed
(since B-LAYOUT fix), so B7 adds the two missing UX pieces that make them
actually useful in practice:

1. **Canvas status bar** — shows the active device name and viewport width
   below the live preview iframe so there is never any ambiguity about what
   size is being previewed.

2. **Block List per-breakpoint hide indicator** — when a block has
   `hide_desktop`, `hide_tablet`, or `hide_mobile` set in the Advanced tab
   (added in B3), the row is dimmed and shows a small `eye-slash · hidden`
   badge in the Block List whenever that device's preview mode is active.
   This closes the feedback loop: the user can see at a glance which blocks
   are invisible on the current device without opening each block's settings.

---

## What already existed (from B-LAYOUT + B3)

| Item | Status before B7 |
|---|---|
| Desktop / Tablet / Mobile toggle buttons in topbar | ✓ present |
| `previewMode` Alpine state + `deviceMaxWidth()` | ✓ present |
| CSS-only `max-width` device sizing on iframe | ✓ present — no JS scale |
| `hide_desktop / hide_tablet / hide_mobile` Advanced tab fields | ✓ present (B3) |
| `BlockStyle::responsiveClasses()` + Tailwind safelist | ✓ present (B3) |

---

## Changes

### `partials/alpine-component.blade.php`

Two new helper methods added adjacent to `deviceMaxWidth()`:

```js
previewModeLabel()     // → 'Desktop · Fluid width' | 'Tablet · 768 px' | 'Mobile · 375 px'
isHiddenOnDevice(node) // → true when node.data.hide_{previewMode} is truthy
```

`isHiddenOnDevice` reads the `data.hide_desktop / hide_tablet / hide_mobile`
fields that the B3 Advanced tab writes. It gracefully returns `false` for any
block that does not have those fields (i.e. non-Group/Columns blocks).

### `partials/canvas.blade.php`

A slim `h-7 shrink-0` status bar added below the canvas viewport and above
the existing overlays. It shows the Font Awesome device icon and the
`previewModeLabel()` string. The bar is `flex-none` so it does not participate
in the flex fill and does not affect the iframe's height.

```
┌─────────────────────── canvas column ────────────────────────┐
│  [iframe — h-full fills available space]                      │
├──────────────────────────────────────────────────────────────┤
│  🖥 Desktop · Fluid width          ← new status bar (h-7)    │
└──────────────────────────────────────────────────────────────┘
   (loading / empty / error overlays sit above, absolute-pinned)
```

### `partials/panel-left.blade.php`

Two additive changes inside the `x-for` tree row:

1. Added `'opacity-50': isHiddenOnDevice(row.node) && dragCid !== row.node._cid`
   to the `:class` object — the whole row dims when the block is hidden on
   the current device. The `dragCid` guard keeps the drag ghost at `opacity-40`
   (the existing value) and does not conflict.

2. New `<span>` badge inserted between the label and the "drop inside" badge:
   ```html
   <span x-show="isHiddenOnDevice(row.node) && dragCid !== row.node._cid"
         class="… text-[9px] text-slate-500 border border-slate-600 …">
       <i class="fa-solid fa-eye-slash"></i> hidden
   </span>
   ```
   The badge is `pointer-events-none` so it does not interfere with click
   selection. It only appears when `isHiddenOnDevice` is true, so blocks
   without the Advanced hide flags are completely unaffected.

---

## Files changed

| File | Change |
|---|---|
| `resources/views/backend/builder/partials/alpine-component.blade.php` | Added `previewModeLabel()` + `isHiddenOnDevice()` |
| `resources/views/backend/builder/partials/canvas.blade.php` | Added viewport status bar |
| `resources/views/backend/builder/partials/panel-left.blade.php` | Added row dim + hidden badge |

**Not changed:** topbar, panel-right, controller, routes, DB, config, tailwind.

---

## Impact

- **DB:** none
- **Routes:** none
- **Frontend (public):** none — changes are builder-admin only
- **Layout shell:** untouched — 20:60:20 grid and CSS-only device sizing intact
- **B4/B5/B6:** unaffected — inline editing, patterns, templates all unchanged
- **Security:** read-only client data access (`node.data`), no new input paths

---

## Verification

- `php artisan view:cache` → compiled successfully, 0 errors
- Alpine JS block parsed via `new Function()` → no syntax errors
- `php artisan test --filter="Builder|PageBlock|Block|Template|Pattern"` →
  **87 tests / 726 assertions / PASS** (no regressions)

---

## Manual QA checklist

1. Open the Visual Builder on any page.
2. Confirm the status bar reads **"Desktop · Fluid width"** by default.
3. Click Tablet → status bar changes to **"Tablet · 768 px"**.
4. Click Mobile → status bar changes to **"Mobile · 375 px"**.
5. Select a Group/Section block → open Settings → Advanced tab.
6. Enable **Hide on Mobile** → switch to Mobile preview.
7. Confirm the block row in the Block List becomes dimmed and shows the
   `eye-slash · hidden` badge.
8. Switch back to Desktop → badge disappears, row is normal.
9. Confirm the iframe at Tablet (768 px) hides the block (Tailwind
   `md:hidden` or combined class applied by `BlockStyle`).

---

## Rollback

`git revert HEAD` — removes canvas status bar, tree badge, and Alpine helpers.
No migration or route change to undo.
