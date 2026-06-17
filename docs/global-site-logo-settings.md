# Global Site Logo Settings

Branch: `feature/global-site-logo-settings`

## Tujuan

Memindahkan pengaturan site logo dari editor page section ke menu admin `Global Assets`, supaya semua area frontend memakai satu sumber asset global dengan beberapa variant logo.

## Struktur Data

Tabel: `site_assets`

Key yang digunakan:

```text
site.logo        = logo utama / fallback default
site.logo.dark   = logo untuk background terang, misalnya header
site.logo.light  = logo untuk background gelap, misalnya footer
site.logo.icon   = logo compact/mark untuk kebutuhan kecil atau future favicon
```

Kolom penting:

```text
key        = identifier asset global
label      = label internal admin
path       = path file di storage/public
alt        = alt text untuk image frontend
is_active  = menentukan asset dimuat ke frontend
```

Storage path untuk upload baru:

```text
storage/app/public/site-assets/{asset-key-slug}/{uuid}.{extension}
```

URL frontend dihasilkan oleh accessor `SiteAsset::url`:

```text
asset('storage/' . path)
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets
route: admin.settings.global-assets.edit
```

Field form:

```text
logos[main]       nullable image, jpg/jpeg/png/webp, max 2048 KB
logos[dark]       nullable image, jpg/jpeg/png/webp, max 2048 KB
logos[light]      nullable image, jpg/jpeg/png/webp, max 2048 KB
logos[icon]       nullable image, jpg/jpeg/png/webp, max 2048 KB
logo_alts[main]   nullable string, max 255
logo_alts[dark]   nullable string, max 255
logo_alts[light]  nullable string, max 255
logo_alts[icon]   nullable string, max 255
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/site-logo
route: admin.settings.global-assets.site-logo.update
```

Flow:

```text
1. Validasi semua file logo variant dan alt text.
2. Loop variant `main`, `dark`, `light`, dan `icon`.
3. Jika ada file baru, ambil atau buat row `site_assets` sesuai key variant.
4. Hapus file lama jika path lama berada di folder `site-assets/`.
5. Simpan file baru ke disk public.
6. Update label, path, alt, dan `is_active=true`.
7. Jika tidak ada file baru tetapi alt text dikirim, update alt text asset yang sudah ada.
8. Redirect kembali ke Global Assets.
```

## Delete

Endpoint:

```text
DELETE /admin/settings/global-assets/site-logo/{variant}
route: admin.settings.global-assets.site-logo.destroy
```

Variant route parameter:

```text
main
dark
light
icon
```

Flow:

```text
1. Mapping `{variant}` ke key `site_assets` yang diizinkan.
2. Cari row `site_assets` sesuai key variant.
2. Hapus file lokal jika path berada di folder site-assets/.
3. Kosongkan path.
4. Set is_active=false.
5. Frontend otomatis tidak menerima variant aktif dan memakai fallback berikutnya.
```

## Sinkronisasi Frontend

Frontend sudah mengambil asset global aktif lewat `siteAssets`.

Area terkait:

```text
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/sections/popular-tour.blade.php
app/Http/Controllers/Frontend/HomeController.php
app/Providers/AppServiceProvider.php
```

Fallback logo frontend:

```text
Header       site.logo.dark  -> site.logo -> BP text
Footer       site.logo.light -> site.logo -> BP text
Popular Tour site.logo       -> LOGO HERE placeholder
```

## Perubahan Admin

Page section editor tidak lagi menerima upload `site_logo`. Untuk section yang memakai logo global, admin hanya melihat info dan link ke `Global Assets`.

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Http/Controllers/Admin/PageSectionController.php
app/Services/PageSectionImageService.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/backend/page-sections/edit.blade.php
resources/views/backend/partials/sidebar.blade.php
```

## Catatan Audit

`HomepageSectionMedia::SITE_LOGO_KEY` tetap menjadi single source untuk key `site.logo`.

`HomepageSectionMedia::usesLogo()` masih dipakai untuk memberi informasi pada section editor bahwa section tersebut memakai logo global, tetapi tidak lagi dipakai untuk mengatur upload logo dari section.

Logo variant tambahan (`site.logo.dark`, `site.logo.light`, `site.logo.icon`) dikelola oleh `SiteSettingController::logoVariants()` supaya whitelist update/delete tetap jelas.
