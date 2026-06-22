# STAGE C3 — Functional Smoke Test
# Bintan Prestige CMS — Phase 5 Release Audit
# New Claude Code session — paste this prompt and run

---

## Siapa kamu dan apa tugasmu

Kamu adalah Claude Code yang menjalankan **Stage C3 (Functional Smoke Test)** untuk Phase 5.
Ini sesi baru. Kamu tidak punya memori sesi sebelumnya.

Phase 5 terdiri dari tiga stage:
- Stage A (A1–A5 Foundation Hardening): **COMPLETE ✅**
- Stage B (B0–B7 Visual Page Builder): **COMPLETE ✅**
- Stage C (C1–C4 Release Audit): **IN PROGRESS**
  - C1 Static Analysis & Code Quality: **COMPLETE ✅**
  - C2 Performance Audit: **COMPLETE ✅**
  - **C3 Functional Smoke Test ← SESI INI**
  - C4 Architecture Documentation & Handoff: PENDING

Tugas sesi ini ada dua, BERURUTAN:
1. Jalankan C3 — Functional Smoke Test
2. Update `ai/reports/phase-5/phase-5-progress-handoff.md` dengan hasil C3

**ATURAN WAJIB:** Setiap selesai task di Stage C, file
`ai/reports/phase-5/phase-5-progress-handoff.md` HARUS di-update.
File ini adalah sumber kebenaran (source of truth) Phase 5 untuk semua
development selanjutnya.

---

## Aturan wajib sesi ini

**DILARANG:**
- Mengubah database schema atau migration
- Rename route, controller, model, middleware, atau config key
- Mengubah URL publik atau admin URL path
- Menghapus fitur, block type, atau fungsionalitas apapun
- Install package baru tanpa approval
- Melemahkan atau menghapus test
- Menurunkan PHPStan level
- Commit atau push otomatis

**BOLEH:**
- Membaca semua file source code
- Menjalankan artisan, PHPStan, test, dan HTTP check commands
- Membuat dan update file di `ai/reports/phase-5/`
- Commit file report dan handoff (TIDAK push)

---

## Konteks penting sebelum mulai

### Stack & Environment
- **Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
- **Working dir:** `C:\laragon\www\bintan-prestige`
- **Branch:** `feature/phase-5-stage-b-visual-builder`
- **HEAD sebelum C3:** `d06ccf5`
- **Server:** Gunakan `php artisan serve --port=8765` untuk HTTP checks
  (Laragon mungkin tidak running saat sesi ini dimulai)

### Hasil C1 (Static Analysis) — referensi
- PHPStan level 5: **0 errors** ✅
- Test suite: **627 tests / 3206 assertions / 0 failures** ✅
- No dead code, no debug statements, no TODO/FIXME

### Hasil C2 (Performance) — referensi
- Semua warm-run public routes: **≤ 300ms** ✅
- DB queries: **3–8 per page render, no N+1** ✅
- Semua Phase 5 DB indexes verified ✅

### Data yang tersedia di database (gunakan untuk HTTP checks)
- **Published pages:** `lagoi` (id:1), `tanjungpinang` (id:3), `bintan` (id:4), `destinations` (id:5)
- **Draft page:** `blog` (id:6) — harus return 404 di public route
- **Published product slug:** `package-test-1-6a1c3602cff72`
- **Builder patterns di DB:** 1 record
- **Builder templates di DB:** 0 records

### Apa yang dibangun di Phase 5 (yang harus dites)
```
Stage A:
  - 8 block types baru: heading, stats, button_group, columns, group,
    tour_itinerary, pricing_table, video_embed (+ frontend render masing-masing)
  - Admin UX refactor: sidebar, data-table, form-shell, publish-box, command-palette
  - Efficiency fixes: eager loading, N+1 resolution

Stage B:
  - Visual builder UI: GET /admin/pages/{page}/builder
  - Save tree: POST /admin/pages/{page}/blocks/save-tree
  - Preview payload: POST /admin/pages/{page}/preview-payload
  - Builder patterns API: GET/POST/GET/DELETE /admin/builder-patterns/*
  - Builder templates API: GET/GET/POST/DELETE /admin/builder-templates/*
  - InlineContentSanitizer (richtext + plaintext sanitization)
  - BuilderTreeSanitizer (shared tree validator)
  - Responsive preview: hide_desktop / hide_tablet / hide_mobile per block
```

