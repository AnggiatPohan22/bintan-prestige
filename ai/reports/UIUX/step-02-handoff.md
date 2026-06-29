# Step 02 Handoff — Shell Dark Base & Sidebar Redesign
**Tanggal:** 2026-06-24
**Status:** ✅ Complete
**Branch:** develop (uncommitted)

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — 1 selector ditambahkan (sidebar group-btn hover icon fix)

### Konteks
Step 01 sudah mengimplementasi SEMUA CSS untuk shell dan sidebar. Step 02 melakukan
cross-check Blade vs CSS dan menemukan **satu gap** yang perlu diperbaiki.

---

## Gap yang Ditemukan & Diperbaiki

**Missing selector:** `.admin-sidebar__group-btn:hover .admin-sidebar__icon`

Masalah: ketika collapsible group button di-hover, text warnanya berubah ke
`--admin-sidebar-text-hover` (lebih terang), tapi icon di dalamnya tetap
di `--admin-sidebar-text` (muted) karena ada CSS rule yang lebih spesifik.

**Fix:**
```css
.admin-sidebar__group-btn:hover .admin-sidebar__icon {
    color: var(--admin-sidebar-text-hover);
}
```

Sekarang hover group-btn: button text + icon keduanya berubah terang bersamaan. ✅

---

## Cross-Check Blade vs CSS

Semua class dari `sidebar.blade.php` terverifikasi ada di CSS:

| Class | Status |
|-------|--------|
| `admin-sidebar-shell` | ✅ |
| `admin-sidebar-toggle` | ✅ |
| `admin-sidebar-backdrop` | ✅ |
| `admin-sidebar` | ✅ |
| `admin-sidebar__brand` | ✅ |
| `admin-sidebar__brand-mark` | ✅ |
| `admin-sidebar__title` | ✅ |
| `admin-sidebar__subtitle` | ✅ |
| `admin-sidebar__close` | ✅ |
| `admin-sidebar__nav` | ✅ |
| `admin-sidebar__link` | ✅ |
| `admin-sidebar__link--active` | ✅ |
| `admin-sidebar__icon` | ✅ |
| `admin-sidebar__divider` | ✅ |
| `admin-sidebar__section-label` | ✅ |
| `admin-sidebar__group-btn` | ✅ |
| `admin-sidebar__group-btn--active` | ✅ |
| `admin-sidebar__chevron` | ✅ |
| `admin-sidebar__children` | ✅ |
| `admin-sidebar__child` | ✅ |
| `admin-sidebar__child--active` | ✅ |

---

## Observasi Lain (Non-blocking)

- Blade Sitemap link (line 208) menggunakan `text-slate-400` sebagai inline Tailwind utility
  untuk external link icon — ini acceptable, warna slate-400 (`#94A3B8`) memiliki cukup
  kontras di atas dark sidebar background.

- Alpine.js sidebar logic tidak berubah — `adminSidebar()` component, `drawerOpen`,
  `isOpen()`, `toggle()` semua tetap sama.

---

## Verifikasi Build

```
✅ npx vite build — sukses
✅ Output: app-DDoGrwVo.css (121.89 kB)
✅ Zero error, zero warning
```

---

## Visual Check (Manual — Perlu Dilakukan Anda)

Fokus sidebar saat di browser:

| Element | Yang Harus Terlihat |
|---------|---------------------|
| Brand mark "BP" | Kotak solid violet (`#7C3AED`) + gold ring tipis |
| Subtitle "Bintan Prestige" | Gold (`#D4AF37`) dengan opacity 70% |
| Section labels (Content, Visibility, Admin) | Ultra-kecil, sangat muted (hampir invisible) |
| Nav link default | Muted gray, tidak terlalu terang |
| Nav link hover | Sedikit lebih terang, ada overlay tipis |
| Nav link active (Dashboard) | Border-left violet + soft violet background |
| Group button (Content, Design, dll) | Default muted, hover → text + icon bersamaan terang |
| Group icon hover | Sama terangnya dengan text (fix dari step ini) |
| Active child link | Soft violet bg + violet text |
| Mobile toggle button | Dark dengan border, no lebih putih lagi |

---

## Rollback

```bash
git checkout HEAD -- resources/css/admin.css
npm run build
```

---

## Next

**Step 03** — Topbar Redesign
- Dark glass background pada topbar
- Search box dark
- Icon buttons dark + subtle hover
- User menu dropdown glassmorphism