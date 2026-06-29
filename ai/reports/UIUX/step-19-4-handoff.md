# Step 19.4 Handoff — Pages + Users Module Audit & Cleanup
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (touched 24 Blade files)
**Phase:** E.19.4 — Pages/Users Blade templates token-aware

---

## Tujuan

Replace hardcoded `bg-white`/`bg-slate-*`/`text-slate-*`/`border-slate-*` Tailwind classes in dashboard, pages module, and users module Blade templates with `admin-*` utility classes that flow CSS vars per mode.

---

## Files Changed (24 templates + 1 CSS file)

### Users module (3 files, 9 fixes)
- `resources/views/admin/users/index.blade.php` — table row text colors
- `resources/views/admin/users/create.blade.php` — checkbox border + label color + form border
- `resources/views/admin/users/edit.blade.php` — same as create

### Pages module — admin views (4 files)
- `resources/views/backend/pages/index.blade.php` — 10 fixes (table row + empty state)
- `resources/views/backend/pages/form.blade.php` — 10 fixes (section headers + hints + dividers)
- `resources/views/backend/pages/edit.blade.php` — 50 fixes (sticky header, all 6 accordion sections, modals)
- `resources/views/backend/pages/partials/block-editor.blade.php` — 9 fixes (block cards, controls)

### Pages module — block edit partials (16 files)
Batch sed-style replacement covering all hex variants:
- `partials/blocks/button-group.blade.php`
- `partials/blocks/columns.blade.php`
- `partials/blocks/contact-form.blade.php`
- `partials/blocks/faq.blade.php`
- `partials/blocks/gallery.blade.php` (18 hits — most touched)
- `partials/blocks/hero.blade.php` (10 hits)
- `partials/blocks/image.blade.php`
- `partials/blocks/map.blade.php`
- `partials/blocks/pricing-table.blade.php`
- `partials/blocks/products-grid.blade.php`
- `partials/blocks/stats.blade.php`
- `partials/blocks/testimonials.blade.php`
- `partials/blocks/text.blade.php`
- `partials/blocks/tour-itinerary.blade.php`
- `partials/blocks/video-embed.blade.php`
- `partials/blocks/partials/background.blade.php`

### admin.css — bonus discovery (3 fixes)
While verifying, found 3 hardcoded `rgba(30, 41, 59, ...)` slate fallbacks in component CSS that leaked into light mode:
- `.admin-page-header` gradient → use `var(--admin-bg-hover)`
- `.admin-sidebar-toggle` bg → use `var(--admin-bg-card)`
- `.admin-dashboard-hero` gradient → use `var(--admin-bg-hover)`

These belong to Step 19.3 scope but were only visible after Step 19.4's Blade cleanup let the page render fully.

---

## Replacement Map (used consistently across all files)

| Hardcoded | Replaced with |
|---|---|
| `text-slate-100` | `text-admin-primary` |
| `text-slate-400` | `text-admin-secondary` |
| `text-slate-500/600` | `text-admin-secondary` |
| `text-slate-700/800/900` | `text-admin-primary` |
| `border-slate-100/200/300` | `border-admin` |
| `border-slate-700/800` | `border-admin` |
| `bg-white` | `bg-admin-card` |
| `bg-slate-50/100/200` | `bg-admin-card` |
| `bg-slate-800/900` | `bg-admin-card` |
| `hover:bg-slate-700 hover:text-slate-200` | `hover:opacity-75` |

All `admin-*` utility classes consume CSS vars that flip via Step 19.2's `toCssVarsForMode()` emission — automatic mode-aware behavior.

---

## Audit Counts (Before → After)

| Module | Before | After |
|--------|--------|-------|
| Dashboard | 0 | 0 (already clean) |
| Users (3 files) | 9 | 0 |
| Pages (20 files) | 136 | 0 |
| **Total** | **145** | **0** |

---

## Verification (Live Browser, 1400×900)

### Dark mode
- `/admin/pages` — Pages list table all dark, no white card bg leak
- `/admin/pages/1/edit` — All 6 accordion sections (Basic Info, SEO, Content Blocks, Revisions, Danger Zone) dark coherent

### Light mode
- `/admin/users` — Header gradient white→soft-gray (was dark slate before bonus fix)
- `/admin/pages/1/edit` — Sticky header light, accordion sections white, form inputs light, all text dark/readable

Screenshots captured for all 4 views (dashboard auto-validated as it was already clean).

---

## Architecture Note

After Step 19.4, the chain for any module touched is:

```
Blade template uses class="text-admin-primary"
       ↓
admin.css .text-admin-primary { color: var(--admin-text-primary); }
       ↓
<style id="admin-appearance-vars"> emits --admin-text-primary per mode
       ↓
config/admin_palettes.php tokens drive the value
```

No mode-specific Tailwind class survives in pages/users Blade — single source of truth via tokens.

---

## Impact

- **DB:** none
- **Routes:** none
- **Frontend (public):** none (block partials at `partials/blocks/*` are admin-only edit UI; frontend renders via different theme view files)
- **Security:** none
- **Performance:** marginally smaller compiled HTML payload (token utility classes are shorter than hardcoded slate variants)

---

## Rollback

```bash
git revert <step-19-4-commit>
npx vite build
```

The batch `perl -i -pe ...` regex was deterministic and reversible via git revert.

---

## Known Carry-Over to Step 19.5

Remaining modules with hardcoded slate classes (estimated counts via grep):
- `backend/categories/` — ~15 hits
- `backend/destinations/` — ~10 hits
- `backend/media/` — ~30 hits
- `backend/menus/` — ~20 hits
- `backend/page-sections/` — ~25 hits
- `backend/plugins/` — ~10 hits
- `backend/settings/` (excluding appearance which Step 19.6 will replace) — ~60 hits
- `backend/seo/`, `backend/audit-logs/`, `backend/themes/`, `backend/builder/`, etc.

Step 19.5 will run the same batch perl regex across all remaining modules then spot-fix any module-specific class patterns that don't match the standard map.

---

## Next Step

→ **Step 19.5** — Audit + cleanup remaining modules (bookings, settings, media,
   menus, plugins, audit, redirects, etc.)
