# Step 5 — SEO Manager (Core Plugin)

**Date:** 2026-06-20
**Branch:** feature/phase-4-plugin-system
**Tests:** O1–O15 (15/15 pass) | Full suite: 575 tests, 2706 assertions, 0 failures

---

## Task: STEP 5 — Core Plugin: SEO Manager

### Changed

**Migrations (new)**
- `database/migrations/2026_06_20_000007_create_redirects_table.php` — `redirects` table (id, from_url unique, to_url, status_code SMALLINT default 301, is_active bool default true, timestamps; index on from_url)
- `database/migrations/2026_06_20_000008_add_seo_robots_to_pages.php` — nullable `seo_robots` VARCHAR(100) column on `pages` table (after og_image)

**Models (new)**
- `app/Models/Redirect.php` — fillable: from_url, to_url, status_code, is_active; casts: int + bool; `scopeActive()` scope

**Observer (new)**
- `app/Observers/RedirectObserver.php` — flushes `cms.redirects` cache key on `saved` and `deleted` events

**Middleware (new)**
- `app/Http/Middleware/HandleRedirects.php` — global HTTP middleware (registered via `$middleware->append()`); normalizes path, reads `Cache::remember('cms.redirects', 600, ...)`, redirects matched active entries with configured status code; no-op on cache miss

**Frontend controllers (new)**
- `app/Http/Controllers/Frontend/SitemapController.php` — serves `GET /sitemap.xml` as `text/xml`; queries all published pages, builds `<url>` elements with canonical loc + lastmod
- `app/Http/Controllers/Frontend/RobotsController.php` — serves `GET /robots.txt` as `text/plain`; reads `storage/app/seo/robots.txt` or returns a safe default

**Admin controllers (new)**
- `app/Http/Controllers/Admin/RedirectController.php` — full CRUD for `redirects` table; auto-prefixes `from_url` with `/`; 301/302 validation
- `app/Http/Controllers/Admin/SeoRobotsController.php` — edit + PUT; stores content to `storage/app/seo/robots.txt`

**Views (new)**
- `resources/views/frontend/sitemap.blade.php` — XML sitemap template
- `resources/views/backend/seo/redirects/index.blade.php` — paginated redirect list with active badge and delete form
- `resources/views/backend/seo/redirects/create.blade.php` — create form with from_url, to_url, status_code, is_active
- `resources/views/backend/seo/redirects/edit.blade.php` — edit form (same fields)
- `resources/views/backend/seo/robots.blade.php` — textarea editor for robots.txt content

**Tests (new)**
- `tests/Feature/Phase4/SeoManagerTest.php` — O1–O15

**Files modified**
- `bootstrap/app.php` — added `HandleRedirects` import + `$middleware->append(HandleRedirects::class)` (global HTTP middleware)
- `routes/frontend.php` — added `GET /sitemap.xml` and `GET /robots.txt` routes
- `routes/admin.php` — added `Route::resource('seo/redirects', ...)` + `GET/PUT seo/robots` under prefix `admin.seo.*`
- `resources/views/backend/partials/sidebar.blade.php` — added "SEO" section with Redirects, Robots.txt, and Sitemap links
- `resources/views/backend/pages/edit.blade.php` — added `seo_robots` dropdown to SEO Settings section
- `app/Models/Page.php` — added `seo_robots` to `$fillable`
- `app/Http/Controllers/Frontend/PageController.php` — `$seoRobots` now falls back to `$page->seo_robots` when not in preview mode
- `app/Http/Requests/Admin/UpdatePageRequest.php` — added `seo_robots` validation (nullable, in allowed values)
- `app/Providers/AppServiceProvider.php` — added `Redirect::observe(RedirectObserver::class)`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php` — updated query bound from 21 → 22 (HandleRedirects adds 1 DB query on cold cache)

---

### Bugs Found and Fixed During Implementation

**Bug 1: Route-group middleware doesn't fire for unmatched URLs**

Initial approach: wrapped frontend routes in `Route::middleware(HandleRedirects::class)->group(...)`. Then switched to `$middleware->web(append: [HandleRedirects::class])` (web group).

Both failed for the same reason: route-group middleware (including the 'web' group) only runs after route matching succeeds. For an arbitrary path like `/old-page` that has no registered route, the router throws `NotFoundHttpException` before any route-group middleware fires.

Fix: `$middleware->append(HandleRedirects::class)` — adds to the global HTTP middleware stack in the Kernel pipeline. Global middleware runs before route resolution, so a redirect check fires even when the target path has no registered route.

---

### Impact

- **DB:** 2 changes — new `redirects` table; `seo_robots` nullable column on `pages`
- **Routes:**
  - `GET /sitemap.xml` → `SitemapController@index`
  - `GET /robots.txt` → `RobotsController@index`
  - `GET|POST /admin/seo/redirects` + show/edit/update/destroy → `RedirectController`
  - `GET|PUT /admin/seo/robots` → `SeoRobotsController`
- **Frontend:** redirect middleware fires on all web requests; transparent when no match
- **Middleware:** HandleRedirects in global HTTP stack; 1 DB query on cold cache, 0 on warm cache (10-min TTL)
- **Security:** no user input reaches redirect destination without admin-only validation; `seo_robots` uses strict `in:` validation

---

### Rollback

```bash
php artisan migrate:rollback --step=2

git checkout bootstrap/app.php
git checkout routes/frontend.php
git checkout routes/admin.php
git checkout resources/views/backend/partials/sidebar.blade.php
git checkout resources/views/backend/pages/edit.blade.php
git checkout app/Models/Page.php
git checkout app/Http/Controllers/Frontend/PageController.php
git checkout app/Http/Requests/Admin/UpdatePageRequest.php
git checkout app/Providers/AppServiceProvider.php
git checkout tests/Feature/Frontend/ProductDetailBookingFormTest.php

git clean -f app/Models/Redirect.php \
              app/Observers/RedirectObserver.php \
              app/Http/Middleware/HandleRedirects.php \
              app/Http/Controllers/Frontend/SitemapController.php \
              app/Http/Controllers/Frontend/RobotsController.php \
              app/Http/Controllers/Admin/RedirectController.php \
              app/Http/Controllers/Admin/SeoRobotsController.php \
              resources/views/frontend/sitemap.blade.php \
              resources/views/backend/seo/ \
              tests/Feature/Phase4/SeoManagerTest.php
```

---

### Next

**STEP 8 — Plugin Security & Sandboxing**
