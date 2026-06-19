# STEP 4 — Widget Areas & Widget Management

Date: 2026-06-19
Branch: `feature/cms-phase-3-claude`
Decision: **COMPLETE**

---

## 1. Scope

Introduce a Widget Manager that lets admins place content widgets (Text, HTML, Image,
Navigation) into named areas declared by the active theme. Widget data is stored in a
new `widgets` table. The frontend can render any widget area by calling
`@include('frontend.partials.widget-area', ['area' => 'footer-col-1'])`.

Widget types deferred to STEP 5 (require pre-loaded data): products grid, menu reference.

---

## 2. Schema change

### New table: `widgets`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | auto-increment |
| `theme_id` | BIGINT UNSIGNED FK | CASCADE on theme delete |
| `area` | VARCHAR(100) | area key declared in `theme.json` `widget_areas` |
| `widget_type` | VARCHAR(50) | one of `text`, `html`, `image`, `navigation` |
| `title` | VARCHAR(255) NULL | admin label (display only) |
| `data` | JSON NULL | type-specific content fields |
| `sort_order` | UNSIGNED INT DEFAULT 0 | render order within area |
| `is_visible` | BOOLEAN DEFAULT TRUE | toggle without deleting |
| `created_at` / `updated_at` | TIMESTAMP | standard Laravel |

Index: `(theme_id, area)`

Migration: `2026_06_22_000003_create_widgets_table.php`
Rollback: `php artisan migrate:rollback --step=1`

---

## 3. Widget types and data schema

| Type | `data` fields |
|------|---------------|
| `text` | `heading` (optional), `content` (HTML string) |
| `html` | `code` (raw HTML string) |
| `image` | `src`, `alt`, `link_url` (optional), `caption` (optional) |
| `navigation` | `heading` (optional), `links[]` `{label, url}` |

All values are sanitized via `strip_tags()` + `trim()` before storage, except
`content` (text) and `code` (html) which are stored verbatim (admin-trusted HTML).

---

## 4. Widget Areas declaration in `theme.json`

```json
{
    "widget_areas": {
        "footer-col-1":  { "label": "Footer Column 1",  "description": "..." },
        "footer-col-2":  { "label": "Footer Column 2",  "description": "..." },
        "footer-col-3":  { "label": "Footer Column 3",  "description": "..." },
        "before-footer": { "label": "Before Footer",    "description": "..." }
    }
}
```

`Theme::widgetAreas()` reads this from disk on each call. Returns `[]` gracefully
when manifest is missing or has no `widget_areas` section.

The `bintan-prestige-luxury` theme now declares 4 widget areas.

---

## 5. Files changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_22_000003_create_widgets_table.php` | Creates the `widgets` table |
| `app/Models/Widget.php` | Eloquent model; constants `TYPES`; scopes `visible`, `forArea`, `ordered`; `typeLabel()` helper |
| `app/Http/Controllers/Admin/WidgetController.php` | Full CRUD + `toggleVisible`; `ensureWidgetBelongsToTheme` guard; type-specific `extractData()` sanitizer |
| `app/Http/Requests/Admin/StoreWidgetRequest.php` | Validates area (against theme schema), widget_type, title, sort_order, type-specific data sub-fields |
| `app/Http/Requests/Admin/UpdateWidgetRequest.php` | Same field set with `sometimes` guards |
| `resources/views/backend/themes/widgets/index.blade.php` | Widget Manager: areas as sections, widget rows with visibility toggle / edit / delete |
| `resources/views/backend/themes/widgets/create.blade.php` | Add widget form with Alpine.js widget-type switcher + navigation link repeater |
| `resources/views/backend/themes/widgets/edit.blade.php` | Edit widget form (type-locked); navigation link repeater pre-filled with existing links |
| `resources/views/frontend/widgets/text.blade.php` | Renders text widget (heading + HTML content) |
| `resources/views/frontend/widgets/html.blade.php` | Renders raw HTML widget |
| `resources/views/frontend/widgets/image.blade.php` | Renders image with optional link + caption |
| `resources/views/frontend/widgets/navigation.blade.php` | Renders nav list with optional heading |
| `resources/views/frontend/partials/widget-area.blade.php` | Resolves widgets via `ThemeService::widgetsForArea($area)` and dispatches to type partials |
| `tests/Feature/Phase3/WidgetManagerTest.php` | 19 tests covering CRUD, auth, widgetsForArea, rendering, cache invalidation, N+1 |

### Modified

| File | Change |
|------|--------|
| `app/Models/Theme.php` | Added `widgets(): HasMany` relationship; added `widgetAreas()` method |
| `app/Services/ThemeService.php` | Added `widgetsForArea(string $area): Collection` with per-request in-memory cache (`$widgetsByArea`); updated `forget()` to reset `$widgetsByArea = null` |
| `app/Providers/AppServiceProvider.php` | Added `Widget::saved` and `Widget::deleted` hooks → `ThemeService::forget()` |
| `routes/admin.php` | Added 7 widget routes all under `admin.themes.widgets.*` namespace |
| `resources/views/backend/themes/index.blade.php` | Added Widgets button (puzzle-piece icon) per theme card |
| `themes/bintan-prestige-luxury/theme.json` | Added `widget_areas` section with 4 areas |
| `tests/Feature/Phase3/ThemeSwitcherTest.php` | Changed `setUp` from `Cache::forget()` to `app(ThemeService::class)->forget()` to also reset in-memory widget state |
| `tests/Feature/Phase3/ThemeCustomizerTest.php` | Same setUp change |

---

## 6. ThemeService::widgetsForArea — performance design

All visible widgets for the active theme are loaded in **one DB query** on the first call
to `widgetsForArea()`. The result is split into an in-memory map `$widgetsByArea[area][]`.
Subsequent calls for other areas in the same request hit memory only.

