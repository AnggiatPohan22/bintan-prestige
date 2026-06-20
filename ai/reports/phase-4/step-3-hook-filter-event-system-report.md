# STEP 3 — Hook & Filter Event System

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 7 hari
**Status:** COMPLETE ✅

---

## 1. Scope

Implement a WordPress-style hook and filter event system for the CMS. Plugins use
`CmsHooks::addAction()` and `CmsHooks::addFilter()` to extend CMS behavior without
touching core code.

Wire the `cms.init`, `plugin.activated`, and `plugin.deactivated` core action hooks
at their actual call sites.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Support/HookManager.php` | Core engine: `addAction`, `doAction`, `addFilter`, `applyFilters`, `hasAction`, `hasFilter`, `removeAll` |
| `app/Facades/CmsHooks.php` | Laravel facade resolving `HookManager::class` |
| `tests/Feature/Phase4/HookManagerTest.php` | 17 tests (G1–G17) |

### Modified

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Registered `HookManager` singleton; fires `CmsHooks::doAction('cms.init')` at end of `boot()` |
| `app/Services/Plugin/PluginManager.php` | Added `use App\Facades\CmsHooks`; fires `plugin.activated` after `activate()` and `plugin.deactivated` after `deactivate()` |

---

## 3. HookManager — API Reference

### Action Hooks

| Method | Signature | Purpose |
|--------|-----------|---------|
| `addAction` | `(string $hook, callable $callback, int $priority = 10): void` | Register a callback on an action hook |
| `doAction` | `(string $hook, mixed ...$args): void` | Fire all callbacks registered on a hook |
| `hasAction` | `(string $hook): bool` | Check if any callbacks are registered |

### Filter Hooks

| Method | Signature | Purpose |
|--------|-----------|---------|
| `addFilter` | `(string $hook, callable $callback, int $priority = 10): void` | Register a value-transforming callback |
| `applyFilters` | `(string $hook, mixed $value, mixed ...$args): mixed` | Thread `$value` through all registered callbacks |
| `hasFilter` | `(string $hook): bool` | Check if any filters are registered |

### Utilities

| Method | Signature | Purpose |
|--------|-----------|---------|
| `removeAll` | `(string $hook): void` | Clear all actions and filters for a hook (used in tests) |

### Priority Rule

Lower priority number = runs first. Default priority = 10. Ties resolved by registration order.

```
priority 1  →  priority 5  →  priority 10 (default)  →  priority 20
```

Internal storage: `$actions[$hook][$priority][] = $callback`. `ksort()` on every `doAction`/`applyFilters` call.

---

## 4. CmsHooks Facade

```php
// app/Facades/CmsHooks.php
class CmsHooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
```

Registered as a singleton in `AppServiceProvider::register()`. No alias entry needed in `config/app.php` (Laravel 11 auto-resolution via container).

---

## 5. Core Hook Call Sites

### Wired in STEP 3

| Hook | Fired in | When |
|------|----------|------|
| `cms.init` | `AppServiceProvider::boot()` | After all CMS services are bootstrapped |
| `plugin.activated` | `PluginManager::activate()` | After DB is updated and cache cleared |
| `plugin.deactivated` | `PluginManager::deactivate()` | After DB is updated and cache cleared |

### Planned for STEPs 5–7

| Hook | Planned call site | Purpose |
|------|-------------------|---------|
| `admin.loaded` | Admin base controller / middleware | STEP 4 Plugin Admin UI |
| `page.render` | `PageController` / `PageService` | STEP 5 SEO Manager |
| `block.render` | Block rendering service | STEP 6 Contact Form |
| `menu.render` | `MenuService::sources()` | STEP 5–6 |
| `page.content` *(filter)* | Page output pipeline | STEP 5 |
| `block.output` *(filter)* | Block output pipeline | STEP 6 |
| `seo.meta` *(filter)* | SEO meta tag builder | STEP 5 |
| `nav.items` *(filter)* | `MenuService` items | STEP 6 |
| `theme.tokens` *(filter)* | `ThemeService::resolvedTokens()` | STEP 3.5 ✅ |

---

## 6. Filter Chaining Behaviour

```php
CmsHooks::addFilter('example', fn($v) => $v + 1);   // priority 10
CmsHooks::addFilter('example', fn($v) => $v * 2);   // priority 10 (tie → registration order)

$result = CmsHooks::applyFilters('example', 5);
// ((5 + 1) * 2) = 12
```

Each callback receives the **current value** (already transformed by previous callbacks),
not the original. Extra args are passed unchanged to every callback.

---

## 7. Test Coverage (G1–G17)

| Test | Contract |
|------|----------|
| G1 | Registered action callback is called by `doAction` |
| G2 | `doAction` passes arguments to callback |
| G3 | Multiple callbacks on the same action hook all fire |
| G4 | Action callbacks fire in priority order (lower first) |
| G5 | `doAction` on unregistered hook does not throw |
| G6 | Registered filter callback transforms value |
| G7 | Filter callback receives extra arguments |
| G8 | Multiple filter callbacks chain — each gets previous return value |
| G9 | Filter callbacks fire in priority order |
| G10 | `applyFilters` returns original value when no filters registered |
| G11 | `hasAction` returns true when action registered |
| G12 | `hasFilter` returns true when filter registered |
| G13 | `removeAll` clears both actions and filters for a hook |
| G14 | `CmsHooks` facade resolves to `HookManager` instance |
| G15 | `HookManager` is a singleton |
| G16 | `plugin.activated` hook fires when plugin activated via `PluginManager` |
| G17 | `plugin.deactivated` hook fires when plugin deactivated via `PluginManager` |

---

## 8. Test Results

```
php artisan test tests/Feature/Phase4/HookManagerTest.php
Tests:  17 passed
Assertions: 19

php artisan test tests/Feature/Phase4/
Tests:  72 passed
Assertions: 160

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
```

---

## 9. Impact Summary

| Area | Impact |
|------|--------|
| DB | None |
| Routes | None |
| Frontend (public) | None (hook system is infrastructure; visible effects come in STEP 3.5+) |
| Plugin API | Plugins can now call `CmsHooks::addAction()` / `addFilter()` in their `boot()` methods |
| Performance | Negligible — hook registry is an in-memory array; `ksort()` on small arrays |
| Tests | +17 passing (184 total, no regressions) |

---

## 10. Rollback

```bash
git revert HEAD
```

---

## 11. Next Step

**STEP 3.5 — IMP-06: Extended Design Tokens** (3 hari): `--bp-space-*`, `--bp-radius-*`,
`--bp-shadow-*`, `--bp-text-*` CSS custom properties added to the luxury theme's
`customization_schema`; exposed via `theme.tokens` filter hook.
