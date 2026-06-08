# STEP 11D - Page Sections Index UI Upgrade

## Tujuan

Merapikan UX `Page Sections` agar scalable untuk banyak frontend page. Sebelumnya index dapat menjadi panjang dan bercampur antara page filter dan section list. Sub-step ini memisahkan page selector dan section list memakai query parameter.

## File yang Berubah

- `resources/views/backend/page-sections/index.blade.php`
- `resources/views/backend/page-sections/sections.blade.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `routes/admin.php`

## File yang Terkait

- `resources/css/admin.css`
- `app/Models/PageSection.php`
- `app/Models/PageSectionMedia.php`
- `app/Support/PageSectionRegistry.php`
- `app/Support/HomepageSectionMedia.php`
- `app/Services/PageSectionImageService.php`

## Perubahan UI di Index Page

URL:

- `/admin/page-sections`

Isi halaman:

- Page Filter saja.
- Group tabs/pills: `All`, `Home`, `Products`, `Destinations`, `Content`, `System`.
- Search input client-side menggunakan Alpine state `search`.
- Page cards dalam grouped layout.
- Tombol `Manage Sections` pada setiap page card.
- Active page key badge jika ada query page lama yang valid.

Index page tidak lagi menampilkan Section List table. Ini mengurangi scroll dan membuat admin memilih page terlebih dahulu.

## Grouping Logic

Grouping dilakukan di Blade berdasarkan pattern `page_key`:

- `home` atau `home.*` masuk `Home`.
- `products.*` masuk `Products`.
- `destinations.*` masuk `Destinations`.
- `faqs`, `faq`, `contact`, `about`, `blog`, atau sub-key sejenis masuk `Content`.
- `404`, `popup`, `settings`, `global`, atau sub-key sejenis masuk `System`.
- Sisanya fallback ke `Content`.

Grouping ini tidak mengubah data database. Ini hanya cara menampilkan registered pages di admin.

## Perubahan UI di Sections Page

URL:

- `/admin/page-sections/sections?page=home`
- `/admin/page-sections/sections?page=products.index`
- `/admin/page-sections/sections?page=products.show`

Route name:

- `admin.page-sections.sections`

Isi halaman:

- Back button ke Page Sections index.
- Selected page title.
- Selected page description.
- Selected page key.
- Section List table yang sudah difilter berdasarkan `page_key`.
- Status badge.
- Sort order.
- Media count untuk section yang support image.
- `No image input` badge merah untuk section yang tidak support image.
- Edit button tetap memakai route existing.

## Perubahan Controller

File:

- `app/Http/Controllers/Admin/PageSectionController.php`

Perubahan penting:

- `index(Request $request)` tetap melakukan `PageSectionRegistry::syncRegisteredSections()`.
- `index()` sekarang hanya menyiapkan:
  - `$pageKeys`
  - `$activePageKey`
  - `$pageOptions`
- Method baru `sections(Request $request)` dipakai untuk list section per page.
- Method `sections()` membaca query parameter `page`.
- Jika `page` kosong atau tidak valid, user diarahkan kembali ke `admin.page-sections.index` dengan warning.
- Query section difilter dengan `where('page_key', $pageKey)`.
- Query tetap memakai `withCount('media')`.
- Order section mengikuti gabungan:
  - `HomepageSectionMedia::orderedSectionKeys()`
  - `PageSectionRegistry::registeredSectionKeys()`
  - lalu fallback `sort_order` dan `section_key`.
- Pagination memakai `paginate(20)->withQueryString()`.

## Perubahan Route

File:

- `routes/admin.php`

Route baru:

- `GET page-sections/sections`
- Name: `admin.page-sections.sections`
- Controller: `PageSectionController@sections`

Route existing tetap dipertahankan:

- `admin.page-sections.index`
- `admin.page-sections.edit`
- `admin.page-sections.update`
- `admin.page-sections.media.destroy`

## Kenapa Pakai Query Parameter

`page_key` bisa berisi dot seperti `products.index` dan `products.show`. Query parameter membuat URL aman dan tidak bentrok dengan route model binding `page-sections/{pageSection}/edit`.

Contoh:

- Aman: `/admin/page-sections/sections?page=products.index`
- Tidak perlu membuat route dinamis seperti `/page-sections/products.index` yang berisiko bentrok.

## Dampak ke Database

Tidak ada perubahan database.

- Tidak ada migration.
- Tidak ada schema update.
- Tidak ada perubahan table `page_sections`.
- Tidak ada perubahan table `page_section_media`.
- Tidak ada perubahan media upload path.

## Dampak ke Model

Tidak ada perubahan model.

- `PageSection` tetap model existing.
- `PageSectionMedia` tetap model existing.
- `MEDIA_LIMIT` tetap dari `PageSection`.

## Dampak ke Upload/Edit Logic

Tidak ada perubahan edit/upload/save logic.

- `edit.blade.php` tidak disentuh pada sub-step ini.
- `update()` tetap existing.
- `destroyMedia()` tetap existing.
- Media slot logic tetap existing.
- Image upload logic tetap existing.

## Risiko

Sedang-rendah. Ada route dan method controller baru, tetapi bersifat additive dan tidak mengubah route edit/update/delete existing. Risiko utama ada pada test lama yang mungkin masih mengharapkan Section List muncul langsung di `/admin/page-sections`; test seperti itu perlu diperbarui agar mengikuti UX baru.

## Catatan Audit

Sub-step ini membuat Page Sections lebih scalable untuk banyak page. Admin tidak lagi melihat semua section bercampur dalam satu table; admin memilih page lebih dulu, lalu masuk ke list section page tersebut.

