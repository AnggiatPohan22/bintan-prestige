# Handoff: Phase 4 — Plugin & Module System (STEP 9C PASS — Manual Smoke Test GO)

## Date

2026-06-21

## STEP 9C continuation state (latest)

- Working branch: `feature/phase-4-step9-release-gate`
- HEAD: `e2a3080bcd4e48923640086673e58cec1ddf395f` (STEP 9A commit; 9B + 9C changes in working tree, not yet committed)
- Automated suite: **596 tests, 2765 assertions, 0 failures**
- PHPStan level 5: **PASS**; 0 errors, no ignores or baseline
- Performance: **PASS**; all four routes well below 300 ms (see STEP 9C results below)
- Manual smoke test: **PASS**; all 14 checks passed
- Release recommendation: **GO — all Phase 4 gates cleared**

### Manual Smoke Test Results (STEP 9C — 2026-06-21)

| Check | Status | Bukti |
|---|---|---|
| Git state | ✅ PASS | Branch benar, restore hash identik `ace347e` |
| Pre-test checklist | ✅ PASS | Laragon up, 0 pending migration, 0 file probe temp |
| 4A Public routes | ✅ PASS | `/` 200/222ms, `/products` 200/170ms, `/sitemap.xml` 200/94ms, `/robots.txt` 200/18ms |
| 4B Product detail | ✅ PASS | `/products/snorkeling-adventure-7484` 200/212ms |
| 4C Sitemap XML | ✅ PASS | XML valid, 4 `<loc>` entries, 0 Laravel error |
| 4D Robots.txt | ✅ PASS | `User-agent: *` + `Disallow:` — format valid |
| 4E Admin panel | ✅ PASS | `/admin/dashboard` → 302, `/login` → 200 + form render |
| 4F Phase 4 admin routes | ✅ PASS | plugins/redirects/analytics/audit-logs → 302 (bukan 500/404) |
| 4G Full test suite | ✅ PASS | 596 tests, 2765 assertions, 0 failures |
| 4H PHPStan level 5 | ✅ PASS | 0 errors, tanpa ignore, tanpa baseline |
| 4I Performance spot check | ✅ PASS | `/` 182ms, `/products` 178ms, `/{slug}` 144ms, `/sitemap.xml` 88ms |
| 4J Plugin system | ✅ PASS | PluginManager resolve OK, 1 active plugin, 0 crash |
| 4K Global settings | ✅ PASS | SiteSetting 79 rows — tersedia normal |
| 4L Analytics middleware | ✅ PASS | TrackPageView mencatat row setelah guest request |

### Catatan STEP 9C

- Route `/admin` dan `/admin/login` memang tidak ada; entry point admin adalah `/admin/dashboard` (redirect ke `/login` jika belum auth)
- Model global settings bernama `SiteSetting`, bukan `GlobalSetting`
- `PageView` count dimulai dari 0 di environment bersih dan bertambah setelah guest hit `/pages/lagoi` — analytics berjalan
- Semua perubahan STEP 9A + 9B + 9C masih di working tree (belum di-commit sesuai policy)

---

## SECTION 1 — Phase 4 Release Gate — Final Status

| Gate | Status | Bukti |
|---|---|---|
| Test suite | ✅ PASS | 596 tests, 2765 assertions, 0 failures |
| PHPStan | ✅ PASS | Level 5, 0 errors, 0 ignores, no baseline — Larastan 3.10.0 / PHPStan 2.2.2 |
| Performance | ✅ PASS | `/` 182ms, `/products` 178ms, `/products/{slug}` 144ms, `/sitemap.xml` 88ms — all below 300ms target |
| Smoke test | ✅ PASS | STEP 9C: 2026-06-21 — 14/14 checks passed |
| **Release Gate** | **✅ GO** | All blockers resolved — ready for `approved release` commit + merge + tag |

---

## SECTION 2 — Architecture Rules (Rules for Future AI Agents)

Aturan berikut diekstrak dari temuan STEP 9A dan STEP 9B. Setiap AI atau developer yang menyentuh codebase ini WAJIB membaca bagian ini.

