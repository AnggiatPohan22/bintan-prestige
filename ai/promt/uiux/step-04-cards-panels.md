# STEP 04 — Cards, Panels & Page Headers
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01 ✅ + Step 02 ✅ + Step 03 ✅

**Scope:** `.admin-card`, `.admin-card-header`, `.admin-card-body`,
`.admin-form-card`, `.admin-page-header`, `.admin-page-title`,
`.admin-page-subtitle`. Tambah varian card baru: `admin-card--accent`,
`admin-card--gold`, `admin-stat-card`.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.4

---

## Risk Assessment

**Risk: 🟡 Medium**

Cards adalah elemen yang paling sering muncul di setiap halaman admin.
Perubahan yang salah di sini akan terlihat di semua halaman.
Perubahan hanya CSS — zero Blade impact.

---

## Phase 3 — Implementasi

### Page Header & Title

```css
.admin-page {
    @apply space-y-6;
}

.admin-page-header {
    @apply rounded-xl p-6;
    background: linear-gradient(135deg, var(--admin-bg-card) 0%, rgba(30, 41, 59, 0.7) 100%);
    border: 1px solid var(--admin-border);
}

.admin-page-title {
    @apply text-2xl font-extrabold tracking-tight sm:text-3xl;
    color: var(--admin-text-primary);
}

.admin-page-subtitle {
    @apply mt-2 max-w-3xl text-sm leading-6;
    color: var(--admin-text-secondary);
}
```

### Card Base

```css
.admin-card {
    @apply overflow-hidden rounded-xl;
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.admin-card-header {
    @apply px-6 py-4;
    background: rgba(15, 23, 42, 0.5);
    border-bottom: 1px solid var(--admin-border);
}

.admin-card-body {
    @apply p-6;
}

.admin-form-card {
    @apply rounded-xl p-6;
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
}
```

### Card Variants (Baru)

```css
/* Accent violet — untuk featured/highlighted section */
.admin-card--accent {
    border-color: var(--admin-primary-soft);
    box-shadow: 0 0 0 1px var(--admin-primary-soft) inset,
                0 4px 16px rgba(0, 0, 0, 0.2);
}

/* Gold hint — untuk premium/brand section */
.admin-card--gold {
    border-color: var(--admin-gold-soft);
    background: linear-gradient(135deg, var(--admin-bg-card), var(--admin-gold-soft));
}

/* Stat card — untuk dashboard KPI */
.admin-stat-card {
    @apply overflow-hidden rounded-xl p-6 transition duration-150;
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
}
.admin-stat-card:hover {
    border-color: var(--admin-border-md);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    transform: translateY(-1px);
}
```

---

## Phase 4 — Verifikasi

- [ ] Card background: dark slate (bukan putih)
- [ ] Card border: sangat subtle (barely visible)
- [ ] Card header: slightly darker dari card body
- [ ] Page header: ada subtle gradient
- [ ] Page title: terang, readable
- [ ] Stat card hover: lift effect

Buka beberapa halaman admin berbeda dan pastikan layout tidak rusak:
- [ ] Index/list page (ada table dalam card)
- [ ] Create/edit form page (ada form dalam card)
- [ ] Settings page (ada multiple cards)

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-04-handoff.md`.

---

## STOP — Tunggu approval owner sebelum lanjut ke Step 05.
