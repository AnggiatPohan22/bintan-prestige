# Global Default Media Placeholder Assets

Branch: `feature/global-default-media-placeholder-assets`

## Tujuan

Menambahkan Default Media / Placeholder Assets sebagai pusat fallback visual website.

Fitur ini memastikan frontend tetap tampil rapi ketika product, destination, section, hero, atau avatar belum memiliki gambar khusus.

Default Media tidak menggantikan gambar asli. Gambar khusus product, section, destination, atau entity lain tetap menjadi prioritas pertama.

## Struktur Data

Tabel yang digunakan:

```text
site_assets
site_settings
```

Tidak ada migration baru di branch ini.

Kolom `site_assets`:

```text
key        = identifier asset global
label      = label internal admin
path       = path file di storage public atau null jika dihapus
alt        = alt text gambar
is_active  = menentukan asset dimuat ke layout
```

Kolom `site_settings`:

```text
key        = identifier setting tampilan
label      = label internal admin
value      = nilai fit image
type       = select
group      = default_media_settings
is_active  = menentukan setting dimuat ke layout
```

Key `site_assets` yang digunakan:

```text
default_media.product
default_media.destination
default_media.section
default_media.hero
default_media.hero_mobile
default_media.avatar
```

Key `site_settings` yang digunakan untuk image fit:

```text
default_media.product.fit
default_media.destination.fit
default_media.section.fit
default_media.hero.fit
default_media.hero_mobile.fit
default_media.avatar.fit
```

Variant dan helper fallback dikelola oleh:

```text
app/Support/DefaultMediaAssets.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=default-media
route: admin.settings.global-assets.edit
```

Field form:

```text
default_media[product]                nullable image jpg/jpeg/png/webp max 4096 KB
default_media[destination]            nullable image jpg/jpeg/png/webp max 4096 KB
default_media[section]                nullable image jpg/jpeg/png/webp max 4096 KB
default_media[hero]                   nullable image jpg/jpeg/png/webp max 4096 KB
default_media[hero_mobile]            nullable image jpg/jpeg/png/webp max 4096 KB
default_media[avatar]                 nullable image jpg/jpeg/png/webp max 4096 KB
default_media_alts[product]           nullable string max 255
default_media_alts[destination]       nullable string max 255
default_media_alts[section]           nullable string max 255
default_media_alts[hero]              nullable string max 255
default_media_alts[hero_mobile]       nullable string max 255
default_media_alts[avatar]            nullable string max 255
default_media_fits[product]           nullable select cover/contain/fill/scale-down
default_media_fits[destination]       nullable select cover/contain/fill/scale-down
default_media_fits[section]           nullable select cover/contain/fill/scale-down
default_media_fits[hero]              nullable select cover/contain/fill/scale-down
default_media_fits[hero_mobile]       nullable select cover/contain/fill/scale-down
default_media_fits[avatar]            nullable select cover/contain/fill/scale-down
```

Admin preview behavior:

```text
Jika asset sudah tersimpan, admin melihat preview gambar dan status Saved image active.
Jika belum ada upload, admin melihat status No image uploaded dan frame No image.
Saat user memilih file baru, preview langsung berubah sebelum save.
Saat file baru dipilih, status berubah menjadi New image selected. Save to publish this placeholder.
Tombol Clear selected image menghapus pilihan file yang belum disimpan.
Jika asset lama sudah ada, Clear selected image mengembalikan preview ke asset tersimpan.
Jika asset lama belum ada, Clear selected image mengembalikan frame ke No image.
```

Image fit options:

```text
cover       = gambar memenuhi frame dan crop jika rasio berbeda
contain     = seluruh gambar terlihat, bisa ada ruang kosong di frame
fill        = gambar dipaksa memenuhi frame, rasio bisa berubah
scale-down  = gambar dikecilkan jika perlu tanpa diperbesar berlebihan
```

## Variant

Product placeholder:

```text
key: default_media.product
fungsi: fallback untuk product card, listing, related product, dan product detail gallery
```

