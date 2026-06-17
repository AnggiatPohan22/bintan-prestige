# Global Brand Colors Settings

Branch: `feature/global-brand-colors-settings`

## Tujuan

Menambahkan pengaturan brand colour sebagai global setting agar warna brand website tidak diatur dari page section dan dapat dipakai lintas frontend.

## Struktur Data

Tabel baru:

```text
site_settings
```

Migration wajib dijalankan setelah masuk branch ini:

```text
php artisan migrate
```

Jika migration belum dijalankan, frontend tetap memakai default color dari `BrandColorSettings` dan tidak melakukan query ke tabel yang belum ada.

Kolom:

```text
key        = identifier setting global
label      = label internal admin
value      = nilai setting, untuk branch ini berupa HEX color
type       = tipe setting, untuk branch ini color
group      = kelompok setting, untuk branch ini brand_colors
is_active  = menentukan setting dimuat ke layout
```

Key yang digunakan pada fondasi awal:

```text
brand.palette.primary
brand.palette.secondary
brand.palette.accent

brand.surface.body
brand.surface.card
brand.surface.soft
brand.surface.dark
brand.surface.border

brand.text.title
brand.text.body
brand.text.muted
brand.text.link
brand.text.on_dark

brand.button.primary.bg
brand.button.primary.text
brand.button.primary.hover_bg
brand.button.primary.hover_text
brand.button.cta.bg
brand.button.cta.text
brand.button.submit.bg
brand.button.submit.text
```

Struktur key dibuat scalable:

```text
brand.palette.* = warna dasar brand
brand.surface.* = warna background, card, dan border
brand.text.*    = warna typography
brand.button.*  = warna button per tipe
```

Mapping CSS variables utama:

```text
brand.palette.primary        -> --frontend-black
brand.palette.secondary      -> --frontend-gold
brand.palette.accent         -> --frontend-brand-accent
brand.surface.card           -> --frontend-white
brand.surface.soft           -> --frontend-gold-pale
brand.surface.border         -> --frontend-border
brand.text.title             -> --frontend-charcoal
brand.text.muted             -> --frontend-gray
brand.text.on_dark           -> --frontend-gold-soft

brand.button.primary.bg         -> --frontend-button-primary-bg
brand.button.primary.text       -> --frontend-button-primary-text
brand.button.primary.hover_bg   -> --frontend-button-primary-hover-bg
brand.button.primary.hover_text -> --frontend-button-primary-hover-text
brand.button.cta.bg             -> --frontend-button-cta-bg
brand.button.cta.text           -> --frontend-button-cta-text
brand.button.submit.bg          -> --frontend-button-submit-bg
brand.button.submit.text        -> --frontend-button-submit-text
```

Default color dikelola oleh:

```text
app/Support/BrandColorSettings.php
```

## Input

Admin membuka:

```text
GET /admin/settings/global-assets?tab=brand-colors
route: admin.settings.global-assets.edit
```

Field form:

```text
brand_colors[palette_primary]              required HEX #RRGGBB
brand_colors[palette_secondary]            required HEX #RRGGBB
brand_colors[palette_accent]               required HEX #RRGGBB
brand_colors[surface_body]                 required HEX #RRGGBB
brand_colors[surface_card]                 required HEX #RRGGBB
brand_colors[surface_soft]                 required HEX #RRGGBB
brand_colors[surface_dark]                 required HEX #RRGGBB
brand_colors[surface_border]               required HEX #RRGGBB
brand_colors[text_title]                   required HEX #RRGGBB
brand_colors[text_body]                    required HEX #RRGGBB
brand_colors[text_muted]                   required HEX #RRGGBB
brand_colors[text_link]                    required HEX #RRGGBB
brand_colors[text_on_dark]                 required HEX #RRGGBB
brand_colors[button_primary_bg]            required HEX #RRGGBB
brand_colors[button_primary_text]          required HEX #RRGGBB
brand_colors[button_primary_hover_bg]      required HEX #RRGGBB
brand_colors[button_primary_hover_text]    required HEX #RRGGBB
brand_colors[button_cta_bg]                required HEX #RRGGBB
brand_colors[button_cta_text]              required HEX #RRGGBB
brand_colors[button_submit_bg]             required HEX #RRGGBB
brand_colors[button_submit_text]           required HEX #RRGGBB
```

## Update

Endpoint:

```text
PUT /admin/settings/global-assets/brand-colors
route: admin.settings.global-assets.brand-colors.update
```

Flow:

```text
1. Validasi semua value color harus format HEX #RRGGBB.
2. Loop semua field dari BrandColorSettings::fields().
3. updateOrCreate row site_settings sesuai key.
4. Set label, value, type=color, group=brand_colors, is_active=true.
5. Redirect kembali ke Global Assets tab brand-colors.
```

## Delete

Belum ada delete untuk brand colors di branch ini.

Alasan:

```text
Brand colors adalah setting fondasi theme. Jika tidak ada row aktif, frontend otomatis memakai default dari BrandColorSettings.
```

## Sinkronisasi Frontend

Brand color dimuat melalui partial:

```text
resources/views/partials/site-brand-colors.blade.php
```

Partial ini mengeluarkan inline CSS variables di layout setelah Vite CSS, sehingga value global settings override default token di CSS.

Layout yang memakai partial:

```text
resources/views/layouts/frontend.blade.php
resources/views/frontend/frontend.blade.php
resources/views/layouts/admin.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/guest.blade.php
```

View composer memuat setting aktif:

```text
app/Providers/AppServiceProvider.php
```

## Perubahan Admin

Halaman `Global Assets` sekarang memiliki tab tambahan:

```text
Brand Colors
```

Tab ini berisi input warna, preview swatch, dan tombol save.

Field di tab ini dikelompokkan agar tetap mudah dipakai:

```text
Palette
Surfaces
Typography
Buttons
```

File terkait:

```text
app/Http/Controllers/Admin/SiteSettingController.php
app/Models/SiteSetting.php
app/Support/BrandColorSettings.php
database/migrations/2026_06_04_000001_create_site_settings_table.php
routes/admin.php
resources/views/backend/settings/global-assets.blade.php
resources/views/partials/site-brand-colors.blade.php
resources/css/frontend-theme.css
tests/Feature/Admin/GlobalBrandColorsSettingsTest.php
docs/global-brand-colors-settings.md
```

## Catatan Audit

Brand colors tidak menggunakan `site_assets` karena bukan file upload.

`site_settings` dibuat agar global setting non-file berikutnya seperti contact info, social links, SEO defaults, dan tracking bisa memakai struktur yang sama.

Untuk detail lanjutan, branch berikutnya dapat menambah key tanpa migration baru, misalnya:

```text
brand.button.outline.bg
brand.button.outline.text
brand.section.cta.bg
brand.section.gallery.bg
brand.section.footer.bg
```
