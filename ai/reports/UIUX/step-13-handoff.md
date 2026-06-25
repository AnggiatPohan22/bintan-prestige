# Step 13 Handoff — AdminAppearanceService + ViewComposer
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium

---

## Files Baru / Diubah

- `app/Services/AdminAppearanceService.php` — **baru**
- `app/View/Composers/AdminAppearanceComposer.php` — **baru**
- `app/Providers/AppServiceProvider.php` — tambah singleton + ViewComposer registration
- `resources/views/layouts/admin.blade.php` — inject HTML attrs + CSS vars style tag

---

## Perubahan Detail

### 1. AdminAppearanceService

**Methods:**
- `getCurrent(): AdminDashboardAppearance` — baca dari Cache 1 jam, fallback ke DB, fallback ke makeDefault()
- `update(array $data, User $user): AdminDashboardAppearance` — upsert + clearCache
- `reset(User $user): AdminDashboardAppearance` — reset ke makeDefault() values
- `clearCache(): void`
- `toCssVars(AdminDashboardAppearance $a): string` — generate `:root { ... }` CSS string

**Cache:** `Cache::remember('admin_dashboard_appearance', 3600, fn)` — 1 jam TTL

**`toCssVars()` — Audit Fix Applied:**

Perbedaan dari prompt Step 13 asli:

| Var | Prompt Asli | Fix Kita |
|-----|-------------|----------|
| `--admin-bg-surface` | mapped ke `sidebar_bg` ❌ | **DIHAPUS dari override** — legacy only |
| `--admin-sidebar-bg` | mapped ke `sidebar_bg` ✅ | Tetap |
| `custom_vars` | tidak ada | **Ditambahkan** — loop JSON array |

`--admin-bg-surface` tidak di-override karena:
- Hanya dipakai di legacy `.card-header` class (tidak digunakan di admin UI baru)
- Menghindari konfusi `sidebar_bg` ≠ surface background

`custom_vars` JSON di-inject dengan sanitasi:
- Nama var: harus match `^--[\w-]+$`
- Value: harus match `^[\w\s#().,%\/]+$`

### 2. AdminAppearanceComposer

Inject 2 variabel ke `layouts.admin`:
- `$adminAppearance` — instance `AdminDashboardAppearance`
- `$adminAppearanceCss` — string `:root { ... }`

### 3. AppServiceProvider

```php
// register()
$this->app->singleton(AdminAppearanceService::class);

// boot() — setelah existing View::composer block
View::composer('layouts.admin', AdminAppearanceComposer::class);
```

### 4. admin.blade.php — Audit Fixes Applied

**Fix A: HTML attributes (mode activation)**

```blade
<html lang="en"
    @isset($adminAppearance)
        @if($adminAppearance->mode !== 'dark') data-admin-mode="light" @endif
        @if($adminAppearance->sidebar_style === 'light') data-admin-sidebar="light" @endif
    @endisset
>
```

Ini memastikan Step 10's `[data-admin-mode="light"]` CSS selectors aktif
sesuai dengan mode yang tersimpan di DB.

**Fix B: Dynamic inline background**

```html
<style>
    html, body { background: {{ $adminAppearance->bg_base ?? '#020617' }}; }
    ...
</style>
```

Mencegah FOUC dengan warna yang benar (bukan selalu #020617).

**Fix C: CSS vars injection**

```blade
@isset($adminAppearanceCss)
<style id="admin-appearance-vars">
    {!! $adminAppearanceCss !!}
</style>
@endisset
```

`{!! !!}` digunakan karena nilai CSS mengandung `{}` dan `:`.
Aman karena hex values divalidasi FormRequest (Step 14).

---

## Tinker Verification

```
mode: dark
primary: #7C3AED

:root {
    --admin-bg-base: #020617;
    --admin-bg-card: #1E293B;
    --admin-bg-input: #0F172A;
    --admin-sidebar-bg: #020617;
    --admin-primary: #7C3AED;
    --admin-primary-hover: #6D28D9;
    ...
}
```

✅ getCurrent() bekerja dengan benar
✅ toCssVars() menghasilkan string CSS yang valid

---

## Browser Verification

Setelah Step 14 + 15 selesai, cek di DevTools:
- [ ] `<html>` tag: tidak ada `data-admin-mode` (dark mode default, benar)
- [ ] `<head>` → `<style id="admin-appearance-vars">`: ada, isi `:root { ... }`
- [ ] Tidak ada PHP error di log
- [ ] Tampilan admin: tidak ada perubahan visual (dark mode default sama)

---

## Rollback

```bash
git checkout HEAD -- app/Providers/AppServiceProvider.php
git checkout HEAD -- resources/views/layouts/admin.blade.php
# Hapus file baru:
# app/Services/AdminAppearanceService.php
# app/View/Composers/AdminAppearanceComposer.php
php artisan view:clear && php artisan config:clear
```

---

## Audit Fixes Remaining (Steps 14–15)

| Issue | Status |
|-------|--------|
| toCssVars `--admin-bg-surface` mapping | ✅ Fixed di step ini |
| HTML attr `data-admin-mode` injection | ✅ Fixed di step ini |
| `resetToDefault()` JS pakai GET ke POST route | ⬜ Fix di Step 15 |
| View path `admin.*` vs `backend.*` | ⬜ Fix di Step 15 |
| `darken()` JS function tidak implement | ⬜ Fix di Step 15 |

---

## Next

**Step 14** — FormRequest + Controller + Routes
