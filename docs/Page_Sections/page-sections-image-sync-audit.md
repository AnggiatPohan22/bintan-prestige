# Page Sections Image Sync Audit

Branch: `feature/audit-page-sections-image-sync`

## Tujuan

Branch ini dibuat untuk audit dan memperbaiki sinkronisasi image antara admin `Page Sections` dan tampilan frontend.

Masalah awal:

```text
Admin dapat upload image pada beberapa Page Sections.
Namun sebagian image tidak muncul atau tidak berubah di frontend.
Beberapa section di admin juga tidak sesuai urutan frontend.
Beberapa section frontend memakai teks/gambar hardcoded, bukan data Page Sections.
```

Branch ini menjaga scope tetap di Page Sections.

Branch ini tidak mengubah logic Product, Global Assets, SEO Default, Booking CTA, Footer Settings global, atau Default Media global selain membaca fallback image yang memang sudah ada.

## Ringkasan Hasil Audit

Masalah yang ditemukan:

```text
home.about_journey
- Frontend memiliki area gambar.
- Admin sebelumnya tidak memiliki media slot khusus untuk area gambar itu.
- Frontend memakai Default Media placeholder, bukan upload Page Sections.

home.explore_banner
- Admin memiliki desktop dan mobile background slot.
- Frontend hanya membaca desktop background.
- Mobile background upload tidak terlihat di frontend.

home.faq
- Admin memiliki row Page Sections home.faq.
- Frontend FAQ preview masih hardcoded.
- Gambar FAQ preview memakai Default Media placeholder, bukan Page Sections.

home.footer_cta
- Admin memiliki row Page Sections home.footer_cta.
- Frontend Footer CTA masih hardcoded.
- Gambar Footer CTA memakai Default Media placeholder, bukan Page Sections.

Page Sections index
- Urutan admin mengikuti sort_order database lama.
- Urutan ini tidak selalu sama dengan urutan render frontend.
- FAQ bisa tampil di bawah Footer CTA, padahal di frontend FAQ berada sebelum Footer CTA.

Image display control
- Admin belum bisa mengatur crop/fit image per slot.
- Jika rasio gambar tidak cocok, layout frontend bisa crop bagian penting.
```

## Struktur Database

Tabel utama:

```text
page_sections
page_section_media
```

Tidak ada table baru.

Ada migration baru untuk menambah display options pada media:

```text
database/migrations/2026_06_07_000001_add_display_options_to_page_section_media_table.php
```

Migration ini menambah kolom nullable:

```text
page_section_media.object_fit
page_section_media.object_position
```

Kolom ini nullable agar data image lama tetap aman.

Jika kolom belum ada, admin tidak akan menampilkan dropdown image size/position dan akan menampilkan notice agar menjalankan migration.

## Schema: `page_sections`

Kolom terkait:

```text
id            = primary key
page_key      = identifier halaman, contoh home
section_key   = identifier section, contoh home.footer_cta
label         = eyebrow/kicker/label section
title         = title utama section
subtitle      = subtitle section jika ada
description   = deskripsi section
button_text   = text button section
button_url    = URL button section
image         = legacy desktop image path
mobile_image  = legacy mobile image path
extra_data    = json untuk data tambahan seperti animation atau overlay_title
is_active     = menentukan section aktif
sort_order    = urutan data lama
timestamps
```

Catatan:

```text
image dan mobile_image masih dipertahankan sebagai legacy fallback.
Untuk section yang sudah memiliki media slot, source utama image adalah page_section_media.
```

## Schema: `page_section_media`

Kolom terkait:

```text
id                 = primary key
page_section_id    = relasi ke page_sections
role               = kategori media, contoh frame/background/gallery
slot_key           = posisi media di layout, contoh main_visual/desktop_background
label              = label admin untuk slot
path               = path file di storage public
alt                = alt text gambar
object_fit         = opsi object-fit frontend
object_position    = opsi object-position frontend
sort_order         = urutan media, terutama gallery
is_active          = menentukan media aktif
timestamps
```