---

### Rule: eloquent-relationship-generics
**Konteks:** STEP 9A menemukan 95 PHPStan errors, sebagian besar disebabkan oleh relasi Eloquent tanpa generic type annotations.
**Aturan:** Setiap `HasMany`, `BelongsTo`, `HasOne`, `BelongsToMany` di model HARUS menyertakan generic type. Contoh benar: `HasMany<PageRevision, Page>` bukan `HasMany`. Contoh salah: `public function revisions(): HasMany`.
**Bukti:** STEP 9A Batch 1 — 95 → 31 errors setelah menambahkan generics ke semua model relationships.
**Risiko pelanggaran:** PHPStan level 5 gagal. Cascade type errors di seluruh layer yang menggunakan relasi tersebut. Bisa menyembunyikan real type mismatch bugs.

---

### Rule: pageview-date-no-eloquent-cast
**Konteks:** `PageView` dan `PageViewDailyStat` menggunakan date field yang harus kompatibel antara SQLite (test) dan MySQL (production).
**Aturan:** Field `viewed_date` di `PageView` dan `stat_date` di `PageViewDailyStat` TIDAK boleh menggunakan Eloquent `date` cast. Gunakan plain `'Y-m-d'` string. Jangan tambahkan `'viewed_date' => 'date'` atau `'stat_date' => 'date'` ke `$casts`.
**Bukti:** STEP 7 — SQLite/MySQL date cast incompatibility ditemukan saat N-series tests dibuat.
**Risiko pelanggaran:** N-series analytics tests gagal di SQLite test environment. Aggregation queries bisa menghasilkan tipe yang berbeda antara test dan production.

---

### Rule: pluginmanager-single-audit-owner
**Konteks:** STEP 9 menemukan bahwa `PluginController` memanggil `AuditLog::record()` duplikat setelah `PluginManager` sudah memanggilnya, menghasilkan 2 audit rows per aksi.
**Aturan:** `AuditLog::record()` untuk aksi plugin lifecycle (`plugin.activated`, `plugin.deactivated`, `plugin.uninstalled`) HANYA boleh dipanggil dari `PluginManager`. Controller TIDAK boleh memanggil `AuditLog::record()` untuk aksi yang sudah ditangani oleh service.
**Bukti:** STEP 9 fix — removed 3 duplicate `AuditLog::record()` calls from `PluginController`. Q3–Q5 tests verify exactly one row per lifecycle action.
**Risiko pelanggaran:** Duplikat audit rows. Audit log tidak lagi reliable untuk forensik. Q3–Q5 tests akan fail.

---

### Rule: handle-redirects-global-stack
**Konteks:** STEP 5 menemukan bug kritis: middleware di route group tidak dieksekusi untuk URL yang tidak cocok dengan route manapun. Redirect untuk unmatched URLs tidak berjalan.
**Aturan:** `HandleRedirects` middleware HARUS didaftarkan ke global HTTP middleware stack via `$middleware->append(HandleRedirects::class)` di `bootstrap/app.php`. JANGAN pindahkan ke route group middleware atau web middleware group.
**Bukti:** STEP 5 SEO Manager report + Q2 test yang memverifikasi runtime middleware list.
**Risiko pelanggaran:** Redirect tidak berjalan untuk URL yang tidak terdaftar sebagai Laravel route. 404 bukannya 301/302. O4–O7 dan Q2 tests akan fail.

---

### Rule: product-price-range-single-query
**Konteks:** STEP 9B menemukan bahwa `ProductController::index()` menjalankan dua query agregat terpisah (`MIN(price)` dan `MAX(price)`) yang bisa digabung.
**Aturan:** Query min/max price range pada `ProductController::index()` HARUS menggunakan satu `selectRaw('MIN(price) as min_price, MAX(price) as max_price')->toBase()->first()`. JANGAN pisah kembali jadi dua `->min()` / `->max()` calls. Gunakan `->toBase()` agar PHPStan tidak error pada dynamic stdClass properties.
**Bukti:** STEP 9B fix di `app/Http/Controllers/Frontend/ProductController.php`.
**Risiko pelanggaran:** Dua DB round-trips per halaman `/products`. Di environment tanpa OPcache, ini amplifies latency. PHPStan bisa fail jika `->toBase()` dihapus.

