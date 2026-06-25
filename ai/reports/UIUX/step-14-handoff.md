# Step 14 Handoff — FormRequest + Controller + Routes
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium

---

## Files Baru / Diubah

- `config/admin_appearance_presets.php` — **baru**: 4 presets siap pakai
- `app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php` — **baru**: hex validation + show_gold checkbox fix
- `app/Http/Controllers/Admin/DashboardAppearanceController.php` — **baru**: index / update / reset
- `routes/admin.php` — tambah 3 routes + import controller

---

## Routes

```
GET  /admin/settings/appearance        admin.settings.appearance.index   → index()
POST /admin/settings/appearance        admin.settings.appearance.update  → update()
POST /admin/settings/appearance/reset  admin.settings.appearance.reset   → reset()
```

**Middleware:** `auth + admin + can:manage-users` (superadmin only)
**Audit fix:** semua routes POST (bukan GET) — mencegah CSRF bypass / 405 error di Step 15

---

## Config: `admin_appearance_presets`

| Key | Label | Mode | Sidebar |
|-----|-------|------|---------|
| `command_center_dark` | Command Center Dark | dark | dark |
| `midnight_navy` | Midnight Navy | dark | dark |
| `light_classic` | Light Classic | light | dark |
| `full_light` | Full Light | light | light |

Akses via `config('admin_appearance_presets')` atau `config('admin_appearance_presets.command_center_dark')`.

---

## FormRequest: `UpdateDashboardAppearanceRequest`

**Validasi utama:** regex `^#[0-9A-Fa-f]{3,6}$` untuk semua field warna
**show_gold:** `prepareForValidation()` default ke `false` saat checkbox tidak terkirim
**authorize():** `true` — authorization sudah via `can:manage-users` middleware route

---

## Controller: `DashboardAppearanceController`

```php
index()  → view('backend.settings.appearance.index', [
               'appearance' => $this->service->getCurrent(),
               'presets'    => config('admin_appearance_presets'),
           ])

update() → UpdateDashboardAppearanceRequest (validated) → service->update() → redirect success

reset()  → plain Request (tidak perlu validate) → service->reset() → redirect success
```

**Audit fix:** `reset()` pakai `Request` bukan `UpdateDashboardAppearanceRequest`
— reset tidak memiliki user input, tidak perlu validasi hex

---

## Verifikasi

```bash
php artisan route:list --path=admin/settings/appearance

# Output:
# GET|HEAD  admin/settings/appearance       admin.settings.appearance.index  → index
# POST      admin/settings/appearance       admin.settings.appearance.update → update
# POST      admin/settings/appearance/reset admin.settings.appearance.reset  → reset
```

✅ 3 routes terdaftar dengan benar
✅ Semua di bawah `can:manage-users` middleware (superadmin only)

---

## Audit Fixes Applied

| Issue | Status |
|-------|--------|
| View path `admin.*` vs `backend.*` | ✅ Fixed — `backend.settings.appearance.index` |
| `reset` route harus POST bukan GET | ✅ Fixed — POST `/admin/settings/appearance/reset` |
| `reset()` tidak perlu FormRequest | ✅ Fixed — pakai `Request` biasa |

---

## Rollback

```bash
git checkout HEAD -- routes/admin.php
# Hapus file baru:
# config/admin_appearance_presets.php
# app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php
# app/Http/Controllers/Admin/DashboardAppearanceController.php
```

---

## Next

**Step 15** — Blade UI: `resources/views/backend/settings/appearance/index.blade.php`
- Color picker inputs untuk setiap field
- Preset selector cards
- Reset button sebagai FORM POST + CSRF (bukan `window.location.href`)
- Flash alert untuk success/error messages
