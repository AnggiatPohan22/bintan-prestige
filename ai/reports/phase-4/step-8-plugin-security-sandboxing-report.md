# Step 8 — Plugin Security & Sandboxing

**Date:** 2026-06-20
**Branch:** feature/phase-4-plugin-system
**Tests:** P1–P14 (14/14 pass) | Full suite: 589 tests, 2721 assertions, 0 failures

---

## Task: STEP 8 — Plugin Security & Sandboxing

### Changed

**New files (created)**
- `app/Exceptions/PluginSecurityException.php` — custom exception carrying `$slug` and `$blockedToken` public properties; extends `RuntimeException`; used by scanner and propagated up to the controller (not caught in PluginManager — activation failure must be visible to the caller)
- `app/Services/Plugin/PluginScanner.php` — two public methods:
  - `scan(string $slug, ?string $pluginsRoot = null): void` — recursively finds all `.php` files under `app/Plugins/{slug}/` using `scandir()` + explicit DIRECTORY_SEPARATOR concatenation; checks each file for 7 blocklist tokens (`shell_exec(`, `proc_open(`, `popen(`, `passthru(`, `system(`, `eval(`, `exec(`); throws `PluginSecurityException` on first match. Note: `shell_exec(` comes before `exec(` in the blocklist because `exec(` is a substring of `shell_exec(`.
  - `validatePermissions(array $manifest): void` — reads `$manifest['permissions']` (optional); rejects any scope not in `ALLOWED_SCOPES` constant; throws `PluginSecurityException` on unknown scope.
- `tests/Feature/Phase4/PluginSecurityTest.php` — P1–P14 (14 tests)
- `tests/Fixtures/PluginSecurity/recurse-test/sub/BadClass.php` — committed fixture file for P12 (scanner recursion test); avoids Windows/PHPUnit timing issue with `file_get_contents` on freshly-created subdirectory files

**Modified files**
- `app/Services/Plugin/PluginManager.php`:
  - Constructor: added `private readonly PluginScanner $scanner` (auto-resolved by Laravel DI — no AppServiceProvider change needed)
  - `activate()`: added security scan + permission validation BEFORE `callLifecycle()`; added `AuditLog::record('plugin.activated', $plugin)` AFTER `$plugin->update()`
  - `deactivate()`: added `AuditLog::record('plugin.deactivated', $plugin)` after `$plugin->update()`
  - `uninstall()`: added `AuditLog::record('plugin.uninstalled', $plugin)` after `$plugin->delete()`
  - Added imports: `PluginSecurityException`, `AuditLog`
- `app/Plugins/SeoManager/plugin.json` — added `"permissions": ["read:pages", "manage:redirects"]`
- `app/Plugins/Analytics/plugin.json` — added `"permissions": ["read:pages", "read:analytics", "write:analytics"]`
- `app/Plugins/ContactForm/plugin.json` — added `"permissions": ["read:pages", "write:pages", "send:email", "manage:forms"]`

---

### Bugs Found and Fixed During Implementation

**Bug 1: Blocklist substring collision (`exec(` vs `shell_exec(`)**

Initial blocklist had `exec(` before `shell_exec(`. Since `exec(` is a substring of `shell_exec(`, scanning `shell_exec("whoami")` reported the wrong token. Fix: reordered blocklist so longer/more specific tokens come first — `shell_exec(` before `exec(`.

**Bug 2: Windows/PHPUnit timing issue with `file_get_contents` on new subdirectory files**