---

## FASE 1 — Baca konteks (JANGAN SKIP)

```bash
# Git state
git branch --show-current
git status --short
git log -5 --oneline --decorate

# Baca handoff doc (source of truth)
cat ai/reports/phase-5/phase-5-progress-handoff.md

# Baca summary C1 dan C2
head -60 ai/reports/phase-5/c1-static-analysis-code-quality-report.md
head -60 ai/reports/phase-5/c2-performance-audit-report.md
```

Tulis ringkasan:
```
=== KONTEKS C3 ===
Branch       : [branch name]
HEAD         : [hash]
C1 status    : DONE — PHPStan 0 errors, 627 tests / 0 failures
C2 status    : DONE — all routes ≤300ms, no N+1
C3 status    : STARTING
Published pages available: lagoi, tanjungpinang, bintan, destinations
Draft page (should 404): blog
===
```

---

## FASE 2 — Jalankan automated test suite (C3A)

```bash
php artisan test 2>&1 | tail -20
```

Catat:
- Total tests (referensi C1: 627)
- Total assertions (referensi C1: 3206)
- Failures (harus 0)
- Waktu eksekusi

Jika ada failure baru (yang tidak ada di C1), ini adalah **blocker** —
investigate sebelum lanjut.

---

## FASE 3 — Jalankan PHPStan (C3B)

```bash
vendor/bin/phpstan analyse --no-progress 2>&1
```

Harus: 0 errors. Jika ada error baru (yang tidak ada di C1), ini adalah **blocker**.

---

## FASE 4 — HTTP Route Smoke Test (C3C)

Start server:
```powershell
# PowerShell — jalankan di background
Start-Process php -ArgumentList "artisan","serve","--port=8765" -WindowStyle Hidden
Start-Sleep -Seconds 3
```

Verify server up:
```powershell
Invoke-WebRequest "http://127.0.0.1:8765" -TimeoutSec 5 -UseBasicParsing | Select-Object StatusCode
```

### 4A — Public routes (harus 200)

Test setiap route berikut. Catat status code dan pastikan TIDAK ada 500:

```powershell
$base = "http://127.0.0.1:8765"
$checks = @(
    @{ route="/"; expect=200 },
    @{ route="/products"; expect=200 },
    @{ route="/products/package-test-1-6a1c3602cff72"; expect=200 },
    @{ route="/pages/lagoi"; expect=200 },
    @{ route="/pages/tanjungpinang"; expect=200 },
    @{ route="/pages/bintan"; expect=200 },
    @{ route="/pages/destinations"; expect=200 },
    @{ route="/sitemap.xml"; expect=200 },
    @{ route="/robots.txt"; expect=200 }
)
foreach ($c in $checks) {
    try {
        $r = Invoke-WebRequest "$base$($c.route)" -TimeoutSec 15 -UseBasicParsing -ErrorAction Stop
        $ok = if ($r.StatusCode -eq $c.expect) { "PASS" } else { "FAIL (got $($r.StatusCode))" }
        Write-Host "$ok  $($r.StatusCode)  $($c.route)"
    } catch {
        Write-Host "ERR   $($c.route)  $($_.Exception.Message -split '\n' | Select-Object -First 1)"
    }
    Start-Sleep -Milliseconds 200
}
```

### 4B — Draft page harus return 404 (bukan 200)

```powershell
$base = "http://127.0.0.1:8765"
try {
    $r = Invoke-WebRequest "$base/pages/blog" -TimeoutSec 10 -UseBasicParsing -ErrorAction Stop
    Write-Host "FAIL — expected 404, got $($r.StatusCode)"
} catch {
    $msg = $_.Exception.Message
    if ($msg -match "404") { Write-Host "PASS — /pages/blog returns 404 (draft page correctly blocked)" }
    else { Write-Host "UNEXPECTED — $msg" }
}
```

