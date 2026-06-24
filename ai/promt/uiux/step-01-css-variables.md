# STEP 01 — CSS Custom Properties Foundation
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

Kamu adalah Claude Code yang mengerjakan **Step 01** dari Admin UI/UX Redesign
untuk Bintan Prestige CMS. Ini sesi baru — kamu tidak punya memori sesi sebelumnya.

**Referensi wajib baca sebelum mulai:**
- `AGENTS.md`
- `DESIGN-SYSTEM.md`
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` (Section 6: CSS Custom Properties)
- `ai/promt/uiux/00-overview-and-checklist.md`

**Scope step ini:** Refactor `resources/css/admin.css` — ganti semua hardcoded hex color
dengan CSS custom properties (`var(--admin-*)`), dan define `:root` block dengan semua
default values (Dark Mode "Command Center Dark").

---

## Rules Wajib

**DILARANG:**
- Mengubah nama CSS class apapun — hanya nilai propertinya
- Mengubah file selain `resources/css/admin.css`
- Mengubah struktur @layer, urutan class, atau Tailwind @apply directives
- Mengubah spacing, radius, atau typography
- Menyentuh file PHP, Blade, route, controller, migration

**BOLEH:**
- Membaca semua file referensi
- Mengedit `resources/css/admin.css` — HANYA mengganti hex values dengan `var()`
- Menambahkan `:root { }` block di awal `@layer components {`
- Membuat `ai/reports/UIUX/step-01-handoff.md`

---

## Risk Assessment

**Risk: 🟡 Medium**

Mengapa medium:
- Semua class names tidak berubah — zero Blade file impact
- Tapi jika CSS var typo atau nilai salah → seluruh admin bisa broken secara visual
- Mitigasi: test di browser setelah selesai, pastikan tampilan sama dengan sebelumnya

Rollback: `git checkout HEAD -- resources/css/admin.css`

---

## Phase 1 — Baca Konteks

Baca file-file ini secara berurutan:

1. `AGENTS.md` — authority order
2. `DESIGN-SYSTEM.md` — Section 3 (Admin color palette)
3. `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 6 (CSS Custom Properties)
4. `resources/css/admin.css` — seluruh file (baca sampai selesai)

Setelah membaca, konfirmasi:
```
=== STEP 01 CONTEXT ===
File target: resources/css/admin.css
Tujuan: Refactor hex → var(--admin-*)
CSS class yang disentuh: SEMUA (hanya nilai warna, bukan nama class)
Estimasi perubahan: ~30-40 color values
Risk: Medium
Rollback: git checkout HEAD -- resources/css/admin.css
```

---

## Phase 2 — Inspect & Inventory

Baca `resources/css/admin.css` dan buat daftar inventory SEMUA hardcoded hex/color yang
akan diganti. Format:

```
=== COLOR INVENTORY ===
1. .admin-body          → bg-slate-950  (#F8FAFC saat ini) → var(--admin-bg-base)
2. .admin-shell         → dari-white → var(--admin-bg-base)
3. .admin-card          → bg-white → var(--admin-bg-card)
4. .admin-btn-primary   → bg-indigo-600 → bg-violet-600 (+ var)
... dst
```

Pastikan inventory lengkap sebelum mulai edit.

---

## Phase 3 — Implementasi

### 3.1 Tambah `:root` block

Di awal `@layer components {` (sebelum `.form-input`), tambahkan:

```css
/* ============================================================
   ADMIN APPEARANCE SYSTEM — CSS Custom Properties
   Default: "Command Center Dark" theme
   Override via: <style id="admin-appearance-vars"> inject
   dari AdminAppearanceService (Customizer feature — Step 12-16)
   ============================================================ */

:root {
    /* Surfaces */
    --admin-bg-base:       #020617;
    --admin-bg-surface:    #0F172A;
    --admin-bg-card:       #1E293B;
    --admin-bg-input:      #0F172A;
    --admin-bg-hover:      #334155;

    /* Borders */
    --admin-border:        rgba(255, 255, 255, 0.08);
    --admin-border-md:     rgba(255, 255, 255, 0.12);
    --admin-border-strong: rgba(255, 255, 255, 0.20);

    /* Text */
    --admin-text-primary:   #F1F5F9;
    --admin-text-secondary: #94A3B8;
    --admin-text-muted:     #64748B;

    /* Primary — Electric Violet */
    --admin-primary:        #7C3AED;
    --admin-primary-hover:  #6D28D9;
    --admin-primary-soft:   rgba(124, 58, 237, 0.15);
    --admin-primary-glow:   rgba(124, 58, 237, 0.40);
    --admin-primary-text:   #FFFFFF;

    /* Accent — Cyan */
    --admin-accent:         #06B6D4;
    --admin-accent-soft:    rgba(6, 182, 212, 0.15);

    /* Brand Gold */
    --admin-gold:           #D4AF37;
    --admin-gold-soft:      rgba(212, 175, 55, 0.10);

    /* Sidebar */
    --admin-sidebar-bg:           #020617;
    --admin-sidebar-border:       rgba(255, 255, 255, 0.06);
    --admin-sidebar-text:         #64748B;
    --admin-sidebar-text-hover:   #CBD5E1;
    --admin-sidebar-active-bg:    rgba(124, 58, 237, 0.20);
    --admin-sidebar-active-text:  #C4B5FD;
    --admin-sidebar-active-border:#7C3AED;

    /* Status — fixed semantic, tidak dikustomisasi */
    --admin-success:  #10B981;
    --admin-warning:  #F59E0B;
    --admin-danger:   #EF4444;
    --admin-info:     #06B6D4;

    /* Radius */
    --admin-radius-sm:  6px;
    --admin-radius-md:  8px;
    --admin-radius-lg:  12px;
    --admin-radius-xl:  16px;
}
```

### 3.2 Ganti Hardcoded Values dengan var()

**Prioritas urutan penggantian:**

1. `.admin-body` → `background-color: var(--admin-bg-base)`, `color: var(--admin-text-primary)`
2. `.admin-shell` → ganti gradient Tailwind dengan `background: var(--admin-bg-base)` di `@apply`, tambahkan dot-grid pattern
3. `.admin-card` → ganti bg-white dan border-slate-200 dengan var
4. `.admin-card-header` → ganti bg-slate-50 dan border dengan var
5. `.admin-btn-primary` → ganti bg-indigo-600 dengan bg-violet-600 + shadow var
6. `.admin-input/.admin-select/.admin-textarea` → ganti bg-white, border, text dengan var
7. `.admin-sidebar` → ganti bg-slate-900 dengan var(--admin-sidebar-bg)
8. `.admin-sidebar__link` → ganti text-slate-400/white dengan var
9. `.admin-sidebar__link--active` → ganti bg-indigo-600 dengan var
10. `.admin-topbar` → ganti bg-white dengan var based dark
11. `.admin-table-wrapper, .admin-table-header, .admin-table-row` → var
12. `.admin-badge-*` → var (akan direfine di Step 08)
13. `.admin-empty-state` → var

**Penting:** Untuk Tailwind `@apply`, properties yang bisa pakai var() langsung:
- Gunakan `@apply` tetap untuk utility classes yang tersedia (rounded, flex, dll.)
- Untuk warna yang perlu jadi var(), gunakan CSS property langsung (bukan @apply)

Contoh pola:
```css
/* SEBELUM */
.admin-card {
    @apply overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm;
}

/* SESUDAH */
.admin-card {
    @apply overflow-hidden;
    border-radius: var(--admin-radius-lg);
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}
```

---

## Phase 4 — Verifikasi

Setelah selesai, jalankan pengecekan ini:

**4.1 CSS Var Count Check**
```
Berapa banyak var(--admin-*) yang muncul di admin.css?
Target: minimal 30 occurrences.
```

**4.2 Orphan Hex Check**
Cari apakah masih ada hardcoded hex yang belum diganti (kecuali yang memang intentional):
```bash
grep -n "#[0-9A-Fa-f]\{3,6\}" resources/css/admin.css
```
Setiap hex yang tersisa harus ada alasannya (misal: rgba di gradient, atau nilai
yang memang tidak perlu var karena fixed).

**4.3 Syntax Check**
```bash
php artisan vite:build 2>&1 | tail -20
# atau
npx vite build 2>&1 | tail -20
```
Tidak boleh ada CSS syntax error.

**4.4 Visual Check (manual)**
Buka admin di browser → pastikan:
- [ ] Sidebar terlihat gelap (tidak blank/white)
- [ ] Card terlihat (tidak invisible)
- [ ] Button masih terlihat dan clickable
- [ ] Input field masih terlihat
- [ ] Tidak ada area yang hilang atau invisible

---

## Phase 5 — Buat Handoff

Buat file `ai/reports/UIUX/step-01-handoff.md` dengan format:

```markdown
# Step 01 Handoff — CSS Custom Properties Foundation
**Tanggal:** [isi]
**Status:** ✅ Complete / ❌ Blocked / ⚠️ Partial
**Branch:** feature/admin-uiux-redesign

## Yang Dikerjakan
- [ ] :root block ditambahkan di admin.css
- [ ] Semua hex warna utama diganti dengan var()
- [ ] CSS build sukses (no errors)
- [ ] Visual check di browser: OK

## Hasil
- Total var() yang digunakan: [jumlah]
- Hex yang tersisa (intentional): [list alasan]
- Build output: [sukses/error]

## Temuan / Catatan
[Tulis temuan apapun selama inspeksi]

## Perubahan File
- `resources/css/admin.css` — refactor [X] color values → var()

## Risk Yang Terjadi
[Apakah ada masalah? Bagaimana diselesaikan?]

## Rollback
git checkout HEAD -- resources/css/admin.css

## Next Step
Step 02 — Shell & Sidebar Redesign
File prompt: ai/promt/uiux/step-02-shell-sidebar.md
Prerequisite: Step 01 ini harus ✅ sebelum mulai Step 02
```

---

## STOP

Setelah handoff dibuat: **BERHENTI.** Jangan mulai Step 02 di sesi yang sama.
Tunggu owner verifikasi visual → approve → buka sesi baru untuk Step 02.