```
Request timeline:
  @include widget-area footer-col-1  → DB query (loads ALL areas)
  @include widget-area footer-col-2  → memory only
  @include widget-area before-footer → memory only
  Total DB queries: 1
```

The in-memory cache is reset by `forget()`, which fires on every `Widget::saved`,
`Widget::deleted`, `Theme::saved`, and `Theme::deleted`.

---

## 7. Admin UI

**Widget Manager URL:** `GET /admin/themes/{id}/widgets`

| Action | Route | Behavior |
|--------|-------|----------|
| Index | GET `themes/{theme}/widgets` | Lists all declared areas; widgets grouped by area |
| Create | GET `themes/{theme}/widgets/create?area=X` | Form with type switcher + dynamic fields |
| Store | POST `themes/{theme}/widgets` | Saves, redirects to index |
| Edit | GET `themes/{theme}/widgets/{widget}/edit` | Form pre-filled with existing data |
| Update | PUT `themes/{theme}/widgets/{widget}` | Saves, redirects to index |
| Delete | DELETE `themes/{theme}/widgets/{widget}` | Soft deletes, redirects to index |
| Toggle | POST `themes/{theme}/widgets/{widget}/toggle-visible` | Flips `is_visible`, redirects |

**Theme Library card (index.blade.php):** Now shows three action buttons per card:
Activate → Widgets (puzzle-piece) → Customize (sliders).

**Empty state:** When theme has no `widget_areas` in `theme.json`, shows "No widget areas
defined" guidance.

**Security:** All routes inside the existing `auth` + `admin` middleware group.
`ensureWidgetBelongsToTheme` guard returns 404 if widget belongs to a different theme.

---

## 8. Frontend usage

To render a widget area in any Blade template (theme partials, layouts, etc.):

```blade
@include('frontend.partials.widget-area', ['area' => 'footer-col-1'])
```

The partial calls `ThemeService::widgetsForArea('footer-col-1')` and renders each
visible widget via its type partial (`frontend.widgets.{type}`). Unknown types are
silently skipped (`view()->exists()` guard).

Widget areas are **inert when no theme is active** — the partial renders empty.

---

## 9. Routes added

| Name | Method | URI |
|------|--------|-----|
| `admin.themes.widgets.index` | GET | `/admin/themes/{theme}/widgets` |
| `admin.themes.widgets.create` | GET | `/admin/themes/{theme}/widgets/create` |
| `admin.themes.widgets.store` | POST | `/admin/themes/{theme}/widgets` |
| `admin.themes.widgets.edit` | GET | `/admin/themes/{theme}/widgets/{widget}/edit` |
| `admin.themes.widgets.update` | PUT | `/admin/themes/{theme}/widgets/{widget}` |
| `admin.themes.widgets.destroy` | DELETE | `/admin/themes/{theme}/widgets/{widget}` |
| `admin.themes.widgets.toggle-visible` | POST | `/admin/themes/{theme}/widgets/{widget}/toggle-visible` |

All 7 match `admin.themes.*` so the navbar already shows "Themes" as the page title — no navbar change needed.

---

## 10. Test results

### STEP 4 tests only

```
php artisan test tests/Feature/Phase3/WidgetManagerTest.php
Tests:  19 passed
Assertions: 47
```

### Phase 3 suite (STEP 0–4)

```
php artisan test tests/Feature/Phase3/
Tests:  82 passed
Assertions: 223
```

### Full suite after STEP 4

```
php artisan test
Tests:  378 passed  (359 STEP 3 baseline + 19 new)
Assertions: 2,276
```

---

## 11. Impact summary

| Area | Impact |
|------|--------|
| DB | New `widgets` table; no existing tables altered |
| Routes | 7 new admin routes (all under `admin.themes.*`) |
| Frontend (public) | `widget-area` partial renders widget content when active theme has widgets; inert otherwise |
| Admin Theme Library | Widgets button added to each theme card |
| Security | Route group middleware; `ensureWidgetBelongsToTheme` 404 guard; `strip_tags` sanitization on safe fields |
| Cache | `$widgetsByArea` in-memory singleton; cleared via `Widget::saved/deleted` → `ThemeService::forget()` |
| Tests | +19 passing tests; full suite 378/378 |

---

## 12. Rollback

```bash
php artisan migrate:rollback --step=1
git checkout app/Models/Theme.php
git checkout app/Services/ThemeService.php
git checkout app/Providers/AppServiceProvider.php
git checkout routes/admin.php
git checkout resources/views/backend/themes/index.blade.php
git checkout themes/bintan-prestige-luxury/theme.json
git checkout tests/Feature/Phase3/ThemeSwitcherTest.php
git checkout tests/Feature/Phase3/ThemeCustomizerTest.php
git rm app/Models/Widget.php
git rm app/Http/Controllers/Admin/WidgetController.php
git rm app/Http/Requests/Admin/StoreWidgetRequest.php
git rm app/Http/Requests/Admin/UpdateWidgetRequest.php
git rm -r resources/views/backend/themes/widgets/
git rm -r resources/views/frontend/widgets/
git rm resources/views/frontend/partials/widget-area.blade.php
git rm tests/Feature/Phase3/WidgetManagerTest.php
```

---

## 13. Next step

**STEP 5 — Theme Template Hierarchy**

Override the default Phase 2 page templates with theme-specific Blade files.
When the active theme has `layouts/default.blade.php`, `partials/header.blade.php`,
or `partials/footer.blade.php` on disk, those files take over from the Phase 2
defaults. The `ThemeService::resolveLayout()` and `resolvePartial()` methods (STEP 2)
already implement the resolution chain — STEP 5 creates the actual theme-specific
Blade files for `bintan-prestige-luxury` that use widget areas and token variables.
