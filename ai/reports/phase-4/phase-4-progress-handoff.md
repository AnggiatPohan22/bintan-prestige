# Handoff: Phase 4 — Plugin & Module System (STEP 9 NO-GO)

## Date

2026-06-20

## STEP 9 continuation state

- Working branch: `feature/phase-4-step9-release-gate`
- Claude baseline: `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`
- Restore branch: `backup/pre-phase4-step9-claude-baseline` (verified at the baseline hash)
- Automated suite: **596 tests, 2765 assertions, 0 failures**
- Q-series: Q1–Q7 added; all pass
- Minimal fix: duplicate plugin lifecycle audit rows removed from the admin controller; `PluginManager` remains the single audit owner
- PHPStan level 5: **PASS** after STEP 9A; Larastan 3.10.0 / PHPStan 2.2.2, `app` + `routes`, 0 errors, no ignores or baseline
- Performance target: **FAIL**; four local representative routes average 730.79–912.51 ms against the <300 ms target
- Release recommendation: **NO-GO**
- `AGENTS.md`: intentionally not marked complete
- Git release actions: no commit, merge, tag, or push performed

Complete evidence and command results: `ai/reports/phase-4/step-9-phase4-release-gate-report.md`.

## STEP 9A continuation state

- Status: **PASS**
- Initial PHPStan level 5 result: 95 errors
- Final PHPStan level 5 result: 0 errors
- Analysis paths: `app`, `routes`
- Baseline: not used
- Ignore rules: none
- Regression suite: **596 tests, 2765 assertions, 0 failures**
- Composer validation: PASS
- Dependency audit: 9 advisories affecting 6 existing packages; requires a separately approved security update task
- Remaining Phase 4 blockers: performance target and dependency advisories

Complete STEP 9A evidence: `ai/reports/phase-4/step-9a-phpstan-larastan-foundation-report.md`.

## Branch

`feature/phase-4-step9-release-gate` (Phase 4 baseline sudah merge ke `develop`; STEP 9 belum diintegrasikan)

Last stable Phase 3 commit: `137dc5e` (`feat: complete phase 3 - theme system`)

---

## Cara Membaca Dokumen Ini

File ini adalah sumber konteks tunggal untuk melanjutkan Phase 4.
Baca dari atas ke bawah secara berurutan sebelum menyentuh satu baris kode pun.

**Urutan baca wajib:**
1. File ini (orientasi status + arsitektur)
2. `ai/skills/phase4-plugin-module-skill.md` → berisi jadwal lengkap semua STEP + aturan wajib Phase 4
3. Report STEP terakhir yang selesai (`step-4-5-imp-02-content-revision-history-report.md`)
4. Inspect file yang akan diubah sebelum edit

---

## Status Phase 4 — Ringkasan

| Step | Judul | Status |
|------|-------|--------|
| PRE-STEP IMP-04 | Duplicate Page | ✅ DONE |
| PRE-STEP IMP-01 | Admin Audit Log | ✅ DONE |
| STEP 0 | Baseline & Architecture Design | ✅ DONE |
| STEP 1 | Plugin Registry & Discovery | ✅ DONE |
| STEP 1.5 / IMP-05 | Theme Export & Import (ZIP) | ✅ DONE |
| STEP 2 | Plugin Loader & Lifecycle | ✅ DONE |
| STEP 3 | Hook & Filter Event System | ✅ DONE |
| STEP 3.5 / IMP-06 | Extended Design Tokens | ✅ DONE |
| STEP 3.6 / IMP-07 | Google Fonts Integration | ✅ DONE |
| STEP 4 | Plugin Admin Interface | ✅ DONE |
| STEP 4.5 / IMP-02 | Content Revision History | ✅ DONE |
| STEP 4.6 / IMP-03 | Content Scheduling | ✅ DONE |
| STEP 6 | Core Plugin: Contact Form Builder | ✅ DONE |
| STEP 7 | Core Plugin: Analytics Dashboard | ✅ DONE |
| STEP 5 | Core Plugin: SEO Manager *(dipindah ke setelah STEP 7)* | ✅ DONE |
| STEP 8 | Plugin Security & Sandboxing | ✅ DONE |
| **STEP 9** | **Phase 4 Release Gate** | **⚠ NO-GO — PHPStan blocked, performance failed** |
| [BONUS] IMP-08 | Child Theme Support | ⬜ LOW PRIORITY |

---

## Test Baseline Saat Ini

```
php artisan test
Tests:  589 passed
Assertions: 2,721
```

Tidak boleh ada regresi dari angka ini. Setiap STEP harus menjalankan full suite sebelum dianggap selesai.

