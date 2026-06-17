# Global Browser Favicon Settings

Branch: `feature/global-favicon-settings`

## Tujuan

Menambahkan pengaturan favicon browser sebagai asset global agar icon tab browser, bookmark, dan shortcut preview tidak diatur dari page section.

## Struktur Data

Tabel: `site_assets`

Key yang digunakan:

```text
site.favicon
```

Fallback render favicon:

```text
site.favicon -> site.logo.icon -> no favicon link
```

Kolom penting:

```text
key        = identifier asset global
label      = label internal admin
path       = path file di storage/public
alt        = deskripsi asset untuk admin/accessibility context
is_active  = menentukan asset dimuat ke layout
```

Storage path untuk upload baru:

```text
storage/app/public/site-assets/site-favicon/{uuid}.{extension}
```

URL layout dihasilkan oleh accessor `SiteAsset::url`:

```text
asset('storage/' . path)
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets
route: admin.settings.global-assets.edit
```

Halaman Global Assets memakai sub button/tab berbasis query param supaya konten settings tidak memanjang.

Tab yang tersedia di branch ini:

```text
/admin/settings/global-assets?tab=site-logo
/admin/settings/global-assets?tab=favicon
```

Default tab:

```text
site-logo
```

Field form favicon:

```text
favicon      required file, ico/png/svg/webp/jpg/jpeg, max 1024 KB
favicon_alt  nullable string, max 255
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/favicon
route: admin.settings.global-assets.favicon.update
```

Flow:

```text
1. Validasi file favicon dan alt text.
2. Ambil atau buat row site_assets dengan key site.favicon.
3. Hapus file lama jika path lama berada di folder site-assets/.
4. Simpan file baru ke disk public.
5. Update label, path, alt, dan is_active=true.
6. Redirect kembali ke Global Assets tab favicon.
```

## Delete

Endpoint:

```text
DELETE /admin/settings/global-assets/favicon
route: admin.settings.global-assets.favicon.destroy
```

Flow:

```text
1. Cari row site_assets dengan key site.favicon.
2. Hapus file lokal jika path berada di folder site-assets/.
3. Kosongkan path.
4. Set is_active=false.
5. Layout otomatis tidak menerima site.favicon aktif dan fallback ke site.logo.icon jika tersedia.
6. Redirect kembali ke Global Assets tab favicon.
```

## Sinkronisasi Layout

Favicon dimuat melalui partial:

```text
resources/views/partials/site-favicon.blade.php
```

Layout yang memakai partial favicon:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/layouts/admin.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/guest.blade.php
```

View composer diperluas agar layout menerima `siteAssets`:

```text
app/Providers/AppServiceProvider.php
```

## Perubahan Admin

Halaman `Global Assets` sekarang memiliki sub button/tab untuk memisahkan settings:

```text
Site Logo
Browser Favicon
```

Tab `Browser Favicon` berisi upload, preview, dan delete favicon. Tab `Site Logo` tetap berisi logo variants dari branch sebelumnya.

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/partials/site-favicon.blade.php
tests/Feature/Admin/GlobalFaviconSettingsTest.php
docs/global-favicon-settings.md
```

## Catatan Audit

Favicon tidak menggunakan page section dan tidak mempunyai field di editor section.

`site.logo.icon` tetap berfungsi sebagai fallback favicon jika `site.favicon` belum aktif, sehingga asset icon dari Logo Variants tetap berguna untuk kebutuhan browser sementara.
