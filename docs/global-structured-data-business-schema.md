# Global Structured Data / Business Schema

Branch: `feature/global-structured-data-business-schema`

## Tujuan

Menambahkan Structured Data / Business Schema sebagai pusat pengaturan JSON-LD global website.

Structured Data ditempatkan di Global Assets karena data ini dipakai lintas halaman frontend, bukan milik satu page section atau satu product saja.

Fitur ini membantu search engine membaca struktur website dengan lebih rapi melalui satu JSON-LD graph yang bisa berisi:

```text
Business schema
Website schema
Breadcrumb schema
Product / Tour schema
```

Branch ini juga memindahkan render Organization JSON-LD lama dari SEO Default ke Structured Data agar schema tidak dobel.

SEO Default tetap berjalan untuk metadata biasa seperti title, description, canonical, Open Graph, Twitter card, dan robots.

Product SEO tetap berjalan sendiri. Structured Data tidak menulis ke table `products` dan tidak mengubah field SEO product yang sudah ada.

## Prinsip Utama

Structured Data hanya menjadi layer schema untuk search engine.

Structured Data tidak mengganti:

```text
SEO product
SEO Default metadata
Business Identity data
Contact Information data
Social Media Links data
Site Logo data
Product content
```

Jika override field di Structured Data dikosongkan, schema mengambil data dari Global Assets lain secara otomatis.

Dengan rule ini, admin tidak perlu mengisi ulang data yang sudah ada di Global Assets lain.

## Struktur Data

Tabel yang digunakan:

```text
site_settings
```

Tidak ada migration baru di branch ini.

Branch ini tidak membuat table baru karena setting Structured Data masih berupa konfigurasi global.

Kolom `site_settings`:

```text
key        = identifier setting global
label      = label internal admin
value      = nilai text, textarea, select, atau boolean
type       = tipe field
group      = structured_data_settings
is_active  = menentukan setting dimuat ke layout
```

Group yang digunakan:

```text
structured_data_settings
```

Default value dan daftar field dikelola oleh:

```text
app/Support/StructuredDataSettings.php
```

Builder JSON-LD dikelola oleh:

```text
app/Support/StructuredDataBuilder.php
```

## Key `site_settings`

Key yang digunakan:

```text
structured_data.enabled
structured_data.business.enabled
structured_data.business.type
structured_data.business.name_override
structured_data.business.legal_name_override
structured_data.business.description_override
structured_data.business.price_range
structured_data.business.currencies
structured_data.business.area_served
structured_data.business.service_type
structured_data.business.opening_hours_source
structured_data.business.custom_opening_hours
structured_data.website.enabled
structured_data.breadcrumbs.enabled
structured_data.product.enabled
```

## Field Admin

Admin membuka:

```text
GET /admin/settings/global-assets?tab=structured-data
route: admin.settings.global-assets.edit
```

Field form:

```text
structured_data[enabled]                 nullable boolean
structured_data[business_enabled]        nullable boolean
structured_data[business_type]           required select Organization/LocalBusiness/TravelAgency/TouristInformationCenter
structured_data[business_name_override]  nullable string max 255
structured_data[legal_name_override]     nullable string max 255
structured_data[description_override]    nullable string max 1500
structured_data[price_range]             nullable string max 255
structured_data[currencies]              nullable string max 255
structured_data[area_served]             nullable string max 255
structured_data[service_type]            nullable string max 255
structured_data[opening_hours_source]    required select contact_information/custom/disabled
structured_data[custom_opening_hours]    nullable string max 255
structured_data[website_enabled]         nullable boolean
structured_data[breadcrumbs_enabled]     nullable boolean
structured_data[product_enabled]         nullable boolean
```

## Admin UI

Tab Global Assets baru:

```text
Structured Data
```

Form memakai accordion agar halaman tidak panjang dan tetap mudah dibaca admin.

Accordion section:

```text
General
Business Identity Schema
Website Schema
Breadcrumb Schema
Product / Tour Schema
```

Behavior accordion:

```text
General terbuka secara default.
Saat section lain dibuka, section yang sedang terbuka otomatis tertutup.
```

Rule yang ditampilkan ke admin:

```text
Leave override fields empty if you want schema to follow Business Identity, Contact Information, Social Media Links, Site Logo, and SEO Default automatically.
```

Admin juga diberi informasi bahwa Business Schema tetap menghormati toggle lama dari SEO Default:

```text
SEO Default -> Enable organization schema
```

