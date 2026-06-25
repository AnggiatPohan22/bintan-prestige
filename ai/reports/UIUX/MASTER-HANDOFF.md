# MASTER HANDOFF — Admin UI/UX Redesign
## Bintan Prestige CMS — "Command Center Dark" Aesthetic

**Terakhir diupdate:** 2026-06-25
**Session yang membuat:** claude-sonnet-4-6

---

## 📋 CARA PAKAI FILE INI

### Untuk melanjutkan di sesi Claude baru atau Claude Codex:

```
Paste instruksi berikut ke sesi baru:

"Baca file ai/reports/UIUX/MASTER-HANDOFF.md untuk melanjutkan pekerjaan
Admin UI/UX Redesign. File ini berisi semua konteks, progress, dan instruksi
untuk melanjutkan step berikutnya."
```

File ini adalah **single source of truth**. Selalu update setelah setiap step selesai.

---

## 🎯 TUJUAN PROJECT

Transformasi admin dashboard Bintan Prestige CMS dari generic white/indigo Tailwind
menjadi **"Command Center Dark"** aesthetic:

- **Dark base:** 5-layer surface system
- **Primary:** Electric Violet `#7C3AED`
- **Accent:** Cyan `#06B6D4`
- **Brand hint:** Gold `#D4AF37`
- **Glassmorphism** untuk floating elements
- **Micro-interactions** pada semua interactive elements

---

## 🏗️ ARSITEKTUR CSS

### File Utama
- **`resources/css/admin.css`** — satu-satunya file yang diubah di Fase A-B
- `npx vite build` untuk compile ke `public/build/assets/app-*.css`
- Rollback Fase A-B: `git checkout HEAD -- resources/css/admin.css && npx vite build`

### CSS Variable System

Semua warna di-define via CSS custom properties di `:root {}` inside `@layer components`:

```css
:root {
    /* Surfaces */
    --admin-bg-base:       #020617;   /* konten body */
    --admin-bg-surface:    #0F172A;   /* sidebar bg, raised sections */
    --admin-bg-card:       #1E293B;   /* cards, panels */
    --admin-bg-input:      #0F172A;   /* form inputs */
    --admin-bg-hover:      #334155;   /* hover state */

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

    /* Sidebar specific */
    --admin-sidebar-bg:            #020617;
    --admin-sidebar-border:        rgba(255, 255, 255, 0.06);
    --admin-sidebar-text:          #64748B;
    --admin-sidebar-text-hover:    #CBD5E1;
    --admin-sidebar-active-bg:     rgba(124, 58, 237, 0.20);
    --admin-sidebar-active-text:   #C4B5FD;
    --admin-sidebar-active-border: #7C3AED;

    /* Status — semantic, tidak berubah dengan tema */
    --admin-success: #10B981;
    --admin-warning: #F59E0B;
    --admin-danger:  #EF4444;
    --admin-info:    #06B6D4;

    /* Radius */
    --admin-radius-sm: 6px;
    --admin-radius-md: 8px;
    --admin-radius-lg: 12px;
    --admin-radius-xl: 16px;
}
```

### Layer Precedence (Penting!)

```
@layer utilities  (Tailwind generated)  ← PRIORITAS TERTINGGI
@layer components (admin.css kita)      ← di bawah utilities
```

**Implikasi:** Tailwind inline classes di Blade (`text-slate-800`) menang atas CSS vars kita.
Solusi: Blade cleanup sudah dilakukan (step 06 — tidak ada lagi dark text di dark bg).

---

## ✅ PROGRESS CHECKLIST

### FASE A — CSS Foundation

