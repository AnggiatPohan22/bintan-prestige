# STEP 3.5 / IMP-06 — Extended Design Tokens

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 3 hari
**Priority:** MEDIUM
**Status:** COMPLETE ✅

---

## 1. Scope

Add four new token groups to the luxury theme's `customization_schema` (spacing, border-radius,
box-shadow, font-sizes), implement `ThemeService::resolvedTokens()` which merges schema defaults
with saved overrides and runs the `theme.tokens` filter hook, and switch the frontend layout
to use `resolvedTokens()` so all 21 new tokens render as CSS custom properties on every page.

---

## 2. Files Changed

### Modified

| File | Change |
|------|--------|
| `themes/bintan-prestige-luxury/theme.json` | Added `spacing`, `border_radius`, `box_shadow`, `font_sizes` groups to `customization_schema` (21 new tokens) |
| `app/Services/ThemeService.php` | Added `resolvedTokens()` method; added `use App\Facades\CmsHooks` |
| `resources/views/layouts/frontend.blade.php` | Changed `getTokenOverrides()` → `resolvedTokens()` |

### Created

| File | Purpose |
|------|---------|
| `tests/Feature/Phase4/ExtendedDesignTokensTest.php` | 10 tests (H1–H10) |

---

## 3. New Token Groups in `theme.json`

### Spacing Scale (6 tokens)

| CSS Variable | Default |
|--------------|---------|
| `--bp-space-xs` | `4px` |
| `--bp-space-sm` | `8px` |
| `--bp-space-md` | `16px` |
| `--bp-space-lg` | `24px` |
| `--bp-space-xl` | `32px` |
| `--bp-space-2xl` | `48px` |

### Border Radius (4 tokens)

| CSS Variable | Default |
|--------------|---------|
| `--bp-radius-sm` | `4px` |
| `--bp-radius-md` | `8px` |
| `--bp-radius-lg` | `16px` |
| `--bp-radius-full` | `9999px` |

### Box Shadow (4 tokens)

| CSS Variable | Default |
|--------------|---------|
| `--bp-shadow-sm` | `0 1px 3px rgba(0,0,0,.12)` |
| `--bp-shadow-md` | `0 4px 6px rgba(0,0,0,.1)` |
| `--bp-shadow-lg` | `0 10px 15px rgba(0,0,0,.1)` |
| `--bp-shadow-xl` | `0 20px 25px rgba(0,0,0,.1)` |

### Font Sizes (7 tokens)

| CSS Variable | Default |
|--------------|---------|
| `--bp-text-sm` | `0.875rem` |
| `--bp-text-base` | `1rem` |
| `--bp-text-lg` | `1.125rem` |
| `--bp-text-xl` | `1.25rem` |
| `--bp-text-2xl` | `1.5rem` |
| `--bp-text-3xl` | `1.875rem` |
| `--bp-text-4xl` | `2.25rem` |

---

## 4. ThemeService::resolvedTokens()

```php
public function resolvedTokens(): array
{
    $theme = $this->getActiveTheme();

    if ($theme === null) {
        return [];
    }

    // Collect all defaults from the theme's customization_schema.
    $defaults = [];
    foreach ($theme->customizationSchema() as $group) {
        foreach ($group['tokens'] ?? [] as $token) {
            if (! empty($token['key']) && array_key_exists('default', $token)) {
                $defaults[$token['key']] = $token['default'];
            }
        }
    }

    // Saved admin overrides win over schema defaults.
    $tokens = array_merge($defaults, $theme->customization ?? []);

    // Allow plugins to add, modify, or remove tokens.
    return CmsHooks::applyFilters('theme.tokens', $tokens, $theme);
}
```

**Layering order:**
```
schema defaults   →   merged with saved DB overrides   →   theme.tokens filter applied
```

---

## 5. getTokenOverrides() vs resolvedTokens()

