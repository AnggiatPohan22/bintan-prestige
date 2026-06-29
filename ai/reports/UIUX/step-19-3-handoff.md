# Step 19.3 Handoff — Component CSS Refactor (Token-Driven)
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (touches every admin-* component class)
**Phase:** E.19.3 — admin.css components now flow per-mode tokens

---

## Tujuan

After Step 19.2 wired per-mode JSON palettes through the service, this step
makes the CSS actually USE them. Replace remaining hardcoded hex in every
`.admin-btn-*`, `.admin-badge-*`, `.admin-table-*`, `.admin-modal-*`,
`.admin-topbar*`, `.admin-alert-*` with `var(--admin-*)` calls. Also delete
the 158-line legacy `html[data-admin-mode="light"] .admin-*` block that was
hardcoding light-mode colors and fighting the token system.

---

## Files Changed

| File | Aksi |
|------|------|
| `resources/css/admin.css` | Refactor components → vars + delete legacy light overrides (-158 lines / +30 lines) |
| `public/build/assets/app-*.css` | Rebuilt |

---

## Component Refactors

### Buttons
| Class | Before | After |
|---|---|---|
| `.admin-btn-icon:hover` | `rgba(255,255,255,0.06)` | `var(--admin-bg-hover)` |
| `.admin-btn-secondary` | `rgba(255,255,255,0.05)` | `var(--admin-btn-secondary-bg, ...)` |
| `.admin-btn-secondary:hover` | `rgba(255,255,255,0.08)` | `var(--admin-bg-hover)` |
| `.admin-btn-success:hover` | `#059669` solid | `var(--admin-success)` + `filter:brightness(.92)` |
| `.admin-btn-danger` | `var(--admin-danger)` hard | `var(--admin-btn-danger-bg, var(--admin-danger))` |
| `.admin-btn-danger:hover` | `#B91C1C` hard | `var(--admin-btn-danger-hover, ...)` + filter |
| `.admin-btn-soft` | `#C4B5FD` + `rgba(124,58,237,*)` (violet hardcoded) | `var(--admin-primary)` family — auto-flips with primary mode token |

### Badges
| Class | Before | After |
|---|---|---|
| `.admin-badge-success` | `rgba(16,185,129,.12)` + `#34D399` | `var(--admin-alert-success-bg)` + `var(--admin-success)` |
| `.admin-badge-warning` | hardcoded | `var(--admin-alert-warning-bg)` + `var(--admin-warning)` |
| `.admin-badge-danger` | hardcoded | `var(--admin-alert-danger-bg)` + `var(--admin-danger)` |
| `.admin-badge-info` | `#22D3EE` hardcoded | `var(--admin-info)` |
| `.admin-badge-neutral` | `rgba(255,255,255,0.06)` (dark-only) | `var(--admin-bg-hover)` |

### Tables
- `.admin-table-header` bg: `rgba(15,23,42,0.6)` → `var(--admin-table-header-bg, ...)`
- `.admin-table-row` border: `rgba(255,255,255,0.04)` → `var(--admin-table-border, ...)`
- `.admin-table-row:hover` bg: `rgba(255,255,255,0.02)` → `var(--admin-table-row-hover, ...)`

### Modal
- `.admin-modal-overlay` bg: `rgba(2,6,23,0.75)` → `var(--admin-modal-overlay, ...)`
- `.admin-modal-panel` bg + border: `rgba(15,23,42,0.97)` + hard → `var(--admin-modal-bg)` + `var(--admin-modal-border, ...)`
- `.admin-modal-footer` bg: `rgba(2,6,23,0.3)` → `var(--admin-bg-hover)`

### Topbar
- `.admin-topbar` bg + border → `var(--admin-topbar-bg)` + `var(--admin-topbar-border)`
- `.admin-topbar__search` bg: `rgba(15,23,42,0.8)` → `var(--admin-bg-input)`
- `.admin-topbar__search span:last-child` bg: `rgba(255,255,255,0.06)` → `var(--admin-bg-hover)`
- `.admin-topbar__icon-button:hover` bg: `rgba(255,255,255,0.06)` → `var(--admin-bg-hover)`
- `.admin-topbar__notification-dot` shadow uses `var(--admin-topbar-bg, ...)` for mode-aware halo

### Alerts
- `.admin-alert-success` bg/border/color → all `var(--admin-alert-success-bg)` + `var(--admin-success)`
- `.admin-alert-danger` bg/border/color → all `var(--admin-alert-danger-bg)` + `var(--admin-danger)`