| Step | Scope | Risk | Status | Handoff |
|------|-------|------|--------|---------|
| **01** | `:root` CSS vars + semua admin-* classes dark | 🟡 Medium | ✅ DONE | [step-01-handoff.md](step-01-handoff.md) |
| **02** | Shell dark base + Sidebar full redesign | 🟡 Medium | ✅ DONE | [step-02-handoff.md](step-02-handoff.md) |
| **03** | Topbar dark + Search + User menu dropdown | 🟢 Low | ✅ DONE | [step-03-handoff.md](step-03-handoff.md) |
| **04** | Cards, panels, page headers + card variants | 🟡 Medium | ✅ DONE | [step-04-handoff.md](step-04-handoff.md) |
| **05** | Forms, inputs, selects, textarea dark | 🟡 Medium | ✅ DONE | [step-05-handoff.md](step-05-handoff.md) |
| **06** | Tables dark + Blade text color cleanup | 🟢 Low | ✅ DONE | [step-06-handoff.md](step-06-handoff.md) |
| **07** | Buttons violet + size variants + icon btn | 🔴 High | ✅ DONE | [step-07-handoff.md](step-07-handoff.md) |
| **08** | Badges dark-optimized + dot indicator + neutral | 🟢 Low | ✅ DONE | [step-08-handoff.md](step-08-handoff.md) |
| **09** | Modal glass + Toast dark + Empty state | 🟢 Low | ✅ DONE | [step-09-handoff.md](step-09-handoff.md) |

### FASE B — Light Mode

| Step | Scope | Risk | Status | Handoff |
|------|-------|------|--------|---------|
| **10** | `[data-admin-mode="light"]` CSS overrides | 🟡 Medium | ✅ DONE | [step-10-handoff.md](step-10-handoff.md) |

### FASE C — Dashboard Home

| Step | Scope | Risk | Status | Handoff |
|------|-------|------|--------|---------|
| **11** | KPI stat cards + quick links + recent activity | 🟡 Medium | ✅ DONE | [step-11-handoff.md](step-11-handoff.md) |

### FASE D — Appearance Customizer (⚠️ butuh owner approval Step 12)

| Step | Scope | Risk | Status | Handoff |
|------|-------|------|--------|---------|
| **12** | Migration `admin_dashboard_appearances` + Model | 🔴 High | ✅ DONE | [step-12-handoff.md](step-12-handoff.md) |
| **13** | AdminAppearanceService + ViewComposer | 🟡 Medium | ✅ DONE | [step-13-handoff.md](step-13-handoff.md) |
| **14** | DashboardAppearanceController + Routes | 🟡 Medium | ✅ DONE | [step-14-handoff.md](step-14-handoff.md) |
| **15** | Blade UI: Settings > Customize Dashboard | 🟡 Medium | ✅ DONE | [step-15-handoff.md](step-15-handoff.md) |
| **16** | Alpine.js live preview + Accessibility QA | 🟢 Low | ⬜ TODO | — |

---

## 🔍 NEXT STEP — Step 16: Final QA + Accessibility

**Risk:** 🟢 Low
**Scope:** Keyboard nav, WCAG AA contrast, screen reader, smoke test semua steps, docs update

---

## ⚠️ KNOWN ISSUES & CATATAN PENTING

### 1. `bg-white` Tersisa di Beberapa Blade Files (Non-blocking)

File-file berikut masih punya `bg-white` tapi **intentional**:
- `builder/partials/canvas.blade.php` — canvas preview harus putih
- `media/*.blade.php` — media picker modal
- `menus/edit.blade.php` — menu item cards (perlu review manual)
- `page-sections/*.blade.php` — section preview areas
- `pages/edit.blade.php` — content editor area

Action: Review manual per-file saat Step 11 (Dashboard Home) atau sebelum release.

### 2. `border-slate-200` Tersisa di Blade Files (Non-blocking)

Border warna terang akan hampir invisible di dark card bg. Ini secondary concern —
tidak rusak secara fungsional tapi bisa kurang visible. Bisa difix di Blade cleanup pass berikutnya.

### 3. Step 12 Butuh Approval Eksplisit Owner

Jangan jalankan Step 12 tanpa approval tertulis dari owner karena membuat DB migration baru.

---

## 📁 STRUKTUR FILE

