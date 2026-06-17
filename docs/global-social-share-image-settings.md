# Global Default Social Share Image Settings

Branch: `feature/global-social-share-image-settings`

## Tujuan

Menambahkan default social share image sebagai global asset agar preview link WhatsApp, Facebook, X/Twitter, LinkedIn, dan platform lain memiliki fallback image yang konsisten.

Setting ini tidak diatur dari page section. Nantinya page, product, atau post dapat memiliki share image sendiri, tetapi jika tidak ada maka layout memakai default global ini.

## Struktur Data

Tabel yang digunakan:

```text
site_assets
```

Key yang digunakan:

```text
site.social_share.default_image
```

Kolom penting:

```text
key        = identifier asset global
label      = label internal admin
path       = path file di storage/public
alt        = deskripsi internal/admin untuk asset
is_active  = menentukan asset dimuat ke layout
```

Storage path untuk upload baru:

```text
storage/app/public/site-assets/site-social-share-default-image/{uuid}.{extension}
```

URL layout dihasilkan oleh accessor `SiteAsset::url`:

```text
asset('storage/' . path)
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=social-share-image
route: admin.settings.global-assets.edit
```

Field form:

```text
social_share_image      required image, jpg/jpeg/png/webp, max 4096 KB
social_share_image_alt  nullable string, max 255
```

Rekomendasi ukuran image:

```text
1200 x 630 px
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/social-share-image
route: admin.settings.global-assets.social-share-image.update
```

Flow:

```text
1. Validasi file image dan alt/internal description.
2. Ambil atau buat row site_assets dengan key site.social_share.default_image.
3. Hapus file lama jika path lama berada di folder site-assets/.
4. Simpan file baru ke disk public.
5. Update label, path, alt, dan is_active=true.
6. Redirect kembali ke Global Assets tab social-share-image.
```

## Delete

Endpoint:

```text
DELETE /admin/settings/global-assets/social-share-image
route: admin.settings.global-assets.social-share-image.destroy
```

Flow:

```text
1. Cari row site_assets dengan key site.social_share.default_image.
2. Hapus file lokal jika path berada di folder site-assets/.
3. Kosongkan path.
4. Set is_active=false.
5. Layout otomatis tidak mengeluarkan og:image/twitter:image global jika asset tidak aktif.
6. Redirect kembali ke Global Assets tab social-share-image.
```

## Sinkronisasi Frontend

Social share meta dimuat melalui partial:

```text
resources/views/partials/site-social-share-meta.blade.php
```

Meta yang dikeluarkan:

```text
og:type
og:title
og:description
og:image jika asset aktif
twitter:card
twitter:title
twitter:description
twitter:image jika asset aktif
```

Layout public yang memakai partial:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
```

View composer yang memastikan `siteAssets` tersedia:

```text
app/Providers/AppServiceProvider.php
```

## Perubahan Admin

Halaman `Global Assets` memiliki tab tambahan:

```text
Social Share Image
```

Tab ini berisi:

```text
upload image
alt/internal description
preview image 1200:630
delete image
```

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/partials/site-social-share-meta.blade.php
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
tests/Feature/Admin/GlobalSocialShareImageSettingsTest.php
docs/global-social-share-image-settings.md
```

## Catatan Audit

Tidak ada perubahan database baru di branch ini.

Branch ini memakai tabel `site_assets` yang sudah dibuat pada fondasi global assets sebelumnya.

Struktur ini scalable untuk kebutuhan post/artikel/product nanti:

```text
page/post/product share image sendiri -> fallback ke site.social_share.default_image
```

Jika nanti SEO default title/description juga ingin dibuat global, sebaiknya memakai tabel `site_settings`, misalnya:

```text
seo.default.title
seo.default.description
seo.default.site_name
```
