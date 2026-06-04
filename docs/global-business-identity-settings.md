# Global Business Identity Settings

Branch: `feature/global-business-identity-settings`

## Tujuan

Menambahkan Company / Business Identity sebagai global setting agar nama brand, legal name, tagline, deskripsi singkat, tipe bisnis, lokasi, dan copyright tidak diatur berulang di header, footer, metadata, atau page section.

## Struktur Data

Tabel yang digunakan:

```text
site_settings
```

Migration `site_settings` sudah dibuat pada branch Brand Colors. Jika environment belum memiliki tabel ini, jalankan:

```text
php artisan migrate
```

Kolom:

```text
key        = identifier setting global
label      = label internal admin
value      = nilai text/textarea
type       = text atau textarea
group      = business_identity
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
business.identity.brand_name
business.identity.legal_name
business.identity.tagline
business.identity.short_description
business.identity.business_type
business.identity.location_label
business.identity.copyright_text
```

Default value dan daftar field dikelola oleh:

```text
app/Support/BusinessIdentitySettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=business-identity
route: admin.settings.global-assets.edit
```

Field form:

```text
business_identity[brand_name]         nullable string max 255
business_identity[legal_name]         nullable string max 255
business_identity[tagline]            nullable string max 255
business_identity[short_description]  nullable string max 1000
business_identity[business_type]      nullable string max 255
business_identity[location_label]     nullable string max 255
business_identity[copyright_text]     nullable string max 255
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/business-identity
route: admin.settings.global-assets.business-identity.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi semua field text/textarea.
3. Loop semua field dari BusinessIdentitySettings::fields().
4. updateOrCreate row site_settings sesuai key.
5. Set label, value, type, group=business_identity, is_active=true.
6. Redirect kembali ke Global Assets tab business-identity.
```

## Delete

Belum ada delete untuk Business Identity di branch ini.

Alasan:

```text
Business Identity adalah setting fondasi. Jika row belum tersedia atau value kosong, frontend memakai default dari BusinessIdentitySettings.
```

## Sinkronisasi Frontend

Business identity dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan ke layout/partial:

```text
businessIdentity
```

Area frontend yang memakai identity:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/partials/site-social-share-meta.blade.php
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
```

Pemakaian:

```text
brand_name        -> default title, header brand, footer brand
short_description -> default social share description, footer brand text
location_label    -> footer information location
copyright_text    -> footer copyright suffix
```

## Perubahan Admin

Halaman `Global Assets` memiliki tab tambahan:

```text
Business Identity
```

Tab ini berisi form text/textarea untuk field identity.

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/BusinessIdentitySettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/partials/site-social-share-meta.blade.php
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
tests/Feature/Admin/GlobalBusinessIdentitySettingsTest.php
docs/global-business-identity-settings.md
```

## Catatan Audit

Tidak ada tabel baru di branch ini.

Branch ini memakai `site_settings` supaya scalable untuk global setting non-file lain seperti contact info, social links, SEO defaults, dan tracking.

Field yang belum dipakai langsung di frontend tetap disimpan sebagai fondasi untuk kebutuhan berikutnya:

```text
legal_name
tagline
business_type
```
