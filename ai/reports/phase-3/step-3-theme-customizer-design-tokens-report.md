# STEP 3 — Theme Customizer (Design Tokens)

Date: 2026-06-19
Branch: `feature/cms-phase-3-claude`
Decision: **COMPLETE**

---

## 1. Scope

Allow admins to override per-theme CSS custom properties (design tokens) from the admin
dashboard. Token values are stored as JSON in the `themes` table and injected into the
frontend `<head>` as a `<style>` block of `:root` overrides that sit after the base
`site-brand-colors` partial and therefore take precedence.

The available tokens for each theme are declared in `theme.json` under `customization_schema`.
Only schema-declared keys are accepted when saving — unknown keys and CSS-injection
characters are silently stripped.

---

## 2. Schema change

### New column: `themes.customization`

| Column | Type | Notes |
|--------|------|-------|
| `customization` | JSON NULL | Stores CSS variable overrides as `{ "--css-var": "value" }` map |

Migration: `2026_06_22_000002_add_customization_to_themes_table.php`
Rollback: `php artisan migrate:rollback --step=1`

---

## 3. theme.json — customization_schema format

```json
{
    "customization_schema": {
        "colors": {
            "label": "Colors",
            "tokens": [
                { "key": "--frontend-gold",  "label": "Brand Gold",  "type": "color", "default": "#B8924A" },
                { "key": "--frontend-black", "label": "Primary Dark", "type": "color", "default": "#1a1a1a" }
            ]
        },
        "typography": {
            "label": "Typography",
            "tokens": [
                { "key": "--frontend-font-heading", "label": "Heading Font", "type": "text", "default": "Forum, Georgia, serif" }
            ]
        }
    }
}
```

**Token types:**
- `color` — renders a color picker (`<input type="color">`) synced to a hex text input via Alpine.js
- `text` — renders a plain text input (for font stacks, spacing values, etc.)

The `bintan-prestige-luxury` theme exposes 15 color tokens and 2 typography tokens (17 total).

---

## 4. Files changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_22_000002_add_customization_to_themes_table.php` | Adds `customization` JSON nullable column |
| `app/Http/Requests/Admin/UpdateThemeCustomizationRequest.php` | Validates `tokens` as nullable array of strings (max 200 chars each) |
| `resources/views/backend/themes/customize.blade.php` | Customize form: token groups with color pickers + text inputs; Save + Reset actions |
| `tests/Feature/Phase3/ThemeCustomizerTest.php` | 14 tests (access, storage, injection, reset, security) |

### Modified

| File | Change |
|------|--------|
| `app/Models/Theme.php` | Added `customization` to `$fillable` + `array` cast; added `customizationSchema()` method |
| `app/Services/ThemeService.php` | Added `getTokenOverrides(): array` |
| `app/Http/Controllers/Admin/ThemeController.php` | Injected `ThemeService`; added `customize()`, `updateCustomization()`, `resetCustomization()` |
| `routes/admin.php` | Added GET `themes/{theme}/customize`, PUT `themes/{theme}/customization`, DELETE `themes/{theme}/customization` |
| `resources/views/layouts/frontend.blade.php` | Assigned `$themeService` in `@php` block; added `<style>` block injecting token overrides after `site-brand-colors` |
| `resources/views/backend/themes/index.blade.php` | Customize button enabled — now an `<a>` link to the customize page |
| `themes/bintan-prestige-luxury/theme.json` | Added `customization_schema` with 15 color tokens + 2 typography tokens |

---

## 5. Security — CSS injection prevention

Token values submitted by the admin are filtered before storage:

```php
$value = preg_replace('/[<>"\'{}\\\\\n\r]/', '', trim($submitted[$cssVar]));
```

This strips `{`, `}`, `"`, `'`, `<`, `>`, `\`, and newlines — the characters needed to
break out of a CSS `:root { }` block or inject other markup. Combined with:
- Schema key whitelist (only declared `--css-var` names are stored)
- Max length 200 via FormRequest validation
- Blade `{{ }}` encoding on output (extra safety layer in the template)

---

## 6. ThemeService additions

```php
getTokenOverrides(): array
  → returns active theme's $theme->customization ?? []
  → returns [] when no theme is active
  → called from resources/views/layouts/frontend.blade.php on every frontend request
