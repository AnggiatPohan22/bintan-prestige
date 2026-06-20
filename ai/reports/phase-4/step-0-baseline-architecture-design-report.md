# STEP 0 — Baseline & Architecture Design

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 5 hari
**Status:** COMPLETE ✅

---

## 1. Scope

Lock Phase 1–3 contracts with characterization tests, design and document the plugin system
architecture (ADR), create the `plugins` database table, establish the `app/Plugins/`
namespace root, and define the `plugin.json` manifest format.

No plugin loading logic is implemented at this step — that begins at STEP 1.

---

## 2. Schema Change

### New table: `plugins`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | auto-increment |
| `name` | VARCHAR(255) | Human-readable plugin name |
| `slug` | VARCHAR(255) UNIQUE | Machine identifier (kebab-case) |
| `version` | VARCHAR(255) DEFAULT '1.0.0' | Semver string |
| `author` | VARCHAR(255) NULL | Optional author name |
| `description` | TEXT NULL | Optional description |
| `is_active` | BOOLEAN DEFAULT false | Whether the plugin is currently loaded |
| `config` | JSON NULL | Plugin-specific settings stored by admin |
| `installed_at` | TIMESTAMP NULL | When the plugin was first discovered |
| `activated_at` | TIMESTAMP NULL | When the plugin was last activated |
| `created_at` / `updated_at` | TIMESTAMP | Standard Laravel timestamps |

**Index:** `idx_plugins_active` on `is_active` (STEP 2 will query this on every boot)

Migration: `2026_06_23_000002_create_plugins_table.php`

---

## 3. Files Changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_23_000002_create_plugins_table.php` | Creates `plugins` table |
| `app/Models/Plugin.php` | Eloquent model; `scopeActive()`, `isActive()`, JSON cast on `config`, datetime casts on `installed_at`/`activated_at` |
| `app/Plugins/.gitkeep` | Establishes `app/Plugins/` directory as the plugin namespace root |
| `docs/architecture/ADR-001-plugin-system.md` | Architecture Decision Record — plugin manifest format, hook system API, directory structure, alternatives rejected |
| `tests/Feature/Phase4/Phase4BaselineCharacterizationTest.php` | 15 tests locking 6 contracts (see Section 4 below) |

---

## 4. Baseline Contracts (C1–C6)

### C1 — Plugin directory

- `app/Plugins/` exists on disk and is writable
- PSR-4 `App\\` root contains `app/Plugins/` as a subdirectory (so `App\Plugins\*` autoloads)

### C2 — AuditLog::record() contract

- Creates a DB entry when user is authenticated
- No-op when unauthenticated (safe for seeders, jobs, observers during tests)
- Resolves `auditable_label` from `title` field automatically
- Stores only changed fields in `old_values` / `new_values`

### C3 — Admin route middleware

- Guest → redirect to login on all Phase 1–3 admin routes
- Non-admin authenticated user → HTTP 403
- Admin user → HTTP 200

Routes tested: `admin.pages.index`, `admin.themes.index`, `admin.menus.index`,
`admin.products.index`, `admin.audit-logs.index`

### C4 — Cache key isolation

Phase 4 planned cache keys do not collide with Phase 1–3 keys:

| Phase 4 key | Must not equal |
|-------------|---------------|
| `active_plugins` | `global_settings.public.v1`, `global_assets.public.v1`, `theme.active.v1` |
| `plugin.registry.v1` | same |

### C5 — Plugin table schema

- `plugins` table exists after `RefreshDatabase` runs all migrations
- All 12 columns present
- `Plugin::create([...])` works with all fillable fields
- `is_active` defaults to `false`
- Duplicate slug raises `QueryException`
- `config` field is cast to `array`

### C6 — Regression baseline

At least 45 Phase 1–3 test files exist across all test directories, confirming no
tests were deleted or lost between phases.

---

## 5. Architecture Decision Record Summary

Full ADR at: `docs/architecture/ADR-001-plugin-system.md`

**Decision:** Build a plugin system using:
- **Discovery:** `app/Plugins/{Name}/plugin.json` manifest files
- **Registration:** `plugins` DB table tracking installed + active state
- **Loading:** Active plugin ServiceProviders registered in `AppServiceProvider::register()`
- **Extension points:** `CmsHooks` facade with `addAction()` / `doAction()` / `addFilter()` / `applyFilters()`
- **Admin UI:** `/admin/plugins` — activate, deactivate, settings
- **Security:** PHP token scanner before activation; permission scopes in manifest

**Alternatives rejected:**

| Alternative | Reason |
|-------------|--------|
| Laravel Packages via Composer | Too heavy; forces a publish/release cycle |
| Event listeners only (no manifest) | No on/off toggle, no security scanning, no admin UI |
| Hard-coded feature flags | Doesn't scale; every flag requires a deploy |

---

## 6. plugin.json Manifest Format

```json
{
    "name":             "SEO Manager",
    "slug":             "seo-manager",
    "version":          "1.0.0",
    "author":           "Bintan Prestige",
    "description":      "...",
    "min_cms_version":  "4.0.0",
    "service_provider": "App\\Plugins\\SeoManager\\SeoManagerServiceProvider",
    "requires":         []
}
```

**Required fields:** `name`, `slug`, `version`, `service_provider`
**Optional fields:** `author`, `description`, `min_cms_version`, `requires`

---

## 7. Test Results

```
php artisan test tests/Feature/Phase4/Phase4BaselineCharacterizationTest.php
Tests:  15 passed
Assertions: 35
```

---

## 8. Impact Summary

| Area | Impact |
|------|--------|
| DB | New `plugins` table (migration run after owner approval) |
| Routes | None |
| Frontend (public) | None |
| Admin UI | None (plugin admin UI built in STEP 4) |
| Tests | +15 baseline characterization tests |
| Docs | ADR-001 created in `docs/architecture/` |

---

## 9. Rollback

```bash
php artisan migrate:rollback --step=1   # rolls back plugins table
git revert HEAD
```

---

## 10. Next Step

**STEP 1 — Plugin Registry & Discovery** (5 hari): `PluginRegistry` service, scan
`app/Plugins/*/plugin.json`, sync discovered plugins to the DB, detect new / updated /
orphaned / unchanged states.
