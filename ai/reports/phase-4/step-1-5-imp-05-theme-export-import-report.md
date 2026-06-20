# STEP 1.5 / IMP-05 — Theme Export & Import (ZIP)

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 5 hari
**Priority:** HIGH (shared ZIP infrastructure used by STEP 8 Plugin Install)
**Status:** COMPLETE ✅

---

## 1. Scope

Implement theme export (download as ZIP) and import (upload ZIP → install as theme)
using a shared `ZipService` that will also be used by STEP 8's plugin upload installer.

Export bundles the theme directory + a generated `theme_config.json` (customization tokens
+ widgets snapshot) into a downloadable archive.

Import validates the ZIP (size, blocked extensions, path traversal, manifest), extracts
it into `themes/{slug}/`, and registers it via the existing `ThemeDiscoveryService`.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `app/Services/ZipService.php` | Shared ZIP utility: `createFromDirectory()` + `extractTheme()` |
| `app/Http/Requests/Admin/ImportThemeRequest.php` | Form request: `required`, `file`, `mimes:zip`, `max:51200` |
| `tests/Feature/Phase4/ThemeExportImportTest.php` | 16 tests (E1–E4 export, I1–I8 import, Z1–Z4 unit) |

### Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/ThemeController.php` | Injected `ZipService`; added `export()`, `import()`, private `buildThemeConfig()` |
| `routes/admin.php` | Added `POST themes/import` (before `{theme}` wildcard) + `GET themes/{theme}/export` |
| `resources/views/backend/themes/index.blade.php` | Added Import panel (Alpine.js collapsible) + Export button per theme card |

---

## 3. ZipService

**Namespace:** `App\Services\ZipService`
**Location:** `app/Services/ZipService.php`

### Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `MAX_SIZE_BYTES` | `52_428_800` | 50 MB upload cap |
| `BLOCKED_EXTENSIONS` | `['php','php3','php4','php5','phtml','phar','htaccess']` | Execution-capable file types |
| `THEME_REQUIRED` | `['name','slug','version']` | Required fields in `theme.json` |

### Public API

#### createFromDirectory(string $sourceDir, array $extraFiles = []): string

- Creates a temp ZIP at `sys_get_temp_dir() . '/bp-theme-export-{uniqid}.zip'`
- Recursively adds all files from `$sourceDir` (using `RecursiveIteratorIterator`)
- Appends `$extraFiles` (ZIP path → content string) via `ZipArchive::addFromString()`
- Returns the temp path — **caller is responsible for deleting it** after streaming
- Throws `RuntimeException` if the directory does not exist or ZipArchive cannot open

#### extractTheme(string $zipPath, string $targetBase): array

Security pipeline (order enforced):

1. **Size guard** — `filesize($zipPath) > MAX_SIZE_BYTES` → throw
2. **Open** — `ZipArchive::open()` → throw on error code
3. **Blocked extensions scan** — scan all entries → throw on `.php`, `.phar`, `.htaccess`, etc.
4. **Path traversal scan** — throw on `../` or `..\` in any entry name
5. **Manifest locate** — `theme.json` at root OR `{folder}/theme.json` one level deep
6. **Manifest parse** — valid JSON with `name`, `slug`, `version` fields
7. **Extract** to `{targetBase}/{slug}/`, stripping folder prefix

Returns the parsed `theme.json` manifest array.

### Windows Fix (Critical)

`sys_get_temp_dir()` on Windows returns a path with backslashes, while PHP's
`RecursiveIteratorIterator` may use mixed separators. Without normalization, the
prefix-stripping step fails silently and produces empty relative paths.

Fix applied in `addDirectoryToZip()`:

```php
$absoluteDir = rtrim(str_replace('\\', '/', $absoluteDir), '/');
$realPath    = str_replace('\\', '/', (string) $file->getRealPath());
$relative    = ltrim(str_replace($absoluteDir . '/', '', $realPath), '/');
$zip->addFile((string) $file->getRealPath(), $zipEntry);
```

All comparisons use forward slashes; only `addFile()` receives the original OS path.

---

## 4. Export Implementation

### ThemeController::export(Theme $theme): StreamedResponse

```
GET /admin/themes/{theme}/export
```

1. Calls `buildThemeConfig($theme)` — serializes customization tokens + widgets as JSON
2. Calls `ZipService::createFromDirectory(theme_path, ['theme_config.json' => $json])`
3. Returns `StreamedResponse` via `Response::streamDownload()`:
   - `readfile($tempPath)` streams bytes to browser
   - `@unlink($tempPath)` deletes temp file after streaming
4. Download filename: `{slug}-{version}.zip`

### buildThemeConfig(Theme $theme): string (private)

Encodes a snapshot of the theme's `customization` array and its `widgets` relationship
as `theme_config.json` in the ZIP root. Used to restore widget layout on import.

---

## 5. Import Implementation

### ThemeController::import(ImportThemeRequest $request): RedirectResponse

```
POST /admin/themes/import
```

1. `ImportThemeRequest` validates: `file`, `mimes:zip`, `max:51200` (50 MB in KB)
2. Stores upload to `storage/app/tmp/`
3. Calls `ZipService::extractTheme($uploadPath, base_path('themes'))`
4. Delegates to `ThemeDiscoveryService::sync()` to register the new theme in DB
5. Cleans up the tmp upload file
6. Redirects to `admin.themes.index` with success flash naming the imported theme

### Route Ordering (Critical)

`POST themes/import` must be registered **before** the `{theme}` resource routes to
prevent Laravel from binding `"import"` as a Theme model slug:

```php
Route::post('themes/import',  [ThemeController::class, 'import'])->name('admin.themes.import');
Route::get('themes/{theme}/export', [ThemeController::class, 'export'])->name('admin.themes.export');
Route::resource('themes', ThemeController::class);  // after explicit routes
```

---

## 6. Admin UI

### Import Panel (Alpine.js collapsible)

```html
<div x-data="{ importOpen: false }">
    <button @click="importOpen = !importOpen">Import Theme</button>

    <div x-show="importOpen" x-transition>
        <form method="POST" action="{{ route('admin.themes.import') }}"
              enctype="multipart/form-data">
            @csrf
            <input type="file" name="zip_file" accept=".zip">
            <button type="submit">Upload & Install</button>
        </form>
    </div>
