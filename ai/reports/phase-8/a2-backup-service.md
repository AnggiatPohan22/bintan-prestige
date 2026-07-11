# Phase 8 — A2: BackupService + `backups` table + `backup:snapshot`

**Branch:** `feature/phase-8-a2-backup-service` (base: `develop` @ `0ca0f01`)
**Date:** 2026-07-11
**Owner sign-off:** *"approved"* (2026-07-11).
**Pre-ALTER backup:** `storage/app/db-backups/pre-pre-a2-backups-table-20260711-074919.sql` (275 KB).
**Test baseline:** 1017 → **1032** (15 new fences). PHPStan L5: 0 errors.

## Task: Phase 8 A2 — BackupService + `backups` table + `backup:snapshot`

### Changed

- `database/migrations/2026_07_11_000001_create_backups_table.php` — new.
  Additive-only; single new table with a compound
  `(type, status, created_at)` index for dashboard + prune queries. No
  ALTER on any existing table. Rollback tested clean on dev MySQL.
- `app/Models/Backup.php` — new. Type/status enum constants
  (`TYPE_DB` / `TYPE_MEDIA` / `STATUS_OK` / `STATUS_FAILED` / `STATUS_PRUNED`);
  `meta` json cast; `size_bytes` int cast.
- `config/backup.php` — new. Owner-tunable knobs: `source_connection`
  (which DB to dump — decoupled from `database.default` so tests can dump
  the mysql connection while writing Backup rows to sqlite), `disks`,
  `paths`, `retention` (14 db_daily / 8 db_weekly / 4 media_weekly),
  `schedule.db_daily_at` (default `03:00`), `verify.enabled` + `min_size_bytes`,
  `mysqldump.timeout_seconds`.
- `app/Services/Backup/DatabaseDumper.php` — new interface. Contract:
  `dump(array $connection, string $outputSqlPath, int $timeout): int` +
  `lastErrorOutput(): string`. Non-throwing; return exit code.
- `app/Services/Backup/MysqldumpRunner.php` — new. Default implementation.
  Shells out to `mysqldump --single-transaction --routines --triggers
  --skip-lock-tables --databases {db} --result-file=…`; passes password
  via `MYSQL_PWD` env var (not command line); mirrors resolution logic
  from existing `BackupDatabase` command.
- `app/Services/BackupService.php` — new. Core engine:
  - `dumpDatabase(?string $purpose = null): Backup` — orchestrates dump →
    gzip → SHA-256 → record → verify → prune. Failures at any step are
    recorded as `status='failed'` with `meta.error`.
  - `verify(Backup $backup): bool` — recompute SHA-256 + file-exists +
    size threshold.
  - `prune(string $type = 'db'): int` — keep the newest N rows per type;
    older rows get `status='pruned'` + their disk artifacts unlinked. Rows
    are NEVER hard-deleted (audit trail).
  - `record(array $attributes): Backup` — creates a `Backup` row. Public
    so admin surface (B3) can attach externally-produced dumps to the
    dashboard.
- `app/Console/Commands/BackupSnapshot.php` — new. `backup:snapshot [--purpose=…]`.
  Distinct from the existing `db:backup <task-id>` (which is the manual
  pre-ALTER dumper into `storage/app/db-backups/`); this writes to
  `storage/app/private/backups/db/` and records a `Backup` row.
- `bootstrap/app.php` — edit. Registers `BackupSnapshot` command;
  schedules it at `config('backup.schedule.db_daily_at')` (default `03:00`)
  in `Asia/Jakarta`, with `withoutOverlapping()->onOneServer()`.
- `app/Providers/AppServiceProvider.php` — edit (3 lines). Binds
  `DatabaseDumper::class → MysqldumpRunner::class` in `register()`.
- `tests/Feature/Phase8/BackupServiceTest.php` — new. 13 fence tests:
  - `record()` persists all fields.
  - `dumpDatabase()` records `ok` + gzips + checksums + writes sidecar +
    removes raw `.sql`.
  - `dumpDatabase()` records `failed` when dumper exits non-zero.
  - `dumpDatabase()` records `failed` when dumper exits 0 but writes 0
    bytes (silent-failure trap).
  - `dumpDatabase()` throws when source connection driver is not mysql.
  - `verify()` returns true on match; false on missing file, checksum
    mismatch, or under-min-size file.
  - `prune()` prunes exactly `total - retention` rows; leaves pruned rows
    in place (audit); unlinks disk artifacts (both `.sql.gz` and `.sha256`);
    no-op below retention.