### Forms
- `.admin-input:is(.error)` ring: `rgba(239,68,68,0.15)` → `var(--admin-alert-danger-bg, ...)`

---

## Legacy Block Removed

Lines 1325–1483 of admin.css contained ~158 lines of hardcoded light-mode
overrides like:

```css
html[data-admin-mode="light"] .admin-topbar {
    background: rgba(255, 255, 255, 0.95);   /* hardcoded */
    border-bottom: 1px solid #E2E8F0;        /* hardcoded */
}
html[data-admin-mode="light"] .admin-badge-success {
    background: #D1FAE5;  color: #065F46;    /* hardcoded */
}
/* ...etc for cards, tables, badges, inputs, modal... */
```

These predated the per-mode token system (Step 19.2). Now they fight it —
e.g. badges in light mode were stuck at the old `#D1FAE5`/`#065F46` green,
not the maroon-coordinated tokens. Replaced with a 5-line comment pointing
to the new source of truth (`config/admin_palettes.php`).

**Net delta:** -158 lines legacy + ~25 lines var refactors = ~133 lines smaller.

---

## Verification (Live Browser, 1400×900)

### Light mode (user.ui_mode='light', Full Light preset active)
| Component | Token used | Computed | Verdict |
|---|---|---|---|
| `.admin-topbar` bg | `--admin-topbar-bg` | `rgb(255,255,255)` (clean white, no leftover alpha) | ✅ |
| `.admin-card` bg | `--admin-bg-card` | `rgb(255,255,255)` | ✅ |
| `.admin-badge-success` bg | `--admin-alert-success-bg` | `rgb(236,253,245)` = `#ECFDF5` from config | ✅ |
| `.admin-badge-success` color | `--admin-success` | `rgb(4,120,87)` = `#047857` (light variant) | ✅ |
| `--admin-primary` | from Full Light preset | `#8B2942` maroon | ✅ |

### Dark mode (toggle, reload)
| Component | Token used | Computed | Verdict |
|---|---|---|---|
| `.admin-topbar` bg | `--admin-topbar-bg` | `rgb(15,23,42)` = `#0F172A` | ✅ |
| `.admin-card` bg | `--admin-bg-card` | `rgb(30,41,59)` = `#1E293B` | ✅ |
| `--admin-primary` | from Command Center Dark | `#166AE9` cobalt blue | ✅ |
| Sidebar full dark | `--admin-sidebar-bg` | `rgb(2,6,23)` | ✅ |

Both modes screenshots show coherent flip — no leftover violet, no
white-on-white blindness, no badge color leakage.

---

## Architecture Note

After Step 19.3, the light/dark cascade chain is:

```
config/admin_palettes.php  (source-of-truth: 4 presets × 45 tokens)
       ↓
AdminAppearanceService::paletteFor($mode)
       ↓
AdminAppearanceService::toCssVarsForMode($mode)
       ↓
<style id="admin-appearance-vars">
   dark : :root { --admin-*: ...; }
   light: html[data-admin-mode="light"] { --admin-*: ...; }
       ↓
.admin-* component classes (use var(--admin-*) — Step 19.3)
       ↓
Browser
```

No mid-cascade hardcoded overrides remain in `admin.css` — every token
travels from config → service → CSS var → component class.

---

## Impact

- **DB:** none
- **Routes:** none
- **Frontend (public):** none
- **Security:** none — only CSS
- **Performance:** marginally smaller CSS bundle (-1.7 KB after gzip)

---

## Rollback

```bash
git revert <step-19-3-commit>
npx vite build
```

---

## Known Carry-Over to Step 19.4–19.5

Component CSS is now mode-clean. But many Blade templates still have
hardcoded Tailwind classes like `bg-white`, `bg-slate-800`,
`text-slate-100`, `border-slate-200` that bypass the token system entirely.
Step 19.4 (pages module + dashboard + users) and Step 19.5 (bookings,
settings, media, etc.) systematically replace these.

Also: `.admin-toast` family still has hardcoded `rgba(...)` for dark-mode
toast variants. Out of scope for Step 19.3 — toast not in the 10-section
catalogue. Can be folded into 19.5 if needed.

---

## Next Step

→ **Step 19.4** — Audit + cleanup `bg-white`/`text-slate-*`/`bg-slate-*` in
   Blade templates for: dashboard, pages module, users module
