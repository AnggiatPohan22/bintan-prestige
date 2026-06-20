# STEP 9C — Manual Release Smoke Test

## Scope and Git context

- Date: 2026-06-21
- Working branch: `feature/phase-4-step9-release-gate`
- HEAD at time of test: `e2a3080bcd4e48923640086673e58cec1ddf395f`
- Claude baseline / restore branch: `backup/pre-phase4-step9-claude-baseline` at `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`
- Scope: read-only verification pass — no production code, test, config, schema, route, or dependency changed in STEP 9C.

## Prior gate status

| Gate | Status | Summary |
|---|---|---|
| STEP 9 — Release Gate (functional) | ✅ PASS | 596 tests, 2765 assertions, 0 failures; Q1–Q7 added; duplicate audit rows fixed |
| STEP 9A — PHPStan / Larastan | ✅ PASS | Level 5, 0 errors, 0 ignores, no baseline; Larastan 3.10.0 / PHPStan 2.2.2 |
| STEP 9B — Performance | ✅ PASS | OPcache enabled; all 4 routes < 300 ms average |
| **STEP 9C — Manual Smoke Test** | **✅ PASS** | All 14 checks passed — see results below |

## Environment

| Item | Value |
|---|---|
| OS | Windows 11 Home (10.0.26200) |
| Web server | Apache 2.4.66 via Laragon |
| PHP | 8.3.30 |
| Laravel | 13.11.2 |
| Database | MySQL (bintan_prestige) |
| OPcache | Enabled (128 MB, 10,000 file slots) — enabled in STEP 9B |
| Debug mode | ENABLED (local environment — normal) |
| Base URL | `http://bintan-prestige.test` |
| Config cache | NOT CACHED (normal for local dev) |
| Maintenance mode | OFF |

## Phase 2 — Git state verification

| Item | Expected | Actual | Status |
|---|---|---|---|
| Active branch | `feature/phase-4-step9-release-gate` | `feature/phase-4-step9-release-gate` | ✅ |
| HEAD commit | `e2a3080...` | `e2a3080bcd4e48923640086673e58cec1ddf395f` | ✅ |
| Restore branch hash | `ace347ec...` | `ace347ec0e4c6112d8e82672afb6b6429f1adaf1` | ✅ |
| Dirty working tree | Expected (STEP 9A+9B changes not yet committed) | All changes are STEP 9A+9B files — no unexpected dirty files | ✅ |

## Phase 3 — Pre-test checklist

| Check | Command | Result | Status |
|---|---|---|---|
| Web server alive | `curl http://bintan-prestige.test/` | HTTP 200 | ✅ |
| Laravel operational | `php artisan about` | Laravel 13.11.2 / PHP 8.3.30 / maintenance OFF | ✅ |
| Pending migrations | `php artisan migrate:status` | 0 pending — all `Ran` (last: `create_plugins_table`) | ✅ |
| Probe temp files in `public/` | `ls public/step9b-*` | None found | ✅ |
| Probe temp files in `storage/app/` | `ls storage/app/step9b-*` | None found | ✅ |

## Phase 4 — Smoke test results (4A–4L)

### 4A — Public route availability

| Route | HTTP Status | Response time | Status |
|---|---:|---:|---|
| `/` | 200 | 222 ms | ✅ |
| `/products` | 200 | 170 ms | ✅ |
| `/sitemap.xml` | 200 | 94 ms | ✅ |
| `/robots.txt` | 200 | 18 ms | ✅ |

**Result: PASS**

---

### 4B — Product detail route

- Slug used: `snorkeling-adventure-7484` (first `status = published` product in DB)
- HTTP 200, 212 ms
- `<title>` tag present in rendered HTML

**Result: PASS**

---

### 4C — Sitemap content validation

- XML declaration present: `<?xml version="1.0" encoding="UTF-8"?>`
- `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">` present
- `<loc>` entries: 4
- Laravel errors (Whoops / Exception / 500) in output: 0

**Result: PASS**

---

### 4D — Robots.txt content validation

- Content served:
  ```
  User-agent: *
  Disallow:
  ```
- `User-agent` directive present: yes (1 line)
- Format: valid standard robots.txt

**Result: PASS**

---

### 4E — Admin panel availability

| Route | Expected status | Actual status | Notes |
|---|---|---|---|
| `/admin` | — | 404 | Route does not exist — expected; entry point is `/admin/dashboard` |
| `/admin/login` | — | 404 | Route does not exist — expected; auth route is `/login` |
| `/admin/dashboard` | 302 | 302 | Redirect to `/login` — correct unauthenticated behavior |
| `/login` | 200 | 200 | Login page renders |
| Login form keywords | present | 10 matches (email/password/login) | Form renders correctly |

**Result: PASS**

> **INFO**: Admin panel entry point is `/admin/dashboard`, not `/admin`. Auth route is `/login`, not `/admin/login`. This is by design in the Laravel Breeze scaffold used by this project. Severity: **INFO** (no action required).

---

### 4F — Phase 4 admin routes

All routes verified via `php artisan route:list` and HTTP test (unauthenticated = 302 expected):

| Route | Registered | HTTP (unauth) | Status |
|---|---|---|---|
| `admin/plugins` | ✅ | 302 | ✅ |
| `admin/seo/redirects` | ✅ | 302 | ✅ |
| `admin/analytics` | ✅ | 302 | ✅ |
| `admin/audit-logs` | ✅ | 302 | ✅ |

Additional Phase 4 routes confirmed registered: `admin/plugins/scan`, `admin/plugins/{plugin}`, `admin/plugins/{plugin}/activate`, `admin/plugins/{plugin}/deactivate`, `admin/analytics/export`.

