# Admin Dashboard Skill

> **Grand Master Plan:** Lihat `ai/reports/UIUX/grand-master-plan-admin-uiux.md`
> untuk visi lengkap UI/UX admin "Command Center Dark" — v1.1, dibuat 2026-06-23.
> Mencakup: Dark Mode, Light Mode, CSS Custom Properties architecture,
> dan Appearance Customizer feature spec (Section 17–22).
> Semua pekerjaan UI admin harus mengacu ke dokumen tersebut.

## Required References
- `AGENTS.md` — authority order & safety rules
- `DESIGN-SYSTEM.md` §3 — Admin color palette & CSS class map
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` ⭐ — Grand Master Plan UI/UX
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — CMS builder rules
- `resources/css/admin.css` — source of truth semua CSS admin class

---

## Main Goal

Build admin dashboard yang terasa seperti **"Command Center"** — bukan generic SaaS template.
Precision, depth, identity. Dark-first. Power-user focused.

---

## Design Direction (Updated 2026-06-23)

### Aesthetic Target: "Command Center Dark"
- **Dark base:** `bg-slate-950` halaman background — bukan lagi hybrid dark sidebar + light content
- **5-layer surface system:** base → container → card → input → raised/floating
- **Primary accent: Electric Violet** (`#7C3AED` / `bg-violet-600`) — menggantikan generic indigo-600
- **Secondary accent: Cyan** (`#06B6D4`) — untuk info, highlight, link
- **Brand gold hint:** `#D4AF37` — hadir sangat sparingly (max 2 elemen per halaman) untuk identity premium
- **Glassmorphism** hanya untuk floating elements (modal, dropdown, tooltip)
- **Reduced rounding:** `rounded-lg` untuk komponen structural (bukan `rounded-2xl` semua)
- **Color glow shadows** bukan gray shadows — `shadow-violet-900/40`, `shadow-black/30`

### Anti-Pattern (Jangan Lakukan)
- Jangan pakai `rounded-2xl` di semua tempat — hanya card dan modal yang butuh
- Jangan hardcode warna baru — gunakan class `admin-*` yang sudah ada
- Jangan mix gaya frontend luxury (putih, serif, emas besar) ke dalam admin
- Jangan tambah shadow abu-abu generik — gunakan color-aware shadows

---

## Admin CSS Classes (Wajib Digunakan)

Semua class ada di `resources/css/admin.css`. **Jangan inline Tailwind equivalents.**

| Component | Class | Notes |
|-----------|-------|-------|
| Page wrapper | `admin-page` | `space-y-6` |
| Page header | `admin-page-header` | Dark gradient card |
| Page title | `admin-page-title` | `text-slate-100 tracking-tight` |
| Card/panel | `admin-card` | Dark surface `bg-slate-800` |
| Card header | `admin-card-header` | Slightly darker strip |
| Card body | `admin-card-body` | `p-6` |
| Form card | `admin-form-card` | Sama dengan admin-card |
| Primary button | `admin-btn-primary` | **Violet** — bukan indigo legacy |
| Secondary button | `admin-btn-secondary` | Glass subtle |
| Danger button | `admin-btn-danger` | Red — unchanged |
| Soft button | `admin-btn-soft` | Violet soft bg |
| Text input | `admin-input` | Dark bg, violet focus |
| Textarea | `admin-textarea` | Dark bg |
| Select | `admin-select` | Dark bg, custom arrow |
| Form label | `admin-form-label` | `uppercase tracking-wider text-slate-400` |
| Form hint | `admin-form-hint` | `text-xs text-slate-500` |
| Table wrapper | `admin-table-wrapper` | Dark surface |
| Table | `admin-table` | |
| Table header | `admin-table-header` | `uppercase tracking-widest text-slate-500` |
| Table row | `admin-table-row` | Subtle border + hover |
| Success badge | `admin-badge-success` | Dark-optimized: emerald-400 on emerald-500/15 |
| Warning badge | `admin-badge-warning` | Dark-optimized: amber-400 on amber-500/15 |
| Danger badge | `admin-badge-danger` | Dark-optimized: red-400 on red-500/15 |
| Info badge | `admin-badge-info` | Dark-optimized: cyan-400 on cyan-500/15 |
| Neutral badge | `admin-badge-neutral` | slate-400 on white/10 |
| Empty state | `admin-empty-state` | Diagonal stripe pattern |
| Icon button | `admin-btn-icon` | h-9 w-9 rounded-lg |

