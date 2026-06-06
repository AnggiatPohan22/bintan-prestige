# Global Tracking Integrations Settings

Branch: `feature/global-tracking-integrations-settings`

## Tujuan

Menambahkan Tracking / Integrations sebagai global setting untuk analytics, pixels, site verification, custom scripts, dan WhatsApp CTA tracking.

Setting ini ditempatkan di Global Assets karena dipakai lintas halaman frontend, bukan milik satu section atau satu product saja.

WhatsApp CTA Tracking ditambahkan sebagai step awal untuk mengukur klik tombol booking/contact via WhatsApp, terutama karena flow booking saat ini masih diarahkan ke WhatsApp dan belum masuk payment gateway.

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
value      = text, textarea, select, atau boolean
type       = tipe field
group      = tracking_integrations
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
tracking.enabled
tracking.environment_mode
tracking.ga4.enabled
tracking.ga4.measurement_id
tracking.gtm.enabled
tracking.gtm.container_id
tracking.meta_pixel.enabled
tracking.meta_pixel.pixel_id
tracking.google_verification
tracking.clarity.enabled
tracking.clarity.project_id
tracking.custom_head.enabled
tracking.custom_head.script
tracking.custom_body_start.enabled
tracking.custom_body_start.script
tracking.custom_body_end.enabled
tracking.custom_body_end.script
tracking.whatsapp.enabled
tracking.whatsapp.ga4_event_name
tracking.whatsapp.meta_event_name
tracking.whatsapp.track_header
tracking.whatsapp.track_footer
tracking.whatsapp.track_product
```

Default value, field definition, boolean casting, dan render guard dikelola oleh:

```text
app/Support/TrackingIntegrationSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=tracking-integrations
route: admin.settings.global-assets.edit
```

Field form:

```text
tracking_integrations[enabled]                    nullable boolean
tracking_integrations[environment_mode]           required select production_only/all/disabled
tracking_integrations[ga4_enabled]                nullable boolean
tracking_integrations[ga4_measurement_id]         nullable string max 255
tracking_integrations[gtm_enabled]                nullable boolean
tracking_integrations[gtm_container_id]           nullable string max 255
tracking_integrations[meta_pixel_enabled]         nullable boolean
tracking_integrations[meta_pixel_id]              nullable string max 255
tracking_integrations[google_verification]        nullable string max 255
tracking_integrations[clarity_enabled]            nullable boolean
tracking_integrations[clarity_project_id]         nullable string max 255
tracking_integrations[custom_head_enabled]        nullable boolean
tracking_integrations[custom_head_script]         nullable string max 10000
tracking_integrations[custom_body_start_enabled]  nullable boolean
tracking_integrations[custom_body_start_script]   nullable string max 10000
tracking_integrations[custom_body_end_enabled]    nullable boolean
tracking_integrations[custom_body_end_script]     nullable string max 10000
tracking_integrations[whatsapp_enabled]           nullable boolean
tracking_integrations[whatsapp_ga4_event_name]    nullable string max 255
tracking_integrations[whatsapp_meta_event_name]   nullable string max 255
tracking_integrations[whatsapp_track_header]      nullable boolean
tracking_integrations[whatsapp_track_footer]      nullable boolean
tracking_integrations[whatsapp_track_product]     nullable boolean
```

Admin UI memakai accordion:

```text
General terbuka secara default.
Saat section lain dibuka, section yang sedang terbuka otomatis tertutup.
```

Section accordion:

```text
General
Google Analytics
Google Tag Manager
Meta Pixel
Site Verification
Microsoft Clarity
Custom Scripts
WhatsApp CTA Tracking
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/tracking-integrations
route: admin.settings.global-assets.tracking-integrations.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi semua field Tracking / Integrations.
3. Simpan field boolean sebagai 0/1.
4. Simpan field text, textarea, dan select sesuai input atau default value.
5. Set group menjadi tracking_integrations dan is_active=true.
6. Redirect kembali ke Global Assets tab tracking-integrations.
```

## Delete

Belum ada delete endpoint terpisah di branch ini.

Untuk menonaktifkan integrasi:

```text
Matikan checkbox integration terkait lalu Save Tracking / Integrations.
```

Untuk menghentikan semua tracking:

```text
Matikan Enable tracking integrations atau ubah Render mode menjadi Disabled.
```

## Sinkronisasi Frontend

Tracking / Integrations dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
trackingIntegrationSettings
```

Area frontend yang memakai tracking settings:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/partials/tracking-head.blade.php
resources/views/partials/tracking-body-start.blade.php
resources/views/partials/tracking-body-end.blade.php
resources/js/frontend.js
```

Render mode:

```text
production_only = script hanya dirender saat APP_ENV production
all             = script dirender di semua environment
disabled        = semua tracking script tidak dirender
```

Script placement:

```text
tracking-head.blade.php        = head tag, GA4, GTM head, Meta Pixel, Google verification, Clarity, custom head
tracking-body-start.blade.php  = awal body, GTM noscript, custom body start
tracking-body-end.blade.php    = akhir body, WhatsApp tracking config, custom body end
```

## WhatsApp CTA Tracking

WhatsApp CTA Tracking menandai link frontend dengan attribute:

```text
data-whatsapp-tracking
data-tracking-label
data-product-id
data-product-name
data-product-slug
```

Lokasi yang ditrack:

```text
header  = Header CTA jika URL mengarah ke wa.me, mengandung whatsapp, atau #whatsapp-cta
footer  = Footer WhatsApp CTA dan WhatsApp Contact link
product = Product detail WhatsApp chat dan booking button
```

JavaScript frontend mengirim event ke:

```text
window.gtag      = GA4 event
window.dataLayer = GTM dataLayer push
window.fbq       = Meta Pixel event
```

Default event:

```text
GA4/DataLayer event = whatsapp_cta_click
Meta event          = Lead
```

Payload event:

```text
button_location
cta_label
page_url
destination_url
product_id
product_name
product_slug
```

Event tidak melakukan preventDefault, sehingga klik user tetap langsung membuka WhatsApp atau link tujuan.

## Custom Scripts

Custom script tersedia untuk:

```text
Head
Body start
Body end
```

Catatan:

```text
Custom script dirender raw karena memang digunakan untuk integrasi pihak ketiga.
Hanya admin terpercaya yang sebaiknya mengisi field ini.
```

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/TrackingIntegrationSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/frontend.blade.php
resources/views/layouts/frontend.blade.php
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/products/show.blade.php
resources/views/partials/tracking-head.blade.php
resources/views/partials/tracking-body-start.blade.php
resources/views/partials/tracking-body-end.blade.php
resources/js/frontend.js
tests/Feature/Admin/GlobalTrackingIntegrationsSettingsTest.php
docs/global-tracking-integrations-settings.md
```

## Test

Test yang ditambahkan:

```text
tests/Feature/Admin/GlobalTrackingIntegrationsSettingsTest.php
```

Coverage:

```text
Admin dapat save tracking integrations.
Tab tracking hanya menampilkan form tracking.
Partial frontend merender integration script saat aktif.
WhatsApp CTA memiliki metadata tracking di header, footer, dan product detail.
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Branch ini memakai `site_settings` agar Tracking / Integrations tetap scalable.

Tracking ini tidak menulis ke tabel product, booking, atau page section.

SEO Default dan Product SEO tidak diubah oleh branch ini.

Booking / CTA Global bisa dikembangkan pada branch berikutnya untuk mengatur label, URL, pesan WhatsApp default, dan CTA placement global. Tracking branch ini hanya mengukur klik CTA yang sudah ada.
