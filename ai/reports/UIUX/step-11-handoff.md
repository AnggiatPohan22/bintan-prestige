# Step 11 Handoff — Dashboard Home Redesign
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium

---

## Apa yang Berubah

### Files
- `resources/views/backend/dashboard.blade.php` — redesign visual (icons + empty state)

**Controller tidak diubah** — data sudah complete dari sebelumnya.

---

## Perubahan Detail

### 1. Stat Card Icons — Text Initials → FontAwesome

**Before:** Text initials seperti "TP", "PB", "DR", "CT", dll.

**After:** FontAwesome icons dengan warna per-card via Tailwind classes:

| Card | Icon | Warna |
|------|------|-------|
| Total Products | `fa-map-location-dot` | `bg-violet-900/20 text-violet-400` |
| Published | `fa-circle-check` | `bg-emerald-900/20 text-emerald-400` |
| Draft | `fa-pencil` | `bg-amber-900/20 text-amber-400` |
| Categories | `fa-tag` | `bg-cyan-900/20 text-cyan-400` |
| Destinations | `fa-location-dot` | `bg-violet-900/20 text-violet-400` |
| FAQs | `fa-circle-question` | `bg-yellow-900/20 text-yellow-400` |
| Page Sections | `fa-layer-group` | `bg-cyan-900/20 text-cyan-400` |
| Product Images | `fa-images` | `bg-rose-900/20 text-rose-400` |

**Teknik:** Tailwind `bg-*` dan `text-*` dalam `@layer utilities` mengoverride
CSS component `.admin-stat-card__icon` yang di `@layer components`. No inline CSS.

### 2. Stat Card "Lihat semua" Link

Setiap stat card sekarang punya link "Lihat semua →" ke route yang relevan.
Cards yang belum punya route (FAQs — belum ada `admin.faqs.index`) → link tidak ditampilkan (conditional).

### 3. Quick Actions — Text Initials → FontAwesome

**Before:** Text initials "CP", "MP", "PS", dll.

**After:** 6 quick actions dengan FA icons:

| Action | Icon | Route |
|--------|------|-------|
| Create Product | `fa-plus` | `admin.products.create` |
| Manage Products | `fa-boxes-stacked` | `admin.products.index` |
| Pages | `fa-file-lines` | `admin.pages.index` |
| Page Sections | `fa-layer-group` | `admin.page-sections.index` |
| Media Library | `fa-images` | `admin.media.index` |
| Global Assets | `fa-gear` | `admin.settings.global-assets.edit` |

### 4. Empty State — Custom Styles → `admin-empty-state`

**Before:**
```html
<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-800 px-5 py-10 text-center">
    <p class="text-sm font-semibold text-slate-400">No products found yet.</p>
    <a ...>Create Product</a>
</div>
```

**After:**
```html
<div class="admin-empty-state">
    <div class="admin-empty-state__icon">
        <i class="fa-solid fa-boxes-stacked"></i>
    </div>
    <h3 class="admin-empty-state__title">No products yet</h3>
    <p class="admin-empty-state__description">Create your first product to get started.</p>
    <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">
        <i class="fa-solid fa-plus"></i> Create Product
    </a>
</div>
```

---

## Routes Verified

Semua routes di dashboard dicek via `php artisan route:list`:

| Route Name | URL | Status |
|------------|-----|--------|
| `admin.products.index` | `admin/products` | ✅ |
| `admin.products.create` | `admin/products/create` | ✅ |
| `admin.products.edit` | `admin/products/{product}/edit` | ✅ |
| `admin.categories.index` | `admin/categories` | ✅ |
| `admin.destinations.index` | `admin/destinations` | ✅ |
| `admin.page-sections.index` | `admin/page-sections` | ✅ |
| `admin.media.index` | `admin/media` | ✅ |
| `admin.pages.index` | `admin/pages` | ✅ |
| `admin.settings.global-assets.edit` | `admin/settings/global-assets/edit` | ✅ |
| `admin.faqs.index` | — | ❌ tidak ada, link di-skip |

---

## Data Variables dari Controller

Controller `DashboardController@index` tidak diubah. Data yang di-pass:

```php
$productCount, $publishedProductCount, $draftProductCount,
$categoryCount, $destinationCount, $faqCount,
$pageSectionCount, $productImageCount, $bookingCount,
$recentProducts (5 latest, with category+destination)
```

`$bookingCount` ada di controller tapi belum ditampilkan di stat cards
(karena tidak ada `admin.bookings.index` route untuk link).

---

## Verifikasi

```bash
php artisan view:clear
# Buka /admin/dashboard → tidak ada error
```

**Manual check:**
- [ ] Dashboard load tanpa error
- [ ] 8 stat cards dengan FA icons dan warna berbeda
- [ ] Hover stat card: lift + shadow effect
- [ ] Link "Lihat semua →" di setiap card (kecuali FAQs)
- [ ] Quick access: 6 link dengan FA icons
- [ ] Recent products: list atau empty state
- [ ] Empty state: icon violet box + title + button

---

## Rollback

```bash
git checkout HEAD -- resources/views/backend/dashboard.blade.php
php artisan view:clear
```

---

## Next

**Step 12** — DB Migration `admin_dashboard_appearances` (🔴 HIGH — butuh owner approval)

⚠️ Tunggu approval eksplisit sebelum Step 12.