Destination placeholder:

```text
key: default_media.destination
fungsi: fallback untuk destination card, destination section, category/destination-style card, dan future destination detail
```

Section placeholder:

```text
key: default_media.section
fungsi: fallback untuk homepage section, manual ads, popular tour frames, footer CTA visual, FAQ preview image, dan section umum lain
```

Hero placeholder:

```text
key: default_media.hero
fungsi: fallback visual besar untuk homepage hero dan banner hero
```

Mobile hero placeholder:

```text
key: default_media.hero_mobile
fungsi: fallback khusus mobile hero ketika crop mobile berbeda dari desktop
```

Avatar placeholder:

```text
key: default_media.avatar
fungsi: fallback untuk testimonial avatar dan future author, guide, team, atau user avatar
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/default-media
route: admin.settings.global-assets.default-media.update
```

Flow:

```text
1. Validasi upload image, alt text, dan image fit.
2. Untuk setiap variant yang memiliki upload baru:
   - hapus file site asset lama jika ada
   - simpan file baru ke storage public
   - update row site_assets dengan key variant terkait
   - set label, path, alt, dan is_active=true
3. Untuk variant tanpa upload baru tetapi alt text diubah:
   - update alt pada row site_assets terkait
4. Simpan image fit ke site_settings group default_media_settings.
5. Redirect kembali ke Global Assets tab default-media.
```

## Delete

Endpoint:

```text
DELETE /admin/settings/global-assets/default-media/{variant}
route: admin.settings.global-assets.default-media.destroy
```

Variant route parameter:

```text
product
destination
section
hero
hero_mobile
avatar
```

Flow:

```text
1. Cari variant berdasarkan slug.
2. Cari row site_assets berdasarkan key variant.
3. Jika ada asset, hapus file melalui PageSectionImageService::clearSiteAsset().
4. Set path=null dan is_active=false.
5. Redirect kembali ke tab default-media.
```

Delete/reset behavior:

```text
Tombol Delete / Reset to system fallback menghapus upload user untuk variant terkait.
Setelah reset, frontend kembali memakai fallback bawaan sistem berupa teks placeholder lama atau CSS fallback.
Setting image fit tidak dihapus, sehingga jika user upload gambar baru, pilihan fit terakhir tetap bisa dipakai.
Delete/reset hanya muncul untuk asset yang sudah tersimpan.
File yang baru dipilih tetapi belum disimpan bisa dibatalkan dengan tombol Clear selected image.
```

## Sinkronisasi Frontend

Default Media dimuat bersama `siteAssets` melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Helper yang digunakan:

```text
DefaultMediaAssets::asset($siteAssets, 'product')
DefaultMediaAssets::asset($siteAssets, 'destination')
DefaultMediaAssets::asset($siteAssets, 'section')
DefaultMediaAssets::asset($siteAssets, 'hero')
DefaultMediaAssets::asset($siteAssets, 'hero_mobile')
DefaultMediaAssets::asset($siteAssets, 'avatar')
DefaultMediaAssets::fit($defaultMediaSettings, 'product')
DefaultMediaAssets::fit($defaultMediaSettings, 'destination')
DefaultMediaAssets::fit($defaultMediaSettings, 'section')
DefaultMediaAssets::fit($defaultMediaSettings, 'hero')
DefaultMediaAssets::fit($defaultMediaSettings, 'hero_mobile')
DefaultMediaAssets::fit($defaultMediaSettings, 'avatar')
```

## Render Rule

Product:

```text
product.thumbnail_url
-> default_media.product
-> teks placeholder lama
```

Image fit:

```text
Image tag fallback memakai inline object-fit sesuai setting admin.
Hero background memakai CSS variable --home-hero-media-fit yang diisi oleh frontend JS.
Desktop hero memakai fit dari default_media.hero.fit.
Mobile hero memakai fit dari default_media.hero_mobile.fit.
```

Homepage hero:

```text
section background slot
-> section image_url
-> HomeController heroBackgroundUrl
-> default_media.hero
-> fallback CSS/system
```

