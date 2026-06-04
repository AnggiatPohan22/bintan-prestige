# Global Contact Information Settings

Branch: `feature/global-contact-information-settings`

## Tujuan

Menambahkan Contact Information sebagai global setting agar email, phone, WhatsApp, address, Google Maps URL, dan opening hours tidak diatur manual di footer, CTA, atau page section.

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
value      = nilai text/url/email/textarea
type       = text, email, url, atau textarea
group      = contact_information
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
contact.email
contact.phone
contact.whatsapp_number
contact.whatsapp_message
contact.address
contact.google_maps_url
contact.opening_hours
```

Default value dan daftar field dikelola oleh:

```text
app/Support/ContactInformationSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=contact-information
route: admin.settings.global-assets.edit
```

Field form:

```text
contact_information[email]             nullable email max 500
contact_information[phone]             nullable string max 500
contact_information[whatsapp_number]   nullable string max 500
contact_information[whatsapp_message]  nullable string max 1000
contact_information[address]           nullable string max 1000
contact_information[google_maps_url]   nullable url max 500
contact_information[opening_hours]     nullable string max 500
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/contact-information
route: admin.settings.global-assets.contact-information.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi email, URL, text, dan textarea.
3. Loop semua field dari ContactInformationSettings::fields().
4. updateOrCreate row site_settings sesuai key.
5. Set label, value, type, group=contact_information, is_active=true.
6. Redirect kembali ke Global Assets tab contact-information.
```

## Delete

Belum ada delete untuk Contact Information di branch ini.

Alasan:

```text
Contact Information adalah setting fondasi. Jika row belum tersedia atau value kosong, frontend memakai default dari ContactInformationSettings.
```

## Sinkronisasi Frontend

Contact information dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan ke layout/partial:

```text
contactInformation
contactWhatsappUrl
```

Area frontend yang memakai contact info:

```text
resources/views/frontend/partials/footer.blade.php
```

Pemakaian:

```text
email             -> footer mailto link
phone             -> footer tel link
whatsapp_number   -> WhatsApp CTA/link target
whatsapp_message  -> WhatsApp prefilled text
address           -> footer information location
google_maps_url   -> optional footer address link
opening_hours     -> footer information hours
```

## Perubahan Admin

Halaman `Global Assets` memiliki tab tambahan:

```text
Contact Information
```

Tab ini berisi form text/email/url/textarea untuk field contact.

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/ContactInformationSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/partials/footer.blade.php
tests/Feature/Admin/GlobalContactInformationSettingsTest.php
docs/global-contact-information-settings.md
```

## Catatan Audit

Tidak ada tabel baru di branch ini.

Branch ini memakai `site_settings` supaya scalable untuk global setting non-file berikutnya seperti social links, SEO defaults, dan tracking.

WhatsApp URL dibuat oleh:

```text
ContactInformationSettings::whatsappUrl()
```

Nomor WhatsApp dibersihkan menjadi digit saja sebelum dibuat URL `wa.me`.