| Method | Returns | Used by |
|--------|---------|---------|
| `getTokenOverrides()` | Only saved DB overrides (may be empty) | `ThemeController::buildThemeConfig()` (ZIP export — exports only user changes, not all defaults) |
| `resolvedTokens()` | Defaults + overrides + hook-filtered | `layouts/frontend.blade.php` (frontend CSS rendering) |

`getTokenOverrides()` is intentionally unchanged — ZIP export should not bundle 21 default tokens that are already in `theme.json`.

---

## 6. Frontend Rendering

`layouts/frontend.blade.php` line 35 now calls `resolvedTokens()`:

```blade
@php($themeTokens = $themeService->resolvedTokens())
@if($themeTokens)
    <style>
        :root {
            @foreach($themeTokens as $cssVar => $value)
                {{ $cssVar }}: {{ $value }};
            @endforeach
        }
    </style>
@endif
```

With the luxury theme active and no overrides set, the rendered output includes all 38 total tokens (17 existing + 21 new) as CSS custom properties.

---

## 7. Admin Customize Form

The customize form (`backend/themes/customize.blade.php`) uses the same `$schema` from
`customizationSchema()`. No Blade changes needed — the existing `@foreach($schema as ...)` loop
automatically renders the 4 new groups as `<div class="rounded-2xl border ...">` sections.

The live preview iframe updates the new `--bp-*` CSS variables in real time as the admin
types, via the existing `pushTokensToPreview()` Alpine.js function.

---

## 8. Plugin Integration via theme.tokens Filter

```php
// Any plugin's boot() method can inject new tokens:
CmsHooks::addFilter('theme.tokens', function (array $tokens, Theme $theme) {
    $tokens['--plugin-primary'] = '#FF6600';
    return $tokens;
});

// Or can read the active theme to make conditional changes:
CmsHooks::addFilter('theme.tokens', function (array $tokens, Theme $theme) {
    if ($theme->slug === 'bintan-prestige-luxury') {
        $tokens['--bp-radius-md'] = '12px';
    }
    return $tokens;
});
```

---

## 9. Test Coverage (H1–H10)

| Test | Contract |
|------|----------|
| H1 | `resolvedTokens()` returns `[]` when no active theme |
| H2 | `resolvedTokens()` includes schema defaults when no overrides saved |
| H3 | Saved override wins over schema default for the same key |
| H4 | `resolvedTokens()` applies `theme.tokens` filter and returns modified map |
| H5 | `theme.tokens` filter receives `Theme` model as second argument |
| H6 | Luxury `theme.json` contains `spacing` group with all `--bp-space-*` keys |
| H7 | Luxury `theme.json` contains `border_radius` group with all `--bp-radius-*` keys |
| H8 | Luxury `theme.json` contains `box_shadow` group with all `--bp-shadow-*` keys |
| H9 | Luxury `theme.json` contains `font_sizes` group with all `--bp-text-*` keys |
| H10 | Plugin can inject a custom token via `theme.tokens` filter |

---

## 10. Test Results

```
php artisan test tests/Feature/Phase4/ExtendedDesignTokensTest.php
Tests:  10 passed
Assertions: 37

php artisan test tests/Feature/Phase4/
Tests:  82 passed
Assertions: 197

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
```

---

## 11. Impact Summary

| Area | Impact |
|------|--------|
| DB | None — no migration; `customization` JSON column handles new tokens transparently |
| Routes | None |
| Frontend (public) | 21 new `--bp-*` CSS custom properties rendered in `:root {}` on every page |
| Admin UI | 4 new token groups appear automatically in theme customizer |
| Hook | `theme.tokens` filter is now live and used in production render path |
| ZIP Export | Unaffected — `buildThemeConfig()` still uses `getTokenOverrides()` |
| Tests | +10 passing (194 total, no regressions) |

---

## 12. Rollback

```bash
git revert HEAD
```

---

## 13. Next Step

**STEP 3.6 — IMP-07: Google Fonts Integration** (3 hari): font picker in theme
customizer, cached Google Fonts API list, `@import` rendered in `<head>`,
preview on hover/select.
