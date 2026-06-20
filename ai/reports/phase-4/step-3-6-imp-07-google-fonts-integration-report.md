# STEP 3.6 / IMP-07 — Google Fonts Integration

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 3 hari
**Priority:** MEDIUM
**Status:** COMPLETE ✅

---

## 1. Scope

Add a searchable Google Font picker to the theme customizer. The selected font is
stored in the `customization` column alongside CSS tokens, loaded via a `<link>` tag
in the frontend `<head>`, and applied to `body` as `font-family`.

The font list is fetched from the Google Fonts API, cached for 24 hours, and falls
back to `[]` (picker hidden) when no API key is configured.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Services/GoogleFontsService.php` | `getFontList()` (cached 24h, Http::fake-safe), `forget()` |
| `tests/Feature/Phase4/GoogleFontsIntegrationTest.php` | 10 tests (I1–I10) |

### Modified

| File | Change |
|------|--------|
| `config/services.php` | Added `google_fonts.key => env('GOOGLE_FONTS_API_KEY')` |
| `app/Http/Requests/Admin/UpdateThemeCustomizationRequest.php` | Added `google_font` nullable string rule |
| `app/Services/ThemeService.php` | Added `getGoogleFont()`; `resolvedTokens()` now filters out non-`--` keys from CSS pipeline |
| `app/Http/Controllers/Admin/ThemeController.php` | Injected `GoogleFontsService`; `customize()` passes `$fontList`; `updateCustomization()` saves `_google_font` |
| `resources/views/layouts/frontend.blade.php` | Outputs Google Font `<link>` + `body { font-family }` when font is selected |
| `resources/views/backend/themes/customize.blade.php` | Added searchable font picker panel + `googleFontPicker()` Alpine.js component |

---

## 3. GoogleFontsService

**Namespace:** `App\Services\GoogleFontsService`
**Cache key:** `google_fonts.list.v1`
**Cache TTL:** 86400 seconds (24 hours)

### getFontList(): array

```
1. Check config('services.google_fonts.key') — return [] if empty
2. Cache::remember(CACHE_KEY, 86400, fn() => ...)
   └─ Http::timeout(5)->get('googleapis.com/webfonts/v1/webfonts', ['key', 'sort=popularity'])
   └─ On success: collect items → pluck('family') → all()
   └─ On failure (non-200 or exception): Log::warning + return []
```

**Fallback guarantees:** The font picker is never shown when the API key is not set.
A failed API call returns `[]` silently — no exception bubbles to the admin UI.

---

## 4. Storage Design

The selected Google Font is stored in the `customization` JSON column under the
special key `_google_font` (underscore prefix = non-CSS metadata).

| Key | Example value | Used by |
|-----|---------------|---------|
| `--frontend-gold` | `#B8924A` | CSS `:root {}` block |
| `--bp-space-md` | `16px` | CSS `:root {}` block |
| `_google_font` | `Roboto` | `<link>` tag in `<head>` |

`resolvedTokens()` now filters the saved customization to **only CSS-variable keys**
(`str_starts_with($key, '--')`) before merging with schema defaults. This ensures
`_google_font` never appears as an invalid CSS property in `:root {}`.

`getGoogleFont()` reads `_google_font` directly from `$theme->customization` — a
dedicated accessor bypassing the CSS-vars pipeline.

---

## 5. ThemeService — New and Modified Methods

### getGoogleFont(): ?string (new)

```php
public function getGoogleFont(): ?string
{
    $font = $this->getActiveTheme()?->customization['_google_font'] ?? null;
    return filled($font) ? trim((string) $font) : null;
}
```

### resolvedTokens() — key change

```php
// Before:
$tokens = array_merge($defaults, $theme->customization ?? []);

// After:
$saved = array_filter(
    $theme->customization ?? [],
    fn ($key) => str_starts_with($key, '--'),
    ARRAY_FILTER_USE_KEY
);
$tokens = array_merge($defaults, $saved);
```