Relasi:

```text
page_sections.id -> page_section_media.page_section_id
```

Storage path:

```text
storage/app/public/page-sections/{page_key}/{section_key}/{role}/{uuid}.{ext}
```

Public URL:

```text
asset('storage/' . page_section_media.path)
```

## Object Fit Options

Dikelola di:

```text
app/Models/PageSectionMedia.php
```

Options:

```text
cover       = gambar memenuhi frame, bisa crop jika rasio berbeda
contain     = seluruh gambar terlihat, bisa ada ruang kosong
fill        = gambar dipaksa memenuhi frame, rasio bisa berubah
scale-down  = gambar mengecil jika perlu, tidak dipaksa membesar
none        = gambar memakai ukuran aslinya
```

Default fallback:

```text
cover
```

## Object Position Options

Dikelola di:

```text
app/Models/PageSectionMedia.php
```

Options:

```text
center center
center top
center bottom
left center
left top
left bottom
right center
right top
right bottom
```

Default fallback:

```text
center center
```

Frontend render style:

```html
style="object-fit: cover; object-position: center center"
```

Helper yang menghasilkan style:

```text
PageSectionMedia::image_style
```

## Admin Flow

Admin membuka:

```text
GET /admin/page-sections
route: admin.page-sections.index
```

Admin edit section:

```text
GET /admin/page-sections/{pageSection}/edit
route: admin.page-sections.edit
```

Admin update section:

```text
PUT /admin/page-sections/{pageSection}
route: admin.page-sections.update
```

Field image slot:

```text
slot_uploads[role][slot_key]              image jpg/jpeg/png/webp max 2048 KB
slot_object_fits[role][slot_key]          select cover/contain/fill/scale-down/none
slot_object_positions[role][slot_key]     select center center/center top/etc
```

Contoh Footer CTA:

```text
slot_uploads[frame][main_visual]
slot_object_fits[frame][main_visual]
slot_object_positions[frame][main_visual]
```

## Update Flow

Flow update di:

```text
app/Http/Controllers/Admin/PageSectionController.php
method: update()
```

Flow:

```text
1. Ambil media slot berdasarkan section_key dari HomepageSectionMedia.
2. Validasi text content, legacy images, slot uploads, object_fit, object_position, gallery, extra_data, animation, status, sort_order.
3. Update row page_sections.
4. Untuk setiap slot:
   - Jika ada file baru, simpan melalui PageSectionImageService::storeSlotUpload().
   - Jika tidak ada file baru, update object_fit/object_position pada media yang sudah ada.
5. Jika gallery diizinkan, simpan gallery uploads.
6. Redirect kembali ke edit Page Section.
```

Guard migration:

```text
Jika kolom object_fit/object_position belum ada:
- Validation untuk field display options tidak dipasang.
- Update object_fit/object_position tidak dijalankan.
- Admin UI menampilkan notice untuk menjalankan php artisan migrate.
```

Alasan guard:

```text
Mencegah error SQL Unknown column object_fit pada environment yang belum menjalankan migration.
```

## Media Slot Source Of Truth

Konfigurasi slot dan urutan frontend dikelola di:

```text
app/Support/HomepageSectionMedia.php
```

Helper penting:

```text
slotsFor(section_key)
allowsGallery(section_key)
usesLogo(section_key)
supportsLegacyImages(section_key)
displayOrder(section_key)
orderedSectionKeys()
```

`HomepageSectionMedia` menjadi source of truth untuk:

```text
Media slot apa yang tersedia di admin.
Gallery apakah aktif untuk section tersebut.
Legacy image upload apakah masih ditampilkan.
Urutan Page Sections di admin agar sama dengan frontend.
```

## Mapping Section

Urutan frontend dan admin:

```text
0   home.hero
10  home.popular_tour
20  home.popular_products_intro
30  home.manual_ads
40  home.about_journey
50  home.categories_intro
60  home.explore_banner
70  home.testimonials
80  home.faq
90  home.footer_cta
```

