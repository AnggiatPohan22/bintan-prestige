# STEP 05 — Forms & Inputs Dark Redesign
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01–04 ✅

**Scope:** `.admin-input`, `.admin-select`, `.admin-textarea`,
`.admin-form-label`, `.admin-form-hint`. Error state styling.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.5

---

## Risk Assessment

**Risk: 🟡 Medium**

Form inputs adalah elemen yang paling sering diinteraksi. Keterbacaan teks
di dalam input adalah kritis. Jika warna teks input tidak contrast cukup
di dark background → user tidak bisa baca apa yang mereka ketik.

Accessibility WAJIB: text di input harus contrast ≥ 4.5:1 vs input background.

Rollback: `git checkout HEAD -- resources/css/admin.css`

---

## Phase 3 — Implementasi

### Input Base

```css
.admin-input,
.admin-select,
.admin-textarea {
    @apply w-full rounded-lg border px-4 py-3 text-sm
           transition duration-150 focus:outline-none;
    background: var(--admin-bg-input);
    border-color: var(--admin-border-md);
    color: var(--admin-text-primary);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) inset;
}

.admin-input::placeholder,
.admin-select::placeholder,
.admin-textarea::placeholder {
    color: var(--admin-text-muted);
    opacity: 0.7;
}

.admin-input:focus,
.admin-select:focus,
.admin-textarea:focus {
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px var(--admin-primary-soft);
    outline: none;
}

/* Error state */
.admin-input:is(.error, [aria-invalid="true"]),
.admin-select:is(.error, [aria-invalid="true"]),
.admin-textarea:is(.error, [aria-invalid="true"]) {
    border-color: var(--admin-danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.admin-textarea {
    @apply min-h-32 resize-y;
}
```

### Select — Custom Arrow Dark

```css
.admin-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748B' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}
```

### Label & Hint

```css
.admin-form-label {
    @apply mb-2 block text-xs font-bold uppercase;
    letter-spacing: 0.06em;
    color: var(--admin-text-secondary);
}

.admin-form-hint {
    @apply mt-1.5 text-xs;
    color: var(--admin-text-muted);
}
```

### Legacy form-input, form-label, form-select (backward compat)

```css
/* Legacy classes — tetap berfungsi, re-use token baru */
.form-input,
.form-select,
.form-textarea {
    @apply w-full rounded-lg border px-4 py-3 text-sm transition duration-200 focus:outline-none;
    background: var(--admin-bg-input);
    border-color: var(--admin-border-md);
    color: var(--admin-text-primary);
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px var(--admin-primary-soft);
}

.form-label {
    @apply mb-2 block text-xs font-bold uppercase;
    letter-spacing: 0.06em;
    color: var(--admin-text-secondary);
}
```

---

## Phase 4 — Verifikasi KRITIS

**Ini step yang paling kritis untuk accessibility:**

- [ ] Ketik teks di input → teks terbaca jelas (slate-100 di atas slate-900)
- [ ] Placeholder terlihat tapi berbeda dari teks yang diketik
- [ ] Focus ring violet terlihat jelas saat tab ke input
- [ ] Select dropdown arrow terlihat (bukan invisible di dark bg)
- [ ] Error state terlihat (border merah + ring merah)
- [ ] Textarea resize handle masih terlihat
- [ ] Form label uppercase tracking: mudah dibedakan dari body text

**Contrast check:**
- Input text (`#F1F5F9`) di atas input bg (`#0F172A`): hitung ratio → harus ≥ 4.5:1
- Placeholder (`#64748B`) di atas input bg (`#0F172A`): harus ≥ 3:1

Test di halaman dengan banyak form: Products create/edit, Settings.

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-05-handoff.md` dengan checklist khusus accessibility.

---

## STOP — Tunggu approval owner sebelum lanjut ke Step 06.
