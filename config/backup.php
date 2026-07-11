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

        // Phase 8 B2 — offsite mirror targets. Leave `null` to disable
        // offsite copy for that backup type. Point at any Laravel
        // filesystems disk (e.g. `r2`, `s3`, `sftp`).
        'db_offsite'    => env('BACKUP_DB_OFFSITE_DISK'),
        'media_offsite' => env('BACKUP_MEDIA_OFFSITE_DISK'),
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
    | Media snapshot source configuration (B1).
    |
    | `source_paths` — filesystem paths (relative to `base_path()`) whose
    |                  contents are folded into the weekly ZIP. Add more
    |                  paths (comma-separated in the env var) as the CMS
    |                  grows.
    | `follow_symlinks` — default false; broken symlinks always skipped.
    | `exclude_patterns` — case-sensitive glob-ish substrings applied to
    |                  each candidate's relative path. Useful for '.gitignore'
    |                  files or third-party junk.
    */
    'media' => [
        'source_paths' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('BACKUP_MEDIA_SOURCE_PATHS', 'storage/app/public'))
        ))),
        'follow_symlinks'  => filter_var(env('BACKUP_MEDIA_FOLLOW_SYMLINKS', false), FILTER_VALIDATE_BOOLEAN),
        'exclude_patterns' => ['/.DS_Store', '/Thumbs.db'],
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
        'db_daily_at'      => env('BACKUP_DB_DAILY_AT', '03:00'),
        'media_weekly_at'  => env('BACKUP_MEDIA_WEEKLY_AT', '04:00'),
        // 0 = Sunday, 6 = Saturday (matches Laravel scheduler weeklyOn()).
        'media_weekly_day' => (int) env('BACKUP_MEDIA_WEEKLY_DAY', 0),
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

    /*
    | Phase 8 B2 — offsite copy behavior.
    |
    | `copy_after_snapshot` — auto-invoke offsite mirror right after each
    |                         ok db/media snapshot (opt-in default: true).
    | `retry_attempts` / `retry_delay_seconds` — for transient network errors.
    | `alert_email` — optional recipient for critical-red alerts on final
    |                 offsite failure. Falls back to `config('mail.from.address')`.
    */
    'offsite' => [
        'copy_after_snapshot' => filter_var(env('BACKUP_OFFSITE_COPY_AFTER_SNAPSHOT', true), FILTER_VALIDATE_BOOLEAN),
        'retry_attempts'      => (int) env('BACKUP_OFFSITE_RETRY_ATTEMPTS', 3),
        'retry_delay_seconds' => (int) env('BACKUP_OFFSITE_RETRY_DELAY', 30),
        'alert_email'         => env('BACKUP_OFFSITE_ALERT_EMAIL'),
    ],

];
