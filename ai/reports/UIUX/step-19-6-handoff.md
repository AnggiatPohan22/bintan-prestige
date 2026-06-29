# Step 19.6 Handoff — Customizer v2 UI (Tabbed + Live Preview)
**Tanggal:** 2026-06-26
**Status:** ✅ Complete
**Commit:** `555ea69`
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (3 files changed, 506 insertions, 318 deletions)
**Phase:** E.19.6 — Customizer v2 replaces flat preset picker

---

## Tujuan

Rewrite halaman `/admin/settings/appearance` dari flat preset picker legacy menjadi **Customizer v2** — UI dual-mode dengan 10-section sidebar, live preview per section, color picker per token, preset strip, dan AJAX save per mode.

---

## Files Changed

| File | Perubahan |
|---|---|
| `app/Http/Controllers/Admin/DashboardAppearanceController.php` | Update `index()` + tambah `savePalette()` + `sanitizeTokens()` |
| `routes/admin.php` | Tambah `Route::post('/palette', ...)` → `settings.appearance.palette.save` |
| `resources/views/backend/settings/appearance/index.blade.php` | Full rewrite ~350 LOC |

---

## Controller Changes (`DashboardAppearanceController.php`)

### `index()` — baru pass 5 var tambahan ke view

```php
return view('backend.settings.appearance.index', [
    'appearance'     => $this->service->getCurrent(),
    'darkTokens'     => $this->service->paletteFor('dark'),
    'lightTokens'    => $this->service->paletteFor('light'),
    'sections'       => $palettesConfig['sections'] ?? [],
    'tokenCatalogue' => $palettesConfig['tokens'] ?? [],
    'presets'        => $palettesConfig['presets'] ?? [],
    'initialMode'    => $this->service->resolveModeForUser(auth()->user()),
]);
```

### `savePalette()` — AJAX endpoint baru

```
POST /admin/settings/appearance/palette
Content-Type: application/json
{ "mode": "dark"|"light", "tokens": { "primary": "#166AE9", ... } }

Response: { "ok": true }
```

- Validates `mode` (in:dark,light) + `tokens` (array)
- `sanitizeTokens()`: whitelist key dari `config('admin_palettes.tokens')` + regex `^(#[0-9A-Fa-f]{3,8}|rgba?\([^)]+\))$`
- Saves ke `dark_palette` / `light_palette` JSON column via `$service->update()`
- Legacy `update()` + `reset()` methods **tidak diubah** — backwards compatible

### Route Baru

```php
// routes/admin.php — dalam group settings/appearance
Route::post('/palette', [DashboardAppearanceController::class, 'savePalette'])
    ->name('settings.appearance.palette.save');
```

---

## View — Customizer v2 (`appearance/index.blade.php`)

### Struktur UI

