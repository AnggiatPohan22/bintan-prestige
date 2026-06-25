# Step 19.2 Handoff — Palette Schema + Service Rewrite
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (DB migration + service core path)
**Phase:** E.19.2 — Per-mode JSON palettes wired to composer

---

## Tujuan

Replace single-row global hex columns with **per-mode JSON token bags** (~45 keys × 2 modes). Service emits CSS based on the resolved mode for each user. **No UI change yet** — visual output should match Step 19.1 lock spec automatically.

---

## Files Changed

| File | Aksi |
|------|------|
| `database/migrations/2026_06_25_111050_add_palette_columns_to_admin_dashboard_appearances.php` | Baru — 2 JSON cols + 2 preset_name cols |
| `config/admin_palettes.php` | Baru — 4 preset starters + section/token catalogue (~300 LOC) |
| `app/Models/AdminDashboardAppearance.php` | + fillable + cast `array` for new columns |
| `app/Services/AdminAppearanceService.php` | + `paletteFor(mode)` + `toCssVarsForMode(mode)` |
| `app/View/Composers/AdminAppearanceComposer.php` | Prefer `toCssVarsForMode()`; fall back to legacy `toCssVars()` if bag empty |

---

## Schema Change

```sql
ALTER TABLE admin_dashboard_appearances
  ADD COLUMN dark_palette       JSON         NULL AFTER mode,
  ADD COLUMN light_palette      JSON         NULL AFTER dark_palette,
  ADD COLUMN dark_preset_name   VARCHAR(80)  NULL AFTER light_palette,
  ADD COLUMN light_preset_name  VARCHAR(80)  NULL AFTER dark_preset_name;
```

Old hex columns (`primary_color`, `bg_card`, etc.) **retained** for back-compat. Composer falls through to them if both JSON columns are null (legacy install).

---

## 4 Starter Presets (config/admin_palettes.php)

| Preset key | Category | Mode | Primary | Note |
|------------|----------|------|---------|------|
| `command-center-dark` | Dark starter | dark | `#166AE9` cobalt | (was violet #7C3AED, replaced per owner) |
| `maroon` | Dark starter | dark | `#B83E5C` burgundy | New (replaced "Midnight Navy") |
| `full-light` ⭐ DEFAULT | Light starter | light | `#8B2942` wine maroon | Pure white surfaces |
| `studio-light` | Light starter | light | `#8B2942` wine maroon | Slate-tinted surfaces |

Each preset = flat dict of ~45 hex keys covering 10 sections (Surfaces, Text, Buttons, Forms, Badges, Tables, Alerts, Modal, Topbar, Sidebar).

---

## Service Resolution

```php
// AdminAppearanceService
paletteFor($mode): array
  1. Try DB JSON column (dark_palette / light_palette)
  2. Fall back to config preset (defaults.{mode}_preset)
  3. Return [] -> composer routes to legacy toCssVars()

toCssVarsForMode($mode): string
  - dark : ":root { --admin-key: value; ... }"
  - light: "html[data-admin-mode=\"light\"] { --admin-key: value; ... }"
```

**Selector strategy** (critical for cascade):
- Dark `:root` ties with admin.css `:root`; source-order wins (our `<style>` after vite bundle).
- Light `html[data-admin-mode="light"]` ties with admin.css light block; same source-order rule wins.

This way, customizer tokens override admin.css defaults in both modes consistently.

---

## Composer Behavior

```php
$resolved = $service->resolveModeForUser(auth()->user());
$modeCss  = $service->toCssVarsForMode($resolved);   // new path

if ($modeCss === '') {
    // legacy install, no JSON columns populated yet
    $modeCss = $resolved === $appearance->mode
        ? $service->toCssVars($appearance)
        : null;
}
```

Old behavior preserved for installs that have not yet populated the JSON columns. Once superadmin saves any change in the Customizer v2 (Step 19.6), the JSON path takes over.

---

## Verification (Live Browser)

**Light mode (user.ui_mode='light', no JSON in DB → uses Full Light preset):**
| Var | Value | Expected |
|---|---|---|
| `--admin-primary` | `#8B2942` | ✅ deep wine maroon |
| `--admin-bg-base` | `#FFFFFF` | ✅ Full Light pure white |
| `--admin-sidebar-active-text` | `#8B2942` | ✅ maroon |
| `<style id="admin-appearance-vars">` selector | `html[data-admin-mode="light"]` | ✅ correct |

**Dark mode (toggle, reload):**
| Var | Value | Expected |
|---|---|---|
| `--admin-primary` | `#166AE9` | ✅ cobalt blue (was violet) |
| `--admin-bg-base` | `#020617` | ✅ slate-950 |
| `--admin-sidebar-bg` | `#020617` | ✅ |
| `--admin-modal-bg` | `#1E293B` | ✅ NEW token emitted |
| `--admin-topbar-bg` | `#0F172A` | ✅ NEW token emitted |
| `<style id="admin-appearance-vars">` selector | `:root` | ✅ correct |

Service smoke test: `paletteFor('dark')` and `paletteFor('light')` return correct 45-key bags. `dark.primary = #166AE9`, `light.primary = #8B2942`. Verdict: PASS.

---

## Visual Change Notice

Even tho "no UI change yet" is the intent, owner-approved tone tuning means **dark mode primary shifted violet → cobalt blue**. This is intentional (locked in Step 19.1).

Anything in dark mode that previously rendered violet (active sidebar item, primary buttons, focus rings) now renders cobalt blue. No structural/layout change.

---

## Impact

- **DB:** migration added; rollback `php artisan migrate:rollback --step=1`
- **Routes:** none changed
- **Frontend (public):** none — fully scoped to `/admin/*`
- **Security:** `toCssVarsForMode()` whitelist-validates both keys (`^[a-z0-9-]+$`) and values (`^[#\w\s().,%\/-]+$`) before raw output. Defense-in-depth vs CSS injection.
- **Performance:** zero — `paletteFor()` reuses cached `getCurrent()`, no extra queries

---

## Rollback

```bash
git revert <step-19-2-commit>
php artisan migrate:rollback --step=1
```

Composer falls back gracefully to legacy `toCssVars()` even WITHOUT rollback, so reverting code alone is also safe.

---

## Next Step

→ **Step 19.3** — Refactor admin.css component layer:
  - All `admin-btn-*`, `admin-input`, `admin-badge-*`, `admin-modal-*`, `admin-table-*` use new tokens
  - Remove hardcoded hex inside component classes
  - Validate tokens covered by config catalogue
