# Phase 8 — A1: Destructive-Command Guards

**Branch:** `feature/phase-8-a1-guards` (base: `develop` @ `008e242`)
**Date:** 2026-07-11
**Owner sign-off:** *"approved"* (2026-07-11) — A0 Decision 6 pre-approves this approach.
**Test baseline:** 1003 → **1017** (14 new fences). PHPStan L5: 0 errors.

## Task: Phase 8 A1 — Destructive-command guards (G1 / G2 / G3)

### Changed

- `config/safety.php` — new config. Toggles: `destructive_allowed`,
  `auto_backup_before_migrate`, `skip_auto_backup`. Lists: `destructive_commands`
  (`migrate:fresh`, `migrate:reset`, `db:wipe`), `protected_drivers` (`mysql`,
  `mariadb`). All owner-tunable; all owner-overridable via env.
- `app/Support/DestructiveCommandGuard.php` — new. Pure logic class. Three
  guards in one entry point:
  - **G1:** throws `RuntimeException` when a `destructive_commands` member
    fires against a `protected_drivers` connection without
    `APP_ALLOW_DESTRUCTIVE=true`. Error message points at AGENTS.md §8 +
    Phase 6 §16 incident and shows the exact override incantation.
  - **G2:** on `migrate` against a protected driver, invokes
    `db:backup pre-migrate-<ts> --purpose="Auto pre-migrate snapshot (Phase 8 A1 / G2)"`
    via the Kernel before the actual command runs. Non-zero exit code from
    the backup aborts `migrate` — safety net stays enforceable. Toggle
    `safety.auto_backup_before_migrate` (default ON); per-invocation skip
    `APP_SKIP_AUTO_BACKUP=true` for the "I just manually dumped, don't
    double-dump" case.
  - **G3:** on ANY artisan command in `local` env, if
    `bootstrap/cache/config.php` exists → writes a single-line
    `[safety-guard G3]` warning to stderr. Warning only; does not block.
    This directly addresses the vector behind the 2026-07-07 wipe.
- `app/Providers/AppServiceProvider.php` — imports and registers a listener
  for `Illuminate\Console\Events\CommandStarting` that forwards to
  `DestructiveCommandGuard::handle`. Kept intentionally thin so the logic
  stays unit-testable.
- `tests/Feature/Phase8/DestructiveCommandGuardTest.php` — new. 14 fence
  tests, 20 assertions, uses `MockeryPHPUnitIntegration`:
  - G1: blocks `migrate:fresh`, `migrate:reset`, `db:wipe` on `mysql`;
    allows on `mysql` with env override; does not block on `sqlite`.
  - G2: invokes `db:backup` before `migrate` on `mysql`; respects
    `safety.skip_auto_backup`; respects `safety.auto_backup_before_migrate`
    master toggle; aborts `migrate` on backup failure; does not run on
    `sqlite`.
  - G3: warns in `local` with cache present; silent in `testing` (i.e. any
    non-local); silent in `local` without the cache.
  - Housekeeping: bare `php artisan` (empty command string) is a no-op.

### Impact

- **DB:** none. Purely runtime guards.
- **Routes:** none.
- **Frontend:** none — admin/frontend surfaces unchanged.
- **Security / operations:**
  - `php artisan migrate:fresh` on a MySQL connection now aborts before
    doing anything unless `APP_ALLOW_DESTRUCTIVE=true` is set for that
    invocation. **Structurally closes the 2026-07-07 incident vector.**
  - Every `php artisan migrate` on MySQL now begins with an automatic
    `db:backup`, filename convention `pre-migrate-{YYYYMMDD-HHMMSS}.sql`
    under `storage/app/db-backups/` — matches AGENTS.md §13 naming.
  - `local` env with `bootstrap/cache/config.php` present is now
    self-flagging. First line of any artisan invocation surfaces the
    warning until the operator runs `php artisan optimize:clear`.
- **Test suite:** 1003 → 1017 (+14). PHPStan L5: 0 errors. Duration ~112 s.
- **Env vars (new, opt-in):**
  - `APP_ALLOW_DESTRUCTIVE=true` — allow one destructive command run.
  - `APP_SKIP_AUTO_BACKUP=true` — skip G2's auto pre-migrate backup.
  - `APP_AUTO_BACKUP_BEFORE_MIGRATE=false` — master switch off (for CI
    envs that don't have `mysqldump`).

### Rollback

Single commit rollback:
```bash
git revert <A1-commit-hash>
```
Or, on a branch reset:
```bash
git checkout develop
git branch -D feature/phase-8-a1-guards
```
No schema, no data, no config artifacts on disk to clean.

### Verification steps run

- Full suite: **1017/1017 pass** (baseline 1003 + 14 A1 fences).
- PHPStan level 5: **0 errors**.
- `php artisan --version` + `php artisan list` sanity check: artisan
  lifecycle intact; `db:backup`, `migrate:*`, `route:list` still listed.
- Guard tests exercise all three guards' full paths (block, allow via env,
  skip via env, master toggle, failure abort, sqlite inertness, local vs
  non-local, cache present vs absent).

### Guardrails observed

- Zero new Composer/NPM packages (Decision 8) — pure Laravel + Symfony
  console primitives (`CommandStarting`, `OutputInterface`, `ArrayInput`).
- Media Library rule (AGENTS.md §8): not applicable — no upload surface.
- i18n rule (`ai/skills/i18n-skill.md`): not applicable — error message is
  operator-facing, developer-only text in `local`/CI.
- AGENTS.md §13 backup naming: G2 uses `pre-migrate-{YYYYMMDD-HHMMSS}.sql`
  — same convention as manual `db:backup` calls.

### Next

Handoff §1 flips A1 → ✅ DONE. Next task is **A2** (Milestone M1 close):
`BackupService` core + `backups` table + scheduled daily dump with
retention + checksum verify. A2 gate is a schema change (⚠️ new `backups`
table) — needs owner "approved" before the migration lands.
