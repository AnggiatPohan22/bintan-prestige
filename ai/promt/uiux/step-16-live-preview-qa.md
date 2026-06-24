# STEP 16 — Final QA, Accessibility & Smoke Test
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–15 ✅ (semua selesai)

**Scope:**
1. Full accessibility audit (contrast ratios semua mode)
2. Smoke test semua halaman admin utama
3. Customizer end-to-end test (dark → hotel luxury → save → verify)
4. Mobile responsive check
5. Sidebar link untuk Customize Dashboard (tambah ke sidebar jika belum ada)
6. Dokumentasi final

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 13 (Accessibility)
- `AGENTS.md` §11 (Report Format)

---

## Risk Assessment

**Risk: 🟢 Low**

Step ini adalah QA — minimal file changes. Kemungkinan:
- Tambah/fix CSS jika ada contrast issue yang ditemukan
- Tambah sidebar link ke Customize Dashboard
- Fix minor Blade issues

---

## Phase 1 — Sidebar Link untuk Customize Dashboard

Cek `resources/views/components/admin/sidebar.blade.php`.
Tambahkan link "Customize Dashboard" di section Settings (hanya tampil untuk super admin):

```blade
{{-- Hanya tampil untuk super admin --}}
@if(auth()->user()?->isSuperAdmin())
<a href="{{ route('admin.settings.appearance') }}"
   class="admin-sidebar__link {{ request()->routeIs('admin.settings.appearance*') ? 'admin-sidebar__link--active' : '' }}"
   @if(request()->routeIs('admin.settings.appearance*')) aria-current="page" @endif>
    <span class="admin-sidebar__icon">
        <i class="fa-solid fa-palette" aria-hidden="true"></i>
    </span>
    <span>Customize Dashboard</span>
</a>
@endif
```

---

## Phase 2 — Accessibility Audit

### 2.1 Contrast Ratio Check (manual atau via DevTools)

Buka browser DevTools → Accessibility tab → check contrast.

**Dark Mode checklist:**

| Element | Text | Background | Expected | Status |
|---------|------|-----------|---------|--------|
| Nav link text | `#64748B` | `#020617` | ≥ 3:1 | ⬜ |
| Nav link hover | `#CBD5E1` | `#020617` | ≥ 4.5:1 | ⬜ |
| Nav link active | `#C4B5FD` | `#020617` | ≥ 4.5:1 | ⬜ |
| Card body text | `#94A3B8` | `#1E293B` | ≥ 4.5:1 | ⬜ |
| Page title | `#F1F5F9` | `#1E293B` | ≥ 7:1 | ⬜ |
| Input text | `#F1F5F9` | `#0F172A` | ≥ 7:1 | ⬜ |
| Input placeholder | `#64748B` | `#0F172A` | ≥ 3:1 | ⬜ |
| Table header | `#64748B` | `#0F172A` | ≥ 3:1 | ⬜ |
| Badge success | `#34D399` | `rgba(16,185,129,0.12)` | ≥ 3:1 | ⬜ |
| Primary button | `#FFFFFF` | `#7C3AED` | ≥ 4.5:1 | ⬜ |

**Light Mode checklist:**

| Element | Text | Background | Expected | Status |
|---------|------|-----------|---------|--------|
| Nav link (dark sidebar) | `#64748B` | `#0F172A` | ≥ 3:1 | ⬜ |
| Card body text | `#475569` | `#FFFFFF` | ≥ 4.5:1 | ⬜ |
| Input text | `#0F172A` | `#FFFFFF` | ≥ 7:1 | ⬜ |
| Badge success | `#065F46` | `#D1FAE5` | ≥ 4.5:1 | ⬜ |

### 2.2 Keyboard Navigation Check

- [ ] Tab melalui sidebar links — semua terfokus dengan visible ring
- [ ] Tab melalui topbar — search, notification, user menu
- [ ] Tab melalui form — semua input, select, textarea, button
- [ ] Escape menutup user menu dropdown

### 2.3 Focus Ring Check

- [ ] Primary button: `ring-2` visible dengan violet/brand color
- [ ] Input: focus ring visible
- [ ] Sidebar link: focus ring tidak hidden oleh border

---

## Phase 3 — Smoke Test Halaman Utama

Buka setiap halaman, pastikan tidak ada:
- PHP error atau Blade error
- CSS broken (invisible elements, missing backgrounds)
- JavaScript console errors