## Mapping Media Slot

### `home.hero`

Frontend:

```text
resources/views/frontend/home.blade.php
```

Slots:

```text
background / desktop_background
background / mobile_background
```

Gallery:

```text
Enabled
```

Legacy image:

```text
Enabled
```

Priority frontend:

```text
desktop background:
1. page_section_media background/desktop_background
2. page_sections.image
3. heroBackgroundUrl
4. Default Media hero

mobile background:
1. page_section_media background/mobile_background
2. page_sections.mobile_image
3. Default Media hero_mobile
```

### `home.popular_tour`

Frontend:

```text
resources/views/frontend/sections/popular-tour.blade.php
```

Slots:

```text
frame / left_wide
frame / left_small
frame / right_wide
frame / right_small
```

Gallery:

```text
Disabled
```

Global logo:

```text
Uses Global Assets site.logo
```

Priority frontend:

```text
1. matching page_section_media slot
2. Default Media section placeholder
3. No Image placeholder
```

### `home.manual_ads`

Frontend:

```text
resources/views/frontend/partials/manual-ads.blade.php
```

Slots:

```text
frame / main_visual
```

Legacy image:

```text
Enabled
```

Priority frontend:

```text
1. page_section_media frame/main_visual
2. page_sections.image
3. Default Media section placeholder
4. No Image placeholder
```

### `home.about_journey`

Frontend:

```text
resources/views/frontend/sections/about-journey.blade.php
```

Slots:

```text
frame / main_visual
frame / secondary_visual
```

Priority frontend:

```text
1. matching page_section_media slot
2. Default Media section placeholder
3. No Image placeholder
```

Catatan audit:

```text
Sebelumnya frontend hanya memakai Default Media placeholder.
Sekarang upload Page Sections terbaca di dua frame visual section ini.
```

### `home.explore_banner`

Frontend:

```text
resources/views/frontend/sections/explore-banner.blade.php
```

Slots:

```text
background / desktop_background
background / mobile_background
```

Legacy image:

```text
Enabled
```

Priority frontend:

```text
desktop:
1. page_section_media background/desktop_background
2. page_sections.image
3. page_section_media background/mobile_background
4. page_sections.mobile_image
5. Default Media hero

mobile source:
1. page_section_media background/mobile_background
2. page_sections.mobile_image
```

Catatan audit:

```text
Sebelumnya mobile_background bisa diupload di admin tetapi tidak dibaca frontend.
Sekarang frontend memakai <picture> agar mobile image aktif pada viewport <= 767px.
```

### `home.faq`

Frontend:

```text
resources/views/frontend/home.blade.php
```

Slots:

```text
frame / main_visual
```

Priority frontend:

```text
1. page_section_media frame/main_visual
2. Default Media section placeholder
3. Travel Guide Image placeholder
```

Content yang dibaca:

```text
label
title
```

Catatan audit:

```text
Sebelumnya FAQ preview title dan image hardcoded.
Sekarang label/title/image membaca Page Sections home.faq.
```

### `home.footer_cta`

Frontend:

```text
resources/views/frontend/partials/footer.blade.php
```

Slots:

```text
frame / main_visual
```

Priority frontend:

```text
1. page_section_media frame/main_visual
2. Default Media section placeholder
3. No Image placeholder
```

Content yang dibaca:

```text
label
title
description
```

Catatan audit:

```text
Sebelumnya Footer CTA content dan image hardcoded di footer partial.
Sekarang label/title/description/image membaca Page Sections home.footer_cta.
```

CTA button:

```text
Label dan URL tetap mengikuti Booking / CTA Global jika placement footer aktif.
Jika tidak aktif, fallback WhatsApp lama tetap dipakai.
```

## Section Tanpa Dedicated Page Section Image

Beberapa section tidak menampilkan image dari Page Sections karena visualnya berasal dari entity lain.

Section:

