# STEP 2 — Theme Switcher

Date: 2026-06-19
Branch: `feature/cms-phase-3-claude`
Decision: **COMPLETE**

---

## 1. Scope

Enable admins to activate a theme from the Theme Library. When a theme is active, the
frontend resolves its layout and partial files instead of the hardcoded defaults.
Full fallback to Phase 2 files is preserved when no theme is active or when theme
files are missing from disk.

No DB schema changes. No existing routes renamed. No Phase 2 features removed.

---

## 2. Architecture decision — theme resolution

### Problem
Theme view files live in `themes/{slug}/layouts/` and `themes/{slug}/partials/` — outside
`resources/views/`. Standard Blade view names cannot reference them without registration.

### Solution
`ThemeService` dynamically calls `View::addNamespace('theme-active', $theme->basePath())`
when a matching file is found on disk. This registers the theme's root as a Blade namespace
for the current request only. The resolved view name is returned as `theme-active::partials.header`,
which Blade resolves normally.

When no file exists on disk, the service returns the default Phase 2 view name unchanged.

### Single-active-theme constraint
Enforced in `ThemeController@activate` with a two-step Eloquent update:
1. `Theme::query()->where('id', '!=', $theme->id)->update(['is_active' => false])` — bulk, no events
2. `$theme->update(['is_active' => true])` — fires `saved` event → cache cleared

---

## 3. Files changed

### Created

| File | Purpose |
|------|---------|
| `app/Services/ThemeService.php` | `getActiveTheme()`, `resolveLayout()`, `resolvePartial()`, `forget()` with 30-min cache |
| `tests/Feature/Phase3/ThemeSwitcherTest.php` | 13 tests covering activation, route security, resolve fallback, resolve file-exists, cache invalidation |

### Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/ThemeController.php` | Added `activate()` method: deactivates all others, activates target, redirects with success flash |
| `app/Providers/AppServiceProvider.php` | Registered `ThemeService` as singleton; added `Theme::saved` + `Theme::deleted` hooks → `ThemeService::forget()` |
| `routes/admin.php` | Added `PATCH admin/themes/{theme}/activate` → `admin.themes.activate` |
| `resources/views/layouts/frontend.blade.php` | `@include('frontend.partials.header/footer')` replaced with `@include(app(ThemeService)->resolvePartial(...))` |
| `resources/views/backend/themes/index.blade.php` | Activate button is live (PATCH form + JS confirmation); Active theme shows green "Active Theme" label; success flash added; "coming soon" footer note updated |
| `tests/Feature/Phase3/ThemeBaselineCharacterizationTest.php` | Updated one STEP 0 raw-file-content assertion to match the new dynamic include form |

---

## 4. ThemeService contract

```php
// All three methods are side-effect free (no writes).
// Called from layouts/frontend.blade.php on every frontend request.

getActiveTheme(): ?Theme
  → reads cache key 'theme.active.v1' (TTL 30 min)
  → on miss: Theme::active()->value('slug') + Theme::where('slug', ...)->first()
  → returns null when no theme is active

resolveLayout(string $key = 'default'): string
  → checks: themes/{slug}/layouts/{key}.blade.php on disk
  → hit  → registers namespace, returns 'theme-active::layouts.{key}'
  → miss → returns 'frontend.templates.{key}'

resolvePartial(string $key): string
  → checks: themes/{slug}/partials/{key}.blade.php on disk
  → hit  → registers namespace, returns 'theme-active::partials.{key}'
  → miss → returns 'frontend.partials.{key}'

forget(): void
  → Cache::forget('theme.active.v1')
  → called by AppServiceProvider hooks on Theme::saved + Theme::deleted
```

---

## 5. Cache

| Key | `theme.active.v1` |
|-----|-------------------|
| Value stored | Active theme slug (string) or null |
| TTL | 30 minutes |
| Populated by | `ThemeService::getActiveTheme()` via `Cache::remember()` |
| Invalidated by | `Theme::saved` and `Theme::deleted` model events → `ThemeService::forget()` |
| Driver | App cache driver (array in tests, configured driver in production) |

---

## 6. Routes added

| Name | Method | URI | Controller |
|------|--------|-----|------------|
| `admin.themes.activate` | PATCH | `/admin/themes/{theme}/activate` | `ThemeController@activate` |

Inside the existing `auth` + `admin` middleware group. No public routes.

---

## 7. Admin UI changes

**URL:** `GET /admin/themes`

| State | Behavior |
|-------|----------|
| Active theme card | Green "Active Theme" label replaces Activate button |
| Inactive theme card | "Activate" button — submits PATCH form with JS confirm dialog |
| JS confirm message | `"Activate '{name}'? This will change the live site appearance."` |
| Success flash | Green banner: `"{name}" is now the active theme.` |
| Customize button | Still disabled — STEP 3 |
| Footer note | Updated from "switcher coming" to "customizer coming" |

---

## 8. Backward compatibility

| Scenario | Frontend behavior |
|----------|-------------------|
| No theme is active | Identical to Phase 2 — `frontend.partials.header/footer` rendered |
| Theme active, no layouts/ dir | Falls back to `frontend.templates.default` |
| Theme active, no partials/ dir | Falls back to `frontend.partials.header/footer` |
| Theme active, partials exist on disk | Theme's files are rendered via `theme-active::` namespace |

The existing `frontend/templates/` and `frontend/partials/` directories are never removed.
All existing route names, model names, and public URLs are unchanged.

---

## 9. Test results

### STEP 2 tests only

```
php artisan test tests/Feature/Phase3/ThemeSwitcherTest.php
Tests:  13 passed
Assertions: 22
```

### Phase 3 suite (STEP 0 + STEP 1 + STEP 2)

```
php artisan test tests/Feature/Phase3/
Tests:  49 passed
Assertions: 142
```

### Full suite after STEP 2

```
php artisan test
Tests:  345 passed  (332 Phase 1+2 baseline + 13 new)
Assertions: 2,195
```

---

## 10. Impact summary

| Area | Impact |
|------|--------|
| DB | None — no new migrations |
| Routes | 1 new admin route (`admin.themes.activate`); all existing routes untouched |
| Frontend (public) | `@include` for header/footer now dynamic; behavior identical when no theme active |
| Admin Theme Library | Activate button functional; Active theme card shows green label |
| Security | Activate route behind existing `auth` + `admin` middleware |
| Cache | `theme.active.v1` key (30 min); cleared on `Theme::saved` + `Theme::deleted` |
| Tests | +13 passing tests; full suite 345/345 |

---

## 11. Rollback

```bash
git checkout resources/views/layouts/frontend.blade.php
git checkout resources/views/backend/themes/index.blade.php
git checkout tests/Feature/Phase3/ThemeBaselineCharacterizationTest.php
git checkout app/Http/Controllers/Admin/ThemeController.php
git checkout app/Providers/AppServiceProvider.php
git checkout routes/admin.php
git rm app/Services/ThemeService.php
git rm tests/Feature/Phase3/ThemeSwitcherTest.php
```

---

## 12. Next step

**STEP 3 — Theme Customizer (Design Tokens)**

Admin UI to edit per-theme CSS custom properties (brand colors, typography, spacing).
Tokens stored as JSON in the `themes` table. Injected into the frontend `<head>` as a
`<style>` block of `:root` overrides, sitting after the base `site-brand-colors` partial
so they take precedence. Editable from the Theme Library's Customize button.
