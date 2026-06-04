# Global Header Navigation Settings

Branch: `feature/global-navigation-settings`

## Tujuan

Menambahkan Header Navigation sebagai global setting agar menu header, CTA header, dan behavior sticky tidak hardcode di Blade frontend.

Admin dapat mengubah item menu dari halaman `Global Assets` tanpa masuk ke page section.

## Struktur Data

Tabel yang digunakan:

```text
site_settings
```

Tidak ada migration baru di branch ini.

Branch ini memakai tabel `site_settings` yang sudah dibuat pada branch Brand Colors. Jika environment belum memiliki tabel ini, jalankan:

```text
php artisan migrate
```

Kolom:

```text
key        = identifier setting global
label      = label internal admin
value      = text, boolean, atau JSON navigation
type       = text, boolean, json
group      = navigation_settings
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan:

```text
navigation.header.cta_label
navigation.header.cta_url
navigation.header.is_sticky
navigation.header.menu_text_color
navigation.header.menu_hover_color
navigation.header.scrolled_menu_text_color
navigation.header.scrolled_menu_hover_color
navigation.header.dropdown_text_color
navigation.header.dropdown_hover_background
navigation.header.items
```

Default value dan normalisasi data dikelola oleh:

```text
app/Support/NavigationSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=navigation-settings
route: admin.settings.global-assets.edit
```

Field form:

```text
navigation_settings[cta_label]       nullable string max 100
navigation_settings[cta_url]         nullable string max 500
navigation_settings[is_sticky]       nullable boolean
navigation_settings[menu_text_color]              nullable hex color
navigation_settings[menu_hover_color]             nullable hex color
navigation_settings[scrolled_menu_text_color]     nullable hex color
navigation_settings[scrolled_menu_hover_color]    nullable hex color
navigation_settings[dropdown_text_color]          nullable hex color
navigation_settings[dropdown_hover_background]    nullable hex color
navigation_items[*][label]           nullable string max 80
navigation_items[*][url]             nullable string max 500
navigation_items[*][is_external]     nullable boolean
navigation_items[*][children]        nullable array
navigation_items[*][children][*][label]        nullable string max 80
navigation_items[*][children][*][url]          nullable string max 500
navigation_items[*][children][*][is_external] nullable boolean
```

Struktur item menu:

```text
label       = teks menu yang tampil di header
url         = target link, mendukung /path, #anchor, /#anchor, atau https://...
is_external = jika true, link dibuka di tab baru dengan rel noopener noreferrer
children    = optional dropdown items di bawah menu utama
```

Struktur dropdown item:

```text
label       = teks dropdown
url         = target link dropdown
is_external = jika true, dropdown link dibuka di tab baru
```

Catatan:

```text
active_route tidak ditampilkan ke admin.
Active menu dihitung otomatis dari URL dan current path frontend.
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/navigation-settings
route: admin.settings.global-assets.navigation-settings.update
```

Flow:

```text
1. Pastikan tabel site_settings tersedia.
2. Validasi field CTA, sticky toggle, dan array navigation_items.
3. Validasi field warna dengan format hex `#RRGGBB`.
4. Simpan field CTA, sticky, dan warna menu ke key masing-masing.
5. Normalisasi navigation_items agar hanya row dengan label dan URL yang disimpan.
6. Normalisasi children dropdown agar hanya row dengan label dan URL yang disimpan.
7. Simpan menu items ke key navigation.header.items sebagai JSON.
8. Set group=navigation_settings dan is_active=true.
9. Redirect kembali ke Global Assets tab navigation-settings.
```

## Delete

Belum ada delete endpoint terpisah di branch ini.

Untuk menghapus menu:

```text
Klik Remove pada row menu, lalu Save Header Navigation.
```

Untuk menghapus dropdown:

```text
Klik Remove pada row dropdown, lalu Save Header Navigation.
```

Jika semua row menu dikosongkan, sistem menyimpan fallback default menu agar header tidak kosong.

## Sinkronisasi Frontend

Navigation settings dimuat melalui View Composer:

```text
app/Providers/AppServiceProvider.php
```

Variable yang dibagikan:

```text
navigationSettings
```

Area frontend yang memakai navigation settings:

```text
resources/views/frontend/partials/header.blade.php
```

Render rule:

```text
navigation.header.items      -> menu header
navigation.header.cta_label  -> teks tombol CTA header
navigation.header.cta_url    -> URL tombol CTA header dan icon CTA mobile
navigation.header.is_sticky  -> menentukan header floating sticky atau inline
navigation.header menu colors -> CSS variables untuk warna menu header
```

Render dropdown:

```text
Jika item memiliki children, header menampilkan dropdown saat hover/focus.
Parent tetap bisa punya URL sendiri.
Child link bisa internal atau external.
```

Active state:

```text
Menu dianggap active jika URL menu cocok dengan current path.
Menu parent juga active jika salah satu child cocok dengan current path.
Anchor seperti /#destinations tidak memakai active state halaman.
External URL tidak memakai active state halaman.
```

Jika setting belum pernah disimpan, frontend memakai default dari `NavigationSettings::defaultItems()`.

## Perubahan Admin

Halaman `Global Assets` memiliki tab tambahan:

```text
Header Navigation
```

Tab ini berisi:

```text
Header Settings accordion
Menu Colors accordion
Menu Items accordion
```

Accordion behavior:

```text
Header Settings terbuka saat tab pertama kali dibuka.
Saat satu accordion dibuka, accordion lain otomatis tertutup.
Field yang berada di accordion tertutup tetap ikut tersimpan saat form disubmit.
```

Isi `Header Settings`:

```text
Header CTA label
Header CTA URL
Sticky header toggle
```

Isi `Menu Colors`:

```text
Menu text
Menu hover / active
Scrolled menu text
Scrolled hover / active
Dropdown text
Dropdown hover background
```

Isi `Menu Items`:

```text
Menu Items dengan tombol Add Menu, Move Up, Move Down, dan Remove
Dropdown Items dengan tombol Add Dropdown dan Remove
Open in new tab checkbox untuk link external
```

Field `Menu Colors`:

```text
Menu text                  = warna menu saat header transparent
Menu hover / active        = warna hover dan active saat header transparent
Scrolled menu text         = warna menu saat header scrolled atau inline
Scrolled hover / active    = warna hover dan active saat header scrolled atau inline
Dropdown text              = warna teks dropdown
Dropdown hover background  = warna background dropdown saat hover atau active
```

Catatan:

```text
Warna menu header sengaja diletakkan di Header Navigation, bukan Brand Colors.
Alasannya, ini style spesifik komponen navigation.
Brand Colors tetap menjadi palette global, sedangkan Header Navigation mengatur pemakaian detail di header.
```

## Function Detail

```text
NavigationSettings::fields()
```

Menentukan field global non-menu:

```text
cta_label
cta_url
is_sticky
menu color fields
```

```text
NavigationSettings::defaultItems()
```

Menyediakan fallback menu jika admin belum menyimpan navigation setting.

```text
NavigationSettings::valuesFromSettings()
```

Mengubah row `site_settings` menjadi array siap pakai di Blade.
Termasuk fallback warna default jika admin belum pernah menyimpan field warna.

```text
NavigationSettings::normalizeItems()
```

Membersihkan menu utama sebelum disimpan:

```text
trim label dan URL
cast is_external ke boolean
buang row kosong
normalisasi children
```

```text
NavigationSettings::normalizeChildren()
```

Membersihkan dropdown item sebelum disimpan:

```text
trim label dan URL
cast is_external ke boolean
buang row kosong
```

```text
NavigationSettings::resolveUrl()
```

Mengubah URL internal menjadi URL penuh Laravel:

```text
/products      -> http://domain/products
/#destinations -> http://domain/#destinations
#contact       -> #contact
https://...    -> tetap https://...
```

```text
NavigationSettings::isActiveUrl()
```

Menentukan active state otomatis dari URL, bukan dari input manual admin.

```text
/products aktif untuk /products dan /products/slug
/ hanya aktif untuk home
#anchor dan external URL tidak dihitung active
```

Frontend header memakai CSS variables dari setting warna:

```text
--header-nav-color
--header-nav-hover-color
--header-nav-scrolled-color
--header-nav-scrolled-hover-color
--header-dropdown-text-color
--header-dropdown-hover-background
```

Variable ini dipasang di:

```text
resources/views/frontend/partials/header.blade.php
```

Dan dikonsumsi di:

```text
resources/css/frontend-theme.css
```

## File Terkait

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Providers/AppServiceProvider.php
app/Support/NavigationSettings.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/frontend/partials/header.blade.php
resources/css/frontend-theme.css
tests/Feature/Admin/GlobalNavigationSettingsTest.php
docs/global-navigation-settings.md
```