---

## Arsitektur Phase 4 — Apa Yang Sudah Ada

### Database Tables (sudah ada, jangan buat ulang)

| Tabel | Migration File | Keterangan |
|-------|---------------|------------|
| `audit_logs` | `2026_06_23_000001_create_audit_logs_table.php` | Log aksi admin; immutable (no `updated_at`) |
| `plugins` | `2026_06_23_000002_create_plugins_table.php` | Registry semua plugin |
| `page_revisions` | `2026_06_20_000001_create_page_revisions_table.php` | Snapshot halaman per-save, max 20 per page |
| `page_views` | `2026_06_20_000005_create_page_views_table.php` | Raw hits per page per visitor (server-side, no JS) |
| `page_view_daily_stats` | `2026_06_20_000006_create_page_view_daily_stats_table.php` | Aggregated daily stats per page (view_count + unique_visitors) |
| `redirects` | `2026_06_20_000007_create_redirects_table.php` | URL redirect manager (from_url unique, to_url, status_code 301/302, is_active) |

**Alter existing tables (tidak boleh dibuat ulang):**

| Tabel | Kolom | Migration |
|-------|-------|-----------|
| `pages` | `publish_at` timestamp nullable | `2026_06_XX_add_publish_at_to_pages` |
| `pages` | `seo_robots` varchar(100) nullable | `2026_06_20_000008_add_seo_robots_to_pages` |

### Models (sudah ada)

| Model | File | Key Methods/Scopes |
|-------|------|--------------------|
| `Plugin` | `app/Models/Plugin.php` | `scopeActive()`, `isActive()`, `HasFactory`, casts: `is_active/config/installed_at/activated_at` |
| `AuditLog` | `app/Models/AuditLog.php` | `record()` static helper — no-op saat unauthenticated |
| `PageRevision` | `app/Models/PageRevision.php` | `page()`, `author()` BelongsTo; no timestamps; JSON casts |
| `Page` | `app/Models/Page.php` | ditambah `revisions()` HasMany → `PageRevision` |
| `PageView` | `app/Models/PageView.php` | fillable: page_id, visitor_hash, referrer, country_code, viewed_date; NO date cast (plain 'Y-m-d' string) |
| `PageViewDailyStat` | `app/Models/PageViewDailyStat.php` | fillable: page_id, stat_date, view_count, unique_visitors; NO date cast (plain 'Y-m-d' string) |
| `Redirect` | `app/Models/Redirect.php` | fillable: from_url, to_url, status_code, is_active; `scopeActive()` |

### Services (sudah ada)

| Service | File | Responsibility |
|---------|------|---------------|
| `PluginRegistry` | `app/Services/Plugin/PluginRegistry.php` | `sync()`, `all()`, `active()` cached, `find()`, `forget()` |
| `PluginManager` | `app/Services/Plugin/PluginManager.php` | `boot()`, `activate()`, `deactivate()`, `uninstall()`, topological sort |
| `HookManager` | `app/Support/HookManager.php` | `addAction/doAction`, `addFilter/applyFilters`, `hasAction/hasFilter`, `removeAll` |
| `GoogleFontsService` | `app/Services/GoogleFontsService.php` | `getFontList()` cached 24h, `forget()` |
| `ThemeService` | `app/Services/ThemeService.php` | `resolvedTokens()` + `theme.tokens` filter hook, `getGoogleFont()` |
| `ZipService` | `app/Services/ZipService.php` | `createFromDirectory()`, `extractTheme()` |
| `PageService` | `app/Services/PageService.php` | ditambah `saveRevision()` (dipanggil otomatis di `update()`) |

### Contracts & Base Classes (sudah ada)

| File | Isi |
|------|-----|
| `app/Contracts/PluginLifecycle.php` | Interface: `onInstall`, `onActivate`, `onDeactivate`, `onUninstall` |
| `app/Plugins/PluginServiceProvider.php` | Abstract base class untuk semua plugin providers |

### Facades (sudah ada)

| Facade | File | Resolves To |
|--------|------|------------|
| `CmsHooks` | `app/Facades/CmsHooks.php` | `HookManager::class` singleton |

### Plugin Manifests (stub, belum ada PHP classes)

| Plugin | Manifest | Slug |
|--------|----------|------|
| SEO Manager | `app/Plugins/SeoManager/plugin.json` | `seo-manager` |
| Contact Form | `app/Plugins/ContactForm/plugin.json` | `contact-form` |
| Analytics | `app/Plugins/Analytics/plugin.json` | `analytics` |