Ini dibuat agar setting lama tidak berubah perilakunya secara diam-diam.

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/structured-data
route: admin.settings.global-assets.structured-data.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi semua field Structured Data.
3. Field boolean disimpan sebagai 0 atau 1.
4. Field select divalidasi sesuai option yang tersedia.
5. Field text dan textarea disimpan sesuai input atau default value.
6. Set group menjadi structured_data_settings.
7. Set is_active=true.
8. Redirect kembali ke Global Assets tab structured-data.
```

Tidak ada delete endpoint khusus pada branch ini.

Jika schema ingin dimatikan, admin cukup mematikan checkbox:

```text
Enable structured data
```

Atau mematikan masing-masing bagian:

```text
Enable business schema
Enable website schema
Enable breadcrumb schema
Enable product/tour schema
```

## Sinkronisasi Frontend

Structured Data dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
structuredDataSettings
```

Partial render JSON-LD:

```text
resources/views/partials/site-structured-data.blade.php
```

Partial ini dipasang di layout frontend:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
```

Structured Data dirender di `<head>` setelah metadata SEO/social:

```text
@include('partials.site-social-share-meta')
@include('partials.site-structured-data')
```

## JSON-LD Output

Structured Data menghasilkan satu script:

```html
<script type="application/ld+json">...</script>
```

Format output:

```json
{
  "@context": "https://schema.org",
  "@graph": []
}
```

Semua schema dimasukkan ke satu `@graph`.

Ini sengaja dibuat untuk menghindari banyak script JSON-LD yang berpotensi menimbulkan duplicate schema.

## Business Schema

Business schema aktif jika:

```text
structured_data.enabled = true
structured_data.business.enabled = true
seo.default.enable_organization_schema = true
```

Toggle SEO Default tetap dihormati agar kompatibel dengan setting lama.

Schema type yang tersedia:

```text
Organization
LocalBusiness
TravelAgency
TouristInformationCenter
```

Default:

```text
Organization
```

Data yang dirender:

```text
@type
@id
name
legalName
url
logo
description
email
telephone
address
priceRange
currenciesAccepted
areaServed
knowsAbout
openingHours
sameAs
```

Fallback data Business Schema:

```text
name:
1. structured_data.business.name_override
2. seo.default.site_name
3. business_identity.brand_name
4. config app.name

legalName:
1. structured_data.business.legal_name_override
2. business_identity.legal_name

description:
1. structured_data.business.description_override
2. business_identity.short_description

url:
1. seo.default.canonical_base_url
2. url('/')

logo:
1. site.logo
2. site.logo.dark

email:
1. contact_information.email

telephone:
1. contact_information.phone

address:
1. contact_information.address

openingHours:
1. contact_information.opening_hours jika opening_hours_source = contact_information
2. structured_data.business.custom_opening_hours jika opening_hours_source = custom
3. tidak dirender jika opening_hours_source = disabled

sameAs:
1. active Social Media Links URL
```

## Website Schema

Website schema aktif jika:

```text
structured_data.enabled = true
structured_data.website.enabled = true
```

Data yang dirender:

```text
@type = WebSite
@id
name
url
description
publisher
```

Fallback data Website Schema:

```text
name:
1. seo.default.site_name
2. business_identity.brand_name
3. config app.name

url:
1. seo.default.canonical_base_url
2. url('/')

description:
1. business_identity.short_description
2. seo.default.meta_description

publisher:
1. reference ke #business
```

Catatan penting:

Website schema memakai Business Identity description terlebih dahulu agar SEO Default meta description tidak terlihat seperti menggantikan SEO product pada halaman product.

## Breadcrumb Schema

Breadcrumb schema aktif jika:

```text
structured_data.enabled = true
structured_data.breadcrumbs.enabled = true
```

Breadcrumb schema dirender untuk:

```text
Product listing page
Product detail page
```

Product listing:

```text
Home
Products
```

Product detail:

```text
Home
Products
Product Name
```

Schema type:

```text
BreadcrumbList
ListItem
```

Breadcrumb hanya dirender jika minimal ada dua item.

## Product / Tour Schema

Product / Tour schema aktif jika:

```text
structured_data.enabled = true
structured_data.product.enabled = true
current view memiliki variable product dengan model App\Models\Product
```

Data yang dirender:

```text
@type = Product
@id
name
description
image
url
category
brand
offers
```

Fallback data Product Schema:

```text
description:
1. product.meta_description
2. product.short_description

image:
1. prepared Product Detail media state, excluding placeholder/fallback-only media
2. product og_image jika tersedia
3. product thumbnail jika tersedia

url:
1. prepared Product Detail canonical URL
2. product canonical_url
3. route products.show

category:
1. product.category.name

