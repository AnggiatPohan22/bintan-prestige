# Admin UI Audit — Handoff & Task Log

**Branch:** feature/ui-ux-design-system
**Date:** 2026-06-23
**Auditor:** Claude Opus 4.8
**Scope:** Admin Blade views (`resources/views/backend/` + `admin/users/`) vs DESIGN-SYSTEM.md

---

## Design Direction Decision (confirmed 2026-06-23)

**Admin aesthetic: Dark sidebar + Light content area**
- Content base: `bg-slate-50` / cards: `bg-white border-slate-200`
- Sidebar: `bg-slate-900` (dark, unchanged)
- Primary action button: `admin-btn-primary` (indigo-600) — single standard
- Focus rings: `focus:ring-indigo-500` / `focus:ring-4 focus:ring-indigo-100`
- DESIGN-SYSTEM.md §3 will be updated to document the ACTUAL design

**NOT migrating to full dark admin** — the existing light-content admin is intentional and consistent. Only fixing genuine inconsistencies.

---

## Fix Scope — Real Issues Only

| # | Step | Files | Risk | Status |
|---|------|-------|------|--------|
| 1 | Setup report structure | `ai/reports/UIUX/` | None | ✅ Done |
| 2 | Badge hardcoded `style=` overrides | `pages/index`, `pages/edit` | Low | ✅ Done |
| 3 | Missing `focus:ring` on close/dismiss buttons | `menus/edit`, `media/index` | Low | ✅ Done |
| 4 | Missing `focus:ring` on JS icon-picker buttons | `products/partials/highlights` | Low | ✅ Done |
| 5 | Wrong checkbox focus ring: emerald → indigo | `categories/form`, `destinations/form` | Low | ✅ Done |
| 6 | `btn-primary`/`btn-secondary` → `admin-btn-primary`/`admin-btn-secondary` in small files | `categories/form`, `destinations/form`, `faqs/form`, `dashboard` | Medium | ✅ Done |
| 7 | `form-input`/`form-label`/`form-textarea` → `admin-input`/`admin-form-label`/`admin-textarea` in legacy form files | `categories/form`, `destinations/form`, `faqs/form` | Medium | ✅ Done |
| 8 | `settings/global-assets.blade.php` — 14× btn-primary + $inputClass replace | `settings/global-assets` | High | ✅ Done |
| 9 | Update DESIGN-SYSTEM.md §3 to document actual admin design | `DESIGN-SYSTEM.md` | None | ✅ Done |

---

## Files Changed Per Step

### Step 2 — Badge style overrides
- `resources/views/backend/pages/index.blade.php` — removed `style="background-color:#fef9c3;color:#854d0e;"` from Scheduled badge (line 84)
- `resources/views/backend/pages/edit.blade.php` — removed same override (line 37); removed `style="color:#854d0e;"` from scheduled-date span (line 51)

### Step 3 — Missing focus:ring on close buttons
- `resources/views/backend/menus/edit.blade.php` — added `focus:outline-none focus:ring-2 focus:ring-indigo-500` to drawer × button (line 164)
- `resources/views/backend/media/index.blade.php` — added same to slide-over × button (line 81)

### Step 4 — JS icon-picker focus:ring
- `resources/views/backend/products/partials/highlights.blade.php` — added `focus:outline-none focus:ring-2 focus:ring-indigo-500` to dynamically created icon-picker buttons (line 316)

### Step 5 — Checkbox focus ring: emerald → indigo
- `resources/views/backend/categories/form.blade.php` — `text-emerald-600 focus:ring-emerald-500` → `text-indigo-600 focus:ring-indigo-500` (line 84)
- `resources/views/backend/destinations/form.blade.php` — same fix (line 115)

### Step 6 — btn-primary → admin-btn-primary
- `resources/views/backend/categories/form.blade.php` — `btn-primary` → `admin-btn-primary`, `btn-secondary` → `admin-btn-secondary`
- `resources/views/backend/destinations/form.blade.php` — same
- `resources/views/backend/faqs/form.blade.php` — same
- `resources/views/backend/dashboard.blade.php` — `btn-primary` → `admin-btn-primary`

### Step 7 — form-input → admin-input in legacy files
- `resources/views/backend/categories/form.blade.php` — `form-label` → `admin-form-label`, `form-input` → `admin-input`, `form-textarea` → `admin-textarea`, `form-error` border class removed (handled by `@error`)
- `resources/views/backend/destinations/form.blade.php` — same
- `resources/views/backend/faqs/form.blade.php` — removed `$inputClass` PHP block; replaced inline `{{ $inputClass }}` with `admin-input`; replaced `form-label` with `admin-form-label`

### Step 8 — settings/global-assets
- Removed `$inputClass` PHP variable block; replaced all `{{ $inputClass }}` occurrences with `admin-input`; replaced 14× `btn-primary` with `admin-btn-primary`; replaced `btn-secondary` with `admin-btn-secondary`

### Step 9 — DESIGN-SYSTEM.md §3
- Rewrote §3 (Backend Design System) to accurately document the actual light-content admin design: slate palette, indigo primary, dark sidebar.

---

## Not Changed (Out of Scope)

- `text-slate-*` text color classes — these are correct for the light-content admin
- `bg-white` / `bg-slate-50` on cards/panels — these are correct for the light-content admin
- `resources/views/backend/page-sections/edit.blade.php` `$inputClass` with emerald focus — covered by Step 7 pattern but not tackled in this session (see Next Tasks)
- `resources/views/backend/products/form.blade.php` `$inputClass` — same
- Chart.js hardcoded hex in analytics dashboard (inside `<script>`, non-CSS)
- Historical `ai/reports/` files referencing old skill filenames

---

## Next Tasks (not done yet)

| Priority | File | Issue |
|----------|------|-------|
| ✅ Done | `resources/views/backend/page-sections/edit.blade.php` | `$inputClass` removed; `form-label`→`admin-form-label`; textarea→`admin-textarea` |
| ✅ Done | `resources/views/backend/products/form.blade.php` | Dead `@php` block removed; `form-error` on `<p>` → `mt-1 text-sm text-red-600` |
| ✅ Done | `resources/views/backend/products/partials/search-booking.blade.php` | `form-label/input/textarea`→`admin-*`; toggle `emerald`→`indigo` |
| Low | `resources/views/admin/users/create.blade.php` | Checkbox: `text-indigo-600 focus:ring-indigo-500` — already correct, skip |
| Low | `resources/views/backend/products/partials/features.blade.php` | Icon item rows use `bg-slate-50` (acceptable for light theme) |

---

## Impact Summary

- DB: none
- Routes: none
- Backend logic: none
- Frontend (public): none
- CSS: No new classes added. All fixes use existing `admin-btn-primary`, `admin-input`, `admin-form-label`, `admin-textarea` classes already defined in `admin.css`
- DESIGN-SYSTEM.md: §3 corrected to document actual admin design
