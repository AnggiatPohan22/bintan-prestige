# Step 7 — Analytics Dashboard (Core Plugin)

**Date:** 2026-06-20
**Branch:** feature/phase-4-plugin-system
**Tests:** N1–N12 (12/12 pass) | Full suite: 560 tests, 2670 assertions, 0 failures

---

## Task: STEP 7 — Analytics Dashboard

### Changed

**Migrations (new)**
- `database/migrations/2026_06_20_000005_create_page_views_table.php` — `page_views` table (id, page_id FK cascade, visitor_hash CHAR64, referrer nullable, country_code nullable, viewed_date DATE, timestamps; indexes on page_id+viewed_date and viewed_date)
- `database/migrations/2026_06_20_000006_create_page_view_daily_stats_table.php` — `page_view_daily_stats` table (id, page_id FK cascade, stat_date DATE, view_count uint, unique_visitors uint; unique(page_id, stat_date), index on stat_date)

**Models (new)**
- `app/Models/PageView.php` — fillable: page_id, visitor_hash, referrer, country_code, viewed_date; no date cast (stored as plain 'Y-m-d' string for SQLite test compatibility)
- `app/Models/PageViewDailyStat.php` — fillable: page_id, stat_date, view_count, unique_visitors; no date cast (same reason)

**Middleware (new)**
- `app/Http/Middleware/TrackPageView.php` — runs after response; skips auth'd users; checks `$response->isSuccessful()`; SHA256(ip + user_agent) as visitor_hash; wrapped in try-catch so tracking never breaks page response

**Artisan Command (new)**
- `app/Console/Commands/AggregatePageViewStats.php` — `analytics:aggregate-daily [--date=]`; public `aggregate(string $date): int` method (callable directly in tests); upserts `page_view_daily_stats` via `updateOrCreate`

**Controller (new)**
- `app/Http/Controllers/Admin/AnalyticsDashboardController.php` — `index()`: 30-day chart data, top 10 pages by total views, summary totals; `exportCsv()`: streams CSV download

**Views (new)**
- `resources/views/backend/analytics/dashboard.blade.php` — Chart.js (CDN v4.4.0) line chart for daily views + unique visitors; top 10 pages table with link to admin edit; export CSV button; uses `@push('scripts')`

**Tests (new)**
- `tests/Feature/Phase4/AnalyticsDashboardTest.php` — N1–N12

**Files modified**
- `bootstrap/app.php` — added `AggregatePageViewStats::class` to `withCommands()`; added `analytics:aggregate-daily` to `withSchedule()` at `dailyAt('00:05')`
- `routes/admin.php` — added `GET /admin/analytics` (index) + `GET /admin/analytics/export` (exportCsv) under prefix `analytics.`
- `routes/frontend.php` — added `->middleware(TrackPageView::class)` to the `pages.show` route
- `resources/views/backend/partials/sidebar.blade.php` — added "Analytics" link (fa-chart-line) in System section, above Plugins

---

### Bugs Found and Fixed During Implementation

**Bug 1: SQLite date storage mismatch**

Eloquent's `date` cast uses `fromDateTime()` which serializes via model's `$dateFormat = 'Y-m-d H:i:s'`. SQLite stored `'2026-01-15 00:00:00'` instead of `'2026-01-15'`. Consequently `WHERE viewed_date = '2026-01-15'` returned 0 rows in SQLite.

Fix: Removed `date` cast from `PageView.viewed_date` and `PageViewDailyStat.stat_date`. Values are always stored as plain `'Y-m-d'` strings (via `now()->toDateString()` in middleware, and the `--date` option in the command). Also fixed `AnalyticsDashboardController` to use `->keyBy('stat_date')` and `$row->stat_date` (plain string) instead of `->toDateString()` (Carbon method that would fail on a string).

**Bug 2: artisan-in-test uses separate PDO connection**

`$this->artisan()` and `Artisan::call()` in tests using `RefreshDatabase` (SQLite in-memory) create a fresh PDO connection outside the test's transaction. Uncommitted test data is invisible to artisan commands.

Fix: Extracted aggregate logic to `public aggregate(string $date): int` on the command class. N7 and N8 tests call `$this->app->make(AggregatePageViewStats::class)->aggregate($date)` directly, which runs within the test's database context. The artisan interface (`handle()`) still works normally in production.

---

### Impact

- **DB:** 2 new tables — `page_views`, `page_view_daily_stats`
- **Routes:** `GET /admin/analytics`, `GET /admin/analytics/export`; `TrackPageView` middleware on `pages.show`
- **Frontend:** no visual change — middleware is transparent; tracking is server-side only
- **Scheduler:** `analytics:aggregate-daily` runs at 00:05 daily
- **Security:** tracking skips authenticated users; try-catch prevents tracking errors from breaking pages; no external JS

---

### Rollback

```bash
php artisan migrate:rollback --step=2

git checkout bootstrap/app.php
git checkout routes/admin.php
git checkout routes/frontend.php
git checkout resources/views/backend/partials/sidebar.blade.php

git clean -f app/Http/Middleware/TrackPageView.php \
              app/Console/Commands/AggregatePageViewStats.php \
              app/Http/Controllers/Admin/AnalyticsDashboardController.php \
              app/Models/PageView.php \
              app/Models/PageViewDailyStat.php \
              resources/views/backend/analytics/ \
              tests/Feature/Phase4/AnalyticsDashboardTest.php
```

---

### Next

**STEP 5 — Core Plugin: SEO Manager** (moved here after STEP 7 per schedule).
