# Step 18 Handoff — Topbar Night/Light Toggle + Unified Interface Mode
**Tanggal:** 2026-06-25
**Status:** ✅ Complete (with sub-step iterations 18.1 → 18.7)
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (UI surface area, no DB schema change)
**Phase:** E.2 — Topbar Toggle + Whole-Interface Mode Sync

---

## Tujuan

Wire UI untuk Step 17's `users.ui_mode` column:
- Toggle button di topbar untuk semua admin user
- Klik = flip instan + persist
- **Whole interface flips together** — sidebar, topbar, content panels semua ikut mode

---

## Sub-steps (Iteration History)

### 18.1 — Endpoint + validation
| File | Aksi |
|---|---|
| `app/Http/Controllers/Admin/UiModeController.php` | Baru — POST `/admin/settings/ui-mode`, validate via `Rule::in([UI_MODE_DARK, UI_MODE_LIGHT])`, returns 204 |
| `routes/admin.php` | + `Route::post('settings/ui-mode', [UiModeController::class, 'update'])` di admin middleware group (semua admin user, bukan superadmin-only) |

### 18.2 — Topbar button + Alpine component
| File | Aksi |
|---|---|
| `resources/views/backend/partials/navbar.blade.php` | + `<button x-data="adminUiModeToggle(...)" x-on:click="toggle">` di kiri bell icon |
| `resources/js/app.js` | + `Alpine.data('adminUiModeToggle')` — applyDom + persist via fetch |

### 18.3 — Live verification (initial 3-state cycle)
Pertama implementasi: cycle auto → dark → light → auto. Verified working: DOM swap, DB persist, reload restores.

### 18.4 — Owner feedback: drop 'auto', simplify to 2-state
**Issue owner:** "klik auto cuma kembali ke light, ambigu."
**Fix:** strict toggle dark ↔ light. `auto` masih ditolerir dari DB read (back-compat) tapi tidak pernah di-write dari UI.

### 18.5 — Owner feedback: sidebar harus ikut mode
**Issue owner:** "side menu masih dark waktu light, harus ikut."
**Fix:** di admin.css block `html[data-admin-mode="light"]`, flip semua sidebar var:
```css
--admin-sidebar-bg:           #FFFFFF;  /* dari #0F172A */
--admin-sidebar-border:       #E2E8F0;
--admin-sidebar-text:         #475569;
--admin-sidebar-text-hover:   #0F172A;
--admin-sidebar-active-bg:    rgba(124, 58, 237, 0.10);
--admin-sidebar-active-text:  #6D28D9;
```

### 18.6 — Verify whole-interface flip
Resize viewport ke 1400×900, screenshot kedua mode → sidebar+topbar+content semua flip together. ✅

### 18.7 — Owner reported residual: sidebar masih putih di dark mode
**Root cause:** `<html>` masih punya **dua attribute terpisah**:
- `data-admin-mode` (Step 17 — driven by `$adminUiMode`)
- `data-admin-sidebar="light"` (legacy dari customizer preset `sidebar_style='light'`)

Yang kedua punya 35 baris CSS hardcoded `background: #FFFFFF` yang selalu override `--admin-sidebar-bg` var apapun mode.

**Fix:**
- `admin.blade.php`: hapus `data-admin-sidebar` attribute injection
- `admin.css`: hapus 35 baris `[data-admin-sidebar="light"]` block (dead code)

---

## Final State Files

| File | Net change |
|------|------|
| `app/Http/Controllers/Admin/UiModeController.php` | Baru (+25) |
| `routes/admin.php` | +3 (import + route) |
| `resources/views/backend/partials/navbar.blade.php` | +12 (toggle button) |
| `resources/js/app.js` | +54 (Alpine component) |
| `resources/views/layouts/admin.blade.php` | -3 (drop legacy attr) |
| `resources/css/admin.css` | +7 light sidebar vars, -35 legacy block |

---

## Verification (Live Browser, 1400×900 viewport)

| Mode | Sidebar bg | Sidebar text | Content bg | Toggle icon | Verdict |
|---|---|---|---|---|---|
| Night | `rgb(2, 6, 23)` | `rgb(196, 181, 253)` violet | `rgb(2, 6, 23)` | 🌙 fa-moon | ✅ Full dark |
| Light | `rgb(255, 255, 255)` | `rgb(71, 85, 105)` slate | `rgb(248, 250, 252)` | ☀ fa-sun | ✅ Full light |

Persistence: tinker confirms `users.ui_mode` updated on each click. Reload preserves choice.

---

## Architecture: Why This Works

```
                ┌─────────────────────────────────────────┐
                │  User clicks topbar toggle              │
                └────────────────┬────────────────────────┘
                                 │
                ┌────────────────▼──────────────────┐
                │  Alpine adminUiModeToggle.toggle()│
                │  • mode = (mode === 'light') ?    │
                │           'dark' : 'light'        │
                │  • applyDom() [add/remove attr]   │
                │  • persist() [fetch POST]         │
                └────────────────┬──────────────────┘
                                 │
                ┌────────────────▼─────────────────┐
                │  POST /admin/settings/ui-mode    │
                │  → users.ui_mode UPDATE          │
                │  → 204 No Content                │
                └──────────────────────────────────┘

Next page render:
  AdminAppearanceComposer::compose()
    └─ resolveModeForUser(auth()->user())
       └─ $adminUiMode = 'light' or 'dark'

  admin.blade.php
    └─ <html data-admin-mode="light"> (if light)

  CSS cascade:
    :root { /* dark vars */ }                                  ← always applies
    html[data-admin-mode="light"] { /* light overrides */ }    ← higher specificity, wins when present
```

---

## Functions NOT Touched (Per Owner Request)

- Sidebar nav links (collapse, expand, active state, search) — pure CSS var swap
- All form submissions, controllers, routes outside `/admin/settings/ui-mode`
- Existing customizer at `/admin/settings/appearance` — sebagian dikenakan di Step 19
- All product/page/booking/etc CRUD flows

---

## Known Carry-Over to Step 19

User feedback after Step 18.7 (raising scope for Step 19):

1. **Light mode color leakage** — banyak halaman (selain product module) masih hardcoded `bg-white`/`bg-slate-800`/`text-slate-100`. Tidak respect mode.
2. **Buttons same in both modes** — `admin-btn-primary` violet `#7C3AED` di kedua mode, kelihatan "neon" di light. Perlu tone-shifted variant per mode.
3. **Customizer single-mode** — perlu refactor jadi 2-palette (dark + light) terpisah dengan tabbed UI + live preview per section.

Semua di-address di Step 19 (Theme Unification & Customizer v2) — sudah punya analysis dokumen, menunggu sub-step 19.1.

---

## Commits

| Step | Commit | Subject |
|---|---|---|
| 18.1–18.3 | `060053f` | feat(admin-ui): Step 18 — per-user dark/light topbar toggle |
| 18.4–18.6 | `5c04fee` | fix(admin-ui): Step 18 — 2-state toggle (Night/Light) + sidebar follows mode |
| 18.7 | `836149e` | fix(admin-ui): Step 18.7 — sidebar truly follows mode (drop legacy flag) |

---

## Rollback

```bash
git revert 836149e 5c04fee 060053f
npx vite build
```

(Step 17 migration tetap aman, tidak perlu di-rollback.)

---

## Next Step

→ **Step 19** — Theme Unification & Customizer v2 (planned 7 sub-steps 19.1–19.7)
   - 19.1 sedang menunggu approval: preset strategy B (4 starter palettes tetap ada)