- `tests/Feature/Phase8/BackupSnapshotCommandTest.php` — new. 2 fence
  tests: command registration + non-mysql refusal (mirrors existing
  `BackupDatabaseCommandTest`).

### Impact

- **DB:** ⚠️ new `backups` table (owner-approved, pre-ALTER backup on disk).
  Additive; rollback verified clean.
- **Routes:** none.
- **Frontend:** none. Admin surface for backups lands in B3.
- **Filesystem:** new directory `storage/app/private/backups/db/`
  (created on-demand by the service).
- **Scheduler:** new daily job at `03:00 Asia/Jakarta`
  (`withoutOverlapping()->onOneServer()`).
- **Test suite:** 1017 → 1032 (+15). PHPStan L5: 0 errors. Duration ~206 s.
- **Env vars (new, opt-in):**
  - `BACKUP_SOURCE_CONNECTION` — override which DB connection to dump.
  - `BACKUP_DB_DISK` / `BACKUP_MEDIA_DISK` — override target disk (B2 hook).
  - `BACKUP_RETENTION_DB_DAILY` / `BACKUP_RETENTION_DB_WEEKLY` /
    `BACKUP_RETENTION_MEDIA_WEEKLY` — override retention.
  - `BACKUP_DB_DAILY_AT` — override scheduled time.
  - `BACKUP_VERIFY` (bool) — disable post-write verify.
  - `BACKUP_MIN_SIZE_BYTES` — verify threshold.
  - `BACKUP_MYSQLDUMP_TIMEOUT` — subprocess timeout (default 1800 s).

### Rollback

Single-branch rollback:
```bash
git checkout develop
git branch -D feature/phase-8-a2-backup-service
```

Physical DB rollback (if merged, then reverted):
```bash
php artisan db:backup pre-a2-rollback --purpose="Rollback A2"
php artisan migrate:rollback --step=1
```
Rollback was verified on dev MySQL during A2 development.

### Verification steps run on dev MySQL

- Pre-ALTER manual backup: `db:backup pre-a2-backups-table`
  → 275 KB dump written to `storage/app/db-backups/`.
- `migrate --pretend` reviewed — only the new CREATE TABLE + one CREATE
  INDEX.
- `APP_SKIP_AUTO_BACKUP=true php artisan migrate --force` — clean,
  358 ms.
- `APP_SKIP_AUTO_BACKUP=true php artisan migrate:rollback --step=1` —
  clean, 69 ms.
- Re-migrate — clean, 83 ms.
- `php artisan backup:snapshot --purpose="A2 smoke test"` — id=1,
  40875-byte gzip written to `storage/app/private/backups/db/`, SHA-256
  sidecar written, file passes `gzip -t`, gunzip header confirms
  `MySQL dump 10.13 Distrib 8.4.3`.
- `php artisan migrate --force` (no skip) — G2 fires as designed, writes
  `pre-migrate-*` snapshot (277 KB), then reports "Nothing to migrate."
- `php artisan schedule:list` — confirms `0 3 * * * php artisan backup:snapshot`.

### Design decision — decoupling `source_connection` from `database.default`

Testing surfaced that using `database.default` for the driver check
forced `Backup` model INSERTs to target the same connection. In tests
this drove sqlite writes into an unreachable MySQL server. Fix: added
`config('backup.source_connection')` (defaults to `database.default`),
used only for the driver check + connection details for the dumper.
`Backup` model writes always follow `database.default`. Bonus: dumping
a read-replica in production is now a config change instead of a code
change.

### Guardrails observed

- Zero new Composer/NPM packages (Decision 8).
- Additive + reversible migration; rollback verified before merge.
- Pre-migration mysqldump captured (habit, even though no ALTER on
  existing tables).
- G2 verified against real dev MySQL (fires + rolls the safety net
  forward).
- Regression fences ship with the code — 15 new tests across
  `record()`, `dumpDatabase()`, `verify()`, `prune()`, command surface.
- Filename layout matches ADR §4: `{disk}/backups/db/{YYYYMMDD-HHMMSS}.sql.gz`.

### Milestones

- **M1 (Never again)** — CLOSED with A2. Daily verified DB backups exist;
  retention enforced; `migrate:fresh` on MySQL blocked without env
  override; auto pre-migrate snapshot proven live on dev.

### Next

Handoff §1 flips A2 → ✅ DONE. Next task is **B1** (media/storage backup —
weekly ZIP manifest with per-file SHA-256; extends BackupService with
`snapshotMedia()`). No schema change expected; no owner gate beyond the
Decision 2 approval already on file.