```
Halaman wajib di-test:
[ ] /admin/dashboard
[ ] /admin/products
[ ] /admin/products/create
[ ] /admin/products/{id}/edit
[ ] /admin/bookings
[ ] /admin/pages
[ ] /admin/pages/create
[ ] /admin/media
[ ] /admin/menus
[ ] /admin/settings
[ ] /admin/settings/dashboard-appearance (baru)
[ ] /admin/users
[ ] /admin/analytics (jika ada)
[ ] /admin/plugins (jika ada)
```

---

## Phase 4 — Customizer End-to-End Test

1. **Login sebagai super admin**

2. **Test Dark → Hotel Luxury:**
   - Buka `/admin/settings/dashboard-appearance`
   - Klik preset "Hotel Luxury"
   - Lihat live preview: sidebar dark, konten putih, tombol cokelat
   - Klik "Simpan Perubahan"
   - Redirect → flash success
   - Semua halaman admin: tema Hotel Luxury aktif ✓

3. **Test reset:**
   - Kembali ke Customize Dashboard
   - Klik "Reset ke Default"
   - Semua halaman: kembali ke Command Center Dark ✓

4. **Test custom color:**
   - Ubah primary_color ke `#059669` (hijau)
   - Simpan
   - Cek: button hijau, active link hijau, focus ring hijau ✓

5. **Test non-super-admin:**
   - Login sebagai admin biasa
   - Akses `/admin/settings/dashboard-appearance` → 403 ✓
   - Sidebar: link "Customize Dashboard" tidak muncul ✓

---

## Phase 5 — Cache & Performance

```bash
# Clear all caches setelah semua testing
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Rebuild assets
npm run build
# atau
npx vite build
```

---

## Phase 6 — Dokumentasi Final

Update `ai/promt/uiux/00-overview-and-checklist.md`:
- Tandai semua step sebagai ✅

Buat `ai/reports/UIUX/step-16-handoff.md`:

```markdown
# Step 16 Handoff — Final QA & Smoke Test
**Tanggal:** [isi]
**Status:** ✅ Complete
**Branch:** feature/admin-uiux-redesign

## QA Summary

### Accessibility
- Dark mode contrast: [PASS/FAIL + issues]
- Light mode contrast: [PASS/FAIL + issues]
- Keyboard navigation: [PASS/FAIL]
- Focus rings: [PASS/FAIL]

### Smoke Test
- Pages tested: [jumlah]/[total]
- Errors found: [list atau "none"]
- Errors fixed: [list atau "none"]

### Customizer E2E
- Dark → Hotel Luxury: [PASS/FAIL]
- Reset to default: [PASS/FAIL]
- Custom color: [PASS/FAIL]
- 403 for non-super-admin: [PASS/FAIL]

## Files Changed in This Step
- resources/views/components/admin/sidebar.blade.php — tambah Customize link
- resources/css/admin.css — [jika ada contrast fix]

## Final File List (All Steps)
- resources/css/admin.css
- resources/views/layouts/admin.blade.php
- resources/views/backend/dashboard.blade.php
- resources/views/components/admin/sidebar.blade.php
- resources/views/admin/settings/appearance/index.blade.php (baru)
- app/Models/AdminDashboardAppearance.php (baru)
- app/Services/AdminAppearanceService.php (baru)
- app/View/Composers/AdminAppearanceComposer.php (baru)
- app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php (baru)
- app/Http/Controllers/Admin/DashboardAppearanceController.php (baru)
- app/Providers/AppServiceProvider.php (diupdate)
- database/migrations/[timestamp]_create_admin_dashboard_appearances_table.php (baru)
- database/seeders/AdminDashboardAppearanceSeeder.php (baru)
- config/admin_appearance_presets.php (baru)
- routes/web.php (diupdate — 3 route baru)

## Rollback Full Feature D (Customizer)
php artisan migrate:rollback
git revert [step-12..step-16 commits]

## Status Grand Master Plan
✅ Fase A (CSS Foundation) — selesai
✅ Fase B (Light Mode) — selesai
✅ Fase C (Dashboard Home) — selesai
✅ Fase D (Appearance Customizer) — selesai

Grand Master Plan: COMPLETE 🎉
```

---

## COMPLETE — Admin UI/UX Redesign selesai.

Siap untuk PR: `feature/admin-uiux-redesign` → `develop`
