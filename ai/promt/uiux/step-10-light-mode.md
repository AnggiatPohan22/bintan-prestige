# STEP 10 — Light Mode Variant
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–09 ✅ (Fase A selesai semua)

**Scope:** Tambahkan `[data-admin-mode="light"]` dan `[data-admin-sidebar="light"]`
CSS overrides di `admin.css`. Light mode tidak mengubah class names — hanya
menambah selector-based overrides yang aktif ketika attribute ada di `<html>`.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 5 (Light Mode)

---

## Risk Assessment

**Risk: 🟡 Medium**

Light mode selector hanya aktif jika `data-admin-mode="light"` ada di `<html>` tag.
Tanpa attribute ini, dark mode tetap default. Zero regression untuk tampilan saat ini.

Medium karena: jika overrides tidak lengkap → ada "half-dark half-light" mix
yang terlihat aneh saat mode dialihkan via Customizer nanti.

---

## Phase 3 — Implementasi

Tambahkan semua override berikut di **akhir** `admin.css`, setelah semua existing classes:

```css
/* ============================================================
   LIGHT MODE OVERRIDES
   Aktif ketika: <html data-admin-mode="light">
   Diset oleh: Appearance Customizer (Step 13) atau manual
   ============================================================ */

[data-admin-mode="light"] {
    /* Surfaces */
    --admin-bg-base:       #F8FAFC;
    --admin-bg-surface:    #FFFFFF;
    --admin-bg-card:       #FFFFFF;
    --admin-bg-input:      #FFFFFF;
    --admin-bg-hover:      #F1F5F9;

    /* Borders */
    --admin-border:        #E2E8F0;
    --admin-border-md:     #CBD5E1;
    --admin-border-strong: #94A3B8;

    /* Text */
    --admin-text-primary:   #0F172A;
    --admin-text-secondary: #475569;
    --admin-text-muted:     #94A3B8;

    /* Primary — tetap violet (konsisten) */
    --admin-primary:        #7C3AED;
    --admin-primary-hover:  #6D28D9;
    --admin-primary-soft:   #EDE9FE;
    --admin-primary-glow:   rgba(124, 58, 237, 0.15);

    /* Sidebar (default: tetap dark di light classic) */
    --admin-sidebar-bg:           #0F172A;
    --admin-sidebar-border:       rgba(255, 255, 255, 0.06);
    --admin-sidebar-text:         #64748B;
    --admin-sidebar-text-hover:   #CBD5E1;
    --admin-sidebar-active-bg:    rgba(124, 58, 237, 0.20);
    --admin-sidebar-active-text:  #C4B5FD;
    --admin-sidebar-active-border:#7C3AED;
}

/* Light Classic: dark sidebar + light content (default light) */
[data-admin-mode="light"] .admin-shell {
    background: var(--admin-bg-base);
    background-image: none; /* hapus dot grid */
}

[data-admin-mode="light"] .admin-body {
    background: var(--admin-bg-base);
}

[data-admin-mode="light"] .admin-topbar {
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid #E2E8F0;
    backdrop-filter: blur(12px);
}

[data-admin-mode="light"] .admin-topbar__search {
    background: #F8FAFC;
    border-color: #E2E8F0;
    color: #64748B;
}
[data-admin-mode="light"] .admin-topbar__search:hover {
    border-color: #7C3AED;
    background: #FFFFFF;
}

[data-admin-mode="light"] .admin-topbar__icon-button {
    color: #64748B;
}
[data-admin-mode="light"] .admin-topbar__icon-button:hover {
    background: #F1F5F9;
    color: #0F172A;
}

[data-admin-mode="light"] .admin-topbar__breadcrumb {
    color: #94A3B8;
}
[data-admin-mode="light"] .admin-topbar__breadcrumb span {
    color: #475569;
}

[data-admin-mode="light"] .admin-user-menu__trigger:hover {
    background: #F1F5F9;
}

[data-admin-mode="light"] .admin-user-menu__dropdown {
    background: rgba(255, 255, 255, 0.98);
    border-color: #E2E8F0;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

[data-admin-mode="light"] .admin-card {
    background: #FFFFFF;
    border-color: #E2E8F0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
}

[data-admin-mode="light"] .admin-card-header {
    background: #F8FAFC;
    border-bottom-color: #E2E8F0;
}

[data-admin-mode="light"] .admin-form-card {
    background: #FFFFFF;
    border-color: #E2E8F0;
}

[data-admin-mode="light"] .admin-page-header {
    background: #FFFFFF;
    border-color: #E2E8F0;
}

[data-admin-mode="light"] .admin-table-wrapper {
    background: #FFFFFF;
    border-color: #E2E8F0;
}
[data-admin-mode="light"] .admin-table-header {
    background: #F8FAFC;
    border-bottom-color: #E2E8F0;
}
[data-admin-mode="light"] .admin-table-header th {
    color: #94A3B8;
}
[data-admin-mode="light"] .admin-table-row {
    border-bottom-color: #F1F5F9;
}
[data-admin-mode="light"] .admin-table-row td {
    color: #475569;
}
[data-admin-mode="light"] .admin-table-row td:first-child {
    color: #0F172A;
}
[data-admin-mode="light"] .admin-table-row:hover {
    background: #F8FAFC;
}

/* Light Full — sidebar juga putih */
[data-admin-sidebar="light"] .admin-sidebar {
    background: #FFFFFF;
    border-right: 1px solid #E2E8F0;
}
[data-admin-sidebar="light"] .admin-sidebar__brand {
    border-bottom-color: #E2E8F0;
}
[data-admin-sidebar="light"] .admin-sidebar__subtitle {
    color: var(--admin-gold);
    opacity: 0.9;
}
[data-admin-sidebar="light"] .admin-sidebar__link {
    color: #64748B;
}
[data-admin-sidebar="light"] .admin-sidebar__link:hover {
    background: #F1F5F9;
    color: #0F172A;
}
[data-admin-sidebar="light"] .admin-sidebar__link--active {
    background: #EDE9FE;
    color: #7C3AED;
}
[data-admin-sidebar="light"] .admin-sidebar__icon {
    color: #94A3B8;
}
[data-admin-sidebar="light"] .admin-sidebar__link:hover .admin-sidebar__icon,
[data-admin-sidebar="light"] .admin-sidebar__link--active .admin-sidebar__icon {
    color: #7C3AED;
}
[data-admin-sidebar="light"] .admin-sidebar-toggle {
    background: #FFFFFF;
    border-color: #E2E8F0;
    color: #64748B;
}

/* Badges — light-optimized */
[data-admin-mode="light"] .admin-badge-success {
    background: #D1FAE5;
    color: #065F46;
}
[data-admin-mode="light"] .admin-badge-success::before { background: #059669; }

[data-admin-mode="light"] .admin-badge-warning {
    background: #FEF3C7;
    color: #92400E;
}
[data-admin-mode="light"] .admin-badge-warning::before { background: #D97706; }

[data-admin-mode="light"] .admin-badge-danger {
    background: #FEE2E2;
    color: #991B1B;
}
[data-admin-mode="light"] .admin-badge-danger::before { background: #DC2626; }

[data-admin-mode="light"] .admin-badge-info {
    background: #ECFEFF;
    color: #155E75;
}
[data-admin-mode="light"] .admin-badge-info::before { background: #0891B2; }

/* Input light */
[data-admin-mode="light"] .admin-input,
[data-admin-mode="light"] .admin-select,
[data-admin-mode="light"] .admin-textarea {
    background: #FFFFFF;
    border-color: #CBD5E1;
    color: #0F172A;
}
[data-admin-mode="light"] .admin-input:focus,
[data-admin-mode="light"] .admin-select:focus,
[data-admin-mode="light"] .admin-textarea:focus {
    border-color: #7C3AED;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
}

[data-admin-mode="light"] .admin-form-label {
    color: #475569;
}

[data-admin-mode="light"] .admin-empty-state {
    background: repeating-linear-gradient(
        45deg,
        rgba(0,0,0,0.02),
        rgba(0,0,0,0.02) 1px,
        transparent 1px,
        transparent 14px
    );
    border-color: #CBD5E1;
}

/* Modal light */
[data-admin-mode="light"] .admin-modal-panel {
    background: #FFFFFF;
    border-color: #E2E8F0;
    box-shadow: 0 25px 60px rgba(0,0,0,0.15);
    backdrop-filter: none;
}
[data-admin-mode="light"] .admin-modal-header {
    border-bottom-color: #E2E8F0;
}
[data-admin-mode="light"] .admin-modal-footer {
    border-top-color: #E2E8F0;
    background: #F8FAFC;
}
```

