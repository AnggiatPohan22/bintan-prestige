# Step 01 Handoff — CSS Custom Properties (`:root` vars)
**Tanggal:** 2026-06-24
**Status:** ✅ Complete
**Branch:** develop (uncommitted — commit setelah visual check)

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — refactor lengkap (1370 → ~1050 baris bersih)

### Perubahan Utama

1. **`:root {}` block baru** di dalam `@layer components {}` mendefinisikan 25+ CSS custom properties:

   | Variable | Default Value | Kategori |
   |----------|--------------|---------|
   | `--admin-bg-base` | `#020617` | Surface |
   | `--admin-bg-surface` | `#0F172A` | Surface |
   | `--admin-bg-card` | `#1E293B` | Surface |
   | `--admin-bg-input` | `#0F172A` | Surface |
   | `--admin-border` | `rgba(255,255,255,0.08)` | Border |
   | `--admin-border-md` | `rgba(255,255,255,0.12)` | Border |
   | `--admin-text-primary` | `#F1F5F9` | Text |
   | `--admin-text-secondary` | `#94A3B8` | Text |
   | `--admin-text-muted` | `#64748B` | Text |
   | `--admin-primary` | `#7C3AED` | Brand |
   | `--admin-primary-hover` | `#6D28D9` | Brand |
   | `--admin-primary-soft` | `rgba(124,58,237,0.15)` | Brand |
   | `--admin-primary-glow` | `rgba(124,58,237,0.40)` | Brand |
   | `--admin-accent` | `#06B6D4` | Accent |
   | `--admin-gold` | `#D4AF37` | Brand Gold |
   | `--admin-sidebar-bg` | `#020617` | Sidebar |
   | `--admin-sidebar-active-text` | `#C4B5FD` | Sidebar |
   | `--admin-sidebar-active-border` | `#7C3AED` | Sidebar |
   | `--admin-success` | `#10B981` | Status |
   | `--admin-warning` | `#F59E0B` | Status |
   | `--admin-danger` | `#EF4444` | Status |
   | `--admin-info` | `#06B6D4` | Status |
   | `--admin-radius-*` | `6/8/12/16px` | Radius |

2. **Semua admin-* classes** sekarang pakai `var()` untuk warna — tidak ada hardcoded Tailwind color classes di `@apply`

3. **Tambahan baru:** `.admin-badge-neutral` (belum ada sebelumnya)

4. **Perubahan visual (Dark Mode defaults):**
   - Background halaman: putih → hitam gelap (`#020617`)
   - Card backgrounds: putih → dark navy (`#1E293B`)
   - Sidebar: slate-900 → ultra-dark base (`#020617`) + border kiri violet pada active link
   - Topbar: putih → dark glass (`rgba(2,6,23,0.92)`)
   - Primary button: indigo-600 → Electric Violet (`#7C3AED`) + micro-interaction hover
   - Badges: solid light color → dark soft + dot indicator
   - Active sidebar link: solid indigo block → border-left violet glow

---

## Verifikasi Build

```
✅ npx vite build — sukses, 0 error, 0 warning
✅ Output: public/build/assets/app-C1b641i6.css (121.83 kB)
✅ :root block hadir di output CSS
✅ admin-primary var referenced 1x (minified, correct)
✅ Tidak ada Tailwind color class tersisa di @apply (grep clean)
```

---

## Visual Check (Manual — Perlu Dilakukan Anda)

Buka admin di browser setelah clear cache:

```bash
php artisan view:clear
# kemudian refresh browser dengan Ctrl+Shift+R (hard refresh)
```

**Yang harus terlihat:**

| Element | Sebelum | Sesudah |
|---------|---------|---------|
| Background halaman | putih / slate-50 | hitam gelap (`#020617`) |
| Sidebar | slate-900 dark | ultra-dark, brand mark solid violet |
| Active nav link | solid indigo block | border-left violet + soft violet bg |
| Topbar | putih/95 | dark glass transparan |
| Cards | putih dengan border abu | dark navy (`#1E293B`) |
| Form inputs | putih dengan border abu | dark navy bg, light text |
| Primary button | indigo-600 | Electric Violet + hover lift |
| Badges | solid light pills | dark soft + dot + rectangular |
| Section labels (sidebar) | slate-600 text | ultra-muted 9px uppercase |

**Jika ada elemen yang terlihat broken (blank/invisible/text hilang):**
Jalankan di DevTools console untuk debug:
```js
getComputedStyle(document.documentElement).getPropertyValue('--admin-primary')
// harus return: " #7C3AED"
```

---

## Rollback

```bash
git checkout HEAD -- resources/css/admin.css
npm run build
```

---

## Class Compatibility

| Class | Status | Keterangan |
|-------|--------|-----------|
| `admin-btn-primary` | ✅ Kompatibel | Nama sama, warna dari var() |
| `admin-btn-secondary` | ✅ Kompatibel | Nama sama |
| `admin-btn-success` | ✅ Kompatibel | Nama sama |
| `admin-btn-danger` | ✅ Kompatibel | Nama sama |
| `admin-btn-soft` | ✅ Kompatibel | Nama sama |
| `admin-badge-success/warning/danger/info` | ✅ Kompatibel | Nama sama, shape berubah ke rounded-md + dot |
| `admin-badge-neutral` | ✅ Baru | Ditambah |
| `admin-card` | ✅ Kompatibel | Nama sama |
| `admin-input/select/textarea` | ✅ Kompatibel | Nama sama |
| `admin-table-wrapper/header/row` | ✅ Kompatibel | Nama sama |
| `admin-sidebar-*` | ✅ Kompatibel | Nama sama |
| `admin-topbar-*` | ✅ Kompatibel | Nama sama |
| `form-input/form-label` | ✅ Kompatibel (legacy) | Masih ada, diupdate ke vars |
| `btn-primary/btn-secondary` | ✅ Kompatibel (legacy) | Masih ada, diupdate ke vars |

**Zero breaking changes** — semua nama class tidak berubah.

---

## Impact

- **DB:** none
- **Routes:** none
- **Backend/PHP:** none
- **Blade:** none (class names tidak berubah)
- **JS/Alpine:** none
- **Frontend:** none (publik frontend tidak pakai admin.css)

---

## Next

**Step 02** — Shell & Sidebar: Fine-tune `.admin-shell`, `.admin-body`, `.admin-sidebar*`
untuk precision dark aesthetic (active link border-left behavior, brand mark gold ring,
section labels ultra-muted).

Sebelum lanjut ke Step 02, pastikan visual check di atas sudah dilakukan dan hasilnya OK.