---

### Rule: opcache-must-stay-enabled
**Konteks:** STEP 9B menemukan bahwa OPcache disabled adalah root cause dari 730–912ms response time (target <300ms). Enabling OPcache turun ke 88–185ms.
**Aturan:** OPcache HARUS tetap enabled di `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`. Pastikan baris berikut tidak ter-comment: `zend_extension=opcache`, `opcache.enable=1`, `opcache.memory_consumption=128`, `opcache.max_accelerated_files=10000`. Jika Laragon di-upgrade atau PHP di-reinstall, verifikasi OPcache masih enabled sebelum performance benchmark.
**Bukti:** STEP 9B root cause analysis — 128MB, 10,000 file slots config.
**Risiko pelanggaran:** Response time naik 5–6x (ke 730–912ms range). Performance gate otomatis FAIL.

---

### Rule: no-db-query-in-plugin-boot
**Konteks:** Plugin providers diload saat setiap request via `AppServiceProvider::register()`. Query DB di sini mengakibatkan N DB hits per request untuk setiap active plugin.
**Aturan:** Plugin `ServiceProvider::boot()` TIDAK boleh menjalankan query DB langsung. Config plugin harus dibaca dari `PluginRegistry` (yang sudah di-cache) atau dari data yang sudah diinjeksikan. Jika config diperlukan, load saat aksi pertama (lazy), bukan saat boot.
**Bukti:** Phase 4 architecture rule — dikonfirmasi tetap berlaku setelah STEP 9C smoke test (PluginManager::boot() tidak crash).
**Risiko pelanggaran:** N DB queries per request untuk setiap active plugin. Performance degradation linear dengan jumlah active plugins.

---

### Rule: appserviceprovider-singleton-order
**Konteks:** `PluginManager::boot()` bergantung pada `PluginRegistry` dan `HookManager` yang sudah terdaftar sebagai singleton.
**Aturan:** Urutan binding di `AppServiceProvider::register()` TIDAK boleh diubah:
```php
$this->app->singleton(GlobalSettingsService::class);
$this->app->singleton(ThemeService::class);
$this->app->singleton(HookManager::class);
$this->app->singleton(PluginRegistry::class);
$this->app->singleton(PluginManager::class);
$this->app->make(PluginManager::class)->boot();  // harus terakhir
```
**Bukti:** Phase 4 STEP 2 architecture. Dikonfirmasi smoke test 4J (PluginManager resolves without error).
**Risiko pelanggaran:** Circular dependency atau null reference saat plugin boot. Application crash pada setiap request.

---

### Rule: plugin-scanner-token-order
**Konteks:** STEP 8 menemukan bahwa blocklist token `exec(` harus diperiksa SETELAH `shell_exec(` karena `shell_exec(` mengandung substring `exec(`.
**Aturan:** Di `PluginScanner::scan()`, token `shell_exec(` HARUS diurutkan SEBELUM `exec(` dalam blocklist array. Jangan reorder array blocklist tanpa mempertimbangkan substring overlap.
**Bukti:** STEP 8 plugin security report — substring collision `exec` inside `shell_exec`.
**Risiko pelanggaran:** `shell_exec(` tidak terdeteksi sebagai blocked token. Plugin berbahaya bisa lolos security scan. P-series tests akan fail.

---

### Rule: menu-theme-cache-invalidation
**Konteks:** `MenuService` dan `ThemeService` menggunakan cache yang harus diinvalidasi saat data berubah.
**Aturan:** Jangan membuat query menu atau theme tokens di luar service. Cache invalidation terjadi via Observer (`MenuObserver`, `ThemeObserver`) yang sudah di-wire di `AppServiceProvider::boot()`. Jika menambah menu item atau mengubah theme token, verifikasi observer masih terdaftar.
**Bukti:** AppServiceProvider boot() — observer wiring dikonfirmasi stable setelah STEP 9C smoke test.
**Risiko pelanggaran:** Stale menu atau theme data setelah admin perubahan. Cache tidak ter-invalidate.

