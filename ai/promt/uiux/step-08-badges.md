# STEP 08 — Badges & Status Redesign
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–07 ✅

**Scope:** `.admin-badge-success`, `.admin-badge-warning`, `.admin-badge-danger`,
`.admin-badge-info`. Tambah `.admin-badge-neutral`. Ubah bentuk dari
`rounded-full pill` ke `rounded-md precision`. Tambah dot indicator.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.8

---

## Risk Assessment

**Risk: 🟢 Low**

Badge adalah elemen dekoratif (status display). Perubahan radius (pill → rect)
dan warna dark-optimized. Tidak ada logic yang bergantung pada CSS class badge.
Constraint AGENTS.md yang harus dipertahankan: tidak ada `style=` inline override
di atas badge class.

---

## Phase 3 — Implementasi

```css
/* Base badge */
.admin-badge-success,
.admin-badge-warning,
.admin-badge-danger,
.admin-badge-info,
.admin-badge-neutral {
    @apply inline-flex items-center gap-1.5 text-xs font-bold;
    padding: 0.2rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    white-space: nowrap;
}

/* Dot indicator sebelum label */
.admin-badge-success::before,
.admin-badge-warning::before,
.admin-badge-danger::before,
.admin-badge-info::before,
.admin-badge-neutral::before {
    content: '';
    display: inline-block;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Status colors — dark-optimized */
.admin-badge-success {
    background: rgba(16, 185, 129, 0.12);
    color: #34D399; /* emerald-400 */
}
.admin-badge-success::before { background: var(--admin-success); }

.admin-badge-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #FBBF24; /* amber-400 */
}
.admin-badge-warning::before { background: var(--admin-warning); }

.admin-badge-danger {
    background: rgba(239, 68, 68, 0.12);
    color: #F87171; /* red-400 */
}
.admin-badge-danger::before { background: var(--admin-danger); }

.admin-badge-info {
    background: var(--admin-accent-soft);
    color: #22D3EE; /* cyan-400 */
}
.admin-badge-info::before { background: var(--admin-accent); }

.admin-badge-neutral {
    background: rgba(255, 255, 255, 0.06);
    color: var(--admin-text-muted);
}
.admin-badge-neutral::before { background: var(--admin-text-muted); }
```

---

## Phase 4 — Verifikasi

- [ ] Success badge: green dot + green text, dark transparent bg
- [ ] Warning badge: amber dot + amber text
- [ ] Danger badge: red dot + red text
- [ ] Info badge: cyan dot + cyan text
- [ ] Neutral badge: gray dot + gray text (untuk status unknown/draft)
- [ ] Badge shape: rectangular (bukan pill) — lebih presisi
- [ ] Badge di table row: terlihat di dark row background
- [ ] Tidak ada `style=` override yang override badge (cek pages/index.blade.php)

Test di: Pages index (draft/published/scheduled), Products index (active/inactive),
Bookings index (status badges).

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-08-handoff.md`.

---

## STOP