---

## 6. Frontend Rendering (`layouts/frontend.blade.php`)

```blade
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">

@php($activeGoogleFont = $themeService->getGoogleFont())
@if($activeGoogleFont)
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $activeGoogleFont) }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: '{{ $activeGoogleFont }}', sans-serif; }</style>
@endif
```

The hardcoded Forum font import (for headings) is preserved. The selected Google Font
applies to `body` text in addition — they coexist.

---

## 7. Admin Customize Form

### Font Picker UI

- A search `<input>` filters the font list in real time via Alpine.js computed property
- A `<select size="6">` shows up to 150 matching fonts at a time
- Selecting a font dynamically injects a `<link>` into `document.head` (only once per font) and updates the preview text `font-family`
- Hidden when `$fontList` is empty (no API key configured)
- Current selection shown below the picker if already saved

### Alpine.js component: `googleFontPicker(allFonts, initialFont)`

```
search  → filters allFonts (case-insensitive substring) → slice(0, 150)
selected → bound to <select name="google_font"> → submitted with the form
loadedFonts → Set of already-injected font families → avoid duplicate <link> tags
loadPreview() → inject <link> if needed → set previewText.style.fontFamily
```

### Security: sanitization in controller

```php
$fontFamily = preg_replace('/[^a-zA-Z0-9 ]/', '', $fontInput);
```

Only letters, digits, and spaces are allowed — prevents CSS injection or XSS through
the font family value, which is rendered unescaped in a `<style>` tag.

---

## 8. Configuration

`.env` (add when API key is available):
```
GOOGLE_FONTS_API_KEY=AIzaSy...
```

`config/services.php`:
```php
'google_fonts' => [
    'key' => env('GOOGLE_FONTS_API_KEY'),
],
```

---

## 9. Test Coverage (I1–I10)

| Test | Contract |
|------|----------|
| I1 | `getFontList()` returns `[]` when API key not configured |
| I2 | `getFontList()` fetches and returns family names from API (Http::fake) |
| I3 | `getFontList()` returns `[]` on non-200 API response |
| I4 | `getFontList()` stores result in cache under `CACHE_KEY` |
| I5 | `forget()` clears the font list cache |
| I6 | `getGoogleFont()` returns null when no active theme |
| I7 | `getGoogleFont()` returns null when `_google_font` not in customization |
| I8 | `getGoogleFont()` returns stored font name |
| I9 | `resolvedTokens()` excludes `_google_font` from the returned map |
| I10 | `resolvedTokens()` still includes CSS vars alongside stored `_google_font` |

---

## 10. Test Results

```
php artisan test tests/Feature/Phase4/GoogleFontsIntegrationTest.php
Tests:  10 passed
Assertions: 12

php artisan test tests/Feature/Phase4/
Tests:  92 passed
Assertions: 209

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
```

---

## 11. Impact Summary

| Area | Impact |
|------|--------|
| DB | None — `customization` JSON column stores `_google_font` transparently |
| Routes | None |
| Frontend | Google Font `<link>` rendered when selected; `body { font-family }` applied globally |
| Admin UI | Google Font picker section at top of customize form (only shown with API key) |
| CSS pipeline | `resolvedTokens()` now correctly filters non-CSS metadata from `:root {}` output |
| Config | `GOOGLE_FONTS_API_KEY` env var + `config/services.google_fonts.key` |
| Tests | +10 passing (204 total, no regressions) |

---

## 12. Rollback

```bash
git revert HEAD
```

Remove `GOOGLE_FONTS_API_KEY` from `.env` if added.

---

## 13. Next Step

**STEP 4 — Plugin Admin Interface** (5 hari): `/admin/plugins` route group,
`admin.plugins.index` and `admin.plugins.show` views, activate/deactivate toggle,
plugin settings page API, sidebar auto-update.