```
┌── Page header ─────────────────────────────────────┐
│  "Customize Dashboard"           [Night] [Light]   │
├── Preset strip ───────────────────────────────────┤
│  STARTER PRESETS  [Command Center Dark] [Slate Pro] │  ← Night tab
│  STARTER PRESETS  [Full Light] [Studio Light]       │  ← Light tab
├── 2-column main ──────────────────────────────────┤
│ ┌─ Sidebar ─────┐  ┌─ Editor panel ──────────────┐ │
│ │  SECTIONS     │  │  PREVIEW (real components)  │ │
│ │  > Surfaces   │  │  TOKEN INPUTS               │ │
│ │    Text       │  │  (color picker + text input) │ │
│ │    Buttons    │  │                             │ │
│ │    Forms      │  │                             │ │
│ │    Badges     │  │                             │ │
│ │    Tables     │  │                             │ │
│ │    Alerts     │  │                             │ │
│ │    Modal      │  │                             │ │
│ │    Topbar     │  │                             │ │
│ │    Sidebar    │  │                             │ │
│ │  ─────────── │  │                             │ │
│ │  [Simpan]    │  │                             │ │
│ │  [Reset]     │  │                             │ │
│ └──────────────┘  └─────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

### Alpine Component — `customizerV2(config)`

```javascript
// Diload via @push('scripts') di bawah view
function customizerV2(config) { ... }
```

**State:**

| Property | Default | Keterangan |
|---|---|---|
| `activeMode` | `config.initialMode` (per user DB) | `'dark'` atau `'light'` |
| `activeSection` | `'surfaces'` | Section aktif di sidebar |
| `activePreset` | `null` | Key preset yang aktif (null = custom) |
| `saving` / `saved` | false | AJAX save state |
| `editing.dark` | 46 token dari DB (fallback: preset) | Working copy dark palette |
| `editing.light` | 46 token dari DB (fallback: preset) | Working copy light palette |

**Metode kunci:**

| Method | Fungsi |
|---|---|
| `init()` | `applyLivePreview()` saat mount |
| `setMode(mode)` | Switch `activeMode`, clear preset, apply preview |
| `applyPreset(key)` | Copy token dari config preset ke `editing[mode]`, set `activePreset`, apply preview |
| `updateToken(key, val)` | Update 1 token di `editing[mode]`, clear preset, apply preview |
| `applyLivePreview()` | Set semua CSS vars di `document.documentElement` inline style; toggle `data-admin-mode="light"` |
| `savePalette()` | `fetch POST` → `/palette` JSON → `saved = true` selama 2.5 detik |
| `resetPalette(form)` | `confirm()` dialog → `form.submit()` ke endpoint reset lama |

### Live Preview Logic

```javascript
applyLivePreview() {
    const tokens = this.editing[this.activeMode];
    const root = document.documentElement;
    if (this.activeMode === 'light') {
        root.setAttribute('data-admin-mode', 'light');
    } else {
        root.removeAttribute('data-admin-mode');
    }
    Object.entries(tokens).forEach(([key, value]) => {
        if (value) root.style.setProperty(`--admin-${key}`, value);
    });
}
```

Karena semua `admin-*` CSS classes di seluruh halaman sudah consume `var(--admin-*)`, update inline style di `<html>` cukup untuk re-render seluruh UI tanpa reload.

### RGBA Token Handling

Beberapa token (`border`, `modal-overlay`, `alert-*-bg`) menggunakan format `rgba(...)` — **tidak bisa** pakai `<input type="color">`.

Solusi via `isHex()` helper:
```javascript
isHex(val) { return typeof val === 'string' && val.trim().startsWith('#'); }
```

- **Hex token** → `type="color"` picker + text input (keduanya sync)
- **RGBA token** → label `rgba` pill + text input saja

### Preview Components per Section

Setiap section di sidebar memiliki preview panel berisi real `admin-*` components:

| Section | Preview berisi |
|---|---|
| `surfaces` | Nested bg-base > bg-surface > bg-card + border swatches |
| `text` | primary / secondary / muted / link sample teks |
| `buttons` | admin-btn-primary / secondary / danger / soft |
| `forms` | admin-input + select + checkbox |
| `badges` | admin-badge-success / warning / danger / info / neutral |
| `tables` | admin-table dengan header + 2 rows |
| `alerts` | admin-alert-success / danger + warning/info (inline-styled) |
| `modal` | admin-modal-content di overlay |
| `topbar` | admin-topbar dengan breadcrumb + icons |
| `sidebar` | Mini sidebar item dengan active state (sidebar-* CSS vars) |

---

## Verification (Live Browser)

**Alpine component init (DOM inspection):**
```js
{
  activeMode: "light",   // user is in light mode
  activeSection: "surfaces",
  sectionsCount: 10,
  tokensCount: 46,
  presetsCount: 4,
  darkTokensCount: 46,
  lightTokensCount: 46,
}
// ✅ Semua data terpopulasi
```

**AJAX save:**
```js
await alpine.savePalette()
// → { saved: true, saving: false } ✅
```

**Night mode + preset apply:**
```js
alpine.setMode('dark')
alpine.applyPreset('command-center-dark')
// → activePreset: "command-center-dark" ✅
// → data-admin-mode attr: null (removed) ✅
// → --admin-primary: "#166AE9" (cobalt blue) ✅
```

**Token live update:**
```js
alpine.updateToken('primary', '#FF0000')
// → editing.dark.primary: "#FF0000" ✅
// → CSS var --admin-primary: "#FF0000" (instan) ✅
// → activePreset: null (cleared) ✅
```

---

## Backward Compatibility

- **Legacy `update()` method** (POST ke `/admin/settings/appearance/`) → **tidak diubah**
- **`UpdateDashboardAppearanceRequest`** → **tidak diubah**
- **Reset form** (`POST /admin/settings/appearance/reset`) → tetap dipakai via `resetPalette(formEl)` confirm dialog
- New AJAX route adalah **additive** — tidak replace existing routes

---

## Impact

- **DB:** none (pakai JSON columns `dark_palette` / `light_palette` dari Step 19.2)
- **Routes:** 1 baru — `POST admin/settings/appearance/palette`
- **Frontend (public):** none
- **Security:** input sanitization via whitelist key + hex/rgba regex; CSRF via `X-CSRF-TOKEN` header di fetch call

---

## Rollback

```bash
git revert 555ea69
php artisan route:clear
```

---

## Known Limitation

- `<input type="color">` tidak support `rgba(...)` — token dengan format rgba hanya bisa diedit via text input. Alpha channel bisa diedit manual.
- Screenshot tool preview browser timeout saat Step 19.6 verification — diverifikasi via DOM inspection + Alpine `_x_dataStack` introspection.

---

## Next Step

→ **Step 19.7** — Final QA: screenshot 10+ pages × 2 mode, fix remaining color leaks, accessibility re-check (`focus:ring`, ARIA labels, contrast ratio)
