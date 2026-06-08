# STEP 11A - Categories Index UI Upgrade

## Tujuan

Merapikan halaman `Categories` agar mengikuti Admin Design System, lebih konsisten dengan layout SaaS admin, dan tetap menjaga CRUD/archive logic yang sudah berjalan.

## File yang Berubah

- `resources/views/backend/categories/index.blade.php`

## File yang Terkait

- `resources/css/admin.css`
- `app/Http/Controllers/Admin/CategoryController.php`
- `routes/admin.php`
- `app/Models/Category.php`

## Perubahan UI

- Halaman dibungkus dengan `admin-page`.
- Header memakai `admin-page-header`, `admin-page-title`, dan `admin-page-subtitle`.
- Tombol create memakai `admin-btn-primary`.
- Active Categories dan Archive dibungkus dengan `admin-card`.
- Table memakai `admin-table-wrapper`, `admin-table`, `admin-table-header`, dan `admin-table-row`.
- Status active/inactive memakai `admin-badge-success` dan `admin-badge-warning`.
- Action edit memakai `admin-btn-soft`.
- Action archive/delete permanent memakai `admin-btn-danger`.
- Action restore memakai `admin-btn-success`.
- Empty state memakai `admin-empty-state`.
- Pagination tetap memakai `$categories->links()` dan `$archivedCategories->links()`.

## Data yang Ditampilkan

- Nama category.
- Slug category.
- Description dengan limit visual.
- `products_count`.
- Status active/inactive.
- Data archive termasuk `deleted_at`.

## Dampak ke Database

Tidak ada perubahan database.

- Tidak ada migration baru.
- Tidak ada perubahan schema.
- Tidak ada perubahan column.
- Tidak ada perubahan seed/data.

## Dampak ke Model

Tidak ada perubahan model.

- `Category` tetap memakai relationship dan soft delete logic existing.
- `products_count` tetap berasal dari query/controller existing.

## Dampak ke Controller dan Route

Tidak ada perubahan controller/route pada sub-step ini.

Route yang tetap dipakai:

- `admin.categories.create`
- `admin.categories.edit`
- `admin.categories.destroy`
- `admin.categories.restore`
- `admin.categories.force-delete`

## Risiko

Rendah. Perubahan hanya view/UI. CRUD, archive, restore, permanent delete, search, dan pagination tetap memakai route/method existing.

## Catatan Audit

Halaman ini menjadi pattern awal untuk index page lain: header konsisten, table card konsisten, action button visual lebih jelas, dan empty state lebih rapi.