### Core Hooks (sudah di-wire)

| Hook | Tipe | Fired In |
|------|------|----------|
| `cms.init` | action | `AppServiceProvider::boot()` |
| `plugin.activated` | action | `PluginManager::activate()` |
| `plugin.deactivated` | action | `PluginManager::deactivate()` |
| `theme.tokens` | filter | `ThemeService::resolvedTokens()` |
| `admin.loaded` | action | *Planned STEP 4 — belum di-wire ke middleware* |

### Admin Routes (sudah ada, jangan ubah)

| Route Name | Method + URI | Controller |
|------------|--------------|-----------|
| `admin.plugins.index` | GET `/admin/plugins` | `PluginController@index` |
| `admin.plugins.scan` | POST `/admin/plugins/scan` | `PluginController@scan` |
| `admin.plugins.show` | GET `/admin/plugins/{plugin}` | `PluginController@show` |
| `admin.plugins.activate` | PATCH `/admin/plugins/{plugin}/activate` | `PluginController@activate` |
| `admin.plugins.deactivate` | PATCH `/admin/plugins/{plugin}/deactivate` | `PluginController@deactivate` |
| `admin.plugins.destroy` | DELETE `/admin/plugins/{plugin}` | `PluginController@destroy` |
| `admin.pages.revisions.restore` | POST `/admin/pages/{page}/revisions/{revision}/restore` | `PageController@restoreRevision` |
| `admin.audit-logs.index` | GET `/admin/audit-logs` | `AuditLogController@index` |
| `admin.themes.import` | POST `/admin/themes/import` | `ThemeController@import` |
| `admin.themes.export` | GET `/admin/themes/{theme}/export` | `ThemeController@export` |
| `admin.analytics.index` | GET `/admin/analytics` | `AnalyticsDashboardController@index` |
| `admin.analytics.export` | GET `/admin/analytics/export` | `AnalyticsDashboardController@exportCsv` |
| `admin.seo.redirects.*` | GET/POST/PUT/DELETE `/admin/seo/redirects` | `RedirectController` (resource) |
| `admin.seo.robots.edit` | GET `/admin/seo/robots` | `SeoRobotsController@edit` |
| `admin.seo.robots.update` | PUT `/admin/seo/robots` | `SeoRobotsController@update` |
| `sitemap` | GET `/sitemap.xml` | `SitemapController@index` |
| `robots` | GET `/robots.txt` | `RobotsController@index` |

### Sidebar Admin (sudah ada)

Section "SEO" di sidebar berisi:
- **Redirects** (fa-arrow-right-arrow-left) → `admin.seo.redirects.index`
- **Robots.txt** (fa-robot) → `admin.seo.robots.edit`
- **Sitemap** (fa-sitemap) → `/sitemap.xml` (external link)

Section "System" di sidebar berisi:
- **Analytics** (fa-chart-line) → `admin.analytics.index`
- **Plugins** (fa-puzzle-piece) → `admin.plugins.index`
- **Audit Log** (fa-shield-halved) → `admin.audit-logs.index`

### Factories (sudah ada)

| Factory | File |
|---------|------|
| `PluginFactory` | `database/factories/PluginFactory.php` — states: `active()`, `inactive()` |
| `PageFactory` | `database/factories/PageFactory.php` — states: `published()`, `draft()` |
| `PageBlockFactory` | `database/factories/PageBlockFactory.php` |

### AppServiceProvider::register() (jangan ubah urutan)

```php
$this->app->singleton(GlobalSettingsService::class);
$this->app->singleton(ThemeService::class);
$this->app->singleton(HookManager::class);
$this->app->singleton(PluginRegistry::class);
$this->app->singleton(PluginManager::class);
$this->app->make(PluginManager::class)->boot();  // boot active plugin providers
```

### AppServiceProvider::boot() — hooks yang sudah di-fire

```php
// Observers: Page, Product, Menu, Theme
// MenuService cache invalidation
// ThemeService cache invalidation
// View composers untuk global settings, menu sources, themeService
CmsHooks::doAction('cms.init');  // ← terakhir di boot()
```

---

## Phase 4 Test Files (sudah ada)