```
ai/
├── promt/uiux/
│   ├── 00-overview-and-checklist.md   ← checklist original (less up-to-date dari file ini)
│   ├── step-01-css-variables.md       ← prompt untuk step 01
│   ├── step-02-shell-sidebar.md
│   ├── step-03-topbar.md
│   ├── step-04-cards-panels.md
│   ├── step-05-forms-inputs.md
│   ├── step-06-tables.md
│   ├── step-07-buttons.md
│   ├── step-08-badges.md
│   ├── step-09-modals-toast-empty.md
│   ├── step-10-light-mode.md
│   ├── step-11-dashboard-home.md
│   ├── step-12-migration-model.md
│   ├── step-13-service-composer.md
│   ├── step-14-controller-routes.md
│   ├── step-15-settings-ui.md
│   └── step-16-live-preview-qa.md
│
└── reports/UIUX/
    ├── MASTER-HANDOFF.md              ← FILE INI (selalu update)
    ├── grand-master-plan-admin-uiux.md
    ├── step-01-handoff.md             ✅
    ├── step-02-handoff.md             ✅
    ├── step-03-handoff.md             ✅
    ├── step-04-handoff.md             ✅
    ├── step-05-handoff.md             ✅
    ├── step-06-handoff.md             ✅
    ├── step-07-handoff.md             ✅
    ├── step-08-handoff.md             ✅
    ├── step-09-handoff.md             ✅
    ├── step-10-handoff.md             ✅
    ├── step-11-handoff.md             ✅
    ├── step-12-handoff.md             ✅
    ├── step-13-handoff.md             ✅
    ├── step-14-handoff.md             ✅
    ├── step-15-handoff.md             ✅
    └── step-16-handoff.md             ⬜ belum ada
```

---

## 🔧 QUICK COMMANDS

```bash
# Build CSS
npx vite build

# Rollback CSS only (Fase A-B)
git checkout HEAD -- resources/css/admin.css && npx vite build

# Cek dark text yang tersisa di Blade
grep -rn "text-slate-[789]00\|text-slate-950" resources/views/backend/ --include="*.blade.php"

# Cek penggunaan admin-btn classes
grep -rn "admin-btn" resources/views/backend/ --include="*.blade.php" | head -20

# Clear view cache setelah Blade changes
php artisan view:clear
```

---

## 📝 BUGS YANG SUDAH DIPERBAIKI (Jangan Ulangi)

| Step | Bug | Fix |
|------|-----|-----|
| 01→03 | `.admin-topbar__search span` target semua span termasuk label | Diubah ke `span:last-child` |
| 01→04 | `.admin-stat-card` conflict `hover:-translate-y-0.5` + CSS hover | Hapus dari @apply, pakai CSS property saja |
| 01→05 | Error state `.admin-input.error` tidak cover select/textarea | Pakai `:is(.error, [aria-invalid="true"])` |
| 02 | `.admin-sidebar__group-btn:hover .admin-sidebar__icon` missing | Ditambahkan selector baru |

---

## 🎨 DESIGN DECISIONS (Untuk Referensi)

1. **Semantic status colors (success/warning/danger/info) TIDAK berubah** — mereka seperti traffic light, harus konsisten global
2. **Glassmorphism hanya untuk floating elements** (dropdown, modal) — bukan untuk cards statis
3. **`:root` di dalam `@layer components`** — bukan di luar — untuk runtime override via `<style id="admin-appearance-vars">`
4. **CSS class names TIDAK pernah diubah** — hanya VALUES yang berubah untuk zero-breaking-change

---

## 🚦 DEPENDENCY GRAPH

```
Step 01 (CSS vars) ← FOUNDATION SEMUA
    ↓
    ├── Step 02 (Sidebar)     ✅
    ├── Step 03 (Topbar)      ✅
    ├── Step 04 (Cards)       ✅
    ├── Step 05 (Forms)       ✅
    ├── Step 06 (Tables)      ✅
    ├── Step 07 (Buttons)     ✅
    ├── Step 08 (Badges)      ← NEXT
    └── Step 09 (Modals/Toast)
            ↓
        Step 10 (Light Mode)     ✅
            ↓
        Step 11 (Dashboard Home)  ✅ Blade changes, bukan CSS only
            ↓
        [OWNER APPROVAL]
            ↓
        Step 12 (DB Migration)    ← PERLU APPROVAL EKSPLISIT
            ↓
        Step 13 (Service)
            ↓
        Step 14 (Controller)
            ↓
        Step 15 (Settings UI)
            ↓
        Step 16 (Live Preview + QA)  ← FINAL
```

---

*File ini dibuat otomatis oleh Claude Sonnet 4.6 pada 2026-06-24.*
*Update file ini setiap kali step selesai — ubah ⬜ TODO → ✅ DONE dan tambahkan link handoff.*