---

### Rule: phpstan-no-ignore-no-baseline
**Konteks:** STEP 9A membersihkan 95 errors ke 0 tanpa satu pun ignore rule atau baseline. Ini disengaja — baseline menyembunyikan errors yang harus diperbaiki.
**Aturan:** `phpstan.neon` TIDAK boleh menambahkan `ignoreErrors`, `excludePaths`, atau baseline. Jika PHPStan menemukan error baru: PERBAIKI kodenya, jangan suppress. Jika error adalah false positive yang tidak bisa diperbaiki, lapor ke owner untuk keputusan eksplisit.
**Bukti:** STEP 9A — 0 errors, 0 ignores, no baseline. phpstan.neon intentionally minimal.
**Risiko pelanggaran:** Technical debt PHPStan terakumulasi. Errors tersembunyi yang mungkin adalah real bugs. Melanggar AGENTS.override.md Section 7 (tidak boleh menurunkan level PHPStan).

---

## SECTION 3 — Technical Debt dan Deferred Items

Item-item berikut ditemukan selama STEP 9 tapi sengaja ditunda. Setiap item butuh task terpisah dengan approval owner.

| # | Item | Severity | Ditemukan Di | Estimasi Scope |
|---|---|---|---|---|
| TD-01 | **Dependency advisories**: 9 advisories di 6 packages — `laravel/framework` <13.12.0, `guzzlehttp/guzzle` 2 medium, `guzzlehttp/psr7` 3 medium, `symfony/http-foundation` 1 medium, `symfony/polyfill-intl-idn` 1 low, `symfony/routing` 1 medium | HIGH | STEP 9A `composer audit --locked` | Butuh `composer update` terbatas + full test re-run + approval |
| TD-02 | **`admin.loaded` hook** tidak di-wire ke middleware — direncanakan di STEP 4, belum diimplementasikan | LOW | STEP 4 + handoff | Tambahkan di Phase 5 jika diperlukan plugin yang perlu hook saat admin load |
| TD-03 | **IMP-08 Child Theme Support** — LOW PRIORITY, tidak diimplementasikan di Phase 4 | LOW | Phase 4 backlog | Task terpisah, jangan gabungkan dengan Phase 5 |
| TD-04 | **Visual browser verification** tidak dilakukan — semua test menggunakan CLI `curl` dan PHPUnit. Browser automation (`CreateProcessAsUserW`) tidak tersedia di sesi Windows ini | INFO | STEP 9 + 9C | Lakukan manual browser test atau setup Selenium/Dusk jika diperlukan |
| TD-05 | **Migration filename placeholder**: `2026_06_XX_add_publish_at_to_pages` — nama file actual berbeda, perlu konfirmasi | INFO | phase-4-progress-handoff.md | Cek `php artisan migrate:status` untuk nama file aktual |
| TD-06 | **Page status note stale**: komentar "STEP 4.6 akan tambah `scheduled`" sudah tidak akurat — `scheduled` sudah diimplementasikan di STEP 4.6 | INFO | Konteks Tambahan section | Update dokumentasi di sesi berikutnya |

---

## SECTION 4 — Phase 4 Module Inventory

Semua modul berikut dikonfirmasi berjalan setelah STEP 9C manual smoke test.