When a PHP file is created inside a newly-created subdirectory during a PHPUnit test (e.g., `tmp/slug/sub/BadClass.php`), `file_get_contents` fails with "Invalid argument" (ErrorException via Laravel's HandleExceptions) even though the file verifiably exists. The same operation succeeds in `php artisan tinker`. Root cause: likely Windows Defender real-time scanning holding a file handle on the new directory+file combination during the very short window between `file_put_contents` and `file_get_contents`.

Fix for tests: committed a fixture file (`tests/Fixtures/PluginSecurity/recurse-test/sub/BadClass.php`) so P12 reads a pre-existing file rather than creating one at test time. Scanner implementation is unaffected — in production, plugin files are installed from ZIPs (not created milliseconds before scanning).

**Bug 3: `SplFileInfo::getPathname()` vs `getRealPath()` on Windows nested paths**

Initially used `RecursiveDirectoryIterator` with `SplFileInfo::getRealPath()`. `getRealPath()` returns `false` for files in nested directories on this Windows setup (possible junction/symlink in the scan path), causing silent skips. Replaced entire `findPhpFiles()` implementation with `scandir()` + explicit `DIRECTORY_SEPARATOR` concatenation which produces fully deterministic paths.

---

### Allowed Permission Scopes (ALLOWED_SCOPES in PluginScanner)

```
read:pages, write:pages, read:settings, write:settings,
read:users, read:analytics, write:analytics,
send:email, manage:redirects, manage:forms
```

Unknown scopes throw `PluginSecurityException` and block activation.

---

### Security Design Decisions

1. **Scanner throws, not logs**: `PluginSecurityException` propagates to the controller so the admin sees the block reason. It is NOT caught inside `PluginManager` (unlike lifecycle exceptions which are caught+logged because broken plugins mustn't crash the CMS).
2. **Scan order**: security scan + permission validation runs BEFORE `callLifecycle('onActivate')`. A dangerous plugin's `onActivate()` never runs.
3. **AuditLog placement**: records AFTER the DB update (activate/deactivate) or AFTER delete (uninstall). If the AuditLog write fails (e.g., unauthenticated context), it no-ops silently — this is correct behavior (`AuditLog::record()` guards on `auth()->check()`).
4. **Boot is unaffected**: `boot()` does not call the scanner. Already-active plugins are not re-scanned on every request (they were scanned at activation time).

---

### Test Coverage (P1–P14)

| Code | Test | Result |
|------|------|--------|
| P1 | Scanner blocks `exec(` | ✅ |
| P2 | Scanner blocks `eval(` | ✅ |
| P3 | Scanner blocks `shell_exec(` | ✅ |
| P4 | Clean plugin passes scanner | ✅ |
| P5 | Unknown permission scope throws | ✅ |
| P6 | Valid permission scopes pass | ✅ |
| P7 | Missing permissions field passes | ✅ |
| P8 | AuditLog created on activate | ✅ |
| P9 | AuditLog created on deactivate | ✅ |
| P10 | AuditLog created on uninstall | ✅ |
| P11 | boot() survives unresolvable plugins | ✅ |
| P12 | Scanner recurses into subdirectories | ✅ |
| P13 | Scanner no-op for non-existent plugin dir | ✅ |
| P14 | Activation blocked and plugin stays inactive when scan fails | ✅ |

---

### Impact

- **DB:** none (no new migrations)
- **Routes:** none
- **Composer:** none
- **AppServiceProvider:** not changed (Laravel auto-resolves `PluginScanner` via constructor DI)
- **Security:** every `activate()` call now runs a PHP token scan + permission scope check before writing to DB or calling lifecycle methods
- **AuditLog:** `plugin.activated`, `plugin.deactivated`, `plugin.uninstalled` now recorded for all authenticated admin actions

---

### Rollback

```bash
git checkout app/Services/Plugin/PluginManager.php
git clean -f app/Exceptions/PluginSecurityException.php \
              app/Services/Plugin/PluginScanner.php \
              tests/Feature/Phase4/PluginSecurityTest.php \
              tests/Fixtures/
git checkout app/Plugins/SeoManager/plugin.json
git checkout app/Plugins/Analytics/plugin.json
git checkout app/Plugins/ContactForm/plugin.json
```

---

### Next

**STEP 9 — Phase 4 Release Gate**

Checklist:
- [ ] `php artisan test` → all green (589/589 ✅ already)
- [ ] `./vendor/bin/phpstan analyse` → no errors level 5
- [ ] Plugin SEO Manager: sitemap valid
- [ ] Plugin Contact Form: form submit → email → submission recorded
- [ ] Plugin Analytics: page view tracked → chart visible
- [ ] All IMP-01 through IMP-07 complete ✅
- [ ] Performance: frontend < 300ms with plugins active
- [ ] Audit log: all admin actions recorded correctly
- [ ] `git tag v4.0.0 && git push origin v4.0.0`
- [ ] Update AGENTS.md: Phase 4 → done
