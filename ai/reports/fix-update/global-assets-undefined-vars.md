# Fix Report — Undefined Variables in global-assets.blade.php

**Date:** 2026-06-23
**Branch:** develop
**Files changed:** 2

---

## Root Cause

`resources/views/backend/settings/global-assets.blade.php` uses `$value`, `$checkedValue`, `$navigationBasicFields`, `$navigationColorFields`, `$trackingSections`, `$bookingCtaSections`, dan `$structuredDataSections` yang tidak pernah didefinisikan — baik oleh controller maupun di dalam `@foreach` loop di view. Error `Undefined variable $asset` (yang dilaporkan lebih awal) adalah manifestasi pertama, tapi audit penuh menemukan **19 titik undefined variable** tersebar di 12 tab settings.

---

## Semua Variabel yang Diperbaiki

### A — Controller tidak mengirim variabel yang dibutuhkan view

| Variabel | Tab | Fix |
|----------|-----|-----|
| `$navigationBasicFields` | Header Navigation | Ditambahkan di controller: filter `$navigationFields` by type `!== 'color'` |
| `$navigationColorFields` | Header Navigation | Ditambahkan di controller: filter `$navigationFields` by type `=== 'color'` |
| `$trackingSections` | Tracking / Integrations | Ditambahkan di controller: `collect($trackingIntegrationFields)->groupBy('section')` |
| `$bookingCtaSections` | Booking / CTA | Ditambahkan di controller: `collect($bookingCtaFields)->groupBy('section')` |
| `$structuredDataSections` | Structured Data | Ditambahkan di controller: `collect($structuredDataFields)->groupBy('section')` |

### B — `$value` tidak didefinisikan di dalam `@foreach` loop

| Tab | Loop | Sumber data benar | Fix |
|-----|------|-------------------|-----|
| Site Logo | `@foreach($logoVariants as $variant)` (upload) | `$siteLogos[$variant['key']]` | `@php` block — dilakukan di sesi sebelumnya |
| Site Logo | `@foreach($logoVariants as $variant)` (delete) | `$siteLogos[$variant['key']]` | `@php` block — dilakukan di sesi sebelumnya |
| Default Media | `@foreach($defaultMediaVariants as $variant)` (delete) | `$defaultMediaAssets[$variant['key']]` | `@php` block — dilakukan di sesi sebelumnya |
| Brand Colors | `@foreach($fields as $field)` (inner) | `$brandColors[$field['slug']]` | Tambah `@php $value = $brandColors[$field['slug']] ?? $field['default'];` |
| Business Identity | `@foreach($businessIdentityFields as $field)` | `$businessIdentity[$field['slug']]` | Tambah `@php $value = $businessIdentity[$field['slug']] ?? $field['default'] ?? '';` |
| Contact Information | `@foreach($contactInformationFields as $field)` | `$contactInformation[$field['slug']]` | Tambah `@php $value = $contactInformation[$field['slug']] ?? $field['default'] ?? '';` |
| Social Media Links | `@foreach($socialMediaLinkFields as $field)` | `$socialMediaLinks[$field['slug']]` | Tambah `@php $value = $socialMediaLinks[$field['slug']] ?? '';` |
| Header Navigation (basic) | `@foreach($navigationBasicFields as $field)` | `$navigationSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |
| Header Navigation (color) | `@foreach($navigationColorFields as $field)` | `$navigationSettings[$field['slug']]` | Tambah `@php $value = $navigationSettings[$field['slug']] ?? $field['default'];` |
| Footer Settings | `@foreach($footerFields as $field)` | `$footerSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |
| SEO Default | `@foreach($seoDefaultFields as $field)` | `$seoDefaultSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |
| Tracking / Integrations | `@foreach($fields as $field)` (inner) | `$trackingIntegrationSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |
| Booking / CTA | `@foreach($fields as $field)` (inner) | `$bookingCtaSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |
| Structured Data | `@foreach($fields as $field)` (inner) | `$structuredDataSettings[$field['slug']]` | Tambah `@php $value = ...; $checkedValue = ...;` |

---

## File yang Diubah

### 1. `app/Http/Controllers/Admin/SiteSettingController.php`

**Perubahan di method `edit()`:**

```php
// Setelah $navigationSettings = NavigationSettings::valuesFromSettings(...)
$navigationBasicFields = collect($navigationFields)->filter(fn ($f) => $f['type'] !== 'color')->values()->all();
$navigationColorFields = collect($navigationFields)->filter(fn ($f) => $f['type'] === 'color')->values()->all();

// Setelah $trackingIntegrationSettings = TrackingIntegrationSettings::valuesFromSettings(...)
$trackingSections = collect($trackingIntegrationFields)->groupBy('section')->all();

// Setelah $bookingCtaSettings = BookingCtaSettings::valuesFromSettings(...)
$bookingCtaSections = collect($bookingCtaFields)->groupBy('section')->all();

// Setelah $structuredDataSettings = StructuredDataSettings::valuesFromSettings(...)
$structuredDataSections = collect($structuredDataFields)->groupBy('section')->all();
```

**`compact()` diperbarui** untuk menyertakan 5 variabel baru:
`navigationBasicFields`, `navigationColorFields`, `trackingSections`, `bookingCtaSections`, `structuredDataSections`

### 2. `resources/views/backend/settings/global-assets.blade.php`

**10 `@php` block** ditambahkan — satu di awal setiap `@foreach` loop yang menggunakan `$value` atau `$checkedValue`. Pola yang digunakan:

```php
@foreach($someFields as $field)
    @php
        $value = $settingsArray[$field['slug']] ?? $field['default'] ?? '';
        $checkedValue = (bool) ($settingsArray[$field['slug']] ?? false); // hanya untuk tab dengan boolean
    @endphp
    ...
```

---

## Impact

| Area | Status |
|------|--------|
| DB | Tidak ada perubahan |
| Routes | Tidak ada perubahan |
| Frontend | Tidak ada perubahan |
| Admin UI | Semua 14 tab di `/admin/settings/global-assets` sekarang dapat dirender tanpa error |
| Security | Tidak ada perubahan — `$value` hanya di-render ke form value, bukan dieksekusi |

---

## Rollback

```bash
git diff HEAD app/Http/Controllers/Admin/SiteSettingController.php
git checkout HEAD -- app/Http/Controllers/Admin/SiteSettingController.php
git checkout HEAD -- resources/views/backend/settings/global-assets.blade.php
```

---

## Next

Verifikasi semua 14 tab dapat dibuka di browser tanpa error (`/admin/settings/global-assets?tab=site-logo` sampai `?tab=structured-data`).