## Catatan Audit

Tidak ada perubahan database core di branch ini.

Data menu dan dropdown disimpan sebagai JSON di satu key:

```text
navigation.header.items
```

Contoh JSON:

```text
[
  {
    "label": "Packages",
    "url": "/products",
    "is_external": false,
    "children": [
      {
        "label": "Private Trip",
        "url": "/products/private-trip",
        "is_external": false
      }
    ]
  }
]
```

Pilihan ini dibuat agar scalable. Jika nanti ingin detail lebih jauh, misalnya menu desktop/mobile berbeda, mega menu, dropdown, icon menu, atau CTA per breakpoint, bisa ditambahkan sebagai key baru di group yang sama tanpa migration baru:

```text
navigation.mobile.items
navigation.header.secondary_items
navigation.header.mega_menu
navigation.header.cta_style
navigation.header.mobile_menu_colors
navigation.footer.quick_links
```

Header lama tetap aman karena fallback default disediakan oleh support class.

## Revisi UX

Field `active_route` dihapus dari form admin karena terlalu teknis dan rawan salah isi.

Admin tidak perlu tahu nama route Laravel. Sistem membaca URL menu dan mencocokkannya dengan path halaman aktif.

Checkbox external diganti labelnya menjadi:

```text
Open in new tab
```

Fungsinya untuk link keluar website, misalnya Instagram, Google Maps, marketplace, atau partner link. Jika aktif, frontend menambahkan:

```text
target="_blank"
rel="noopener noreferrer"
```
