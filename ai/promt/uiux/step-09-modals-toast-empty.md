# STEP 09 — Modals, Toast & Empty States
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–08 ✅

**Scope:**
1. Modal/confirm dialog — glassmorphism dark
2. Flash alert / Toast — dark floating (bukan full-width banner)
3. Empty state — diagonal stripe pattern

Perubahan menyentuh `admin.css` DAN mungkin sedikit
`resources/views/components/` (hanya style/class attribute, bukan logic).

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.9, 9.10, 9.11
- `resources/views/components/confirm-modal.blade.php`
- `resources/views/components/flash-alert.blade.php`

---

## Risk Assessment

**Risk: 🟢 Low**

Modal dan flash alert logic tidak berubah (Alpine.js x-show, JS openConfirmModal()).
Hanya CSS visual dan kemungkinan minor class addition di Blade component.
Empty state hanya CSS.

---

## Phase 1 — Baca & Inspect

1. Baca `resources/views/components/confirm-modal.blade.php` — catat class yang dipakai
2. Baca `resources/views/components/flash-alert.blade.php` — catat class yang dipakai
3. Cari halaman yang punya empty state: `grep -r "admin-empty-state" resources/views/ | head`

---

## Phase 3 — Implementasi

### Modal CSS (tambah ke admin.css)

```css
/* Modal Overlay */
.admin-modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center p-4;
    background: rgba(2, 6, 23, 0.75);
    backdrop-filter: blur(8px);
}

/* Modal Panel — Glassmorphism */
.admin-modal-panel {
    @apply relative w-full max-w-lg overflow-hidden rounded-xl;
    background: rgba(15, 23, 42, 0.97);
    border: 1px solid var(--admin-border-md);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 255, 255, 0.04) inset;
    backdrop-filter: blur(20px);
}

.admin-modal-header {
    @apply px-6 py-5;
    border-bottom: 1px solid var(--admin-border);
}

.admin-modal-body {
    @apply px-6 py-5;
    color: var(--admin-text-secondary);
}

.admin-modal-footer {
    @apply flex items-center justify-end gap-3 px-6 py-4;
    border-top: 1px solid var(--admin-border);
    background: rgba(2, 6, 23, 0.3);
}
```

### Flash Alert / Toast

Cek apakah `flash-alert.blade.php` menggunakan class atau inline style.
Jika menggunakan class yang bisa diupdate via CSS — update di CSS.
Jika menggunakan inline style — perlu minor Blade edit (tambah class, remove inline style).

```css
/* Toast base — floating bottom-right */
.admin-toast {
    @apply fixed bottom-6 right-6 z-50 flex max-w-sm items-start gap-3
           overflow-hidden rounded-xl p-4;
    background: rgba(15, 23, 42, 0.97);
    border: 1px solid var(--admin-border-md);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(20px);
    animation: adminToastIn 0.25s ease-out;
}

@keyframes adminToastIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}

/* Accent border kiri */
.admin-toast--success { border-left: 3px solid var(--admin-success); }
.admin-toast--warning { border-left: 3px solid var(--admin-warning); }
.admin-toast--danger  { border-left: 3px solid var(--admin-danger); }
.admin-toast--info    { border-left: 3px solid var(--admin-accent); }

.admin-toast__icon {
    @apply flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm;
}
.admin-toast--success .admin-toast__icon { background: rgba(16,185,129,0.12); color: #34D399; }
.admin-toast--warning .admin-toast__icon { background: rgba(245,158,11,0.12); color: #FBBF24; }
.admin-toast--danger  .admin-toast__icon { background: rgba(239,68,68,0.12);  color: #F87171; }
.admin-toast--info    .admin-toast__icon { background: var(--admin-accent-soft); color: #22D3EE; }

.admin-toast__content {
    @apply min-w-0 flex-1;
}
.admin-toast__title {
    @apply text-sm font-bold;
    color: var(--admin-text-primary);
}
.admin-toast__message {
    @apply mt-0.5 text-xs;
    color: var(--admin-text-muted);
}
```

> **Jika flash-alert.blade.php existing tidak pakai class di atas:**
> Tambahkan class `admin-toast admin-toast--{type}` ke element wrapper di blade.
> Ini adalah minor Blade edit yang acceptable.

### Empty State

```css
.admin-empty-state {
    @apply flex flex-col items-center justify-center rounded-xl px-6 py-16 text-center;
    background: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.01),
        rgba(255, 255, 255, 0.01) 1px,
        transparent 1px,
        transparent 14px
    );
    border: 1px dashed var(--admin-border-md);
}

.admin-empty-state__icon {
    @apply mb-4 flex h-16 w-16 items-center justify-center rounded-2xl text-3xl;
    background: var(--admin-primary-soft);
    border: 1px solid rgba(124, 58, 237, 0.2);
    color: var(--admin-primary);
}

.admin-empty-state__title {
    @apply mb-2 text-base font-bold;
    color: var(--admin-text-primary);
}

.admin-empty-state__description {
    @apply mb-6 max-w-xs text-sm;
    color: var(--admin-text-muted);
}
```

---

## Phase 4 — Verifikasi

**Modal:**
- [ ] Trigger delete/destructive action → modal muncul dengan dark glass background
- [ ] Overlay backdrop: dark blur
- [ ] Modal panel: dark glass, tidak transparan penuh
- [ ] Confirm + Cancel button visible dan accessible

**Toast:**
- [ ] Simpan sesuatu → toast muncul dari kanan bawah
- [ ] Toast background: dark glass
- [ ] Accent border kiri sesuai tipe (success/danger)
- [ ] Teks di toast readable

**Empty State:**
- [ ] Cari halaman dengan kosong (tidak ada data) → empty state tampil dengan pattern
- [ ] Diagonal stripe subtle tapi ada
- [ ] Dashed border visible
- [ ] Icon dengan violet bg

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-09-handoff.md`. Catat apakah ada perubahan
Blade (flash-alert.blade.php atau confirm-modal.blade.php).

---

## STOP — Setelah step ini, Fase A selesai. Request review lengkap dari owner.