```text
home.popular_products_intro
home.categories_intro
home.testimonials
```

Admin behavior:

```text
Tidak menampilkan legacy image upload.
Tidak menampilkan gallery upload.
Menampilkan notice "No section image upload for this layout".
```

Alasan:

```text
popular_products_intro memakai product card image.
categories_intro memakai category/destination placeholder.
testimonials memakai avatar placeholder/global default media.
```

Dengan ini admin tidak bingung upload image yang tidak pernah dipakai frontend.

## File Yang Ditambahkan

```text
database/migrations/2026_06_07_000001_add_display_options_to_page_section_media_table.php
docs/page-sections-image-sync-audit.md
```

## File Yang Diubah

```text
app/Http/Controllers/Admin/PageSectionController.php
app/Models/PageSectionMedia.php
app/Services/PageSectionImageService.php
app/Support/HomepageSectionMedia.php
database/seeders/HomePageSectionSeeder.php
resources/css/frontend-home.css
resources/views/backend/page-sections/edit.blade.php
resources/views/frontend/home.blade.php
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/partials/manual-ads.blade.php
resources/views/frontend/sections/about-journey.blade.php
resources/views/frontend/sections/explore-banner.blade.php
resources/views/frontend/sections/popular-tour.blade.php
tests/Feature/Admin/PageSectionMediaSlotTest.php
```

## Detail Perubahan File

`app/Http/Controllers/Admin/PageSectionController.php`

```text
Menambahkan order Page Sections berdasarkan HomepageSectionMedia::orderedSectionKeys().
Menambahkan validasi object fit dan object position.
Menambahkan guard Schema::hasColumn untuk kolom object_fit/object_position.
Mengupdate object fit/position walaupun admin tidak upload file baru.
Mengirim supportsMediaDisplayOptions ke view edit.
```

`app/Models/PageSectionMedia.php`

```text
Menambahkan OBJECT_FIT_OPTIONS.
Menambahkan OBJECT_POSITION_OPTIONS.
Menambahkan fillable object_fit dan object_position.
Menambahkan accessor resolved_object_fit.
Menambahkan accessor resolved_object_position.
Menambahkan accessor image_style.
```

`app/Services/PageSectionImageService.php`

```text
storeSlotUpload() menerima objectFit dan objectPosition.
Saat update/upload media slot, object_fit dan object_position ikut disimpan jika kolom tersedia.
Menambahkan guard supportsMediaDisplayOptions() agar upload tidak error sebelum migration dijalankan.
```

`app/Support/HomepageSectionMedia.php`

```text
Menjadi source of truth media slot homepage.
Menambahkan display_order untuk urutan admin sesuai frontend.
Menambahkan supportsLegacyImages().
Menambahkan orderedSectionKeys().
Menambahkan slot home.about_journey.
Menambahkan slot home.faq.
Menambahkan slot home.footer_cta.
Menonaktifkan default gallery untuk section yang tidak eksplisit mengizinkan gallery.
```

`database/seeders/HomePageSectionSeeder.php`

```text
Menambahkan seed home.faq.
Mengubah default home.footer_cta agar sama dengan frontend hardcoded sebelumnya.
Mengatur sort_order FAQ sebelum Footer CTA.
```

`resources/views/backend/page-sections/edit.blade.php`

```text
Menampilkan slot image sesuai HomepageSectionMedia.
Menampilkan preview image memakai object fit dan position yang tersimpan.
Menambahkan select Image size.
Menambahkan select Image position.
Menampilkan notice jika migration display options belum jalan.
Menyembunyikan legacy image upload untuk section yang frontend-nya tidak memakai dedicated Page Section image.
```

`resources/views/frontend/home.blade.php`

```text
Home hero tetap membaca desktop/mobile background slot.
FAQ preview sekarang membaca home.faq label/title/image.
FAQ image memakai PageSectionMedia::image_style jika ada.
```

`resources/views/frontend/partials/footer.blade.php`

