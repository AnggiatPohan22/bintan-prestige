# STEP 06 — Tables & Data Grid Dark
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–05 ✅

**Scope:** `.admin-table-wrapper`, `.admin-table`, `.admin-table-header`,
`.admin-table-row`. Typography precision di header.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.6

---

## Risk Assessment

**Risk: 🟢 Low**

Table adalah display-only (tidak ada interaksi complex). Perubahan hanya
warna background, border, dan text style. Row hover masih berfungsi.
Tidak ada JavaScript di table yang terpengaruh.

---

## Phase 3 — Implementasi

```css
.admin-table-wrapper {
    @apply overflow-x-auto rounded-xl;
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
}

.admin-table {
    @apply w-full min-w-[840px] text-left;
}

/* Header row: dark strip dengan uppercase label */
.admin-table-header {
    @apply border-b;
    background: rgba(15, 23, 42, 0.6);
    border-color: var(--admin-border);
}

.admin-table-header th {
    @apply px-6 py-3.5 text-xs font-black uppercase;
    letter-spacing: 0.1em;
    color: var(--admin-text-muted);
}

/* Data rows */
.admin-table-row {
    @apply border-b transition duration-100 last:border-b-0;
    border-color: rgba(255, 255, 255, 0.04);
}

.admin-table-row:hover {
    background: rgba(255, 255, 255, 0.02);
}

.admin-table-row td {
    @apply px-6 py-4 text-sm;
    color: var(--admin-text-secondary);
}

/* Kolom utama (nama, judul) lebih terang */
.admin-table-row td:first-child {
    color: var(--admin-text-primary);
    font-weight: 500;
}
```

---

## Phase 4 — Verifikasi

- [ ] Table wrapper: dark background (bukan putih)
- [ ] Header row: slightly darker dari rows
- [ ] Header text: uppercase, muted, readable
- [ ] Row border: barely visible (sangat subtle)
- [ ] Row hover: sangat subtle, tidak mengganggu teks
- [ ] First column teks: lebih terang dari kolom lain
- [ ] Horizontal scroll masih berfungsi untuk tabel lebar

Test di: Products index, Bookings index, Users index.

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-06-handoff.md`.

---

## STOP