### 4C — Admin routes (unauthenticated harus 302 atau 401, BUKAN 500)

```powershell
$base = "http://127.0.0.1:8765"
$adminRoutes = @(
    "/admin/dashboard",
    "/admin/pages",
    "/admin/pages/1/builder",
    "/admin/builder-patterns",
    "/admin/builder-templates"
)
foreach ($route in $adminRoutes) {
    try {
        $r = Invoke-WebRequest "$base$route" -TimeoutSec 10 -UseBasicParsing -MaximumRedirection 0
        Write-Host "STATUS $($r.StatusCode)  $route"
    } catch {
        $msg = $_.Exception.Message -split '\n' | Select-Object -First 1
        if ($msg -match "302|redirect") { Write-Host "PASS (302)  $route" }
        elseif ($msg -match "401") { Write-Host "PASS (401)  $route" }
        elseif ($msg -match "MaximumRedirect") { Write-Host "PASS (302→redirect chain)  $route" }
        else { Write-Host "CHECK  $route  $msg" }
    }
    Start-Sleep -Milliseconds 100
}
```

**PASS criteria:** Status adalah 302/redirect. 
**FAIL criteria:** Status adalah 500 atau 404 untuk route yang seharusnya ada.

### 4D — Builder JSON API (unauthenticated harus 302/401, BUKAN 500)

```powershell
$base = "http://127.0.0.1:8765"
$apiRoutes = @(
    "/admin/api/block-types",
    "/admin/pages/1/preview-payload"  # POST, so GET should 405 or redirect
)
foreach ($route in $apiRoutes) {
    try {
        $r = Invoke-WebRequest "$base$route" -TimeoutSec 10 -UseBasicParsing -MaximumRedirection 0
        Write-Host "STATUS $($r.StatusCode)  $route"
    } catch {
        $msg = $_.Exception.Message -split '\n' | Select-Object -First 1
        Write-Host "  $route  $msg"
    }
    Start-Sleep -Milliseconds 100
}
```

Stop server setelah semua HTTP checks selesai:
```powershell
Get-Process php -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -match "artisan" } | Stop-Process -Force
```

---

## FASE 5 — Targeted feature tests (C3D)

### 5A — Test suite yang fokus ke Phase 5 features

```bash
# Builder core
php artisan test --filter="Builder" 2>&1 | tail -10

# Page block management (termasuk Phase 5 additions)
php artisan test --filter="PageBlock" 2>&1 | tail -10

# Page management (create/edit/publish/delete)
php artisan test --filter="PageManagement" 2>&1 | tail -10

# Frontend page rendering (termasuk block rendering)
php artisan test --filter="GenericPageRendering" 2>&1 | tail -10

# Phase 4 regression (harus tetap passing)
php artisan test --filter="Phase4" 2>&1 | tail -10
```

Catat setiap failure. Semua harus PASS.

### 5B — Block rendering integrity check via tinker

Verifikasi bahwa semua 19 block types terdaftar di config dan bisa di-load:

```bash
php artisan tinker --execute="
\$blocks = config('blocks');
echo 'Block types registered: '.count(\$blocks).PHP_EOL;
foreach(array_keys(\$blocks) as \$type) { echo '  - '.\$type.PHP_EOL; }
" 2>&1
```

Expected: **19 block types**:
`group, columns, hero, heading, text, image, gallery, video_embed, button_group, stats,
tour_itinerary, pricing_table, cta, products_grid, faq, testimonials, map, divider, contact_form`

### 5C — Frontend block view files exist

Verifikasi file Blade frontend block ada untuk semua block types:

```bash
ls resources/views/frontend/blocks/
```

Expected: semua block types punya file render di `resources/views/frontend/blocks/`.

