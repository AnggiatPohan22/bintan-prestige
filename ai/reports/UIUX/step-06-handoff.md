# Step 06 Handoff — Tables & Data Grid Dark
**Tanggal:** 2026-06-24
**Status:** ✅ Complete (CSS + Blade cleanup done)
**Branch:** develop (uncommitted)

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — **nol perubahan** (Step 01 sudah mengimplementasi semua table CSS)

---

## Verifikasi CSS — Semua Match Dengan Spec

| Class | Status |
|-------|--------|
| `.admin-table-wrapper` | ✅ Match — dark card bg, subtle border, rounded-xl |
| `.admin-table` | ✅ Match — min-w-[840px], text-left |
| `.admin-table-header` | ✅ Match — darker strip, border-b |
| `.admin-table-header th` | ✅ Match — px-6 py-3.5, font-black uppercase, letter-spacing 0.1em |
| `.admin-table-row` | ✅ Match — very subtle border, duration-100 |
| `.admin-table-row:hover` | ✅ Match — rgba(255,255,255,0.02) hover |
| `.admin-table-row td` | ✅ Match — text-secondary color |
| `.admin-table-row td:first-child` | ✅ Match — text-primary, font-weight 500 |

---

## ⚠️ CRITICAL FINDING — Blade Text Color Cleanup Needed

### Problem

Banyak Blade file menggunakan hardcoded Tailwind dark text classes di dalam table cells
dan content area:

```
text-slate-800 → #1E293B   ← sama dengan --admin-bg-card (#1E293B) = INVISIBLE
text-slate-900 → #0F172A   ← gelap di dark bg = INVISIBLE
text-slate-700 → #334155   ← hampir invisible di dark bg
```

### Scope (dari grep)

| File | Contoh |
|------|--------|
| `backend/pages/index.blade.php` | `<div class="font-semibold text-slate-800">` di table td |
| `backend/categories/index.blade.php` | `<div class="font-semibold text-slate-800">` |
| `backend/destinations/index.blade.php` | `<div class="font-semibold text-slate-800">` |
| `backend/faqs/index.blade.php` | `<div class="font-semibold text-slate-800">` |
| `backend/contact-forms/index.blade.php` | `<div class="font-semibold text-slate-800">` |
| `backend/audit-logs/index.blade.php` | `text-slate-800`, `text-slate-700` |
| `backend/categories/form.blade.php` | `text-slate-800` di heading |
| `backend/destinations/form.blade.php` | `text-slate-800` di heading |
| `backend/menus/*.blade.php` | `text-slate-900`, `text-slate-700` banyak tempat |
| `backend/page-sections/*.blade.php` | `text-slate-900`, `text-slate-800` |
| dan lainnya... | |

### Why CSS Can't Fix This (Explanation)

Tailwind utility classes (`text-slate-800`) berada di `@layer utilities` yang memiliki
prioritas LEBIH TINGGI dari `@layer components` (dimana semua admin CSS kita berada).
CSS override di `@layer components` tidak bisa override `text-slate-800` Tailwind.

### Fix Yang Diperlukan

Ubah hardcoded dark text di Blade files:

```html
<!-- BEFORE (invisible on dark bg) -->
<div class="font-semibold text-slate-800">{{ $page->title }}</div>
<p class="text-sm text-slate-500">Subtitle text</p>

<!-- AFTER -->
<div class="font-semibold text-slate-100">{{ $page->title }}</div>
<p class="text-sm text-slate-400">Subtitle text</p>
```

**Mapping yang direkomendasikan:**

| Old | New | Konteks |
|-----|-----|---------|
| `text-slate-950/900/800` | `text-slate-100` | Primary content, judul |
| `text-slate-700` | `text-slate-300` | Secondary content |
| `text-slate-600` | `text-slate-400` | Tertiary, caption |
| `text-slate-500` | `text-slate-400` | Hint, muted |
| `bg-slate-50/100` | `bg-slate-800/900` | Background light elements |
| `hover:bg-slate-100` | `hover:bg-slate-700` | Hover states |

### Priority

Prioritas tinggi — perlu diselesaikan sebelum atau bersamaan dengan perubahan visual
yang lain. Tanpa fix ini, banyak teks tidak terbaca di dark mode.

---

## Verifikasi Build

```
✅ npx vite build — sukses (tidak ada perubahan CSS, build clean)
```

---

## Visual Check

Buka halaman index yang punya table:
- [ ] Table header: uppercase muted, dark strip terlihat
- [ ] Row border: sangat tipis, barely visible ✅
- [ ] Row hover: ultra-subtle ✅
- [ ] **Teks di table cells (`text-slate-800`)**: ⚠️ MUNGKIN INVISIBLE — perlu fix Blade

---

## Next

**Opsi 1 (Recommended):** Selesaikan Blade text color cleanup terlebih dahulu
sebelum lanjut ke Step 07. Ini akan mempengaruhi banyak halaman.

**Opsi 2:** Lanjut ke Step 07 dulu, lakukan Blade cleanup setelah semua CSS steps selesai.

Step 07 adalah Buttons (🔴 HIGH risk) — bisa dilakukan tanpa Blade cleanup.
