# Step 08 Handoff — Badges & Status Indicators
**Tanggal:** 2026-06-24
**Status:** ✅ Complete
**Branch:** develop (uncommitted)
**Risk:** 🟢 Low

---

## Apa yang Berubah

### File
- `resources/css/admin.css` — 2 perubahan di section ADMIN BADGES

---

## Perubahan Detail

### 1. `.admin-badge-neutral` Masuk ke Base Group Selector

**Before:** `.admin-badge-neutral` berdiri sendiri dengan seluruh properti duplikat.

**After:** Digabungkan ke group selector — semua badge sekarang share base styles:

```css
.admin-badge-success,
.admin-badge-warning,
.admin-badge-danger,
.admin-badge-info,
.admin-badge-neutral {          ← ditambahkan di sini
    @apply inline-flex items-center gap-1.5 text-xs font-bold;
    padding:       0.2rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    white-space:   nowrap;
}
```

### 2. `::before` Group Juga Include Neutral

```css
.admin-badge-success::before,
.admin-badge-warning::before,
.admin-badge-danger::before,
.admin-badge-info::before,
.admin-badge-neutral::before {   ← ditambahkan di sini
    content:       '';
    display:       inline-block;
    width:         5px;
    height:        5px;
    border-radius: 50%;
    flex-shrink:   0;
}
```

### 3. `.admin-badge-neutral` Standalone — Hanya Color Properties

Dari 10 baris duplikat menjadi 2 baris:

```css
/* Before (duplikat): */
.admin-badge-neutral {
    @apply inline-flex items-center gap-1.5 text-xs font-bold;
    padding:       0.2rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    background:    rgba(255, 255, 255, 0.06);
    color:         var(--admin-text-muted);
}
.admin-badge-neutral::before {
    content:       '';
    display:       inline-block;
    width:         5px; height: 5px;
    border-radius: 50%;
    background:    var(--admin-text-muted);
    flex-shrink:   0;
}

/* After (DRY): */
.admin-badge-neutral {
    background: rgba(255, 255, 255, 0.06);
    color:      var(--admin-text-muted);
}
.admin-badge-neutral::before { background: var(--admin-text-muted); }
```

---

## Status Semua Badge Classes

| Class | Dot Color | Text Color | Bg | Status |
|-------|-----------|-----------|-----|--------|
| `.admin-badge-success` | `#10B981` | `#34D399` (emerald-400) | rgba emerald 12% | ✅ |
| `.admin-badge-warning` | `#F59E0B` | `#FBBF24` (amber-400) | rgba amber 12% | ✅ |
| `.admin-badge-danger` | `#EF4444` | `#F87171` (red-400) | rgba red 12% | ✅ |
| `.admin-badge-info` | `#06B6D4` | `#22D3EE` (cyan-400) | rgba cyan 15% | ✅ |
| `.admin-badge-neutral` | `#64748B` | `#64748B` | rgba white 6% | ✅ |

### Legacy `.admin-badge--published` / `.admin-badge--draft`

Tetap ada (tidak diubah) — dipakai di pages/index.blade.php untuk status display.
Tidak ada dot indicator (berbeda pattern). Warna sudah dark-optimized dari Step 01.

---

## Verifikasi Build

```
✅ npx vite build — sukses 6.98s, 0 error
```

---

## Visual Check

Cek di halaman yang ada status badge:

| Halaman | Badge yang dicek |
|---------|-----------------|
| `/admin/pages` | `.admin-badge--published` (green), `.admin-badge--draft` (amber) |
| `/admin/products` | Status active/inactive badge |
| Semua index | Row status badges — dot + text terbaca di dark row bg |

**Per-badge checks:**
- [ ] Dot indicator 5×5px terlihat di kiri teks
- [ ] Badge shape: rectangular (radius 6px — bukan pill)
- [ ] Background: sangat subtle (12–15% opacity)
- [ ] `.admin-badge-neutral` punya dot + muted gray text
- [ ] Badge terbaca di `.admin-table-row` dark background

---

## Rollback

```bash
git checkout HEAD -- resources/css/admin.css
npx vite build
```

---

## Next

**Step 09** — Modals, Toast & Empty State (🟢 Low risk)
