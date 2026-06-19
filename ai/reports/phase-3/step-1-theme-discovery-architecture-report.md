# STEP 1 — Theme Directory Architecture & Discovery

Date: 2026-06-19
Branch: `feature/cms-phase-3-claude`
Decision: **COMPLETE**

---

## 1. Scope

Define the `themes/` directory convention, create the `themes` database table, implement
`ThemeDiscoveryService`, build the admin Theme Library page with a Scan action, and ship
the first theme manifest (`bintan-prestige-luxury`).

No frontend rendering was changed. No existing file was removed.

---

## 2. Schema change

### New table: `themes`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | auto-increment |
| `name` | VARCHAR(150) | human-readable theme name |
| `slug` | VARCHAR(150) UNIQUE | machine identifier; matches directory name |
| `directory` | VARCHAR(255) | relative path from `base_path()` (e.g. `themes/bintan-prestige-luxury`) |
| `version` | VARCHAR(30) NULL | from theme.json |
| `author` | VARCHAR(150) NULL | from theme.json |
| `description` | TEXT NULL | from theme.json |
| `screenshot` | VARCHAR(255) NULL | filename relative to theme directory |
| `is_active` | BOOLEAN DEFAULT 0 | only one theme active at a time (enforced in STEP 2) |
| `sort_order` | UNSIGNED INT DEFAULT 0 | display order in Theme Library |
| `created_at` / `updated_at` | TIMESTAMP | standard Laravel timestamps |

Migration: `2026_06_22_000001_create_themes_table.php`
Rollback: `php artisan migrate:rollback --step=1`

---

## 3. Files changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_22_000001_create_themes_table.php` | Creates the `themes` table |
| `app/Models/Theme.php` | Eloquent model; `scopeActive`, `scopeOrdered`, `basePath()`, `hasScreenshot()` |
| `app/Services/ThemeDiscoveryService.php` | Scans `themes/*/theme.json`; upserts into DB; safe on missing dir, bad JSON, missing required fields |
| `app/Http/Controllers/Admin/ThemeController.php` | `index()` lists DB themes; `scan()` triggers discovery and redirects |
| `resources/views/backend/themes/index.blade.php` | Theme Library: card grid, empty state, Scan button; Activate/Customize buttons greyed out pending STEP 2/3 |
| `themes/bintan-prestige-luxury/theme.json` | First theme manifest (name, slug, version, author, description, screenshot, sort_order) |
| `tests/Feature/Phase3/ThemeDiscoveryTest.php` | 20 tests covering service, model, controller, auth |

### Modified

| File | Change |
|------|--------|
| `routes/admin.php` | Added `GET admin/themes` (`themes.index`) and `POST admin/themes/scan` (`themes.scan`); both behind `auth` + `admin` middleware |
| `resources/views/backend/partials/sidebar.blade.php` | Added **Appearance** section with Themes link (`fa-palette` icon); active-state tied to `admin.themes.*` |
| `resources/views/backend/partials/navbar.blade.php` | Added `admin.themes.*` → `'Themes'` to the `$pageTitle` match expression |

---

## 4. theme.json manifest format

```json
{
    "name":        "string (required)",
    "slug":        "string (required, must match directory name)",
    "version":     "string (optional)",
    "author":      "string (optional)",
    "description": "string (optional)",
    "screenshot":  "filename.png (optional, relative to theme directory)",
    "sort_order":  0
}
```

Phase 3 later steps will add optional schema sections:
- `"customization_schema"` — token groups (STEP 3)
- `"widget_areas"` — declared slots (STEP 4)

---

## 5. Theme directory structure (Phase 3 full target)

```
themes/
  bintan-prestige-luxury/
    theme.json          ← manifest (required)
    screenshot.png      ← preview image (optional, place manually)
    layouts/            ← theme-specific layout overrides (STEP 5)
    partials/           ← header.blade.php, footer.blade.php overrides (STEP 2/5)
    blocks/             ← block partial overrides (STEP 5)
    widgets/            ← widget type partials (STEP 4)
```

Only `theme.json` is required. All other directories are resolved with a fallback chain.

---

## 6. ThemeDiscoveryService contract

- `scan(?string $root = null): string[]`
- Reads `{root}/*/theme.json` via `glob()`
- Calls `Theme::updateOrCreate(['slug' => ...], [...])` — idempotent, safe to run multiple times
- Does **not** activate any theme
- Skips on: missing directory, unreadable file, invalid JSON, missing `name` or `slug`
- Logs a `Log::warning()` for DB errors and invalid JSON
- Returns slugs of successfully registered themes

---

## 7. Admin UI

**URL:** `GET /admin/themes`

| State | Behavior |
|-------|----------|
| No themes in DB | Empty state card with scan instructions |
| Themes exist | Card grid — name, version, author, description, Active/Inactive badge |
| Theme is active | Card has indigo border ring + green Active badge |
| Scan triggered | POST `/admin/themes/scan` → `success` or `info` flash → redirect back |
| Activate button | Present but disabled (STEP 2) |
| Customize button | Present but disabled (STEP 3) |

---

## 8. Routes added

| Name | Method | URI | Controller |
|------|--------|-----|------------|
| `admin.themes.index` | GET | `/admin/themes` | `ThemeController@index` |
| `admin.themes.scan` | POST | `/admin/themes/scan` | `ThemeController@scan` |

Both are inside the `auth` + `admin` middleware group. No public routes.

---

## 9. Test results

### STEP 1 tests only

```
php artisan test tests/Feature/Phase3/ThemeDiscoveryTest.php
Tests:  20 passed
Assertions: 40
```

### Full suite after STEP 1

```
php artisan test
Tests:  332 passed  (296 Phase 2 + 16 STEP 0 + 20 STEP 1)
Assertions: 2,172
```

---

## 10. Impact summary

| Area | Impact |
|------|--------|
| DB | New `themes` table; no existing tables altered |
| Routes | 2 new admin routes; all existing routes untouched |
| Frontend (public) | None — no public rendering changed |
| Admin sidebar | New Appearance section with Themes link |
| Admin navbar | `$pageTitle` match extended for `admin.themes.*` |
| Security | Both routes behind existing `auth` + `admin` middleware |
| Cache | None — STEP 1 does not introduce theme caching |
| Tests | +20 passing tests; full suite 332/332 |

---

## 11. Rollback

```bash
php artisan migrate:rollback --step=1
git checkout routes/admin.php
git checkout resources/views/backend/partials/sidebar.blade.php
git checkout resources/views/backend/partials/navbar.blade.php
git rm app/Models/Theme.php app/Services/ThemeDiscoveryService.php
git rm app/Http/Controllers/Admin/ThemeController.php
git rm -r resources/views/backend/themes/
git rm -r themes/
git rm tests/Feature/Phase3/ThemeDiscoveryTest.php
```

---

## 12. Next step

**STEP 2 — Theme Switcher**

Add `ThemeController@activate`, enforce single-active-theme constraint, build `ThemeService`
with `getActiveTheme()` and `resolveLayout()`, register the `ViewServiceProvider` share,
and update the frontend layout to use the active theme's layout when one is set — with
full fallback to the current `resources/views/layouts/frontend.blade.php` chain.