**Result: PASS**

---

### 4G — Full automated test suite

```text
php artisan test --stop-on-failure
Tests:  596 passed
Assertions: 2765
Failures: 0
Duration: ~46 s
```

Identical to STEP 9A reference (596 / 2765 / 0). No test removed, skipped, weakened, or rewritten.

**Result: PASS**

---

### 4H — PHPStan level 5 confirmation

```text
vendor/bin/phpstan analyse --no-progress
Effective level: 5
Analysis paths: app, routes
Errors: 0
Ignore rules: 0
Baseline: not used
```

**Result: PASS**

---

### 4I — Performance spot check

Method: 5 curl requests per route, `http://bintan-prestige.test`, all HTTP 200, one warm-up not included. Times in milliseconds.

| Route | Req 1 | Req 2 | Req 3 | Req 4 | Req 5 | Avg | STEP 9B avg | Target |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| `/` | 265 | 178 | 152 | 145 | 170 | **182 ms** | 185 ms | <300 ms ✅ |
| `/products` | 189 | 172 | 158 | 154 | 216 | **178 ms** | 164 ms | <300 ms ✅ |
| `/products/{slug}` | 143 | 154 | 150 | 125 | 149 | **144 ms** | 164 ms | <300 ms ✅ |
| `/sitemap.xml` | 77 | 100 | 80 | 85 | 98 | **88 ms** | 97 ms | <300 ms ✅ |

All routes within target. Variation vs STEP 9B is normal (±30 ms), no regression.

**Result: PASS**

---

### 4J — Plugin system check

```text
PluginManager: OK - class exists and resolves dari container
Active plugins: 1
Homepage after plugin boot: HTTP 200
```

`App\Services\Plugin\PluginManager` resolves from the IoC container without error. Plugin boot does not crash the application.

**Result: PASS**

---

### 4K — Global settings check

```text
SiteSetting: OK - 79 rows
```

> **INFO**: The global settings model is `App\Models\SiteSetting`, not `App\Models\GlobalSetting`. Class `GlobalSetting` does not exist. The CLAUDE.md and handoff doc references to "13 global settings modules" refer to logical groups within `SiteSetting` rows. Severity: **INFO** (naming discrepancy in documentation only — no code impact).

**Result: PASS**

---

### 4L — Analytics middleware check

```text
PageView count before request: 0
GET /pages/lagoi → HTTP 200
PageView count after request: 1
```

`TrackPageView` middleware fires on guest request to `pages.show` route (`/pages/{slug}`) and records 1 row in `page_views`. Middleware is not applied to `/` or `/products` (by design — analytics is page-level tracking only).

**Result: PASS**

---

## Issues found

| Severity | Area | Finding | Action |
|---|---|---|---|
| INFO | Admin routes | `/admin` and `/admin/login` return 404 — these routes do not exist. Entry point is `/admin/dashboard`; auth is `/login`. This is correct project behavior, not a bug. | Document only — no fix required |
| INFO | Model naming | `App\Models\GlobalSetting` does not exist; correct model is `App\Models\SiteSetting` (79 rows). Discrepancy is in documentation references only. | Correct naming in future docs |
| ADVISORY | Dependencies | `composer audit --locked` reports 9 advisories in 6 packages (`laravel/framework` <13.12.0, `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `symfony/http-foundation`, `symfony/polyfill-intl-idn`, `symfony/routing`). Not introduced by STEP 9. | Requires separately approved security-update task |

No BLOCKER issues found.

## Overall result

| Gate | Result |
|---|---|
| Git state | ✅ PASS |
| Pre-test checklist | ✅ PASS |
| Public routes (4A–4D) | ✅ PASS |
| Admin panel (4E–4F) | ✅ PASS |
| Automated suite (4G) | ✅ PASS — 596/2765/0 |
| PHPStan level 5 (4H) | ✅ PASS — 0 errors |
| Performance (4I) | ✅ PASS — all < 300 ms |
| System integrity (4J–4L) | ✅ PASS |
| **STEP 9C overall** | **✅ PASS** |

## Release recommendation

**GO**

All three Phase 4 release gate blockers are resolved:

1. ✅ STEP 9A — PHPStan level 5: 0 errors (Larastan 3.10.0 / PHPStan 2.2.2)
2. ✅ STEP 9B — Performance: all routes < 300 ms average
3. ✅ STEP 9C — Manual smoke test: all 14 checks passed

The automated suite (596 tests, 2765 assertions, 0 failures) and PHPStan (level 5, 0 errors) remain green. No regression introduced across STEP 9 → 9A → 9B → 9C.

**Pending before release integration (require owner approval):**

1. Commit STEP 9A + 9B + 9C changes to `feature/phase-4-step9-release-gate`
2. Merge `feature/phase-4-step9-release-gate` → `develop` → rerun full suite + PHPStan
3. Merge `develop` → `main` → rerun full suite + PHPStan
4. Create annotated tag `v4.0.0` pointing to final verified commit on `main`
5. Update `AGENTS.md` to mark Phase 4 complete
6. Security update task (dependency advisories) — separately scoped

These steps require keyword `approved release` per `AGENTS.override.md` Section 7.6 Approval Gate 2.

## Rollback

- Restore branch: `backup/pre-phase4-step9-claude-baseline` at `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`
- All STEP 9 changes are in the working tree (not committed) — a `git checkout` of affected files from the restore branch is sufficient if needed
- No schema, migration, route, or public contract was changed in STEP 9C
