<?php

/*
|--------------------------------------------------------------------------
| Phase 8 A1 — Destructive-command guard configuration.
|--------------------------------------------------------------------------
|
| Runtime guards that prevent the 2026-07-07 dev-DB wipe (Phase 6 §16) from
| happening again. Applied via `App\Support\DestructiveCommandGuard`, wired
| into `Illuminate\Console\Events\CommandStarting` from `AppServiceProvider`.
|
| Owner-tunable. See `docs/runbooks/db-backup.md` and
| `ai/reports/phase-8/a0-architecture-decisions.md` (Decision 6).
*/

return [

    /*
    | Escape hatch for G1: when true, destructive commands (`migrate:fresh`,
    | `migrate:reset`, `db:wipe`) run against a protected driver without the
    | guard aborting. Wire only to `APP_ALLOW_DESTRUCTIVE=true` — never
    | commit a truthy value here.
    */
    'destructive_allowed' => filter_var(env('APP_ALLOW_DESTRUCTIVE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | G2 master toggle: automatically snapshot the MySQL DB via `db:backup`
    | right before `php artisan migrate` runs. Default ON everywhere —
    | uniform safety beats environment surprises.
    */
    'auto_backup_before_migrate' => filter_var(env('APP_AUTO_BACKUP_BEFORE_MIGRATE', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | G2 per-invocation skip: when true, `migrate` proceeds without the
    | auto-backup. Use only right after a manual `db:backup` so you don't
    | double-dump. Never commit a truthy value here.
    */
    'skip_auto_backup' => filter_var(env('APP_SKIP_AUTO_BACKUP', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Commands the G1 guard refuses to run on a protected driver. Additive:
    | extend for future footguns; never remove `migrate:fresh` /
    | `migrate:reset` / `db:wipe`.
    */
    'destructive_commands' => [
        'migrate:fresh',
        'migrate:reset',
        'db:wipe',
    ],

    /*
    | Command that triggers the G2 auto pre-migrate backup. Kept single so
    | `db:seed` and similar don't accidentally trigger backups.
    */
    'auto_backup_trigger_command' => 'migrate',

    /*
    | DB drivers that are treated as "real" data — the guard fires against
    | these. `sqlite` (used by the test suite and one-off scratch scripts)
    | is deliberately absent.
    */
    'protected_drivers' => ['mysql', 'mariadb'],

];