---

## Phase 4 — Test Light Mode

**Cara test manual (tanpa Customizer yang belum dibuat):**
Buka browser DevTools → Console → ketik:
```javascript
// Aktifkan light mode
document.documentElement.setAttribute('data-admin-mode', 'light')

// Aktifkan light sidebar juga
document.documentElement.setAttribute('data-admin-sidebar', 'light')

// Kembali ke dark
document.documentElement.removeAttribute('data-admin-mode')
document.documentElement.removeAttribute('data-admin-sidebar')
```

**Cek di dark mode dulu:**
- [ ] Dark mode masih sama seperti Step 09 → tidak ada regression

**Cek di light classic (data-admin-mode="light", sidebar tetap dark):**
- [ ] Content area: white/off-white
- [ ] Sidebar: tetap gelap
- [ ] Card: white dengan border abu
- [ ] Input: white background
- [ ] Badge: warna light-optimized (darker text)

**Cek di light full (data-admin-mode="light" + data-admin-sidebar="light"):**
- [ ] Sidebar: white dengan border
- [ ] Active link: violet bg light
- [ ] Seluruh layout terang dan konsisten

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-10-handoff.md`. Catat test results dari DevTools test.

---

## STOP — Fase B selesai. Siap untuk Fase C (Dashboard Home) atau Fase D (Customizer).
