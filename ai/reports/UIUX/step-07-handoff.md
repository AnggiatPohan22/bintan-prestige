# Step 07 Handoff — Buttons & CTAs (Violet + Micro-Interactions)
**Tanggal:** 2026-06-24
**Status:** ✅ Complete
**Branch:** develop (uncommitted)
**Risk:** 🔴 HIGH — Primary button visible di semua halaman

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — 6 changes di section ADMIN BUTTONS

---

## Perubahan Detail

### 1. Base Reset — Padding & Radius Update

**Before:**
```css
@apply inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold focus:outline-none;
```

**After:**
```css
@apply inline-flex items-center justify-center gap-2 text-sm font-bold focus:outline-none;
padding:       0.625rem 1.25rem;   /* sedikit lebih compact dari py-3 */
border-radius: var(--admin-radius-md);  /* 8px — dari 16px (rounded-xl) */
```

Radius berkurang dari 16px → 8px. Lebih clean, less "pill-like". Padding Y berkurang dari 12px → 10px.

### 2. `user-select: none` Ditambahkan

Mencegah highlight teks saat double-click atau drag pada tombol.

### 3. `.admin-btn-sm` (Baru)

```css
.admin-btn-sm {
    padding:       0.375rem 0.75rem;   /* compact */
    border-radius: var(--admin-radius-sm);  /* 6px */
    font-size:     0.75rem;
}
```

Usage: `<button class="admin-btn-primary admin-btn-sm">Small Action</button>`

### 4. `.admin-btn-lg` (Baru)

```css
.admin-btn-lg {
    padding:       0.75rem 1.5rem;
    border-radius: var(--admin-radius-lg);  /* 12px */
    font-size:     1rem;
}
```

Usage: `<button class="admin-btn-primary admin-btn-lg">Primary CTA</button>`

### 5. `.admin-btn-icon` (Baru)

Icon-only button untuk toolbars, action menus, dll.

```css
.admin-btn-icon {
    @apply flex h-9 w-9 items-center justify-center rounded-lg transition duration-150;
    color:      var(--admin-text-muted);
    background: transparent;
}
.admin-btn-icon:hover {
    background: rgba(255, 255, 255, 0.06);
    color:      var(--admin-text-secondary);
}
```

Usage: `<button class="admin-btn-icon"><i class="fa-solid fa-ellipsis-vertical"></i></button>`

---

## Unchanged (Step 01 — tetap valid)

| Class | Color | Status |
|-------|-------|--------|
| `.admin-btn-primary` | `#7C3AED` Electric Violet | ✅ Unchanged |
| `.admin-btn-primary:hover` | `#6D28D9` + glow + lift -1px | ✅ Unchanged |
| `.admin-btn-primary:active` | snap back y=0 | ✅ Unchanged |
| `.admin-btn-primary:focus-visible` | 3px violet ring | ✅ Unchanged |
| `.admin-btn-secondary` | rgba white 5% + border | ✅ Unchanged |
| `.admin-btn-success` | `#10B981` + glow | ✅ Unchanged |
| `.admin-btn-danger` | `#EF4444` + glow | ✅ Unchanged |
| `.admin-btn-soft` | violet-15% tonal | ✅ Unchanged |

---

## Verifikasi Build

```
✅ npx vite build — sukses 7.30s, 0 error
✅ Output: app-CkdhqXUf.css (106.07 kB)
```

---

## Visual Check (WAJIB — HIGH RISK)

Karena ini HIGH RISK, owner harus cek minimal 5 halaman:

| Halaman | Button yang dicek | Expected |
|---------|------------------|----------|
| `/admin/products` | "Create Product" | Violet solid, radius 8px |
| `/admin/products/create` | "Save Product" | Violet, hover lift -1px |
| `/admin/products/{id}/edit` | "Save Changes" + "Delete" | Violet + Red |
| `/admin/categories` | "Create Category" | Violet |
| `/admin/settings` | "Save Settings" | Violet |

**Per-button checks:**
- [ ] Primary button: violet (`#7C3AED`), bukan indigo (`#4F46E5`)
- [ ] Hover primary: lebih gelap + glow violet + naik 1px
- [ ] Active primary: snap kembali ke posisi normal
- [ ] Secondary button: dark transparent dengan border tipis
- [ ] Danger button: merah, hover lebih gelap merah
- [ ] Focus ring (Tab key): ring violet 3px terlihat
- [ ] Mobile 375px: button tidak overflow, tetap readable
- [ ] `.admin-btn-icon` di navbar: muted icon, hover sedikit terang

---

## Rollback

```bash
git checkout HEAD -- resources/css/admin.css
npx vite build
```

---

## Next

**Step 08** — Badges Redesign (🟢 Low risk)
