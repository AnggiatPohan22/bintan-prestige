# Step 10 Handoff — Light Mode Variant
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium

---

## Apa yang Berubah

### Files
- `resources/css/admin.css` — tambah ~200 baris light mode overrides di akhir file

**Tidak ada Blade file yang diubah.** Light mode aktif via attribute selector saja.

---

## Implementasi

### Posisi di admin.css

Light mode CSS ditambahkan **di luar** `@layer components` (setelah closing `}`).

```
@layer components {
    /* ... semua admin-* classes ... */
}   ← baris 1228

/* LIGHT MODE OVERRIDES */ ← mulai baris 1231
[data-admin-mode="light"] { ... }
[data-admin-sidebar="light"] { ... }
```

**Kenapa di luar layer:** CSS di luar `@layer` punya precedence lebih tinggi dari semua
`@layer components` rules. Ini memastikan `[data-admin-mode="light"] .admin-card` beats
`.admin-card` tanpa perlu `!important`.

---

## Dua Mode yang Tersedia

### 1. Light Classic (dark sidebar + light content)

Aktifkan via:
```html
<html data-admin-mode="light">
```

Atau via JS:
```javascript
document.documentElement.setAttribute('data-admin-mode', 'light')
```

- Content area: `#F8FAFC` base, `#FFFFFF` cards
- Sidebar: **tetap dark** (`#0F172A`) — "light classic" look
- Topbar: `rgba(255,255,255,0.95)` glass dengan border abu
- Inputs: white background, `#CBD5E1` border
- Badges: high-contrast light colors (gelap text di background terang)

### 2. Light Full (sidebar juga putih)

Aktifkan via dua attribute:
```html
<html data-admin-mode="light" data-admin-sidebar="light">
```

Atau via JS:
```javascript
document.documentElement.setAttribute('data-admin-mode', 'light')
document.documentElement.setAttribute('data-admin-sidebar', 'light')
```

- Semua seperti Light Classic, PLUS sidebar juga putih
- Active link: `bg-[#EDE9FE] text-[#7C3AED]` (light violet)
- Border kanan sidebar: `#E2E8F0`

### 3. Kembali ke Dark Mode

```javascript
document.documentElement.removeAttribute('data-admin-mode')
document.documentElement.removeAttribute('data-admin-sidebar')
```

---

## CSS Variables yang Di-override

| Variable | Dark (default) | Light override |
|----------|---------------|----------------|
| `--admin-bg-base` | `#020617` | `#F8FAFC` |
| `--admin-bg-surface` | `#0F172A` | `#FFFFFF` |
| `--admin-bg-card` | `#1E293B` | `#FFFFFF` |
| `--admin-bg-input` | `#0F172A` | `#FFFFFF` |
| `--admin-text-primary` | `#F1F5F9` | `#0F172A` |
| `--admin-text-secondary` | `#94A3B8` | `#475569` |
| `--admin-text-muted` | `#64748B` | `#94A3B8` |
| `--admin-border` | `rgba(255,255,255,0.08)` | `#E2E8F0` |
| `--admin-primary` | `#7C3AED` | `#7C3AED` ← **sama** |

**Sidebar vars tidak berubah** di `data-admin-mode="light"` — sidebar tetap dark.
Hanya `data-admin-sidebar="light"` yang mengubah sidebar appearance secara terpisah.

---

## Classes yang Di-override

| Scope | Classes |
|-------|---------|
| Shell & body | `.admin-shell`, `.admin-body` |
| Topbar | `.admin-topbar`, `.admin-topbar__search`, `.admin-topbar__icon-button`, `.admin-topbar__breadcrumb`, `.admin-user-menu__trigger`, `.admin-user-menu__dropdown` |
| Cards | `.admin-card`, `.admin-card-header`, `.admin-form-card`, `.admin-page-header` |
| Tables | `.admin-table-wrapper`, `.admin-table-header`, `.admin-table-row` |
| Badges | `.admin-badge-success/warning/danger/info` + `::before` dots |
| Forms | `.admin-input`, `.admin-select`, `.admin-textarea`, `.admin-form-label` |
| Misc | `.admin-empty-state`, `.admin-modal-panel`, `.admin-modal-header`, `.admin-modal-footer` |
| Sidebar (via data-admin-sidebar="light") | `.admin-sidebar`, `.admin-sidebar__brand`, `.admin-sidebar__link`, `.admin-sidebar__link--active`, `.admin-sidebar__icon`, `.admin-sidebar-toggle` |

---

## Verifikasi Build

```
✅ npx vite build — sukses 7.09s, 0 error
   app.css: 107.66 kB (dark only) → 112.38 kB (+ light mode = +4.7 kB)
```

---

## Test Manual (DevTools Console)

Buka halaman admin mana pun → F12 → Console:

```javascript
// Test Light Classic
document.documentElement.setAttribute('data-admin-mode', 'light')

// Test Light Full
document.documentElement.setAttribute('data-admin-sidebar', 'light')

// Kembali ke dark
document.documentElement.removeAttribute('data-admin-mode')
document.documentElement.removeAttribute('data-admin-sidebar')
```

### Checklist Visual

**Dark mode (regression check):**
- [ ] Tidak ada perubahan — dark mode identik dengan Step 09

**Light Classic:**
- [ ] Content area: off-white / putih
- [ ] Sidebar: tetap gelap
- [ ] Topbar: semi-transparent putih
- [ ] Cards: putih dengan border abu muda
- [ ] Inputs: putih
- [ ] Badges: light color scheme (text gelap)

**Light Full:**
- [ ] Sidebar: putih dengan border kanan
- [ ] Active link: violet soft background
- [ ] Seluruh layout konsisten terang

---

## Rollback

```bash
# Rollback CSS saja
git checkout HEAD -- resources/css/admin.css
npx vite build

# Atau rollback ke tag Fase A (sebelum Step 10)
git stash
git checkout uiux-fase-a-v1.0
```

---

## Next

**Step 11** — Dashboard Home KPI/stats redesign (🟡 Medium)
— Prompt: `ai/promt/uiux/step-11-dashboard-home.md`
— File: `resources/views/backend/dashboard.blade.php`
