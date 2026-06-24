# UI/UX Admin Dashboard — Step-by-Step Implementation Plan
## Bintan Prestige CMS — Grand Master Plan Execution

**Dibuat:** 2026-06-23
**Referensi:** `ai/reports/UIUX/grand-master-plan-admin-uiux.md`
**Target Branch:** `feature/admin-uiux-redesign`
**Handoff Dir:** `ai/reports/UIUX/` (setiap step buat handoff masing-masing)

---

## Cara Pakai Dokumen Ini

1. Cek checklist di bawah — pilih step berikutnya yang belum ✅
2. Buka file prompt step tersebut (mis. `step-01-css-variables.md`)
3. Copy seluruh isi file prompt → paste ke sesi Claude baru
4. Jalankan step → verifikasi → tandai ✅ di checklist ini
5. Pastikan step tersebut sudah buat `handoff.md` di `ai/reports/UIUX/`
6. Baru lanjut ke step berikutnya

**Jangan loncat step.** Tiap step bergantung pada step sebelumnya.
Tiap step harus selesai + handoff dibuat sebelum lanjut.

---

## Master Checklist

### FASE A — CSS Foundation (Zero DB, Zero Logic Change)
> Semua step di sini hanya mengubah `resources/css/admin.css`.
> Rollback: `git checkout HEAD -- resources/css/admin.css`

| Step | File Prompt | Scope | Risk | Status |
|------|------------|-------|------|--------|
| **01** | `step-01-css-variables.md` | Refactor admin.css: semua hex → `var(--admin-*)` + define `:root` defaults | 🟡 Medium | ⬜ |
| **02** | `step-02-shell-sidebar.md` | Shell dark base + Sidebar full redesign (brand, links, icon, section label) | 🟡 Medium | ⬜ |
| **03** | `step-03-topbar.md` | Topbar dark + Search bar dark + User menu dark + Dropdown glass | 🟢 Low | ⬜ |
| **04** | `step-04-cards-panels.md` | admin-card, admin-form-card, admin-page-header dark + new card variants | 🟡 Medium | ⬜ |
| **05** | `step-05-forms-inputs.md` | admin-input, admin-select, admin-textarea, admin-form-label, admin-form-hint dark | 🟡 Medium | ⬜ |
| **06** | `step-06-tables.md` | admin-table-wrapper, header, row dark + precision typography | 🟢 Low | ⬜ |
| **07** | `step-07-buttons.md` | admin-btn-primary violet + semua button variants + micro-interaction | 🔴 High | ⬜ |
| **08** | `step-08-badges.md` | admin-badge-* dark-optimized + dot indicator + neutral badge baru | 🟢 Low | ⬜ |
| **09** | `step-09-modals-toast-empty.md` | Modal glass + Toast dark slide + Empty state stripe pattern | 🟢 Low | ⬜ |

### FASE B — Light Mode (CSS only, extends Phase A)
> Prerequisite: Fase A selesai semua.

| Step | File Prompt | Scope | Risk | Status |
|------|------------|-------|------|--------|
| **10** | `step-10-light-mode.md` | `[data-admin-mode="light"]` overrides + Light sidebar variant + badge light | 🟡 Medium | ⬜ |

### FASE C — Dashboard Home Redesign (Blade + Controller)
> Prerequisite: Step 01 selesai (CSS vars tersedia).

| Step | File Prompt | Scope | Risk | Status |
|------|------------|-------|------|--------|
| **11** | `step-11-dashboard-home.md` | KPI stat cards + quick links + recent activity layout di dashboard.blade.php | 🟡 Medium | ⬜ |

### FASE D — Appearance Customizer (⚠️ Butuh Approval Owner untuk Step 12)
> Prerequisite: Fase A + Step 10 selesai. Step 12 butuh approval schema change.

| Step | File Prompt | Scope | Risk | Status |
|------|------------|-------|------|--------|
| **12** | `step-12-migration-model.md` | ⚠️ Migration `admin_dashboard_appearances` + Model + Seeder | 🔴 High | ⬜ |
| **13** | `step-13-service-composer.md` | AdminAppearanceService + ViewComposer + AppServiceProvider + admin.blade.php inject | 🟡 Medium | ⬜ |
| **14** | `step-14-controller-routes.md` | DashboardAppearanceController + FormRequest + 3 Routes | 🟡 Medium | ⬜ |
| **15** | `step-15-settings-ui.md` | Blade UI: Settings > Customize Dashboard (preset cards + color pickers) | 🟡 Medium | ⬜ |
| **16** | `step-16-live-preview-qa.md` | Alpine.js live preview + Accessibility contrast QA + Final smoke test | 🟢 Low | ⬜ |

---

## Dependency Graph

```
Step 01 (CSS vars)
    ↓
    ├── Step 02 (Sidebar)
    ├── Step 03 (Topbar)
    ├── Step 04 (Cards)
    ├── Step 05 (Forms)
    ├── Step 06 (Tables)
    ├── Step 07 (Buttons) ← paling visible, test dulu di staging
    ├── Step 08 (Badges)
    └── Step 09 (Modals/Toast/Empty)
            ↓
        Step 10 (Light Mode)  ←─────────────────────────────────────┐
            ↓                                                        │
        Step 11 (Dashboard Home)  ← bisa paralel dengan Step 10     │
            ↓                                                        │
        [OWNER APPROVAL UNTUK STEP 12]                              │
            ↓                                                        │
        Step 12 (Migration + Model)  ← prerequisite semua step D    │
            ↓                                                        │
        Step 13 (Service + Composer)                                 │
            ↓                                                        │
        Step 14 (Controller + Routes)                               │
            ↓                                                        │
        Step 15 (Settings UI)                                       │
            ↓                                                        │
        Step 16 (Live Preview + QA)  ────────────────────────────────┘
```

---

## Risk Level Legend

| Level | Simbol | Artinya |
|-------|--------|---------|
| Low | 🟢 | CSS-only, tidak ada area yang high-visibility |
| Medium | 🟡 | CSS yang mempengaruhi area penting, atau Blade file change |
| High | 🔴 | Primary button warna berubah (Step 07) ATAU DB schema change (Step 12) |

---

## Handoff File Convention

Setiap step wajib membuat file handoff di `ai/reports/UIUX/`:

```
step-01-handoff.md   ← dibuat oleh Step 01
step-02-handoff.md   ← dibuat oleh Step 02
...
step-16-handoff.md   ← dibuat oleh Step 16
```

Template handoff sudah ada di setiap file prompt (bagian akhir).

---

## Git Convention

```bash
# Satu branch untuk semua step Fase A-C
git checkout -b feature/admin-uiux-redesign

# Setiap step: commit focused
git add resources/css/admin.css
git commit -m "style(admin): step 01 — refactor hex to CSS custom properties"

# Step 07 (button color change) — minta visual review sebelum merge
# Step 12 (migration) — minta explicit approval sebelum commit
```

---

## Rollback Per Fase

| Fase | Rollback |
|------|----------|
| A (CSS) | `git checkout HEAD -- resources/css/admin.css` |
| B (Light) | Revert step-10 commit |
| C (Dashboard) | `git checkout HEAD -- resources/views/backend/dashboard.blade.php` |
| D (Customizer) | Revert step-12–16 commits + `php artisan migrate:rollback` |
