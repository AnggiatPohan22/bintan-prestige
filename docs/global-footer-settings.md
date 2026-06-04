# Global Footer Settings

Branch: `feature/global-footer-settings`

## Tujuan

Menambahkan Footer Settings sebagai global setting agar footer dapat diatur dari Global Assets tanpa menduplikasi data global yang sudah ada.

Footer Settings mengatur tampilan, menu footer, dan layout block footer. Data contact tetap mengambil dari `Contact Information`, data social tetap mengambil dari `Social Media Links`, dan brand/copyright tetap mengambil dari `Business Identity`.

## Struktur Data

Tabel yang digunakan:

```text
site_settings
```

Tidak ada migration baru di branch ini.

Kolom:

```text
key        = identifier setting global
label      = label internal admin
value      = text, boolean, select, atau JSON
type       = text, boolean, select, json
group      = footer_settings
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
footer.logo_source
footer.bottom_note
footer.show_cta
footer.show_newsletter
footer.show_social_links
footer.show_contact_column
footer.menu.quick_links
footer.menu.utility_links
footer.layout.blocks
```

Default value, normalisasi link, layout block, dan mapping logo dikelola oleh:

```text
app/Support/FooterSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=footer-settings
route: admin.settings.global-assets.edit
```

Field form display:

```text
footer_settings[logo_source]           required select
footer_settings[bottom_note]           nullable string max 255
footer_settings[show_cta]              nullable boolean
footer_settings[show_newsletter]       nullable boolean
footer_settings[show_social_links]     nullable boolean
footer_settings[show_contact_column]   nullable boolean
```

Footer menu fields:

```text
footer_quick_links[*][label]        nullable string max 80
footer_quick_links[*][url]          nullable string max 500
footer_quick_links[*][is_external]  nullable boolean
footer_utility_links[*][label]        nullable string max 80
footer_utility_links[*][url]          nullable string max 500
footer_utility_links[*][is_external]  nullable boolean
```

Footer layout block fields:

```text
footer_layout_blocks[*][type]                      nullable in quick_links, contact_info, utility_links, maps, custom_text
footer_layout_blocks[*][title]                     nullable string max 80
footer_layout_blocks[*][width]                     nullable in 1, 2, full
footer_layout_blocks[*][is_active]                 nullable boolean
footer_layout_blocks[*][settings][maps_embed_url]  nullable string max 1000
footer_layout_blocks[*][settings][custom_body]     nullable string max 1500
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/footer-settings
route: admin.settings.global-assets.footer-settings.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi footer display fields.
3. Validasi quick links dan utility links.
4. Validasi layout blocks.
5. Normalisasi links agar hanya row dengan label dan URL yang disimpan.
6. Normalisasi layout blocks agar type, width, active state, maps URL, dan custom text aman.
7. Hitung active layout capacity.
8. Tolak save jika total active width lebih dari 3 columns.
9. Simpan display fields ke site_settings.
10. Simpan quick links ke footer.menu.quick_links sebagai JSON.
11. Simpan utility links ke footer.menu.utility_links sebagai JSON.
12. Simpan layout blocks ke footer.layout.blocks sebagai JSON.
13. Redirect kembali ke Global Assets tab footer-settings.
```

## Delete

Belum ada delete endpoint terpisah di branch ini.

Untuk menyembunyikan block:

```text
Matikan checkbox Active pada layout block, lalu Save Footer Settings.
```

Untuk menghapus menu link atau layout block:

```text
Klik Remove pada row terkait, lalu Save Footer Settings.
```

## Sinkronisasi Frontend

Footer Settings dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
footerSettings
```

Area frontend yang memakai footer settings:

```text
resources/views/frontend/partials/footer.blade.php
```

Render rule:

```text
footer.logo_source            -> memilih global logo variant untuk footer
footer.bottom_note            -> teks kanan bawah footer
footer.show_cta               -> menampilkan/menyembunyikan footer WhatsApp CTA
footer.show_newsletter        -> menampilkan/menyembunyikan newsletter form
footer.show_social_links      -> render social dari global Social Media Links
footer.show_contact_column    -> render contact dari global Contact Information
footer.menu.quick_links       -> menu Quick Links
footer.menu.utility_links     -> menu Utility Links
footer.layout.blocks          -> urutan dan komposisi kolom footer
```

## Layout Blocks

Block type yang tersedia:

```text
quick_links   = render footer.menu.quick_links
contact_info  = render data global Contact Information
utility_links = render footer.menu.utility_links
maps          = render iframe maps dari maps_embed_url
custom_text   = render custom text / ads copy
```

Width options:

```text
1     = 1 column
2     = 2 columns
full  = full row / 3 columns
```

Rule kapasitas:

```text
Footer layout maksimal memakai 3 active columns.
Jika user ingin menambahkan block width 1, user harus menonaktifkan minimal 1 column lain.
Jika user ingin menambahkan block width 2, user harus menyediakan 2 column kosong.
Jika total active width lebih dari 3, tombol save disabled di admin dan server juga menolak save.
```

Default layout blocks:

```text
Quick Links    width 1 active
Information    width 1 active
Utility Pages  width 1 active
```

## Data Global

Footer Settings tidak menduplikasi data global berikut:

```text
Business Identity:
- brand_name
- short_description
- copyright_text

Contact Information:
- email
- phone
- address
- google_maps_url
- opening_hours
- WhatsApp URL

Social Media Links:
- activeSocialMediaLinks

Site Assets:
- site.logo
- site.logo.light
- site.logo.dark
- site.logo.icon
```

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/FooterSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/partials/footer.blade.php
resources/css/frontend-theme.css
tests/Feature/Admin/GlobalFooterSettingsTest.php
docs/global-footer-settings.md
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Branch ini memakai `site_settings` agar Footer Settings tetap scalable.

Payment/support badges tidak dikerjakan di branch ini sesuai keputusan scope. Jika nanti dibutuhkan, fitur tersebut bisa ditambahkan sebagai key baru di group yang sama atau sebagai site asset khusus.