```text
Footer CTA sekarang membaca home.footer_cta label/title/description/image.
Footer CTA image memakai PageSectionMedia::image_style jika ada.
Fallback Booking / CTA Global tetap berjalan untuk button label dan URL.
```

`resources/views/frontend/partials/manual-ads.blade.php`

```text
Manual Ads image memakai image_style jika gambar berasal dari media slot.
Fallback legacy/default media tetap aman.
```

`resources/views/frontend/sections/about-journey.blade.php`

```text
Menambahkan main_visual dan secondary_visual slot.
Frontend membaca slot image dari Page Sections.
Image memakai image_style jika media slot tersedia.
```

`resources/views/frontend/sections/explore-banner.blade.php`

```text
Menambahkan pembacaan mobile_background slot.
Menggunakan picture/source untuk mobile background.
Image memakai image_style dari desktop slot atau mobile slot.
```

`resources/views/frontend/sections/popular-tour.blade.php`

```text
Semua frame image memakai image_style jika media slot tersedia.
Fallback default media tetap memakai Default Media fit.
```

`resources/css/frontend-home.css`

```text
Menambahkan style agar <picture> di Explore Banner memenuhi frame full cover.
```

`tests/Feature/Admin/PageSectionMediaSlotTest.php`

```text
Menambahkan test untuk media slot admin.
Menambahkan test section tanpa frontend media tidak menampilkan upload yang menyesatkan.
Menambahkan test about_journey slot muncul di frontend.
Menambahkan test explore_banner mobile background.
Menambahkan test FAQ title/image sync.
Menambahkan test Footer CTA content/image sync.
Menambahkan test admin index order sesuai frontend.
Menambahkan test object_fit/object_position saat upload.
Menambahkan test update object_fit/object_position tanpa reupload.
```

## Migration Notes

Untuk memakai image size dan image position:

```text
php artisan migrate
```

Jika migration belum dijalankan:

```text
Admin update tidak crash.
Dropdown image size/position tidak ditampilkan.
Admin melihat notice untuk menjalankan migration.
Nilai object_fit/object_position belum bisa disimpan.
```

Error yang dicegah:

```text
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'object_fit'
```

## Test

Command yang dijalankan:

```text
php artisan test tests\Feature\Admin\PageSectionMediaSlotTest.php
php artisan test
npm.cmd run build
php artisan migrate:status
```

Hasil terakhir:

```text
PageSectionMediaSlotTest: passed, 10 tests
Full test suite: passed, 84 tests
Frontend build: success
Migration 2026_06_07_000001_add_display_options_to_page_section_media_table: Ran
```

## Scalable Notes

Jika nanti ada section baru:

```text
1. Tambahkan row di HomePageSectionSeeder jika section default perlu ada.
2. Tambahkan config section_key di HomepageSectionMedia.
3. Tentukan display_order sesuai urutan frontend.
4. Tambahkan media_slots sesuai posisi visual nyata.
5. Set gallery=true hanya jika frontend benar-benar membaca gallery.
6. Set legacy_images=true hanya jika frontend masih membaca page_sections.image/mobile_image.
7. Update Blade frontend agar membaca mediaSlot(role, slot_key).
8. Pakai PageSectionMedia::image_style untuk image dari page_section_media.
9. Tambahkan test sync admin -> frontend.
```

Dengan pola ini, admin Page Sections tetap menjadi editor yang sesuai dengan layout frontend, bukan sekadar form upload umum.

## Audit Checklist

Saat mengevaluasi section:

```text
Apakah section_key ada di HomepageSectionMedia?
Apakah display_order sesuai posisi frontend?
Apakah media slot di admin sama dengan posisi visual di frontend?
Apakah frontend membaca mediaSlot() yang sama?
Apakah fallback image jelas?
Apakah section tanpa image tidak menampilkan upload image?
Apakah object_fit/object_position diterapkan ke image dari page_section_media?
Apakah test sudah mengunci sync admin -> frontend?
```