Catatan naming: Laravel pakai kebab-case untuk blade files.
Map yang benar:
```
group.blade.php
columns.blade.php
hero.blade.php
heading.blade.php
text.blade.php
image.blade.php
gallery.blade.php
video-embed.blade.php       ← video_embed
button-group.blade.php      ← button_group
stats.blade.php
tour-itinerary.blade.php    ← tour_itinerary
pricing-table.blade.php     ← pricing_table
cta.blade.php
products.blade.php          ← products_grid
faq.blade.php
testimonials.blade.php
map.blade.php
divider.blade.php
contact-form.blade.php      ← contact_form
```

### 5D — InlineContentSanitizer functional check

```bash
php artisan tinker --execute="
use App\Support\InlineContentSanitizer;

// Plaintext: strips all HTML
\$r1 = InlineContentSanitizer::plaintext('<b>Hello</b> <script>alert(1)</script>World');
echo 'Plaintext: ['.\$r1.']'.PHP_EOL;
// Expected: [Hello World]

// Richtext: allows safe tags, strips dangerous
\$r2 = InlineContentSanitizer::richtext('<p>Hello <strong>world</strong></p><script>alert(1)</script>');
echo 'Richtext (safe): ['.\$r2.']'.PHP_EOL;
// Expected: [<p>Hello <strong>world</strong></p>]

// Richtext: strips javascript: href
\$r3 = InlineContentSanitizer::richtext('<a href=\"javascript:alert(1)\">click</a>');
echo 'Richtext (js href stripped): ['.\$r3.']'.PHP_EOL;
// Expected: [<a>click</a>] or [click] — no javascript: href

// Richtext: allows http href
\$r4 = InlineContentSanitizer::richtext('<a href=\"https://example.com\" target=\"_blank\">link</a>');
echo 'Richtext (https href): ['.\$r4.']'.PHP_EOL;
// Expected: contains href=\"https://example.com\" and rel=\"noopener noreferrer\"
" 2>&1
```

### 5E — BuilderTreeSanitizer functional check

```bash
php artisan tinker --execute="
use App\Services\BuilderTreeSanitizer;
\$sanitizer = app(BuilderTreeSanitizer::class);

// Valid tree
\$tree = \$sanitizer->sanitizeTree([
    ['block_type'=>'heading','label'=>'Test','data'=>['text'=>'Hello','level'=>'h2'],'is_visible'=>true,'sort_order'=>0,'children'=>[]]
]);
echo 'Valid tree: '.count(\$tree).' nodes'.PHP_EOL;

// Nested valid tree
\$nested = \$sanitizer->sanitizeTree([
    ['block_type'=>'group','label'=>'Section','data'=>[],'is_visible'=>true,'sort_order'=>0,'children'=>[
        ['block_type'=>'heading','label'=>'Child','data'=>['text'=>'Child heading'],'is_visible'=>true,'sort_order'=>0,'children'=>[]]
    ]]
]);
echo 'Nested tree: '.\$nested[0]['block_type'].' with '.count(\$nested[0]['children']).' children'.PHP_EOL;
" 2>&1
```

### 5F — BlockStyle sanitization check

```bash
php artisan tinker --execute="
use App\Support\BlockStyle;

// CSS ID: only alphanumeric + dash/underscore, must start with letter
echo BlockStyle::cssId('my-section') . PHP_EOL;     // my-section
echo BlockStyle::cssId('123invalid') . PHP_EOL;      // null (starts with number)
echo BlockStyle::cssId('<script>x') . PHP_EOL;       // (empty or null — stripped)

// Custom CSS: strips < and >
\$css = BlockStyle::customCss('.hero { color: red; } <script>alert(1)</script>');
echo 'CSS (no angle brackets): ['.str_contains(\$css,'<') ? 'FAIL has <' : 'PASS no angle brackets'.']'.PHP_EOL;
" 2>&1
```

### 5G — Phase 5 migration status

```bash
php artisan migrate:status 2>&1 | grep -E "parent_block|builder_pattern|builder_template"
```