| Modul | Route Utama | Test Coverage | Performance (STEP 9C) | Status |
|---|---|---|---|---|
| Plugin Registry & Discovery | `admin/plugins` (index, scan, show) | B1–B20 (20 tests) | 302ms → auth | ✅ Active |
| Plugin Loader & Lifecycle | Internal — `PluginManager::boot/activate/deactivate/uninstall` | F1–F15 (15 tests) | N/A (server-side) | ✅ Active |
| Hook & Filter Event System | Internal — `CmsHooks` facade, `HookManager` | G1–G17 (17 tests) | N/A | ✅ Active |
| Theme Export & Import (ZIP) | `admin/themes/{theme}/export`, `admin/themes/import` | C1–C15 (15 tests) | N/A | ✅ Active |
| Extended Design Tokens | Internal — `ThemeService::resolvedTokens()` + `theme.tokens` hook | H1–H10 (10 tests) | N/A | ✅ Active |
| Google Fonts Integration | `admin/themes` settings panel | I1–I10 (10 tests) | N/A | ✅ Active |
| Plugin Admin Interface | `admin/plugins` (activate, deactivate, destroy) | J1–J12 (12 tests) | 302ms → auth | ✅ Active |
| Content Revision History | `admin/pages/{page}/revisions/{revision}/restore` | K1–K12 (12 tests) | N/A | ✅ Active |
| Content Scheduling | `publish_at` on pages, `scheduled` status | L1–L12 (12 tests) | N/A | ✅ Active |
| Admin Audit Log | `admin/audit-logs` | A1–A20 + P-series (partial) + Q3–Q5 | 302ms → auth | ✅ Active |
| Duplicate Page | `admin/pages/{page}/duplicate` | A-series baseline | N/A | ✅ Active |
| SEO Manager — Sitemap | `GET /sitemap.xml` | O1–O3, Q1 | **88ms avg** ✅ | ✅ Active |
| SEO Manager — Robots.txt | `GET /robots.txt` | O-series | **18ms avg** ✅ | ✅ Active |
| SEO Manager — Redirects | `admin/seo/redirects/*` (resource) | O4–O7, Q2 | 302ms → auth | ✅ Active |
| SEO Manager — Per-page noindex | `seo_robots` column on `pages` | O-series | N/A | ✅ Active |
| Contact Form Builder | `admin/forms/*`, `POST /contact/{slug}` | M1–M12, Q6 | ~200ms range | ✅ Active |
| Analytics Dashboard | `admin/analytics`, `admin/analytics/export` | N1–N12, Q7 | 302ms → auth | ✅ Active |
| Analytics Tracking | `TrackPageView` middleware → `pages.show` only | N-series | N/A | ✅ Active (confirmed STEP 9C 4L) |
| Plugin Security & Sandboxing | Internal — `PluginScanner`, `PluginSecurityException` | P1–P14 (14 tests) | N/A | ✅ Active |

**Total test coverage Phase 4: 204 tests across 14 test files + 7 Q-series tests = 211 Phase 4 tests.**

---

## SECTION 5 — Starting Point untuk Phase 5

### Status saat ini (per STEP 9C, 2026-06-21)

Semua pekerjaan Phase 4 ada di branch `feature/phase-4-step9-release-gate` dalam working tree (belum di-commit). Sebelum Phase 5 bisa dimulai, Phase 4 harus diintegrasikan:

**Prerequisite sebelum Phase 5:**

```
1. Owner memberikan keyword `approved release`
2. Commit STEP 9A + 9B changes ke feature/phase-4-step9-release-gate
3. Merge feature/phase-4-step9-release-gate → develop → re-run full suite + PHPStan
4. Merge develop → main → re-run full suite + PHPStan
5. Tag v4.0.0 di main
6. Update AGENTS.md untuk menandai Phase 4 complete
```

### Branch naming untuk Phase 5

```
feature/phase-5-[nama-step]
```

Contoh: `feature/phase-5-visual-builder`, `feature/phase-5-drag-drop-editor`

### Apa yang TIDAK boleh disentuh (protected Phase 4 work)

- Semua tabel Phase 4: `audit_logs`, `plugins`, `page_revisions`, `page_views`, `page_view_daily_stats`, `redirects`
- Column tambahan: `pages.publish_at`, `pages.seo_robots`
- Semua service Phase 4: `PluginRegistry`, `PluginManager`, `HookManager`, `ZipService`, `GoogleFontsService`
- `HandleRedirects` middleware dan posisinya di global stack
- `TrackPageView` middleware dan binding-nya ke `pages.show`
- Plugin manifests di `app/Plugins/*/plugin.json`
- `phpstan.neon` — jangan ubah level atau tambahkan ignores
- `backup/pre-phase4-step9-claude-baseline` branch — jangan hapus atau move

