# STEP 1 — Plugin Registry & Discovery

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 5 hari
**Status:** COMPLETE ✅

---

## 1. Scope

Implement the `PluginRegistry` service that scans `app/Plugins/*/plugin.json` manifests,
syncs discovered plugins to the `plugins` DB table, and caches the active plugin list for
use by STEP 2's bootstrap loader.

Create the three core plugin manifest stubs (`SeoManager`, `ContactForm`, `Analytics`)
that will be fully implemented in STEPs 5–7.

No plugin loading, activation, or admin UI is implemented at this step.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Services/Plugin/PluginRegistry.php` | Registry service: `sync()`, `all()`, `active()` (cached), `find()`, `forget()` |
| `app/Plugins/SeoManager/plugin.json` | SEO Manager manifest (slug: `seo-manager`, v1.0.0) |
| `app/Plugins/ContactForm/plugin.json` | Contact Form manifest (slug: `contact-form`, v1.0.0) |
| `app/Plugins/Analytics/plugin.json` | Analytics Dashboard manifest (slug: `analytics`, v1.0.0) |
| `tests/Feature/Phase4/PluginRegistryTest.php` | 20 tests (D1–D13) |

### Modified

| File | Change |
|------|--------|
| `tests/Feature/Phase4/Phase4BaselineCharacterizationTest.php` | Fixed C1 PSR-4 path assertion (was comparing `app/Plugins/` to the PSR-4 `App\\` root; should compare `app/`) |

---

## 3. PluginRegistry Service

**Namespace:** `App\Services\Plugin\PluginRegistry`
**Location:** `app/Services/Plugin/PluginRegistry.php`

### Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `ACTIVE_CACHE_KEY` | `'active_plugins'` | Cache key for active plugins collection |
| `CACHE_TTL` | `3600` | Cache lifetime in seconds (60 minutes) |

### Public API

#### sync(?string $root = null): array

Scans `app/Plugins/*/plugin.json`, compares against DB, and returns:

```php
[
    'installed' => ['seo-manager'],   // new, not in DB → Plugin::create()
    'updated'   => ['contact-form'],  // version mismatch → DB updated
    'unchanged' => ['analytics'],     // same version, no action
    'orphaned'  => ['old-plugin'],    // in DB but dir/manifest missing
]
```

- Orphaned plugins are **not deleted** from DB (preserves config and active state)
- Calls `forget()` at the end to clear stale active-plugin cache
- Returns empty arrays if `$root` directory does not exist

#### all(): Collection

Returns all plugins from DB, ordered by `name`.

#### active(): Collection

Returns active plugins from DB, cached under `ACTIVE_CACHE_KEY` for `CACHE_TTL` seconds.
Used by STEP 2's `AppServiceProvider::register()` to load providers on boot.

#### find(string $slug): ?Plugin

Returns Plugin by slug or null.

#### forget(): void

Forgets the `ACTIVE_CACHE_KEY` cache entry. Called automatically by `sync()` and must
also be called by `PluginManager::activate()` / `deactivate()` in STEP 2.

### Manifest Validation

All four required fields must be present and non-empty strings:

```
name  ·  slug  ·  version  ·  service_provider
```

Skipped manifests: invalid JSON, missing required field, unreadable file.
Each skip is logged at `Log::info()` level with the manifest path.

---

## 4. Plugin Manifest Stubs

Three stubs created. Each provides the minimum valid manifest; the PHP classes (ServiceProvider,
Controllers, Models) will be created in the corresponding implementation STEPs.

| Plugin | Slug | STEP |
|--------|------|------|
| SEO Manager | `seo-manager` | STEP 5 |
| Contact Form Builder | `contact-form` | STEP 6 |
| Analytics Dashboard | `analytics` | STEP 7 |

---

## 5. Discovery Flow Diagram

```
Admin triggers PluginRegistry::sync()
          │
          ▼
    Scan app/Plugins/*/plugin.json
          │
          ├─ Read manifest ──► Invalid JSON? → skip + log
          │                    Missing fields? → skip + log
          │
          ▼
    Compare against DB (Plugin::all()->keyBy('slug'))
          │
          ├─ Not in DB        → Plugin::create()  → 'installed'
          ├─ Version mismatch → Plugin::update()  → 'updated'
          ├─ Same version     → no action         → 'unchanged'
          │
          ▼
    Slugs in DB with no matching manifest → 'orphaned' (NOT deleted)
          │
          ▼
    Cache::forget(ACTIVE_CACHE_KEY)
          │
          ▼
    Return result array
```

---

## 6. Test Coverage (D1–D13)

| Test | Contract |
|------|----------|
| D1 | `sync()` discovers new plugin → DB record created |
| D1b | `sync()` discovers multiple plugins from same directory |
| D2 | `sync()` updates version when manifest bumped |
| D3 | `sync()` reports unchanged for same-version plugin |
| D4 | `sync()` reports orphaned for DB plugin with no manifest |
| D5 | `sync()` skips invalid JSON |
| D6a | `sync()` skips manifest missing `name` |
| D6b | `sync()` skips manifest missing `slug` |
| D6c | `sync()` skips manifest missing `version` |
| D6d | `sync()` skips manifest missing `service_provider` |
| D7 | `sync()` returns empty result for nonexistent directory |
| D8 | `sync()` discovers all 3 real `app/Plugins` manifests |
| D9 | `sync()` never activates any discovered plugin |
| D10 | `all()` returns all plugins ordered by name |
| D11a | `active()` returns only `is_active = true` plugins |
| D11b | `active()` result is stored in cache |
| D12a | `find()` returns plugin by slug |
| D12b | `find()` returns null for unknown slug |
| D13a | `forget()` clears cache |
| D13b | `sync()` clears cache after any change |

---

## 7. Test Results

```
php artisan test tests/Feature/Phase4/PluginRegistryTest.php
Tests:  20 passed
Assertions: 45

php artisan test tests/Feature/Phase4/
Tests:  40 passed
Assertions: 99

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
Assertions: 253
```

---

## 8. Impact Summary

| Area | Impact |
|------|--------|
| DB | `plugins` table already exists (created STEP 0); `sync()` populates it |
| Routes | None (admin plugin UI built in STEP 4) |
| Frontend (public) | None |
| Plugin loading | None yet (STEP 2) |
| Cache | `active_plugins` key managed by `forget()` / `active()` |
| Tests | +20 passing tests |

---

## 9. Rollback

```bash
git revert HEAD
```

---

## 10. Next Step

**STEP 1.5 — IMP-05: Theme Export & Import (ZIP)** — shared ZIP handling infrastructure,
then **STEP 2: Plugin Loader & Lifecycle**.