</div>
```

### Export Button (per theme card)

Added alongside "Widgets" and "Customize" buttons on each theme card:

```html
<a href="{{ route('admin.themes.export', $theme) }}"
   class="admin-btn-soft"
   title="Export as ZIP">
    <i class="fas fa-download"></i> Export
</a>
```

---

## 7. Routes Added

| Name | Method | URI | Controller |
|------|--------|-----|------------|
| `admin.themes.import` | POST | `/admin/themes/import` | `ThemeController@import` |
| `admin.themes.export` | GET | `/admin/themes/{theme}/export` | `ThemeController@export` |

---

## 8. Test Coverage

### Export Tests (E1–E4)

| Test | Contract |
|------|----------|
| E1 | Export returns 200 and `Content-Disposition: attachment` header |
| E2 | Exported ZIP contains a `theme.json` file |
| E3 | Exported ZIP contains `theme_config.json` with customization data |
| E4 | Non-existent theme returns 404 |

### Import Tests (I1–I8)

| Test | Contract |
|------|----------|
| I1 | Valid ZIP with theme.json at root installs and redirects with success |
| I2 | Valid ZIP with theme.json one folder deep installs correctly |
| I3 | ZIP containing `.php` file is rejected (422 / redirect with error) |
| I4 | ZIP with `../` path traversal is rejected |
| I5 | ZIP missing `theme.json` is rejected |
| I6 | ZIP with theme.json missing required `slug` field is rejected |
| I7 | Upload exceeding 50 MB is rejected by form request |
| I8 | Non-ZIP file upload is rejected by form request |

### ZipService Unit Tests (Z1–Z4)

| Test | Contract |
|------|----------|
| Z1 | `createFromDirectory()` returns a readable file path |
| Z2 | Created ZIP contains all files from source directory |
| Z3 | `extraFiles` content appears in the ZIP under the given path |
| Z4 | `createFromDirectory()` throws for a non-existent source directory |

---

## 9. Test Results

```
php artisan test tests/Feature/Phase4/ThemeExportImportTest.php
Tests:  16 passed
Assertions: 42

php artisan test tests/Feature/Phase4/
Tests:  56 passed
Assertions: 141

php artisan test tests/Feature/Phase3/
Tests:  96 passed (no regressions)
Assertions: 253
```

Note: Two tests (E2 and Z2) initially failed due to the Windows path separator bug
in `addDirectoryToZip()`. Fixed by normalizing all paths to forward slashes before
prefix comparison.

---

## 10. Impact Summary

| Area | Impact |
|------|--------|
| DB | None (ThemeDiscoveryService handles `themes` table sync) |
| Routes | 2 new admin routes (`import`, `export`) |
| Frontend (public) | None |
| Admin UI | Import panel + Export button on themes index |
| Filesystem | Exports write to OS temp dir (auto-cleaned after stream); imports extract to `themes/{slug}/` |
| Security | 50 MB cap, 7 blocked extensions, path traversal guard, form request validation |
| Shared Infrastructure | `ZipService` reused by STEP 8 Plugin Installer (plugin install from ZIP) |
| Tests | +16 passing tests |

---

## 11. Rollback

```bash
git revert HEAD
```

Installed themes extracted to `themes/{slug}/` must be removed manually if the migration
rollback is needed.

---

## 12. Next Step

**STEP 2 — Plugin Loader & Lifecycle** (5 hari):
- `App\Services\Plugin\PluginManager` (activate, deactivate, uninstall)
- `App\Plugins\PluginServiceProvider` base class
- `App\Contracts\PluginLifecycle` interface
- Auto-register active plugin providers in `AppServiceProvider::register()`