Expected: semua tiga Phase 5 migrations status **Ran**:
- `2026_06_21_000003_add_parent_block_id_to_page_blocks_table`
- `2026_06_22_000004_create_builder_patterns_table`
- `2026_06_22_000005_create_builder_templates_table`

### 5H — View cache integrity

```bash
php artisan view:cache 2>&1
```

Harus: "Blade templates cached successfully." tanpa error.

---

## FASE 6 — Regression check (C3E)

Verifikasi Phase 1–4 features tidak rusak:

```bash
# Full suite satu kali lagi untuk konfirmasi final
php artisan test 2>&1 | tail -5
```

```bash
# Spot check routes Phase 1-4 yang tidak termasuk di atas
php artisan tinker --execute="
// Phase 1: Products exist
echo 'Products: '.\App\Models\Product::where('status','published')->count().PHP_EOL;
// Phase 2: Pages exist
echo 'Published pages: '.\App\Models\Page::where('status','published')->count().PHP_EOL;
// Phase 3: Theme exists
echo 'Active theme: '.(\App\Models\Theme::where('is_active',true)->value('name') ?? 'none').PHP_EOL;
// Phase 4: Plugins table accessible
echo 'Plugins table: '.\App\Models\Plugin::count().' rows'.PHP_EOL;
// Phase 5: Builder patterns/templates tables accessible
echo 'Builder patterns: '.\App\Models\BuilderPattern::count().PHP_EOL;
echo 'Builder templates: '.\App\Models\BuilderTemplate::count().PHP_EOL;
" 2>&1
```

---

## FASE 7 — Buat C3 report

Buat file: `ai/reports/phase-5/c3-smoke-test-report.md`

