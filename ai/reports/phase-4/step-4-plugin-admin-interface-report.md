# STEP 4 — Plugin Admin Interface

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 5 hari
**Status:** COMPLETE ✅

---

## 1. Scope

Build the admin UI for managing plugins: list all installed plugins, view plugin details,
activate/deactivate with confirmation modals, uninstall with guard, scan `app/Plugins/` for
new manifests, and record every state change to the audit log.

Also added "Plugins" entry to the admin sidebar under the System section.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/PluginController.php` | 6 methods: index, show, scan, activate, deactivate, destroy |
| `resources/views/backend/plugins/index.blade.php` | Plugin list — status badge, activate/deactivate buttons, Alpine confirm modals, uninstall |
| `resources/views/backend/plugins/show.blade.php` | Plugin detail — info, manifest, stored config display |
| `tests/Feature/Phase4/PluginAdminInterfaceTest.php` | 12 tests (J1–J12) |

### Modified

| File | Change |
|------|--------|
| `routes/admin.php` | Added `plugins` route group (6 routes), imported `PluginController` |
| `resources/views/backend/partials/sidebar.blade.php` | Added "Plugins" link (puzzle-piece icon) in System section |

---

## 3. Routes

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `admin/plugins` | `admin.plugins.index` | `index()` |
| POST | `admin/plugins/scan` | `admin.plugins.scan` | `scan()` |
| GET | `admin/plugins/{plugin}` | `admin.plugins.show` | `show()` |
| PATCH | `admin/plugins/{plugin}/activate` | `admin.plugins.activate` | `activate()` |
| PATCH | `admin/plugins/{plugin}/deactivate` | `admin.plugins.deactivate` | `deactivate()` |
| DELETE | `admin/plugins/{plugin}` | `admin.plugins.destroy` | `destroy()` |

---

## 4. Controller Logic

### index()
Calls `PluginRegistry::all()` (orders by name). Passes `$plugins` Collection to view.

### show(Plugin $plugin)
Reads `app/Plugins/{slug}/plugin.json` from disk. If found, parses `requires`, `min_cms_version`,
`service_provider` fields to display. Passes both `$plugin` model and `$manifest` array to view.
Gracefully handles missing manifest file (plugin registered in DB but files removed from disk).

### scan()
Calls `PluginRegistry::sync()`. Returns `{installed, updated, orphaned, unchanged}` arrays.
Builds human-readable flash message from counts. Flash type is `success` if any installed/updated,
otherwise `info`.

### activate(Plugin $plugin)
1. Calls `PluginManager::activate($plugin)` — throws `RuntimeException` if already active
2. Records `AuditLog::record('plugin.activated', $plugin)`
3. Redirects to index with success flash

### deactivate(Plugin $plugin)
1. Calls `PluginManager::deactivate($plugin)` — throws `RuntimeException` if not active
2. Records `AuditLog::record('plugin.deactivated', $plugin)`
3. Redirects to index with success flash

### destroy(Plugin $plugin)
1. Guards: if `$plugin->is_active` → redirect with error "Deactivate first"
2. Records audit log BEFORE uninstall (model is deleted by uninstall)
3. Calls `PluginManager::uninstall($plugin)` — removes from DB, clears cache
4. Redirects with success flash

---

## 5. Index View Features

- **Header** with Scan for Plugins button
- **Empty state** when no plugins: big icon + description + scan button
- **Plugin rows** (divide-y layout):
  - Name + version badge + Active/Inactive status badge
  - Description and author line
  - **Activate button** (green): visible when inactive — direct form POST, no modal
  - **Deactivate button** (amber): visible when active — opens Alpine.js confirm modal
  - **Uninstall button** (red): always visible — opens separate Alpine.js confirm modal
  - **Details link** → `admin.plugins.show`
- Uninstall modal warns if plugin is still active

---

## 6. Show View Features

- Breadcrumb: Plugins → Plugin Name
- Header with status badge + Activate/Deactivate button + Back link
- **Plugin Information card**: slug, version, author, installed date, last activated date
- **Manifest card**: requires dependencies, min_cms_version, service_provider class path
  - Shows warning if plugin.json is missing on disk
- **Stored Configuration card** (only rendered if `$plugin->config` is not null): JSON prettified

---

## 7. Audit Log Integration

Every state change is recorded with `AuditLog::record()`:

| Action | `auditable_type` | When |
|--------|-----------------|------|
| `plugin.activated` | `Plugin` | After successful activation |
| `plugin.deactivated` | `Plugin` | After successful deactivation |
| `plugin.uninstalled` | `Plugin` | Before deletion (while model still has ID) |

---

## 8. Test Coverage (J1–J12)

| Test | Contract |
|------|----------|
| J1 | `index` returns 200 for authenticated admin |
| J2 | `index` renders all plugin names |
| J3 | `scan` redirects to index |
| J4 | `show` returns 200 and renders plugin detail |
| J5 | `activate` sets `is_active = true` |
| J6 | `activate` redirects to index with success |
| J7 | `activate` creates `audit_logs` row with `plugin.activated` |
| J8 | `deactivate` sets `is_active = false` |
| J9 | `deactivate` redirects to index with success |
| J10 | `deactivate` creates `audit_logs` row with `plugin.deactivated` |
| J11 | `destroy` deletes inactive plugin from DB |
| J12 | `destroy` returns error flash and keeps DB record when plugin is still active |

---

## 9. Test Results

```
php artisan test tests/Feature/Phase4/PluginAdminInterfaceTest.php
Tests:  12 passed
Assertions: 24

php artisan test tests/Feature/Phase4/
Tests:  120 passed
Assertions: 255

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
```

---

## 10. Impact Summary

| Area | Impact |
|------|--------|
| DB | None — no migration |
| Routes | 6 new routes under `admin/plugins` |
| Frontend (public) | None |
| Admin UI | "Plugins" page fully functional; sidebar link added |
| Audit Log | 3 new action types recorded: `plugin.activated`, `plugin.deactivated`, `plugin.uninstalled` |
| Tests | +12 passing (216 total, no regressions) |

---

## 11. Rollback

```bash
git revert HEAD
```

---

## 12. Next Step

**STEP 4.5 — IMP-02: Content Revision History** (4 hari): snapshot page content on every save,
`page_revisions` table, "Revisions" tab in the page editor, Preview and Restore actions.
