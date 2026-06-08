# STEP 11B - Destinations Index UI Upgrade

## Tujuan

Merapikan halaman `Destinations` mengikuti pattern `Categories Index`, sambil mempertahankan thumbnail/image display dan seluruh logic archive yang sudah ada.

## File yang Berubah

- `resources/views/backend/destinations/index.blade.php`

## File yang Terkait

- `resources/css/admin.css`
- `app/Http/Controllers/Admin/DestinationController.php`
- `routes/admin.php`
- `app/Models/Destination.php`

## Perubahan UI

- Halaman memakai `admin-page`.
- Header memakai `admin-page-header`, `admin-page-title`, dan `admin-page-subtitle`.
- Tombol create memakai `admin-btn-primary`.
- Active Destinations dan Archive memakai `admin-card`.
- Table memakai Admin Design System table class.
- Thumbnail destination tetap tampil jika ada.
- Placeholder `No Image` tetap tampil jika destination belum memiliki image.
- Status active/inactive memakai `admin-badge-success` dan `admin-badge-warning`.
- Edit memakai `admin-btn-soft`.
- Archive dan delete permanent memakai `admin-btn-danger`.
- Restore memakai `admin-btn-success`.
- Empty state memakai `admin-empty-state`.

## Data yang Ditampilkan

- Thumbnail/image destination.
- Nama destination.
- Slug destination.
- Description dengan limit visual.
- `products_count`.
- Status active/inactive.
- Data archive termasuk `deleted_at`.

## Dampak ke Database

Tidak ada perubahan database.

- Tidak ada migration.
- Tidak ada schema update.
- Tidak ada perubahan path storage image.
- Tidak ada perubahan struktur table destinations.

## Dampak ke Model

Tidak ada perubahan model.

- Relationship products tetap berjalan.
- Image property tetap memakai data existing.
- Soft delete behavior tetap existing.

## Dampak ke Controller dan Route

Tidak ada perubahan controller/route pada sub-step ini.

Route yang tetap dipakai:

- `admin.destinations.create`
- `admin.destinations.edit`
- `admin.destinations.destroy`
- `admin.destinations.restore`
- `admin.destinations.force-delete`

## Risiko

Rendah. Perubahan hanya layout Blade. Image display, archive action, restore action, search, dan pagination tetap memakai logic existing.

## Catatan Audit

Destinations Index mengikuti pattern Categories, dengan tambahan area thumbnail. Ini membuat list destination lebih mudah discan tanpa mengubah data source.