```markdown
# C3 — Functional Smoke Test Report
## Phase 5 Release Audit
## Date: [tanggal]
## Branch: [branch name]
## HEAD: [commit hash]

---

## 1. Automated Test Suite (C3A)

- Total tests: [n] (C1 reference: 627)
- Total assertions: [n] (C1 reference: 3206)
- Failures: [n] (must be 0)
- Duration: [n]s
- Status: PASS / FAIL

---

## 2. PHPStan (C3B)

- Level: 5
- Errors: [n] (must be 0)
- Status: PASS / FAIL

---

## 3. HTTP Route Smoke Test (C3C)

### 3A — Public routes

| Route | Expected | Got | Status |
|-------|----------|-----|--------|
| `/` | 200 | [n] | PASS/FAIL |
| `/products` | 200 | [n] | PASS/FAIL |
| `/products/package-test-1-6a1c3602cff72` | 200 | [n] | PASS/FAIL |
| `/pages/lagoi` | 200 | [n] | PASS/FAIL |
| `/pages/tanjungpinang` | 200 | [n] | PASS/FAIL |
| `/pages/bintan` | 200 | [n] | PASS/FAIL |
| `/pages/destinations` | 200 | [n] | PASS/FAIL |
| `/sitemap.xml` | 200 | [n] | PASS/FAIL |
| `/robots.txt` | 200 | [n] | PASS/FAIL |

### 3B — Draft page access control

| Route | Expected | Got | Status |
|-------|----------|-----|--------|
| `/pages/blog` | 404 | [n] | PASS/FAIL |

### 3C — Admin routes (unauthenticated)

| Route | Expected | Got | Status |
|-------|----------|-----|--------|
| `/admin/dashboard` | 302 | [n] | PASS/FAIL |
| `/admin/pages` | 302 | [n] | PASS/FAIL |
| `/admin/pages/1/builder` | 302 | [n] | PASS/FAIL |
| `/admin/builder-patterns` | 302 | [n] | PASS/FAIL |
| `/admin/builder-templates` | 302 | [n] | PASS/FAIL |

---

## 4. Feature Tests (C3D)

### 4A — Targeted test filters

| Filter | Tests | Failures | Status |
|--------|-------|----------|--------|
| `--filter=Builder` | [n] | 0 | PASS/FAIL |
| `--filter=PageBlock` | [n] | 0 | PASS/FAIL |
| `--filter=PageManagement` | [n] | 0 | PASS/FAIL |
| `--filter=GenericPageRendering` | [n] | 0 | PASS/FAIL |
| `--filter=Phase4` | [n] | 0 | PASS/FAIL |

### 4B — Block registry

- Block types registered: [n] (expected: 19)
- All 19 types present: YES / NO (list missing if any)

### 4C — Frontend block view files

- All block views present: YES / NO (list missing if any)

### 4D — InlineContentSanitizer

| Test | Expected | Result | Status |
|------|----------|--------|--------|
| plaintext strips HTML | `Hello World` | [output] | PASS/FAIL |
| richtext allows safe tags | `<p>...<strong>...</strong></p>` | [output] | PASS/FAIL |
| richtext strips `javascript:` href | no javascript: | [output] | PASS/FAIL |
| richtext allows https href + rel noopener | href present, rel added | [output] | PASS/FAIL |

### 4E — BuilderTreeSanitizer

| Test | Result | Status |
|------|--------|--------|
| Valid single node → 1 node output | [output] | PASS/FAIL |
| Valid nested tree → group with 1 child | [output] | PASS/FAIL |

### 4F — BlockStyle sanitization

| Test | Result | Status |
|------|--------|--------|
| Valid CSS ID passes | [output] | PASS/FAIL |
| Numeric-start CSS ID returns null | [output] | PASS/FAIL |
| Custom CSS strips angle brackets | [output] | PASS/FAIL |

### 4G — Phase 5 migrations

| Migration | Status |
|-----------|--------|
| `2026_06_21_000003_add_parent_block_id_to_page_blocks_table` | Ran / MISSING |
| `2026_06_22_000004_create_builder_patterns_table` | Ran / MISSING |
| `2026_06_22_000005_create_builder_templates_table` | Ran / MISSING |

### 4H — View cache

- `php artisan view:cache`: PASS / ERROR

---

## 5. Regression Check (C3E)

| Check | Result | Status |
|-------|--------|--------|
| Published products | [n] | PASS/FAIL |
| Published pages | [n] | PASS/FAIL |
| Active theme | [name or 'none'] | PASS/FAIL |
| Plugins table accessible | [n rows] | PASS/FAIL |
| Builder patterns table | [n rows] | PASS/FAIL |
| Builder templates table | [n rows] | PASS/FAIL |

---

## 6. Summary

| Check | Status | Failures | Notes |
|-------|--------|----------|-------|
| Test suite (C3A) | PASS/FAIL | [n] | — |
| PHPStan (C3B) | PASS/FAIL | [n] | — |
| Public routes (C3C-A) | PASS/FAIL | [n] | — |
| Draft page 404 (C3C-B) | PASS/FAIL | — | — |
| Admin routes secure (C3C-C) | PASS/FAIL | [n] | — |
| Feature tests (C3D-A) | PASS/FAIL | [n] | — |
| Block registry (C3D-B) | PASS/FAIL | — | — |
| Block views (C3D-C) | PASS/FAIL | — | — |
| Sanitizers (C3D-D/E/F) | PASS/FAIL | — | — |
| Migrations (C3D-G) | PASS/FAIL | — | — |
| Phase 1-4 regression (C3E) | PASS/FAIL | — | — |

### C3 Overall: PASS / PARTIAL / FAIL

### Blockers for release: [list atau "none"]

---

## 7. Issues found (jika ada)

[list atau "none — all checks passed"]

---

## 8. Manual QA items (tidak bisa di-automate)

Items berikut memerlukan browser + admin login dan TIDAK bisa diverifikasi dari CLI:

| # | Check | Area | Priority |
|---|-------|------|----------|
| MQ-1 | Builder opens tanpa error, canvas renders | Builder UI | HIGH |
| MQ-2 | Drag-drop reorder blocks berfungsi | Builder B2 | HIGH |
| MQ-3 | Block settings panel terbuka, field tersimpan | Builder B3 | HIGH |
| MQ-4 | Inline edit di canvas (heading, text) tersimpan | Builder B4 | HIGH |
| MQ-5 | Save pattern, load pattern di builder | Builder B5 | HIGH |
| MQ-6 | Save as template, apply template | Builder B6 | HIGH |
| MQ-7 | Device toggle Desktop/Tablet/Mobile, status bar tampil | Builder B7 | MEDIUM |
| MQ-8 | Hide-on-mobile badge tampil di block list | Builder B7 | MEDIUM |
| MQ-9 | Preview iframe refresh saat block diubah | Builder B3 | HIGH |
| MQ-10 | Admin sidebar groups dan item benar | Admin A2 | LOW |
```