```

---

## 7. Theme model additions

```php
customizationSchema(): array
  → reads theme.json from disk
  → returns $data['customization_schema'] ?? []
  → returns [] gracefully on missing file or invalid JSON
  → called from ThemeController@customize and @updateCustomization
```

---

## 8. Admin UI

**Customize page URL:** `GET /admin/themes/{id}/customize`

| State | Behavior |
|-------|----------|
| No schema in theme.json | Empty state: "No customizable tokens defined" |
| Schema present | Token groups rendered as labeled sections |
| Token type = color | Color picker + hex text input, synced via Alpine.js x-model |
| Token type = text | Plain text input with monospace styling |
| Existing override | Input pre-filled with stored value |
| No override | Input pre-filled with schema default value |
| Save button | PUT to `themes.customization.update` |
| Reset to Defaults | DELETE to `themes.customization.destroy` (JS confirm) |
| Back link | Returns to themes index |
| Customize button (index) | Now an active `<a>` link (no longer disabled) |

---

## 9. Frontend token injection

In `resources/views/layouts/frontend.blade.php`:
- After `@include('partials.site-brand-colors')` (base global brand colors)
- Before `</head>`

```blade
@php($themeTokens = $themeService->getTokenOverrides())
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

**Cascade order in the browser:**
1. Tailwind CSS base
2. `resources/css/frontend.css` (compiled CSS variables default values)
3. `partials.site-brand-colors` (CMS global brand color settings)
4. Theme token overrides (this `<style>` block — highest specificity, wins)

**When no theme is active:** no extra `<style>` block is rendered. Frontend behavior is
identical to Phase 2.

---

## 10. Routes added

| Name | Method | URI | Controller | Purpose |
|------|--------|-----|------------|---------|
| `admin.themes.customize` | GET | `/admin/themes/{theme}/customize` | `ThemeController@customize` | Show customize form |
| `admin.themes.customization.update` | PUT | `/admin/themes/{theme}/customization` | `ThemeController@updateCustomization` | Save tokens |
| `admin.themes.customization.destroy` | DELETE | `/admin/themes/{theme}/customization` | `ThemeController@resetCustomization` | Clear all overrides |

All behind existing `auth` + `admin` middleware.

---

## 11. Test results

### STEP 3 tests only

```
php artisan test tests/Feature/Phase3/ThemeCustomizerTest.php
Tests:  14 passed
Assertions: 34
```

### Phase 3 suite (STEP 0 + STEP 1 + STEP 2 + STEP 3)

```
php artisan test tests/Feature/Phase3/
Tests:  63 passed
Assertions: 176
```

### Full suite after STEP 3

```
php artisan test
Tests:  359 passed  (345 STEP 2 baseline + 14 new)
Assertions: 2,229
```

---

## 12. Impact summary

| Area | Impact |
|------|--------|
| DB | New `customization` JSON column on `themes`; no other tables changed |
| Routes | 3 new admin routes; all existing routes untouched |
| Frontend (public) | Token override `<style>` block injected in `<head>` when active theme has customization; no change otherwise |
| Admin Theme Library | Customize button is now a link to the customize page |
| Security | Schema whitelist + regex sanitization + FormRequest max-length; all routes behind auth + admin |
| Cache | No new cache keys; existing `theme.active.v1` (cleared on Theme saved/deleted) covers token overrides too |
| Tests | +14 passing tests; full suite 359/359 |

---

## 13. Rollback

```bash
php artisan migrate:rollback --step=1
git checkout app/Models/Theme.php
git checkout app/Services/ThemeService.php
git checkout app/Http/Controllers/Admin/ThemeController.php
git checkout resources/views/layouts/frontend.blade.php
git checkout resources/views/backend/themes/index.blade.php
git checkout routes/admin.php
git checkout themes/bintan-prestige-luxury/theme.json
git rm app/Http/Requests/Admin/UpdateThemeCustomizationRequest.php
git rm resources/views/backend/themes/customize.blade.php
git rm tests/Feature/Phase3/ThemeCustomizerTest.php
```

---

## 14. Next step

**STEP 4 — Widget Areas & Widget Management**

Declare named widget slots in `theme.json` (sidebar, footer-columns, before-footer, etc.).
Build an admin Widget Manager to assign and configure widget content (text, HTML, image,
products list, navigation links) per slot. Render widget areas in theme partials.
