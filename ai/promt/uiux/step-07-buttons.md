# STEP 07 — Buttons Redesign (Violet Primary + Micro-Interactions)
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–06 ✅

**Scope:** Semua `.admin-btn-*` variants. Primary button berubah dari
`bg-indigo-600` ke `bg-violet-600`. Tambah micro-interaction (hover lift,
active snap). Tambah `.admin-btn-sm`, `.admin-btn-lg`, `.admin-btn-icon`.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.7

---

## ⚠️ Risk Assessment

**Risk: 🔴 HIGH**

Mengapa HIGH:
- Primary button ada di SETIAP halaman admin (save, submit, dll.)
- Warna berubah dari indigo ke violet — perubahan yang sangat visible
- Micro-interaction (translateY) adalah behavior baru — harus tidak mengganggu
- Jika ada CSS conflict atau typo → semua tombol bisa broken

**Mitigasi wajib:**
1. Test di minimal 5 halaman berbeda setelah implementasi
2. Cek di mobile (375px) — button tidak boleh overflow
3. Screenshot sebelum + sesudah untuk comparison

Rollback: `git checkout HEAD -- resources/css/admin.css`

---

## Phase 1 — Baca & Inventory

Baca `resources/css/admin.css` bagian button. Catat semua class button yang ada.
Cari juga di Blade files apakah ada class button yang hardcoded (bukan via admin-btn-*).

```bash
# Cari penggunaan button classes di Blade
grep -r "admin-btn" resources/views/backend/ | grep -v ".DS_Store" | head -30
grep -r "btn-primary\|btn-secondary\|btn-danger" resources/views/backend/ | head -20
```

---

## Phase 3 — Implementasi

### Base Reset

```css
.admin-btn-primary,
.admin-btn-secondary,
.admin-btn-success,
.admin-btn-danger,
.admin-btn-soft {
    @apply inline-flex items-center justify-center gap-2 text-sm font-bold
           focus:outline-none;
    padding: 0.625rem 1.25rem;  /* py-2.5 px-5 */
    border-radius: var(--admin-radius-md);
    transition: background 150ms ease-out, box-shadow 150ms ease-out,
                transform 100ms ease-out;
    cursor: pointer;
    user-select: none;
}
```

### Primary — Electric Violet

```css
.admin-btn-primary {
    background: var(--admin-primary);
    color: var(--admin-primary-text);
    box-shadow: 0 1px 2px rgba(0,0,0,0.3), 0 0 0 1px var(--admin-primary-soft) inset;
}
.admin-btn-primary:hover {
    background: var(--admin-primary-hover);
    box-shadow: 0 4px 12px var(--admin-primary-glow);
    transform: translateY(-1px);
}
.admin-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.admin-btn-primary:focus-visible {
    box-shadow: 0 0 0 3px var(--admin-primary-soft), 0 4px 12px var(--admin-primary-glow);
}
```

### Secondary

```css
.admin-btn-secondary {
    background: rgba(255, 255, 255, 0.05);
    color: var(--admin-text-secondary);
    border: 1px solid var(--admin-border-md);
}
.admin-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--admin-border-strong);
    color: var(--admin-text-primary);
    transform: translateY(-1px);
}
.admin-btn-secondary:active {
    transform: translateY(0);
}
```

### Success

```css
.admin-btn-success {
    background: var(--admin-success);
    color: #FFFFFF;
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.admin-btn-success:hover {
    background: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    transform: translateY(-1px);
}
.admin-btn-success:active { transform: translateY(0); }
```

### Danger

```css
.admin-btn-danger {
    background: var(--admin-danger);
    color: #FFFFFF;
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.admin-btn-danger:hover {
    background: #B91C1C;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    transform: translateY(-1px);
}
.admin-btn-danger:active { transform: translateY(0); }
```

### Soft (Violet Tonal)

```css
.admin-btn-soft {
    background: var(--admin-primary-soft);
    color: #C4B5FD; /* violet-300 */
    border: 1px solid rgba(124, 58, 237, 0.2);
}
.admin-btn-soft:hover {
    background: rgba(124, 58, 237, 0.22);
    color: #DDD6FE; /* violet-200 */
}
```

### Size Variants (Baru)

```css
.admin-btn-sm {
    padding: 0.375rem 0.75rem;
    border-radius: var(--admin-radius-sm);
    font-size: 0.75rem;
}

.admin-btn-lg {
    padding: 0.75rem 1.5rem;
    border-radius: var(--admin-radius-lg);
    font-size: 1rem;
}

/* Icon-only button */
.admin-btn-icon {
    @apply flex h-9 w-9 items-center justify-center rounded-lg
           transition duration-150;
    color: var(--admin-text-muted);
    background: transparent;
}
.admin-btn-icon:hover {
    background: rgba(255, 255, 255, 0.06);
    color: var(--admin-text-secondary);
}
```

---

## Phase 4 — Verifikasi WAJIB (High Risk)

**Visual regression test — buka halaman ini dan cek button:**

- [ ] Products index → "Create Product" button: violet, bukan indigo
- [ ] Product create → "Save Product" button: violet + hover lift
- [ ] Product edit → "Save Changes" dan "Delete" button
- [ ] Categories index → Create + action buttons
- [ ] Settings halaman → Save buttons
- [ ] Dashboard → semua action buttons

**Per-button checks:**
- [ ] Primary button: violet-600, hover violet-700, hover lift -1px
- [ ] Primary button active: snaps back ke y=0
- [ ] Secondary button: dark transparent dengan border
- [ ] Danger button: red, hover darker red
- [ ] Focus ring visible saat keyboard navigation
- [ ] Mobile (375px): button tidak overflow container

**Jangan lanjut ke Step 08 jika ada button yang broken.**

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-07-handoff.md` dengan screenshot descriptions
(atau instruksi manual QA yang owner harus cek).

---

## STOP — Step ini HIGH RISK. Tunggu konfirmasi owner sebelum Step 08.