| File | Kode | Tests |
|------|------|-------|
| `tests/Feature/Phase4/Phase4BaselineCharacterizationTest.php` | A1–A20 | 20 |
| `tests/Feature/Phase4/PluginRegistryTest.php` | B1–B20 | 20 |
| `tests/Feature/Phase4/ThemeExportImportTest.php` | C1–C15 | 15 |
| `tests/Feature/Phase4/PluginManagerTest.php` | F1–F15 | 15 |
| `tests/Feature/Phase4/HookManagerTest.php` | G1–G17 | 17 |
| `tests/Feature/Phase4/ExtendedDesignTokensTest.php` | H1–H10 | 10 |
| `tests/Feature/Phase4/GoogleFontsIntegrationTest.php` | I1–I10 | 10 |
| `tests/Feature/Phase4/PluginAdminInterfaceTest.php` | J1–J12 | 12 |
| `tests/Feature/Phase4/PageRevisionTest.php` | K1–K12 | 12 |
| `tests/Feature/Phase4/ContentSchedulingTest.php` | L1–L12 | 12 |
| `tests/Feature/Phase4/ContactFormBuilderTest.php` | M1–M12 | 12 |
| `tests/Feature/Phase4/AnalyticsDashboardTest.php` | N1–N12 | 12 |
| `tests/Feature/Phase4/SeoManagerTest.php` | O1–O15 | 15 |
| `tests/Feature/Phase4/PluginSecurityTest.php` | P1–P14 | 14 |

Test STEP 9: **Q1–Q7** di `Phase4ReleaseGateTest.php` (7/7 pass)

---

## STEP 4.6 / IMP-03 — Content Scheduling ✅ DONE

See `step-4-6-imp-03-content-scheduling-report.md` for full detail.

---

## STEP 6 — Core Plugin: Contact Form Builder ✅ DONE

See `step-6-contact-form-builder-report.md` for full detail.

**Key additions:**
- Tables: `form_definitions`, `form_submissions`
- Models: `FormDefinition`, `FormSubmission`
- Admin CRUD: `FormDefinitionController`, `FormSubmissionController`
- Frontend submit: `ContactFormController` with honeypot + dynamic validation
- Mail: `ContactFormSubmission` mailable
- Block type `contact_form` integrated into page builder
- Sidebar "Forms" link in Content section
- Tests: M1–M12 (12/12 pass)

---

## STEP 8 — Plugin Security & Sandboxing ✅ DONE

See `step-8-plugin-security-sandboxing-report.md` for full detail.

**Key additions:**
- `PluginSecurityException` — carries `$slug` + `$blockedToken`; propagates to controller
- `PluginScanner` — `scan()` (7-token blocklist, recursive via scandir) + `validatePermissions()` (10 allowed scopes); `shell_exec(` ordered before `exec(` to avoid substring collision
- `PluginManager::activate()` — scanner runs before `callLifecycle()`; `AuditLog::record('plugin.activated')` after DB update
- `PluginManager::deactivate()` — `AuditLog::record('plugin.deactivated')` after DB update
- `PluginManager::uninstall()` — `AuditLog::record('plugin.uninstalled')` after DB delete
- All 3 plugin manifests — added `"permissions"` arrays with allowed CMS scopes
- `tests/Fixtures/PluginSecurity/recurse-test/sub/BadClass.php` — committed fixture to avoid Windows/PHPUnit timing issue with freshly-created subdirectory file reads
- Tests: P1–P14 (14/14 pass)

---

## STEP 5 — Core Plugin: SEO Manager ✅ DONE

See `step-5-seo-manager-report.md` for full detail.

**Key additions:**
- XML Sitemap at `/sitemap.xml` — auto-includes all published pages
- Robots.txt at `/robots.txt` — served from `storage/app/seo/robots.txt`, editable from admin
- Redirect Manager: `redirects` table, admin CRUD, `HandleRedirects` global middleware (cached 10 min, cache-invalidated via `RedirectObserver`)
- Per-page `seo_robots` field on `pages` table — allows noindex override per page
- Critical bug: route-group middleware doesn't fire for unmatched URLs → must use `$middleware->append()` for global HTTP stack
- Tests: O1–O15 (15/15 pass)

---

## STEP 7 — Core Plugin: Analytics Dashboard ✅ DONE

See `step-7-analytics-dashboard-report.md` for full detail.

**Key additions:**
- Tables: `page_views`, `page_view_daily_stats` (no Eloquent `date` cast — plain 'Y-m-d' strings for SQLite/MySQL compat)
- Models: `PageView`, `PageViewDailyStat`
- Middleware: `TrackPageView` — server-side tracking, skips auth'd users, applied only to `pages.show` route
- Command: `analytics:aggregate-daily [--date=]` — public `aggregate()` for direct test invocation; scheduled at 00:05 daily
- Controller: `AnalyticsDashboardController` — 30-day Chart.js chart, top 10 pages, CSV export
- Tests: N1–N12 (12/12 pass)

---

