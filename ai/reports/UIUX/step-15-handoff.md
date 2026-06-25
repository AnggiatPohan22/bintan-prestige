# Step 15 Handoff — Blade UI: Settings Appearance
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium

---

## Files Baru / Diubah

- `resources/views/backend/settings/appearance/index.blade.php` — **baru**: halaman Customize Dashboard
- `resources/views/components/admin/sidebar.blade.php` — tambah link "Customize Dashboard"

---

## Fitur Halaman

### 1. Preset Selector
- 4 preset cards (Command Center Dark, Midnight Navy, Light Classic, Full Light)
- Setiap card menampilkan warna dots (primary + accent + card bg) di atas background preset
- Klik preset → Alpine.js `applyPreset()` isi semua fields sekaligus
- Active preset ditandai dengan `ring-2 ring-violet-500` + ikon centang

### 2. Color Picker Grid (4 cards, 2 kolom)
- **Mode & Sidebar** — dropdown mode, dropdown sidebar_style, color picker sidebar_bg
- **Primary Color** — primary, primary_hover, primary_text
- **Accent & Brand** — accent_color, gold_color, show_gold checkbox
- **Background System** — bg_base, bg_card, bg_input

Setiap color field memiliki:
- `<input type="color">` (native browser color picker)
- `<input type="text">` (manual hex entry, font-mono)
- Keduanya two-way bound via Alpine.js `x-model`

### 3. Live Preview
`$watch('fields', () => this.livePreview())` — setiap perubahan field → update CSS custom properties via `document.documentElement.style.setProperty()` → tampilan berubah real-time tanpa reload.

Termasuk toggle `data-admin-mode` dan `data-admin-sidebar` HTML attributes saat mode/sidebar_style berubah.

### 4. Save & Reset Actions

**Save:**
```html
<button type="submit" form="appearance-form">Simpan Perubahan</button>
```
→ POST `/admin/settings/appearance` (FormRequest validation)

**Reset (audit fix — POST + CSRF):**
```html
<form method="POST" action="{{ route('admin.settings.appearance.reset') }}">
    @csrf
    <button type="submit" onsubmit="confirm(...)">Reset ke Default</button>
</form>
```
→ POST `/admin/settings/appearance/reset` (BUKAN `window.location.href` GET)

---

## CSS Classes yang Dipakai

| Class | Keterangan |
|-------|------------|
| `admin-page-header` | Container page header |
| `admin-page-title` | H1 text |
| `admin-page-subtitle` | Subtitle/deskripsi |
| `admin-card` | Card container |
| `admin-card-header` | Card header dengan border-bottom |
| `admin-card-body` | Card body dengan padding p-6 |
| `admin-form-label` | Label uppercase tracking |
| `admin-input` | Input field |
| `admin-btn-primary` | Save button |
| `admin-btn-secondary` | Reset button |

---

## Sidebar Update

```blade
{{-- Settings group — Customize Dashboard link (superadmin only) --}}
@can('manage-users')
<a href="{{ route('admin.settings.appearance.index') }}"
   class="group admin-sidebar__child {{ Request::routeIs('admin.settings.appearance.*') ? 'admin-sidebar__child--active' : '' }}">
    Customize Dashboard
</a>
@endcan
```

Juga fix: "Global Settings" link sekarang pakai `admin.settings.global-assets.*` (lebih spesifik), bukan `admin.settings.*` yang akan match semua settings routes.

---

## Audit Fixes Applied

| Issue | Status |
|-------|--------|
| Reset button: POST + CSRF (bukan GET) | ✅ Fixed |
| `darken()` JS function | ✅ Tidak dibutuhkan — preset menyimpan hover color |
| View path `backend.settings.appearance.index` | ✅ Sudah dari Step 14 |
| CSS classes — gunakan yang ada (`admin-card-header` bukan `admin-card__header`) | ✅ Fixed |

---

## Testing Manual

1. Login sebagai superadmin
2. Sidebar Settings → "Customize Dashboard"
3. Cek halaman load tanpa error
4. Klik preset "Midnight Navy" → colors update live (sidebar, primary, backgrounds)
5. Klik "Simpan Perubahan" → redirect back dengan toast success
6. Refresh halaman → tampilan tetap sesuai preset yang dipilih
7. Klik "Reset ke Default" → confirm dialog → redirect → tampilan kembali Command Center Dark
8. Login sebagai non-superadmin → link "Customize Dashboard" tidak muncul di sidebar

---

## Rollback

```bash
git checkout HEAD -- resources/views/components/admin/sidebar.blade.php
# Hapus file baru:
# resources/views/backend/settings/appearance/index.blade.php
```

---

## Next

**Step 16** — Final QA + Accessibility Audit:
- Keyboard nav test (Tab, Enter, Space pada preset cards)
- WCAG AA contrast check (color picker labels)
- Screen reader test (`aria-label` pada semua color inputs)
- Smoke test semua 16 steps tidak ada regresi
- Docs update: MASTER-HANDOFF Phase D complete