---

## UX Principles

**Productivity-First:**
- User harus tahu apa yang harus dilakukan selanjutnya — selalu ada CTA jelas
- Required fields harus clear — label + asterisk + error message inline
- Save dan Cancel harus obvious — `admin-btn-primary` dan `admin-btn-secondary`
- Form panjang gunakan tabs atau accordion sections
- Destructive action selalu minta konfirmasi

**Data-First:**
- List pages: filter + search + status badge + pagination
- Empty state: bukan "No data" — guide user ke aksi berikutnya
- Status badge di setiap row — user lihat sekilas tahu statusnya
- Dashboard home: KPI stat cards + shortcut ke modul utama

**Feedback-First:**
- Loading state pada setiap data action
- Toast notification untuk success/error
- Error message inline pada form field
- Confirmation modal sebelum delete / destructive action

---

## Motion Rules (ringkasan — detail di Grand Master Plan §8)

| Action | Duration | Effect |
|--------|----------|--------|
| Button hover | 150ms | `translateY(-1px)` + glow |
| Button active | 100ms | `translateY(0)` snap back |
| Dropdown open | 150ms | `opacity + translateY(-4px→0)` |
| Modal open | 200ms | `opacity + scale(0.97→1)` |
| Card hover | 150ms | `border-color` only — tidak scale |
| Toast enter | 250ms | `translateX(100%→0)` |
| Sidebar open | 300ms | `translateX` |

---

## Rules Tetap (Tidak Berubah)

- Admin dan public frontend styling TIDAK boleh bercampur
- Semua query data disiapkan di controller/service — tidak ada query di Blade
- Semua form punya validation message
- Semua destructive action punya konfirmasi
- Semua interactive element punya focus ring yang visible
- Tidak ada `style="..."` inline override di atas admin-badge-* classes

---

---

## Appearance Customizer (Fitur Baru — v1.1)

Fitur Settings > Customize Dashboard memungkinkan Super Admin mengubah tampilan
visual admin tanpa menyentuh kode. Diimplementasi via **CSS Custom Properties + DB**.

**Architecture:**
```
DB (admin_dashboard_appearances) → AdminAppearanceService → ViewComposer
→ admin.blade.php <style> inject → admin.css var() → Browser
```

**Untuk bekerja di fitur ini:**
1. Baca Grand Master Plan §17–22 untuk spec lengkap
2. Semua warna di `admin.css` harus menggunakan `var(--admin-*)` bukan hardcoded hex
3. Tabel `admin_dashboard_appearances` perlu migration baru (butuh approval owner)
4. Preset brand themes tersedia: Command Center Dark, Hotel Luxury, Resort Tropical,
   Tour Adventure, Restaurant Warm, dan lebih banyak (lihat §20)

**Step 1 yang bisa dimulai tanpa approval:**
Refactor `admin.css` — ganti hardcoded hex dengan `var(--admin-*)` + define `:root` defaults.

---

## What NOT to Do

- ❌ Gunakan `btn-primary` / `btn-secondary` (legacy emerald — deprecated)
- ❌ Gunakan `form-input` / `form-label` / `form-textarea` (legacy — gunakan `admin-*`)
- ❌ Hardcode `$inputClass` PHP variable di Blade
- ❌ Tambah `style="color/background"` di atas badge classes
- ❌ Skip loading/error states pada data actions
- ❌ Buat interactive element tanpa focus ring
- ❌ Hardcode hex color baru — semua dari token di admin.css / Grand Master Plan