## Aturan Wajib Untuk Sesi Berikutnya

1. **Baca `ai/skills/phase4-plugin-module-skill.md`** sebelum menyentuh kode apapun
2. **Minta approval migration** sebelum `php artisan migrate` — tunjukkan schema dulu
3. **Inspect file yang akan diubah** sebelum edit — jangan asumsi struktur
4. **Jalankan full test suite** setelah setiap STEP: `php artisan test`
5. **Baseline saat ini: 589 tests, 2721 assertions** — tidak boleh ada regresi
6. **Q1–Q7 sudah ditambahkan dan lulus**; jangan duplikasi coverage saat blocker STEP 9 ditindaklanjuti
7. **Tidak ada query di dalam plugin `boot()`** — selalu cache config saat load
8. **Setiap plugin wajib punya try-catch** di method yang bisa throw
9. **Selalu `AuditLog::record()`** untuk aksi plugin activation/deactivation/uninstall
10. **Approval gate** (dari skill file Seksi 8): migration baru, package Composer baru, perubahan routing existing, perubahan AppServiceProvider/Kernel

---

## Step Reports Yang Sudah Ada (referensi detail per-step)

Semua laporan ada di `ai/reports/phase-4/`:

| File | Step |
|------|------|
| `pre-step-imp-04-duplicate-page-report.md` | PRE-STEP IMP-04 |
| `pre-step-imp-01-admin-audit-log-report.md` | PRE-STEP IMP-01 |
| `step-0-baseline-architecture-design-report.md` | STEP 0 |
| `step-1-plugin-registry-discovery-report.md` | STEP 1 |
| `step-1-5-imp-05-theme-export-import-report.md` | STEP 1.5 / IMP-05 |
| `step-2-plugin-loader-lifecycle-report.md` | STEP 2 |
| `step-3-hook-filter-event-system-report.md` | STEP 3 |
| `step-3-5-imp-06-extended-design-tokens-report.md` | STEP 3.5 / IMP-06 |
| `step-3-6-imp-07-google-fonts-integration-report.md` | STEP 3.6 / IMP-07 |
| `step-4-plugin-admin-interface-report.md` | STEP 4 |
| `step-4-5-imp-02-content-revision-history-report.md` | STEP 4.5 / IMP-02 |
| `step-4-6-imp-03-content-scheduling-report.md` | STEP 4.6 / IMP-03 |
| `step-6-contact-form-builder-report.md` | STEP 6 |
| `step-7-analytics-dashboard-report.md` | STEP 7 |
| `step-5-seo-manager-report.md` | STEP 5 |
| `step-8-plugin-security-sandboxing-report.md` | STEP 8 |

---

## Cara Mulai Sesi Baru

Kirim prompt ini persis di awal sesi:

```
Read ai/skills/phase4-plugin-module-skill.md first.
Then check current git branch and confirm it is: feature/phase-4-step9-release-gate
Then read ai/reports/phase-4/phase-4-progress-handoff.md for full context.

Current task: STEP 9 — Phase 4 Release Gate
```

Setelah 3 file dibaca, lanjutkan dengan inspect file yang akan diubah sebelum planning.

---

## Konteks Tambahan Yang Perlu Diketahui

### Theme System (Phase 3, sudah stabil)

- Active theme: `bintan-prestige-luxury` di tabel `themes`
- `ThemeService::resolvedTokens()` menggunakan filter hook `theme.tokens`
- `ThemeService::getGoogleFont()` membaca `_google_font` dari kolom `customization` JSON
- Google Fonts API key: `GOOGLE_FONTS_API_KEY` di `.env` (opsional — picker tersembunyi jika tidak ada)
- ZIP export/import sudah ada via `ZipService` + `ThemeController`

### Page System (Phase 2, sudah stabil, baru ditambah revisions)

- `pages` table: `title`, `slug`, `template_id`, `status`, `meta_title`, `meta_description`, `og_image`, `sort_order`
- `page_blocks` table: `page_id`, `block_type`, `label`, `data` (JSON), `sort_order`, `is_visible`
- `page_revisions` table (baru): snapshot setiap kali `PageService::update()` dipanggil
- Status saat ini: `draft` | `published` — STEP 4.6 akan tambah `scheduled`

### Konfigurasi Lingkungan

- Stack: Laravel 13.8 | PHP 8.3 | MySQL | Tailwind CSS | Alpine.js
- Test runner: `php artisan test`
- Asset builder: `npm run build`
- Admin prefix: `/admin` dengan middleware `auth` + `admin`
- Frontend URL pattern: `/pages/{slug}`
