# Step 19.5 Handoff — Remaining Modules Blade Token Cleanup
**Tanggal:** 2026-06-26
**Status:** ✅ Complete
**Commit:** `5014f9f`
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (46 Blade files + 1 CSS file)
**Phase:** E.19.5 — All remaining admin modules token-aware

---

## Tujuan

Replace semua hardcoded `bg-white`/`bg-slate-*`/`text-slate-*`/`border-slate-*` Tailwind classes di sisa backend Blade files (setelah Step 19.4 cover pages + users) dengan `admin-*` utility classes yang consume CSS vars per mode.

---

## Scope — Module yang Diaudit

| Module | Files | Hits Before | Hits After |
|---|---|---|---|
| `backend/categories/` | 2 | 12 | 0 |
| `backend/destinations/` | 4 | 17 | 0 |
| `backend/media/` | 5 | 40 | 0 |
| `backend/menus/` | 3 | 51 | 0 |
| `backend/page-sections/` | 3 | 46 | 0 |
| `backend/plugins/` | 2 | 46 | 0 |
| `backend/seo/redirects/` | 3 | 5 | 0 |
| `backend/audit-logs/` | 1 | 16 | 0 |
| `backend/analytics/` | 1 | 5 | 0 |
| `backend/contact-forms/` | 4 | 15 | 0 |
| `backend/faqs/` | 2 | 7 | 0 |
| `backend/form-submissions/` | 1 | 6 | 0 |
| `backend/settings/global-assets.blade.php` | 1 | 242 | 0 |
| `backend/builder/` (6 files) | 6 | 127 | 0 |
| `backend/themes/` (5 files) | 5 | 65 | 0 |
| `backend/partials/navbar.blade.php` | 1 | 4 | 0 |
| **TOTAL** | **46** | **~750** | **0** |

**Excluded (by design):**
- `backend/pages/` — done in Step 19.4
- `backend/products/` — done in Step 19.3/pre-19
- `backend/settings/appearance/` — reserved for Step 19.6 full rewrite

---

## Extended Replacement Map (vs Step 19.4)

| Hardcoded | Replaced with | Notes |
|---|---|---|
| `text-slate-100` | `text-admin-primary` | — |
| `text-slate-200` | `text-admin-secondary` | **New in 19.5** |
| `text-slate-300` | `text-admin-secondary` | **New in 19.5** |
| `text-slate-400` | `text-admin-secondary` | — |
| `text-slate-500/600` | `text-admin-secondary` | — |
| `text-slate-700/800/900` | `text-admin-primary` | — |
| `border-slate-100/200/300` | `border-admin` | — |
| `border-slate-500` | `border-admin` | **New in 19.5** |
| `border-slate-600` | `border-admin` | **New in 19.5** |
| `border-slate-700/800` | `border-admin` | — |
| `bg-white` | `bg-admin-card` | — |
| `bg-slate-50/100/200` | `bg-admin-card` | — |
| `bg-slate-300` | `bg-admin-card` | **New in 19.5** |
| `bg-slate-700` | `bg-admin-card` | **New in 19.5** (except Alpine active-state, see below) |
| `bg-slate-800/900/950` | `bg-admin-card` | — |
| `hover:bg-slate-700 hover:text-slate-200` | `hover:opacity-75` | compound first |
| `hover:bg-slate-*` | `hover:opacity-75` | — |
| `hover:text-slate-*` | `hover:text-admin-secondary` | **New in 19.5** |
| `hover:border-slate-*` | `hover:border-admin` | **New in 19.5** |

---

## Special Case — Alpine `:class` Active Toggle States

**Problem:** `bg-slate-700` di builder/themes Alpine `:class` bindings dipakai sebagai **active selected button** (bukan container background). Replacing dengan `bg-admin-card` akan membuat active state blend ke background.

**Solution:** tambah utility `.bg-admin-surface { background: var(--admin-bg-hover); }` di admin.css, lalu manual-fix 9 baris:

### Files fixed manually (9 lines):

**`builder/partials/topbar.blade.php`** (5 lines):
```blade
:class="leftCollapsed ? '...' : 'bg-admin-surface text-white'"
:class="rightCollapsed ? '...' : 'bg-admin-surface text-white'"
:class="previewMode === 'desktop' ? 'bg-admin-surface text-white' : '...'"
:class="previewMode === 'tablet' ? 'bg-admin-surface text-white' : '...'"
:class="previewMode === 'mobile' ? 'bg-admin-surface text-white' : '...'"
```

**`builder/partials/panel-right.blade.php`** (1 line):
```blade
:class="activeFieldTab === t ? 'bg-admin-surface text-white shadow' : '...'"
```

**`themes/customize.blade.php`** (3 lines):
```blade
:class="viewport === 'desktop' ? 'bg-admin-surface text-white' : 'bg-admin-card ...'"
```

---

## New Utilities Added — `resources/css/admin.css`

```css
.bg-admin-surface     { background: var(--admin-bg-hover); }
.hover\:text-admin-secondary:hover { color: var(--admin-text-secondary); }
```

`bg-admin-surface` = slightly elevated surface (`--admin-bg-hover`) — semantically correct for "selected/active" state on toggle buttons. In dark mode ≈ `#334155`; in light mode ≈ `#F3F4F6`.

---

## Verification (Live Browser, Dual Mode)

### Dark mode (DB user `ui_mode = 'dark'`)
- `/admin/categories` — dark cards, cobalt blue button, zero white leak ✅
- `/admin/destinations` — dark table, token-aware badges ✅
- `/admin/settings/global-assets` — entire 1468-line settings file dark coherent ✅
- `/admin/media` — dark grid, moon icon visible in topbar ✅

### Light mode (DB user `ui_mode = 'light'`)
- `/admin/categories` — white cards, maroon primary button ✅
- `/admin/menus` — white menu cards, proper token-based text ✅
- `/admin/media` — white background, sun icon in topbar, maroon Upload button ✅

### Zero remaining hits
```bash
grep -rn "bg-white\|bg-slate-\|text-slate-\|border-slate-" resources/views/backend/ \
  --include="*.php" | grep -v "pages/\|products/\|settings/appearance" | wc -l
# → 0
```

---

## Architecture Chain (after 19.5)

All remaining backend modules now follow:
```
Blade → class="text-admin-primary"
      → admin.css .text-admin-primary { color: var(--admin-text-primary); }
      → <style id="admin-appearance-vars"> emits --admin-text-primary per mode
      → config/admin_palettes.php tokens drive the value
```

Single source of truth via tokens. No mode-specific Tailwind class survives in backend Blade (except `settings/appearance/` reserved for 19.6).

---

## Impact

- **DB:** none
- **Routes:** none
- **Frontend (public):** none — all changes are admin-only views
- **Security:** none
- **Performance:** slightly smaller HTML payload (admin-* utilities shorter than slate variants)

---

## Rollback

```bash
git revert 5014f9f
npx vite build
```

---

## What Was NOT Touched

- `layouts/app.blade.php`, `layouts/guest.blade.php`, `layouts/navigation.blade.php` — frontend public layouts, correct as-is
- `layouts/builder.blade.php` — builder full-page layout, has its own mode context
- `backend/settings/appearance/` — Step 19.6 full rewrite

---

## Next Step

→ **Step 19.6** — Customizer v2 UI: mode tabs, 10-section sidebar, live preview, color picker, preset buttons