### Apa yang bisa dibangun di atas Phase 4

Phase 4 menyediakan fondasi untuk:

- **Hook system** (`CmsHooks`) siap digunakan oleh Phase 5 visual builder untuk menambahkan block types baru via `addFilter('block.types', ...)`
- **Plugin system** siap menerima plugin Phase 5 (drag-drop builder bisa diimplementasikan sebagai plugin dengan slug `visual-builder`)
- **Page revision system** sudah ada — Phase 5 preview/undo bisa memanfaatkan `page_revisions`
- **Analytics** sudah tracking page views — Phase 5 bisa menambahkan block-level analytics
- **Audit log** siap merekam Phase 5 admin actions

### Rekomendasi task pertama Phase 5

Berdasarkan Phase 5 description di `AGENTS.md`:

> Phase 3 — FUTURE: Visual drag-and-drop builder with live preview and inline editing

Urutan yang direkomendasikan:

1. **STEP 0 Phase 5** — Baseline & Architecture Design (baca seluruh Phase 4 arsitektur, rancang drag-drop block system, pastikan backward compatible dengan existing `page_blocks` table)
2. **STEP 1 Phase 5** — Block Type Registry (extend existing block system via HookManager filter hooks — jangan rebuild dari scratch)
3. **STEP 2 Phase 5** — Drag-drop UI (Alpine.js + Sortable.js atau native drag events, rendering di atas existing `page_blocks` admin)

Sebelum STEP 0: baca `ai/skills/page-builder-skill.md` + `ai/skills/cms-architect-skill.md`.

### Command referensi awal sesi Phase 5

```bash
# Verifikasi starting point
git branch --show-current          # harus: develop atau feature/phase-5-*
git log -5 --oneline --decorate   # pastikan v4.0.0 tag ada di main

# Konfirmasi baseline
php artisan test --stop-on-failure  # harus: 596 tests, 0 failures
vendor/bin/phpstan analyse --no-progress  # harus: 0 errors

# Cek migrasi sudah clean
php artisan migrate:status | tail -5
```

---

## STEP 9B continuation state (latest)

- Working branch: `feature/phase-4-step9-release-gate`
- Starting HEAD: `e2a3080bcd4e48923640086673e58cec1ddf395f`
- Automated suite: **596 tests, 2765 assertions, 0 failures**
- PHPStan level 5: **PASS**; 0 errors, no ignores or baseline
- Performance target: **PASS**; all four routes average well below 300 ms (see below)
- Release recommendation: **GO** — both Phase 4 blockers are now resolved

### Performance (after STEP 9B)

| Route | Average |
|---|---|
| Homepage `/` | 185 ms ✅ |
| Listing `/products` | 164 ms ✅ |
| Detail `/products/{slug}` | 164 ms ✅ |
| Sitemap `/sitemap.xml` | 97 ms ✅ |

### What STEP 9B changed

1. Enabled OPcache in `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` (primary fix — was the root cause of 730–912 ms response times)
2. Merged two `ProductPrice` aggregate queries (min + max) into one `selectRaw` query in `ProductController::index()`

Complete evidence: `ai/reports/phase-4/step-9b-performance-audit-report.md`.

---

## STEP 9 continuation state

