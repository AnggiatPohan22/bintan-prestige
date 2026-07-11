<?php

/*
|--------------------------------------------------------------------------
| Phase 8 A2 — BackupService configuration.
|--------------------------------------------------------------------------
|
| Owner-tunable knobs for the automated backup pipeline. Consumed by
| `App\Services\BackupService` and `App\Console\Commands\BackupSnapshot`.
| Off-machine target credentials (B2) will piggy-back on `disks.db` /
| `disks.media` — pointing them at a second Laravel filesystem disk is
| the entire migration.
|
| Reference: `ai/reports/phase-8/a0-architecture-decisions.md` (Decisions 1 + 2).
*/

return [

    /*
    | Which Laravel database connection to dump. Null means "use the app's
    | default connection". Set explicitly (e.g. to a read-replica) when the
    | primary connection is not the one you want dumped. Never affects where
    | the Backup model itself writes — that always uses the default.
    */
    'source_connection' => env('BACKUP_SOURCE_CONNECTION'),

    /*
    | Laravel filesystem disk used for each backup type. Point these at a
    | second disk (S3-compatible, FTP, secondary local) in Phase 8 B2 to
    | get off-machine copies without a schema change.
    */
    'disks' => [
        'db'    => env('BACKUP_DB_DISK', 'local'),
        'media' => env('BACKUP_MEDIA_DISK', 'local'),
    ],

    /*
    | Relative path (under the disk root) where each backup type lives.
    | The service creates these directories on demand.
    */
    'paths' => [
        'db'    => 'backups/db',
        'media' => 'backups/media',
    ],

    /*
    | Retention policy — how many backups per bucket to keep. When pruning
    | fires, older entries are marked `status='pruned'` and their file is
    | removed from disk; the row is never hard-deleted (audit trail).
    */
    'retention' => [
        'db_daily'     => (int) env('BACKUP_RETENTION_DB_DAILY', 14),
        'db_weekly'    => (int) env('BACKUP_RETENTION_DB_WEEKLY', 8),
        'media_weekly' => (int) env('BACKUP_RETENTION_MEDIA_WEEKLY', 4),
    ],

    /*
    | Scheduler wall-clock. Runs in `Asia/Jakarta` (matches
    | `config('app.timezone')`).
    */
    'schedule' => [
        'db_daily_at' => env('BACKUP_DB_DAILY_AT', '03:00'),
    ],

    /*
    | Verify each dump immediately after writing it (checksum recompute +
    | file header sanity + minimum size check). Turn off only in low-power
    | envs; the whole point of Phase 8 is not trusting an unverified backup.
    */
    'verify' => [
        'enabled'         => filter_var(env('BACKUP_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
        'min_size_bytes'  => (int) env('BACKUP_MIN_SIZE_BYTES', 1024),
    ],

    /*
    | Hard limits for the mysqldump subprocess. 30 minutes is generous for
    | dev-sized DBs; production dumps should always finish well under this.
    */
    'mysqldump' => [
        'timeout_seconds' => (int) env('BACKUP_MYSQLDUMP_TIMEOUT', 1800),
    ],

];