brand:
1. Bintan Prestige / site brand reference

offers:
1. actual positive IDR price jika ada
2. actual positive SGD price jika ada
3. dua currency dirender sebagai dua Offer terpisah
4. tidak ada Offer jika harga kosong, nol, atau tidak valid
```

Product / Tour schema tidak merender fake availability, fake rating, fake review, SKU, GTIN, stock, booking status, price conversion, atau zero-price Offer.

FAQPage schema untuk Product Detail dirender hanya dari FAQ yang benar-benar tampil di halaman dan memiliki question serta answer yang tidak kosong.

BreadcrumbList schema untuk Product Detail mengikuti prepared visible breadcrumb state agar JSON-LD sama dengan breadcrumb yang terlihat.

## Hubungan Dengan SEO Default

SEO Default masih bertanggung jawab untuk metadata:

```text
title
meta description
meta keywords
robots
canonical
Open Graph
Twitter card
Default OG image
```

Structured Data bertanggung jawab untuk:

```text
JSON-LD schema graph
```

Branch ini menghapus render Organization JSON-LD lama dari:

```text
resources/views/partials/site-social-share-meta.blade.php
```

Kemudian render schema dipusatkan ke:

```text
resources/views/partials/site-structured-data.blade.php
```

Alasan:

```text
Menghindari Organization schema dobel.
Membuat semua JSON-LD dikelola dari satu builder.
Membuat schema lebih scalable untuk Business, Website, Breadcrumb, Product, dan future schema lain.
```

SEO Default field berikut tetap dipakai sebagai safety switch:

```text
seo.default.enable_organization_schema
```

Jika field ini false, Business Schema tidak dirender walaupun Structured Data business schema aktif.

## Hubungan Dengan Product SEO

Product SEO tidak diubah oleh branch ini.

Field product SEO yang tetap menjadi prioritas metadata:

```text
products.meta_title
products.meta_description
products.meta_keywords
products.canonical_url
products.og_image
```

Pada halaman product:

```text
Meta title memakai product meta_title jika ada.
Meta description memakai product meta_description jika ada.
Meta keywords memakai product meta_keywords jika ada.
Canonical memakai product canonical_url jika ada.
OG image memakai product og_image jika ada.
```

Structured Data hanya membaca data product untuk membuat Product schema.

Structured Data tidak mengisi otomatis field SEO product di database.

Jika SEO product kosong:

```text
Frontend metadata tetap mengikuti fallback yang sudah ada:
1. product content
2. SEO Default
```

Product schema tetap bisa dirender dengan data product yang tersedia.

Dengan rule ini, SEO product dan Structured Data tidak bentrok.

## Hubungan Dengan Global Assets Lain

Structured Data membaca data dari Global Assets lain:

```text
Business Identity
Contact Information
Social Media Links
Site Logo
SEO Default
```

Structured Data tidak menduplikasi data tersebut.

Structured Data hanya menyediakan override khusus schema jika admin benar-benar ingin menampilkan nilai yang berbeda untuk search engine.

Contoh:

```text
Business Identity brand_name = Bintan Prestige
Structured Data business_name_override kosong
Schema name = Bintan Prestige
```

Contoh override:

```text
Business Identity brand_name = Bintan Prestige
Structured Data business_name_override = Bintan Prestige Travel Agency
Schema name = Bintan Prestige Travel Agency
```

## File Yang Ditambahkan

```text
app/Support/StructuredDataSettings.php
app/Support/StructuredDataBuilder.php
resources/views/partials/site-structured-data.blade.php
tests/Feature/Admin/GlobalStructuredDataSettingsTest.php
docs/global-structured-data-business-schema.md
```

## File Yang Diubah

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/frontend.blade.php
resources/views/layouts/frontend.blade.php
resources/views/partials/site-social-share-meta.blade.php
routes/admin.php
```

## Detail Perubahan File

`app/Support/StructuredDataSettings.php`

```text
Menentukan group structured_data_settings.
Mendefinisikan semua field Structured Data.
Memberikan default value.
Melakukan casting boolean dari site_settings.
Menyediakan daftar boolean slugs.
```

`app/Support/StructuredDataBuilder.php`

```text
Membangun @graph JSON-LD.
Membangun Business schema.
Membangun WebSite schema.
Membangun BreadcrumbList schema.
Membangun Product schema.
Membersihkan value kosong sebelum JSON-LD dirender.
Menghormati SEO Default enable_organization_schema untuk Business schema.
```

`app/Http/Controllers/Admin/SiteSettingController.php`

