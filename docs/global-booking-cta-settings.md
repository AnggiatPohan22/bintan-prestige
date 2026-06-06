# Global Booking CTA Settings

Branch: `feature/global-booking-cta-settings`

## Tujuan

Menambahkan Booking / CTA sebagai global setting untuk mengatur label CTA, sumber nomor WhatsApp booking, message template, dan placement CTA utama.

Setting ini dibuat agar admin tidak perlu mengubah CTA dari header, footer, atau product detail satu per satu.

Branch ini tidak menggantikan data product, Contact Information, Header Navigation, atau Footer Settings. Booking / CTA hanya menjadi global fallback dan global override jika placement terkait diaktifkan.

Secara default placement header, footer, dan product tidak langsung dioverride. Admin perlu mengaktifkan checkbox placement terkait agar setting lama tidak berubah tanpa sengaja.

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
group      = booking_cta_settings
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
booking_cta.enabled
booking_cta.use_on_header
booking_cta.use_on_footer
booking_cta.use_on_product
booking_cta.header_label
booking_cta.footer_label
booking_cta.product_chat_label
booking_cta.product_booking_label
booking_cta.whatsapp_number_source
booking_cta.whatsapp_number_override
booking_cta.default_message
booking_cta.product_message_template
```

Default value, helper WhatsApp URL, message template, dan placement guard dikelola oleh:

```text
app/Support/BookingCtaSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=booking-cta
route: admin.settings.global-assets.edit
```

Field form:

```text
booking_cta[enabled]                   nullable boolean
booking_cta[use_on_header]             nullable boolean
booking_cta[use_on_footer]             nullable boolean
booking_cta[use_on_product]            nullable boolean
booking_cta[header_label]              nullable string max 255
booking_cta[footer_label]              nullable string max 255
booking_cta[product_chat_label]        nullable string max 255
booking_cta[product_booking_label]     nullable string max 255
booking_cta[whatsapp_number_source]    required select contact_information/override
booking_cta[whatsapp_number_override]  nullable string max 255
booking_cta[default_message]           nullable string max 1500
booking_cta[product_message_template]  nullable string max 1500
```

Admin UI memakai accordion:

```text
General terbuka secara default.
Saat section lain dibuka, section yang sedang terbuka otomatis tertutup.
```

Section accordion:

```text
General
Placement
Labels
WhatsApp Booking
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/booking-cta
route: admin.settings.global-assets.booking-cta.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi semua field Booking / CTA.
3. Simpan field boolean sebagai 0/1.
4. Simpan field text, textarea, dan select sesuai input atau default value.
5. Set group menjadi booking_cta_settings dan is_active=true.
6. Redirect kembali ke Global Assets tab booking-cta.
```

## Delete

Belum ada delete endpoint terpisah di branch ini.

Untuk menonaktifkan global Booking / CTA:

```text
Matikan Enable global booking CTA lalu Save Booking / CTA.
```

Untuk mengembalikan satu area ke setting asal:

```text
Matikan checkbox Use on header CTA, Use on footer CTA, atau Use on product CTA.
```

## Sinkronisasi Frontend

Booking / CTA dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
bookingCtaSettings
```

Area frontend yang memakai Booking / CTA:

```text
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/products/show.blade.php
```

## Render Rule

Header CTA:

```text
Jika booking_cta.enabled=true dan booking_cta.use_on_header=true:
Header memakai booking_cta.header_label dan WhatsApp URL dari Booking / CTA.

Jika tidak:
Header tetap memakai navigation.header.cta_label dan navigation.header.cta_url.
```

Footer CTA:

```text
Jika booking_cta.enabled=true dan booking_cta.use_on_footer=true:
Footer CTA memakai booking_cta.footer_label dan WhatsApp URL dari Booking / CTA.

Jika tidak:
Footer tetap memakai Contact Information WhatsApp URL.
```

Product detail CTA:

```text
Jika booking_cta.enabled=true dan booking_cta.use_on_product=true:
Product memakai label fallback dari Booking / CTA dan message template global.

Jika product memiliki cta_button_text:
Label product tetap menjadi prioritas.

Jika product memiliki whatsapp_number:
Nomor product tetap menjadi prioritas.
```

## Fallback Priority

Priority nomor WhatsApp:

```text
product.whatsapp_number
-> booking_cta.whatsapp_number_override jika source override
-> contact.whatsapp_number
-> wa.me tanpa nomor
```

Priority header CTA:

```text
Booking / CTA jika enabled dan use_on_header aktif
-> Header Navigation CTA
```

Priority footer CTA:

```text
Booking / CTA jika enabled dan use_on_footer aktif
-> Contact Information WhatsApp URL
```

Priority product CTA label:

```text
product.cta_button_text
-> booking_cta.product_chat_label / product_booking_label
-> default system label
```

## Message Template

Default WhatsApp message dipakai untuk header dan footer.

Product message template dipakai untuk product detail.

Token yang didukung:

```text
{site_name}
{product_name}
{product_url}
{page_url}
```

Contoh product template:

```text
Hello {site_name}, I want to ask about:

{product_name}
{product_url}
```

## Tracking Connection

Booking / CTA tidak membuat tracking event baru.

CTA yang dirender tetap memakai attribute tracking yang sudah dibuat pada branch Tracking / Integrations:

```text
data-whatsapp-tracking
data-tracking-label
data-product-id
data-product-name
data-product-slug
```

Dengan begitu:

```text
Booking / CTA = mengatur label, URL, message, dan placement CTA.
Tracking / Integrations = mengukur klik CTA.
```

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/BookingCtaSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/partials/header.blade.php
resources/views/frontend/partials/footer.blade.php
resources/views/frontend/products/show.blade.php
tests/Feature/Admin/GlobalBookingCtaSettingsTest.php
docs/global-booking-cta-settings.md
```

## Test

Test yang ditambahkan:

```text
tests/Feature/Admin/GlobalBookingCtaSettingsTest.php
```

Coverage:

```text
Admin dapat save Booking / CTA settings.
Tab booking hanya menampilkan form Booking / CTA.
Header dan footer dapat memakai global Booking / CTA.
Product detail tetap mempertahankan product.whatsapp_number dan memakai template global sebagai fallback.
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Branch ini memakai `site_settings` agar Booking / CTA scalable dan tidak bentrok dengan tabel lain.

Branch ini tidak menulis ke tabel `products`, `site_assets`, `page_sections`, atau `bookings`.

Header Navigation, Footer Settings, Contact Information, Product CTA, dan Tracking / Integrations tetap memiliki tanggung jawab masing-masing.

Default placement dibuat nonaktif agar branch ini tidak mengubah perilaku Header Navigation, Footer Settings, atau Product CTA sebelum admin memilih area yang ingin memakai Booking / CTA.
