# Global Social Media Links Settings

Branch: `feature/global-social-media-links-settings`

## Tujuan

Menambahkan Social Media Links sebagai global setting agar link sosial media di footer dan menu masa depan tidak hardcode di Blade.

Hanya platform yang memiliki URL akan dirender di frontend.

Admin juga bisa menambahkan custom social link sendiri melalui tombol `Add Link`.

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
value      = URL sosial media
type       = url
group      = social_media_links
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
social.instagram.url
social.facebook.url
social.tiktok.url
social.youtube.url
social.linkedin.url
social.tripadvisor.url
social.google_review.url
social.custom_links
```

Default value dan daftar platform dikelola oleh:

```text
app/Support/SocialMediaLinkSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=social-media-links
route: admin.settings.global-assets.edit
```

Field form:

```text
social_media_links[instagram]      nullable url max 500
social_media_links[facebook]       nullable url max 500
social_media_links[tiktok]         nullable url max 500
social_media_links[youtube]        nullable url max 500
social_media_links[linkedin]       nullable url max 500
social_media_links[tripadvisor]    nullable url max 500
social_media_links[google_review]  nullable url max 500
custom_social_links[*][label]      nullable string max 100
custom_social_links[*][abbr]       nullable string max 8
custom_social_links[*][url]        nullable url max 500
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/social-media-links
route: admin.settings.global-assets.social-media-links.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi semua URL sosial media.
3. Loop semua platform bawaan dari SocialMediaLinkSettings::fields().
4. updateOrCreate row site_settings sesuai key platform bawaan.
5. Simpan custom link ke key `social.custom_links` sebagai JSON.
6. Set group=social_media_links dan is_active=true.
7. Redirect kembali ke Global Assets tab social-media-links.
```

## Delete

Belum ada delete terpisah untuk Social Media Links di branch ini.

Untuk menyembunyikan link, kosongkan field URL dan save.

Untuk custom link, klik `Remove` atau kosongkan label/URL lalu save.

## Sinkronisasi Frontend

Social media links dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
socialMediaLinks
activeSocialMediaLinks
```

Area frontend yang memakai social media links:

```text
resources/views/frontend/partials/footer.blade.php
```

Render rule:

```text
Jika URL kosong -> platform tidak dirender.
Jika URL terisi -> platform tampil sebagai link footer.
Custom links disimpan sebagai JSON dan ikut dirender jika label dan URL terisi.
```

## Perubahan Admin

Halaman `Global Assets` memiliki tab tambahan:

```text
Social Media Links
```

Tab ini berisi input URL untuk setiap platform.

Tab ini juga memiliki area `Custom Social Links`:

```text
Add Link -> menambahkan baris custom link
Remove   -> menghapus baris custom link dari form
```

Struktur custom link:

```text
label = nama platform yang tampil sebagai aria-label
abbr  = teks pendek di icon footer
url   = target link sosial media
```

Tombol tab Global Assets juga diubah menjadi horizontal scroll:

```text
overflow-x-auto
whitespace-nowrap
```

Tujuannya agar tab yang semakin banyak tidak turun ke baris baru dan tidak menambah tinggi halaman.

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/SocialMediaLinkSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/partials/footer.blade.php
tests/Feature/Admin/GlobalSocialMediaLinksSettingsTest.php
docs/global-social-media-links-settings.md
```

## Catatan Audit

Tidak ada tabel baru di branch ini.

Branch ini memakai `site_settings` supaya scalable untuk global setting non-file.

Icon footer saat ini memakai abbreviation mapping dari config:

```text
IG, FB, TT, YT, IN, TA, GR
```

Jika nanti ingin icon visual penuh, mapping bisa ditingkatkan di `SocialMediaLinkSettings` atau component footer tanpa mengubah database.

Custom social links memakai abbreviation dari admin. Jika kosong, sistem fallback ke dua huruf pertama dari label.