Homepage mobile hero:

```text
section mobile background slot
-> section mobile_image_url
-> default_media.hero_mobile
-> fallback desktop/system
```

Section visual:

```text
section slot image
-> section image_url
-> default_media.section
-> teks placeholder lama
```

Destination/category visual:

```text
entity image if available in future
-> default_media.destination
-> teks placeholder lama
```

Avatar:

```text
avatar image if available in future
-> default_media.avatar
-> initials/text placeholder lama
```

## Area Frontend Yang Dipakai

```text
resources/views/frontend/components/product-card.blade.php
resources/views/frontend/products/partials/card.blade.php
resources/views/frontend/products/show.blade.php
resources/views/frontend/home.blade.php
resources/js/frontend.js
resources/css/frontend-home.css
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/partials/manual-ads.blade.php
resources/views/frontend/sections/about-journey.blade.php
resources/views/frontend/sections/categories.blade.php
resources/views/frontend/sections/explore-banner.blade.php
resources/views/frontend/sections/popular-tour.blade.php
resources/views/frontend/sections/testimonials.blade.php
```

## Admin Preview

Admin preview menggunakan asset yang sudah tersimpan di `site_assets`.

Ketika gambar berhasil disimpan, tab Default Media menampilkan:

```text
preview gambar tersimpan
status Saved image active
tombol Delete / Reset to system fallback
```

Catatan teknis:

```text
Key default media memakai titik, misalnya default_media.product.
Saat mengambil collection asset untuk admin, gunakan filter literal key.
Jangan memakai Collection::only() untuk key bertitik karena key dapat dibaca seperti dot notation dan preview admin bisa terlihat kosong walaupun data tersimpan.
```

Rule ini menjaga admin dashboard tetap menampilkan gambar yang sudah diupload setelah save dan refresh.

## Yang Tidak Diubah

Branch ini tidak membuat atau mengubah:

```text
SEO Default OG Image
Social Share Image
Product thumbnail data
Destination database structure
Page section media table
Upload logic product/page section
```

Default Media hanya fallback render saat media asli kosong.

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Support/DefaultMediaAssets.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/components/product-card.blade.php
resources/views/frontend/products/partials/card.blade.php
resources/views/frontend/products/show.blade.php
resources/views/frontend/home.blade.php
resources/js/frontend.js
resources/css/frontend-home.css
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/partials/manual-ads.blade.php
resources/views/frontend/sections/about-journey.blade.php
resources/views/frontend/sections/categories.blade.php
resources/views/frontend/sections/explore-banner.blade.php
resources/views/frontend/sections/popular-tour.blade.php
resources/views/frontend/sections/testimonials.blade.php
tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php
docs/global-default-media-placeholder-assets.md
```

## Test

Test yang ditambahkan:

```text
tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php
```

Coverage:

```text
Admin dapat upload dan delete default media assets.
Admin dapat menyimpan image fit per placeholder asset.
Admin dapat melihat status preview, clear selected image, dan reset saved image.
Admin preview tetap menampilkan gambar tersimpan setelah save dan refresh.
Tab default-media hanya menampilkan form Default Media.
Product card dan product detail memakai product placeholder saat thumbnail kosong.
Homepage memakai hero, mobile hero, dan section placeholder saat media asli kosong.
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Branch ini memakai `site_assets` untuk file/image asset dan `site_settings` untuk opsi tampilan image fit.

Branch ini tidak menulis ke `products`, `destinations`, `page_sections`, atau `page_section_media`.

Admin controller mengambil default media asset dengan filter literal key agar key bertitik seperti `default_media.product` tetap terbaca.

Untuk pengembangan berikutnya, variant baru bisa ditambahkan melalui `DefaultMediaAssets::variants()` tanpa mengubah struktur database.

Contoh variant lanjutan:

```text
default_media.blog
default_media.gallery
default_media.testimonial
default_media.author
default_media.map
default_media.video_thumbnail
```