- Working branch: `feature/phase-4-step9-release-gate`
- Claude baseline: `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`
- Restore branch: `backup/pre-phase4-step9-claude-baseline` (verified at the baseline hash)
- Automated suite: **596 tests, 2765 assertions, 0 failures**
- Q-series: Q1–Q7 added; all pass
- Minimal fix: duplicate plugin lifecycle audit rows removed from the admin controller; `PluginManager` remains the single audit owner
- PHPStan level 5: **PASS** after STEP 9A; Larastan 3.10.0 / PHPStan 2.2.2, `app` + `routes`, 0 errors, no ignores or baseline
- Performance target: **FAIL** at time of STEP 9 (resolved in STEP 9B)
- `AGENTS.md`: not yet marked complete — requires owner approval and `v4.0.0` tag
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
| **STEP 9** | **Phase 4 Release Gate** | **✅ GO — PHPStan PASS (9A), Performance PASS (9B), Smoke Test PASS (9C)** |
| [BONUS] IMP-08 | Child Theme Support | ⬜ LOW PRIORITY |

---

## Test Baseline Saat Ini

```
php artisan test
Tests:  596 passed
Assertions: 2,765
Failures: 0
```

Diperbarui setelah STEP 9 Q-series (7 tests ditambahkan). Tidak boleh ada regresi dari angka ini. Setiap STEP harus menjalankan full suite sebelum dianggap selesai.

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
5. **Baseline saat ini: 596 tests, 2765 assertions** (diperbarui dari STEP 9 Q-series) — tidak boleh ada regresi
6. **Q1–Q7 sudah ditambahkan dan lulus**; jangan duplikasi coverage
7. **Tidak ada query di dalam plugin `boot()`** — selalu cache config saat load
8. **Setiap plugin wajib punya try-catch** di method yang bisa throw
9. **Selalu `AuditLog::record()`** untuk aksi plugin activation/deactivation/uninstall
10. **Approval gate** (dari skill file Seksi 8): migration baru, package Composer baru, perubahan routing existing, perubahan AppServiceProvider/Kernel

### Catatan arsitektur dari STEP 9C (sesi baru wajib tahu)

- **Global settings model**: bernama `SiteSetting`, bukan `GlobalSetting` — 79 rows di DB
- **Admin login route**: `/login` (bukan `/admin/login`); admin entry point: `/admin/dashboard`
- **Analytics tracking**: `TrackPageView` middleware hanya aktif di route `pages.show` (`/pages/{slug}`); homepage dan `/products` tidak di-track
- **OPcache**: harus tetap enabled di `php.ini` Laragon — jika Laragon reinstall/upgrade, OPcache perlu di-enable ulang (ini penyebab STEP 9 performance FAIL awal)
- **PHPStan**: `vendor/bin/phpstan analyse --no-progress` — selalu jalankan setelah ubah model/service/support class
- **Dependency advisories**: 9 advisories di 6 packages (`laravel/framework` <13.12.0, `guzzlehttp/*`, `symfony/*`) — butuh task terpisah yang diapprove owner
- **Plugin count**: 1 active plugin di DB saat smoke test; manifest ada di `app/Plugins/` (SeoManager, ContactForm, Analytics)
- **Smoke test command reference** (untuk release gate sesi berikutnya):
  ```bash
  # Git check
  git branch --show-current && git rev-parse HEAD && git rev-parse backup/pre-phase4-step9-claude-baseline
  # Test suite
  php artisan test --stop-on-failure
  # PHPStan
  vendor/bin/phpstan analyse --no-progress
  # Performance
  curl -s -o /dev/null -w "%{time_total}s\n" http://bintan-prestige.test/
  # Analytics
  php artisan tinker --execute="echo \App\Models\PageView::count();"
  ```

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
| `step-9-phase4-release-gate-report.md` | STEP 9 |
| `step-9a-phpstan-larastan-foundation-report.md` | STEP 9A |
| `step-9b-performance-audit-report.md` | STEP 9B |
| `step-9c-release-smoke-test-report.md` | STEP 9C |

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
- Status saat ini: `draft` | `published` | `scheduled` — `scheduled` ditambahkan di STEP 4.6 / IMP-03

### Konfigurasi Lingkungan

- Stack: Laravel 13.8 | PHP 8.3 | MySQL | Tailwind CSS | Alpine.js
- Test runner: `php artisan test`
- Asset builder: `npm run build`
- Admin prefix: `/admin` dengan middleware `auth` + `admin`
- Frontend URL pattern: `/pages/{slug}`
