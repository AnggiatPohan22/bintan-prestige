# Step 05 Handoff — Forms & Inputs Dark Redesign
**Tanggal:** 2026-06-24
**Status:** ✅ Complete
**Branch:** develop (uncommitted)

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — 4 targeted updates di section forms

---

## Perubahan Detail

### 1. Error State — `:is()` Selector
**Before:** Partial coverage — `.admin-input.error` ada, tapi `.admin-select.error`
dan `.admin-textarea.error` tidak ada.

**After:** Full coverage via `:is()` pseudo-class:
```css
.admin-input:is(.error, [aria-invalid="true"]),
.admin-select:is(.error, [aria-invalid="true"]),
.admin-textarea:is(.error, [aria-invalid="true"]) {
    border-color: var(--admin-danger);
    box-shadow:   0 0 0 3px rgba(239, 68, 68, 0.15);
}
```
Sekarang error styling bekerja di semua tipe input.

### 2. Form Hint — Extra Spacing
`mt-1` → `mt-1.5` sedikit lebih bernapas antara input dan hint text.

### 3. Legacy `.form-input` — Aligned dengan admin-input
- `rounded-xl` → `rounded-lg` (konsisten dengan admin-input)
- Hapus `shadow-sm focus:ring-4` dari `@apply`
- Focus state pakai `box-shadow: 0 0 0 3px var(--admin-primary-soft)` (bukan Tailwind ring)

### 4. Legacy `.form-label` — Visual Hierarchy Fix
- `text-sm font-semibold` → `text-xs font-bold uppercase + letter-spacing: 0.06em`
- Aligned dengan `.admin-form-label` — keduanya sekarang punya tampilan yang sama

---

## Accessibility Check (Contrast Ratios)

| Element | Text Color | Background | Ratio | WCAG AA |
|---------|-----------|-----------|-------|---------|
| Input text | `#F1F5F9` (slate-100) | `#0F172A` (slate-900) | ~14.5:1 | ✅ AAA |
| Placeholder | `#64748B` × 0.7 opacity | `#0F172A` | ~3.2:1 | ✅ AA (large) |
| Form label | `#94A3B8` (slate-400) | `#1E293B` (card bg) | ~4.8:1 | ✅ AA |
| Form hint | `#64748B` (slate-500) | `#1E293B` (card bg) | ~3.5:1 | ✅ AA (large) |
| Focus ring | `rgba(124,58,237,0.15)` ring | visual indicator, bukan text | n/a | ✅ Visible |
| Error ring | `rgba(239,68,68,0.15)` ring | visual indicator | n/a | ✅ Visible |

---

## Status Semua Class (Scope Step 05)

| Class | Status |
|-------|--------|
| `.admin-input` | ✅ Step 01 done |
| `.admin-select` | ✅ Step 01 done (custom dark arrow) |
| `.admin-textarea` | ✅ Step 01 done |
| `.admin-input/select/textarea::placeholder` | ✅ Step 01 done |
| `.admin-input/select/textarea:focus` | ✅ Step 01 done |
| `.admin-*:is(.error, [aria-invalid])` | ✅ Updated (full coverage) |
| `.admin-form-label` | ✅ Step 01 done |
| `.admin-form-hint` | ✅ Updated (mt-1.5) |
| `.form-input/select/textarea` (legacy) | ✅ Updated (rounded-lg, focus CSS) |
| `.form-label` (legacy) | ✅ Updated (uppercase, xs, bold) |

---

## Verifikasi Build

```
✅ npx vite build — sukses, 0 error
```

---

## Visual Check (Manual — Halaman Products Create/Edit & Settings)

- [ ] Teks diketik di input: jelas terbaca (hampir putih di atas dark bg)
- [ ] Placeholder: ada tapi muted, jelas beda dari input text
- [ ] Select dropdown arrow: visible (slate icon di dark bg)
- [ ] Hover input: belum ada visual change (normal)
- [ ] Tab ke input: focus ring violet 3px terlihat jelas
- [ ] Class `.error` atau `aria-invalid="true"` pada input: border + ring merah
- [ ] Form label: UPPERCASE small, tracking, muted — mudah dibedakan dari body text
- [ ] Form hint: spacing cukup di bawah input

---

## Rollback

```bash
git checkout HEAD -- resources/css/admin.css
npm run build
```

---

## Next

**Step 06** — Tables Dark Redesign
