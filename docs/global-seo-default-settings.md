# Global SEO Default Settings

Branch: `feature/global-seo-default-settings`

## Tujuan

Menambahkan SEO Default sebagai global fallback metadata untuk semua halaman frontend.

SEO Default tidak menggantikan SEO khusus product. Jika product memiliki SEO sendiri, data product tetap menjadi prioritas. Jika product SEO kosong, frontend memakai fallback dari product content terlebih dahulu, lalu SEO Default.

## Struktur Data

Tabel yang digunakan untuk setting teks:

```text
site_settings
```

Tabel yang digunakan untuk default OG image:

```text
site_assets
```

Tidak ada migration baru di branch ini.

Kolom `site_settings`:

```text
key        = identifier setting global
label      = label internal admin
value      = text, textarea, select, url, atau boolean
type       = tipe field
group      = seo_default_settings
is_active  = menentukan setting dimuat ke layout
```

Key `site_settings` yang digunakan:

```text
seo.default.meta_title
seo.default.meta_description
seo.default.keywords
seo.default.title_suffix
seo.default.title_separator
seo.default.site_name
seo.default.canonical_base_url
seo.default.robots
seo.default.locale
seo.default.language
seo.default.og_title
seo.default.og_description
seo.default.og_image_alt
seo.default.twitter_card_type
seo.default.enable_organization_schema
```

Key `site_assets` yang digunakan:

```text
seo.default.og_image
```

Default value dan helper SEO dikelola oleh:

```text
app/Support/SeoDefaultSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=seo-default
route: admin.settings.global-assets.edit
```

Field form:

```text
seo_default[meta_title]                  nullable string max 255
seo_default[meta_description]            nullable string max 1000
seo_default[keywords]                    nullable string max 1000
seo_default[title_suffix]                nullable string max 255
seo_default[title_separator]             nullable string max 255
seo_default[site_name]                   nullable string max 255
seo_default[canonical_base_url]          nullable url max 500
seo_default[robots]                      required select
seo_default[locale]                      nullable string max 255
seo_default[language]                    nullable string max 255
seo_default[og_title]                    nullable string max 255
seo_default[og_description]              nullable string max 1000
seo_default[og_image_alt]                nullable string max 255
seo_default[twitter_card_type]           required select
seo_default[enable_organization_schema]  nullable boolean
seo_default_og_image                     nullable image jpg/jpeg/png/webp max 4096 KB
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/seo-default
route: admin.settings.global-assets.seo-default.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi field SEO Default.
3. Simpan semua field teks/select/boolean ke site_settings group seo_default_settings.
4. Jika ada upload Default OG image, simpan ke site_assets key seo.default.og_image.
5. Jika OG image alt diubah tanpa upload baru, update alt asset yang sudah ada.
6. Redirect kembali ke Global Assets tab seo-default.
```

## Delete

Endpoint:

```text
DELETE /admin/settings/global-assets/seo-default/og-image
route: admin.settings.global-assets.seo-default.og-image.destroy
```

Flow:

```text
1. Cari site_assets dengan key seo.default.og_image.
2. Jika ada, hapus file melalui PageSectionImageService::clearSiteAsset().
3. Redirect kembali ke tab seo-default.
```

## Sinkronisasi Frontend

SEO Default dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
seoDefaultSettings
```

Area frontend yang memakai SEO Default:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/partials/site-social-share-meta.blade.php
```

Metadata yang dirender:

```text
title
meta description
meta keywords
meta robots
canonical link
og:type
og:site_name
og:locale
og:url
og:title
og:description
og:image
og:image:alt
twitter:card
twitter:title
twitter:description
twitter:image
Organization JSON-LD
```

## Product SEO Priority

SEO Default tidak menulis ke tabel `products`.

Product tetap memiliki field SEO sendiri:

```text
products.meta_title
products.meta_description
products.meta_keywords
products.canonical_url
products.og_image
```

Priority render untuk product detail:

```text
Title:
product.meta_title
-> product.name
-> seo.default.meta_title

Description:
product.meta_description
-> product.short_description
-> seo.default.meta_description

Keywords:
product.meta_keywords
-> seo.default.keywords

Canonical:
product.canonical_url
-> route products.show
-> canonical base URL + current path

OG Image:
product.og_image
-> product thumbnail
-> seo.default.og_image
-> site.social_share.default_image
```

Dengan rule ini, product yang sudah punya SEO khusus tidak bentrok dengan SEO Default.

Product yang belum punya SEO tetap mendapatkan metadata rapi melalui fallback.

## Organization Schema

Jika `seo.default.enable_organization_schema` aktif, frontend merender JSON-LD Organization memakai data global:

```text
Business Identity:
- brand_name
- short_description

Contact Information:
- email
- phone

Social Media Links:
- activeSocialMediaLinks sebagai sameAs

Site Assets:
- site.logo atau site.logo.dark sebagai logo
```

Data ini tidak diduplikasi di SEO Default.

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Http/Controllers/Frontend/ProductController.php
app/Providers/AppServiceProvider.php
app/Support/SeoDefaultSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/frontend.blade.php
resources/views/layouts/frontend.blade.php
resources/views/partials/site-social-share-meta.blade.php
tests/Feature/Admin/GlobalSeoDefaultSettingsTest.php
docs/global-seo-default-settings.md
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Branch ini memakai `site_settings` dan `site_assets` agar SEO Default scalable sebagai fallback global.

SEO per page/product dapat dikembangkan di masa depan dengan tetap menggunakan SEO Default sebagai fallback paling akhir.
