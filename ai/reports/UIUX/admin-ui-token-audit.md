# Admin UI Token Audit Report

**Date:** 2026-06-23
**Branch:** feature/ui-ux-design-system
**Files audited:** 93 (85 backend + 3 admin/users + layouts + guidelines)

---

## Design Direction Clarification

**Initial finding:** The audit sub-agent compared Blade views against DESIGN-SYSTEM.md §3 which specified a full dark admin (bg-gray-900, amber-400 primary). However, the actual admin (`admin.css`) is a **dark sidebar + light content** design. This was a spec mismatch in DESIGN-SYSTEM.md, not a codebase bug.

**Decision (2026-06-23):** Keep the current light-content admin. Fix only genuine inconsistencies (style overrides, class mixing, missing focus states). Update DESIGN-SYSTEM.md §3 to accurately document the actual design.

---

## Actual Admin Design (Documented)

| Aspect | Actual Value |
|--------|-------------|
| Sidebar | `bg-slate-900` (dark) |
| Content base | `bg-slate-50` |
| Cards/panels | `bg-white border-slate-200` |
| Primary button | `admin-btn-primary` = `bg-indigo-600 hover:bg-indigo-700` |
| Secondary button | `admin-btn-secondary` = `bg-white border-slate-300 text-slate-700` |
| Danger button | `admin-btn-danger` = `bg-red-600 hover:bg-red-700` |
| Inputs | `admin-input` = `bg-white border-slate-300 focus:border-indigo-500 focus:ring-indigo-100` |
| Text primary | `text-slate-900` / `text-slate-800` |
| Text secondary | `text-slate-500` / `text-slate-400` |
| Focus rings | `focus:ring-indigo-500` / `focus:ring-4 focus:ring-indigo-100` |

---

## Issues Found (Real — Fixed)

### 1. Hardcoded `style=` overrides on Scheduled badges

**Problem:** `admin-badge-warning` already outputs `bg-amber-100 text-amber-700` correctly, but two files added a redundant inline `style=` that replicated the same colors. This creates a maintenance hazard — the CSS can't be updated without also updating every hardcoded `style=`.

| File | Line | Fix |
|------|------|-----|
| `backend/pages/index.blade.php` | 84 | Removed `style="background-color:#fef9c3;color:#854d0e;"` |
| `backend/pages/edit.blade.php` | 37, 51 | Removed same `style=` overrides |

### 2. Missing `focus:ring` on interactive close/dismiss buttons

**Problem:** Icon-only close buttons (`×`) had hover states but no keyboard focus indicator — WCAG AA failure.

| File | Line | Fix |
|------|------|-----|
| `backend/menus/edit.blade.php` | 164 | Added `focus:outline-none focus:ring-2 focus:ring-indigo-500` |
| `backend/media/index.blade.php` | 81 | Same |

### 3. Missing `focus:ring` on JS-generated icon picker buttons

**Problem:** Icon picker in highlights partial dynamically creates buttons via JS with no focus ring in `item.className`.

| File | Line | Fix |
|------|------|-----|
| `backend/products/partials/highlights.blade.php` | 316 | Added `focus:outline-none focus:ring-2 focus:ring-indigo-500` to JS className string |

### 4. Wrong checkbox focus ring color: emerald → indigo

**Problem:** Two legacy forms used `text-emerald-600 focus:ring-emerald-500` on checkboxes while the system standard (from `admin-input`, `admin-btn-primary`, sidebar) is indigo.

| File | Line | Fix |
|------|------|-----|
| `backend/categories/form.blade.php` | 84 | `text-emerald-600 focus:ring-emerald-500` → `text-indigo-600 focus:ring-indigo-500` |
| `backend/destinations/form.blade.php` | 115 | Same |

### 5. `btn-primary` / `btn-secondary` mixed with `admin-btn-primary` / `admin-btn-secondary`

**Problem:** 5 files used the legacy `.btn-primary` (emerald-600) and `.btn-secondary` CSS classes. The rest of the admin uses `.admin-btn-primary` (indigo-600). Two different primary action colors creates visual inconsistency.

Files fixed: `categories/form`, `destinations/form`, `faqs/form`, `dashboard`
`settings/global-assets` fixed in Step 8 (14 instances).

### 6. Legacy `form-input` / `form-label` / `form-textarea` classes in legacy form files

**Problem:** Three early-built form partials (`categories/form`, `destinations/form`, `faqs/form`) used pre-design-system CSS classes (`form-input`, `form-label`, `form-textarea`) which have slightly different styling (emerald focus ring) than the current standard `admin-input`, `admin-form-label`, `admin-textarea` (indigo focus ring). Also, `faqs/form.blade.php` had a PHP `$inputClass` variable that hardcoded all input styles inline, preventing any CSS update from propagating.

Files fixed: `categories/form`, `destinations/form`, `faqs/form`

### 7. `settings/global-assets.blade.php` — 14× `btn-primary` + `$inputClass`

**Problem:** The largest admin file (1,473 lines, 13 settings tabs) used `$inputClass` PHP variable to hardcode all input styling, and `btn-primary` on every save button (14 instances). Same issue as #5–6 at larger scale.

---

## Not Issues (False Positives from Initial Audit)

These patterns were flagged but are CORRECT for the current light-content admin:

- `bg-white` cards and panels — correct (light-content design)
- `bg-slate-50` section backgrounds — correct
- `text-slate-900`/`text-slate-700` content text — correct
- `admin-badge-warning` class on Scheduled badges — correct (amber-100 / amber-700)
- `block-editor.blade.php` `focus:ring-indigo-500` on move buttons — already correct
- `admin/users/*.blade.php` `text-indigo-600 focus:ring-indigo-500` checkboxes — already correct

---

## Files PASS (correctly styled, no action needed)

`categories/index`, `categories/create`, `categories/edit`,
`destinations/index`, `destinations/create`, `destinations/edit`,
`faqs/index`, `faqs/create`, `faqs/edit`,
`pages/create`, `pages/form`, `pages/index` (after badge fix),
`analytics/dashboard`, `audit-logs/index`,
`contact-forms/*`, `form-submissions/index`,
`seo/robots`, `seo/redirects/*`,
`plugins/index`, `plugins/show`,
`themes/index`, `themes/customize`, `themes/widgets/*`,
`menus/partials/item-row`,
`media/picker` (body slate-50 is correct for standalone iframe),
`page-sections/index`, `page-sections/sections`,
`pages/partials/blocks/*` (all 12 block editors),
`builder/*` (visual builder — Phase 5, not modified),
`admin/users/index`, `admin/users/create`, `admin/users/edit`,
`partials/sidebar`, `partials/navbar`

---

## Remaining Next Steps (not in scope of this session)

| Priority | File | Issue |
|----------|------|-------|
| Medium | `backend/page-sections/edit.blade.php` | `$inputClass` = `bg-white focus:ring-emerald-*` — same pattern as faqs/form, needs same fix |
| Medium | `backend/products/form.blade.php` | `$inputClass` = `bg-white`; accordion headers `bg-slate-50` (actually fine) |
| Medium | `backend/products/partials/search-booking.blade.php` | Legacy `form-input`/`form-label` classes |
