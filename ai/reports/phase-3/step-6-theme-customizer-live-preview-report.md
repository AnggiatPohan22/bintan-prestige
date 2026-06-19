# Step 6 Report — Theme Customizer Live Preview

**Branch:** `feature/cms-phase-3-claude`
**Date:** 2026-06-19
**Status:** ✅ DONE

---

## Task: Theme Customizer Live Preview

Add a live preview pane to the Theme Customizer admin UI so that token
changes (colors, typography) reflect instantly in an iframe before saving.

---

## Changed

### Modified

- `resources/views/backend/themes/customize.blade.php`
  - Layout changed from single-column form to two-column (form left ~420 px,
    preview pane right — stacks vertically below `xl` breakpoint)
  - Token inputs rewired from per-input `x-data` to a single shared Alpine
    component `themeCustomizer(tokens, url)` on the outer `<div>`
  - All `x-model` bindings now point to `tokens[cssVar]` in the shared state
  - Added right column: live preview iframe (default: `route('home')`)
  - Preview toolbar: editable URL input + reload button
  - Viewport switcher: Desktop / Tablet (768 px × 0.75 scale) / Mobile (390 px × 0.65 scale)
  - Alpine `$watch` on `tokens` → `pushTokensToPreview()` calls
    `iframe.contentDocument.documentElement.style.setProperty()` for each token
  - `onPreviewLoad()` sets `previewReady = true` and pushes current tokens
  - Cross-origin guard (`try/catch`) so external preview URLs don't throw
  - Script block pushed via `@push('scripts')` into admin layout's `@stack('scripts')`

---

## Impact

- **DB:** none
- **Routes:** none
- **Backend PHP:** none
- **Frontend:** Admin-only change. No public frontend views changed.
- **Security:** none — preview iframe uses `sandbox="allow-same-origin allow-scripts allow-forms"`;
  JS only touches `style.setProperty` on same-origin iframe documents

---

## How It Works

1. Admin opens **Customize** for a theme
2. Right pane loads `route('home')` in a sandboxed iframe
3. Admin edits any color or typography token
4. Alpine's deep `$watch` fires → `pushTokensToPreview()` iterates all tokens
   and calls `root.style.setProperty(cssVar, value)` on the iframe's `:root`
5. The iframe re-renders with the new CSS variable values immediately — no
   page reload needed
6. Viewport switcher scales the iframe to desktop / tablet / mobile widths
7. Admin clicks **Save** to persist to DB; or **Reset** to clear overrides

---

## Test Results

```
Before STEP 6: Tests: 387 passed (STEP 5 baseline)
After  STEP 6: Tests: 387 passed (no regressions, no new test added — UI only)
```

---

## Rollback

```bash
git checkout -- resources/views/backend/themes/customize.blade.php
```

---

## Next

**STEP 7 — Admin UX Polish & Documentation**

Polish the Themes admin area: improve empty states, add inline help text,
theme card layout improvements on the index page, and update developer docs.
