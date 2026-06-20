# STEP 2 — Plugin Loader & Lifecycle

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 5 hari
**Status:** COMPLETE ✅

---

## 1. Scope

Implement the plugin activation/deactivation/uninstall lifecycle, a base `PluginServiceProvider`
class all plugins extend, a `PluginLifecycle` contract enforcing the four lifecycle hooks,
and auto-registration of active plugin providers at application boot time.

Topological sort by `requires` dependency graph ensures plugins always load in the correct order.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Contracts/PluginLifecycle.php` | Interface: `onInstall`, `onActivate`, `onDeactivate`, `onUninstall` |
| `app/Plugins/PluginServiceProvider.php` | Abstract base class — extends `Illuminate\Support\ServiceProvider`, implements `PluginLifecycle` with no-op defaults |
| `app/Services/Plugin/PluginManager.php` | `boot()`, `activate()`, `deactivate()`, `uninstall()`, topological sort, `buildManifestMap()`, `callLifecycle()` |
| `database/factories/PluginFactory.php` | Factory with `active()` and `inactive()` states |
| `tests/Feature/Phase4/PluginManagerTest.php` | 15 tests (F1–F15) |

### Modified

| File | Change |
|------|--------|
| `app/Models/Plugin.php` | Added `HasFactory` trait |
| `app/Providers/AppServiceProvider.php` | Registered `PluginRegistry` + `PluginManager` as singletons; calls `PluginManager::boot()` from `register()` |

---

## 3. PluginLifecycle Interface

```php
interface PluginLifecycle
{
    public function onInstall(): void;    // called once on first discovery
    public function onActivate(): void;   // called each time plugin is activated
    public function onDeactivate(): void; // called each time plugin is deactivated
    public function onUninstall(): void;  // called on permanent removal — must clean up data
}
```

---

## 4. PluginServiceProvider Base Class

```php
abstract class PluginServiceProvider extends ServiceProvider implements PluginLifecycle
{
    // All four lifecycle methods are no-ops by default.
    // Plugins override only the ones they need.
    public function onInstall(): void {}
    public function onActivate(): void {}
    public function onDeactivate(): void {}
    public function onUninstall(): void {}
}
```

---

## 5. PluginManager Service

**Namespace:** `App\Services\Plugin\PluginManager`

### boot() — called from AppServiceProvider::register()

```
PluginManager::boot()
      │
      ▼
PluginRegistry::active()  ← cached Collection of Plugin models
      │
      ▼
buildManifestMap()  ← glob app/Plugins/*/plugin.json → slug => manifest[]
      │
      ▼
topologicalSort()  ← DFS sort by manifest['requires'] array
      │
      ▼
For each manifest in sorted order:
    registerProvider(manifest['service_provider'])
    └─ class_exists() ? app->register($class) : skip silently
```

Rules enforced by `boot()`:
- Wrapped in global try-catch — a broken plugin cannot crash the CMS
- Missing ServiceProvider class (STEPs 5–7 not yet built) → silently skipped
- Empty active list → returns immediately (no IO)

### activate(Plugin $plugin): void

1. Guard: throws `RuntimeException` if already active
2. `callLifecycle($plugin, 'onActivate')` — try-catch, skip if class missing
3. `$plugin->update(['is_active' => true, 'activated_at' => now()])`
4. `$registry->forget()` — clears `active_plugins` cache

### deactivate(Plugin $plugin): void

1. Guard: throws `RuntimeException` if not active
2. `callLifecycle($plugin, 'onDeactivate')`
3. `$plugin->update(['is_active' => false])`
4. `$registry->forget()`

### uninstall(Plugin $plugin): void

1. Guard: throws `RuntimeException` if still active (must deactivate first)
2. `callLifecycle($plugin, 'onUninstall')`
3. `$plugin->delete()`
4. `$registry->forget()`

### Topological Sort (DFS)

```
Input:  manifests = [{slug: 'analytics', requires: ['seo-manager']}, {slug: 'seo-manager', requires: []}]
Output: [seo-manager, analytics]   ← dependency before dependent
```

Cycles are broken by the `$visited` guard — first encountered path wins.

---

## 6. AppServiceProvider::register() Changes

```php
$this->app->singleton(PluginRegistry::class);
$this->app->singleton(PluginManager::class);

// Boot active plugin providers so they participate in the full boot cycle.
$this->app->make(PluginManager::class)->boot();
```

Plugin providers registered here (during `register()`) participate in both
`register()` and `boot()` phases of all subsequent providers.

---

## 7. Test Coverage (F1–F15)

| Test | Contract |
|------|----------|
| F1 | `activate()` sets `is_active = true` |
| F2 | `activate()` sets `activated_at` |
| F3 | `activate()` clears `active_plugins` cache |
| F4 | `activate()` throws if already active |
| F5 | `activate()` does not throw when ServiceProvider class is missing |
| F6 | `deactivate()` sets `is_active = false` |
| F7 | `deactivate()` clears cache |
| F8 | `deactivate()` throws if not active |
| F9 | `uninstall()` deletes plugin from DB |
| F10 | `uninstall()` clears cache |
| F11 | `uninstall()` throws if plugin is still active |
| F12 | `boot()` is safe with no active plugins (empty DB) |
| F13 | `boot()` does not throw when provider class is missing |
| F14 | `activate()` then `deactivate()` cycle works correctly |
| F15 | `uninstall()` after inactive plugin works |

---

## 8. Test Results

```
php artisan test tests/Feature/Phase4/PluginManagerTest.php
Tests:  15 passed
Assertions: 20

php artisan test tests/Feature/Phase4/
Tests:  55 passed
Assertions: 141

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
```

---

## 9. Impact Summary

| Area | Impact |
|------|--------|
| DB | None — no migration |
| Routes | None |
| Frontend (public) | None |
| Plugin loading | Active plugin ServiceProviders now auto-register on every app boot |
| Cache | `active_plugins` cleared on every activate/deactivate/uninstall |
| Tests | +15 passing |

---

## 10. Rollback

```bash
git revert HEAD
```

---

## 11. Next Step

**STEP 3 — Hook & Filter Event System** (7 hari): `HookManager`, `CmsHooks` facade,
core action and filter hooks.