```text
Memuat Structured Data fields dan values ke halaman Global Assets.
Menambahkan handler updateStructuredDataSettings().
Menambahkan tab Structured Data ke daftar assetTabs().
Menyimpan field ke table site_settings dengan group structured_data_settings.
```

`app/Providers/AppServiceProvider.php`

```text
Memuat structuredDataSettings melalui View Composer.
Membagikan structuredDataSettings ke view frontend.
```

`resources/views/backend/settings/global-assets.blade.php`

```text
Menambahkan tab UI Structured Data.
Menambahkan form accordion.
Menampilkan helper text agar admin paham fallback data global.
Menampilkan key setting untuk kebutuhan audit.
```

`resources/views/partials/site-structured-data.blade.php`

```text
Memanggil StructuredDataBuilder::jsonLd().
Mengirim context dari layout/frontend ke builder.
Merender script application/ld+json jika graph tidak kosong.
```

`resources/views/partials/site-social-share-meta.blade.php`

```text
Menghapus Organization JSON-LD lama.
Tetap merender meta description, keywords, robots, canonical, Open Graph, dan Twitter card.
```

`resources/views/layouts/frontend.blade.php`

```text
Menambahkan include partial site-structured-data di head.
```

`resources/views/frontend/frontend.blade.php`

```text
Menambahkan include partial site-structured-data di head.
```

`routes/admin.php`

```text
Menambahkan route update Structured Data.
```

`tests/Feature/Admin/GlobalStructuredDataSettingsTest.php`

```text
Menambahkan test update setting.
Menambahkan test tab UI.
Menambahkan test render satu JSON-LD graph.
Menambahkan test Product dan Breadcrumb schema.
Menambahkan test SEO Default organization toggle.
Menambahkan test master switch structured data.
```

## Validation

Validation dilakukan di:

```text
app/Http/Controllers/Admin/SiteSettingController.php
method: updateStructuredDataSettings()
```

Rule utama:

```text
boolean fields  = nullable boolean
select fields   = required dan harus sesuai options field
textarea fields = nullable string max 1500
text fields     = nullable string max 255
```

## Test

Test baru:

```text
tests/Feature/Admin/GlobalStructuredDataSettingsTest.php
```

Command yang dijalankan:

```text
php artisan test tests\Feature\Admin\GlobalStructuredDataSettingsTest.php
php artisan test tests\Feature\Admin\GlobalSeoDefaultSettingsTest.php tests\Feature\Admin\GlobalDefaultMediaAssetsTest.php tests\Feature\Admin\GlobalBookingCtaSettingsTest.php tests\Feature\Admin\GlobalStructuredDataSettingsTest.php
php artisan test
npm.cmd run build
```

Hasil:

```text
Structured Data test: passed
Global Assets related suite: passed
Full test suite: 77 tests passed
Frontend build: success
```

## Catatan Audit

Branch ini tidak membuat migration baru.

Branch ini tidak membuat atau mengubah table:

```text
products
categories
destinations
site_assets
page_sections
bookings
```

Branch ini hanya menulis ke:

```text
site_settings group structured_data_settings
```

Branch ini membaca data dari:

```text
site_settings group seo_default_settings
site_settings group business_identity_settings
site_settings group contact_information_settings
site_settings group social_media_links_settings
site_assets key site.logo
site_assets key site.logo.dark
products pada halaman product detail
```

Data product tidak pernah di-update oleh Structured Data.

Jika Structured Data dimatikan, frontend tidak merender JSON-LD graph dari fitur ini.

Jika Business Schema dimatikan, Website, Breadcrumb, atau Product schema masih bisa berjalan sesuai toggle masing-masing.

Jika SEO Default `enable_organization_schema` dimatikan, Business Schema tidak dirender.

## Scalable Notes

Struktur builder dibuat reusable agar future schema dapat ditambahkan tanpa mengubah layout utama.

Future schema yang bisa ditambahkan:

```text
FAQPage schema
Article schema
Review schema
TouristTrip schema
Service schema
Place schema
ImageObject schema
VideoObject schema
SearchAction schema
```

Penambahan future schema cukup dilakukan dengan pola:

```text
1. Tambahkan field/toggle baru di StructuredDataSettings.php.
2. Tambahkan method builder baru di StructuredDataBuilder.php.
3. Masukkan method tersebut ke graph().
4. Tambahkan UI accordion section jika field perlu dikelola admin.
5. Tambahkan test untuk output JSON-LD dan fallback data.
```

Dengan pendekatan ini, Structured Data tetap menjadi satu sumber JSON-LD global dan tidak tersebar di banyak Blade partial.