---

## FASE 8 — Update phase-5-progress-handoff.md

Update Section 9 (Stage C — Release Audit Progress):

```markdown
### C3 — Functional Smoke Test
- Status: ✅ DONE — [tanggal]
- Test suite: [n] tests / [n] assertions / [n] failures
- HTTP routes: [n]/[n] public routes PASS, draft 404 PASS, admin routes secure PASS
- Feature checks: block registry [n] types, sanitizers PASS, migrations PASS, view cache PASS
- Regression: Phase 1-4 features intact
- Manual QA: [n] items documented (require browser + admin login)
- Blockers: [list atau "none"]
- Report: c3-smoke-test-report.md
```

Update Section 1 (Phase 5 Overall Status):
```markdown
| C | C3 Functional Smoke Test | ✅ DONE | c3-smoke-test-report.md |
```

Update Section 11 (Release Gate Status):
```markdown
| Smoke test | [PASS/FAIL] | [n] checks passed, [n] manual QA items pending |
```

---

## FASE 9 — Commit

```bash
git add ai/reports/phase-5/c3-smoke-test-report.md
git add ai/reports/phase-5/phase-5-progress-handoff.md
git status --short
```

Pastikan hanya 2 file tersebut yang ter-stage.

```bash
git commit -m "audit: Phase 5 Stage C3 — functional smoke test

- Test suite: [n] tests, [n] assertions, 0 failures
- HTTP smoke: [n]/[n] public routes PASS, admin routes secure
- Feature checks: 19 block types, sanitizers, migrations, view cache — all PASS
- Regression: Phase 1-4 intact
- [n] manual QA items documented (require browser)
- C3 overall: PASS / PARTIAL / FAIL"
```

Jangan push. Owner akan review.

---

## FASE 10 — Output final

```
================================================
STAGE C3 — FUNCTIONAL SMOKE TEST COMPLETE
================================================
Branch              : [branch name]
HEAD                : [hash]

Automated Test Suite:
  Tests             : [n] (C1 ref: 627)
  Assertions        : [n] (C1 ref: 3206)
  Failures          : [n] (must be 0)
  Status            : PASS / FAIL

PHPStan:
  Errors            : [n] (must be 0)
  Status            : PASS / FAIL

HTTP Smoke Test:
  Public routes     : [n]/[n] PASS
  Draft 404 guard   : PASS / FAIL
  Admin auth guard  : [n]/[n] PASS (302 on unauthenticated)
  Status            : PASS / FAIL

Feature Checks:
  Block registry    : [n]/19 types registered
  Block views       : all present YES/NO
  InlineContentSanitizer  : PASS / FAIL
  BuilderTreeSanitizer    : PASS / FAIL
  BlockStyle sanitizer    : PASS / FAIL
  Phase 5 migrations      : all Ran YES/NO
  View cache              : PASS / FAIL

Regression:
  Phase 1-4 features      : intact YES/NO
  Status                  : PASS / FAIL

Manual QA items (pending browser):
  [n] items documented in report

C3 Overall          : PASS / PARTIAL / FAIL
Blockers            : [list atau "none"]

Reports:
  C3 report : ai/reports/phase-5/c3-smoke-test-report.md
  Handoff   : ai/reports/phase-5/phase-5-progress-handoff.md (UPDATED)

Next step: C4 — Architecture Documentation & Handoff
================================================
```

STOP. Jangan push. Owner review dulu.
