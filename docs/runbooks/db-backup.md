# Runbook: Database Backup

> Enforced by AGENTS.md §8 (Database non-negotiables) + §13. Every ALTER on an
> existing table is preceded by a backup with the naming convention below.

---

## 1. The rule (non-negotiable)

Before ANY of these:

- `php artisan migrate` where the pending migration includes an ALTER,
  DROP INDEX, RENAME, or DEFAULT change on an existing table.
- Manually running SQL against MySQL that touches existing tables.
- Every production deploy — even hotfixes.

Run **one command**:

```bash
php artisan db:backup <task-id> --purpose="short description"
```

Output: `storage/app/db-backups/pre-<task-id>-YYYYMMDD-HHMMSS.sql`
Companion metadata (if `--purpose` was given): same path + `.meta`.

Exit code is non-zero on failure — CI / deploy scripts can `set -e` on it.

---

## 2. `<task-id>` convention

Must match `^[a-z0-9][a-z0-9._-]{0,80}$`. Examples:

| Situation | task-id |
|---|---|
| Phase 8 A1 (this runbook itself) | `phase-8-a1-runbook` |
| Product child i18n migration | `b6.1-product-children` |
| Ad-hoc pre-deploy dump | `deploy-YYYYMMDD` |
| Before manual data patch | `patch-<ticket-id>` |

The command refuses invalid ids and exits code `2` — designed to catch typos
before the file lands on disk with a hard-to-search name.

---

## 3. Verifying a backup is real

The command already verifies:

1. mysqldump binary was found (Laragon paths + PATH).
2. Process exited zero.
3. Dump file exists.
4. Dump starts with the `MySQL dump` header line.
5. Dump size > 1 KB (a common silent-failure signature).

If any check fails the partial file is deleted so you don't accidentally rely
on it.

Manual verification (recommended once a week):

```bash
# Newest backup
ls -lt storage/app/db-backups/pre-*.sql | head -1

# Peek header + last line
BACKUP=$(ls -t storage/app/db-backups/pre-*.sql | head -1)
head -1 "$BACKUP"                          # → -- MySQL dump 10.13 ...
tail -1 "$BACKUP"                          # → -- Dump completed on ...
grep -c "CREATE TABLE" "$BACKUP"           # → should equal SHOW TABLES count
```

---

## 4. Storage & retention (AGENTS.md §13)

- Path: `storage/app/db-backups/` (gitignored).
- Retention: keep on disk for at least 90 days.
- The command warns when it finds `pre-*.sql` files older than 90 days.
  **Never auto-delete** — review manually and archive to cold storage
  (external drive / cloud) if valuable.
- **Never delete** `storage/app/binlog-recovery/` files — they saved us in
  Phase 6.

---

## 5. Cron / scheduled snapshot (recommended)

Add to your host cron (not `withSchedule` — we want this even if the app
crashes):

```cron
# Daily dump at 02:15 local time, keep 30 days offline
15 2 * * * cd /path/to/bintan-prestige && \
    php artisan db:backup "daily-$(date +\%Y\%m\%d)" \
      --purpose="scheduled daily snapshot" \
      >> storage/logs/backup.log 2>&1
```

Rsync/rclone offsite once a week.

---

## 6. Bash alternative (`scripts/backup-db.sh`)

The artisan command uses PHP; if you need to back up when the app itself is
broken (composer install failed, PHP has a fatal, etc.):

```bash
scripts/backup-db.sh <task-id> --purpose="..."
```

Same naming convention, same safety checks. Requires bash (Git Bash on Windows
is fine).

---

## 7. Failure modes

| Symptom | Cause | Fix |
|---|---|---|
| `Invalid task id: ...` (exit 2) | Spaces / uppercase / weird chars | Rename to `^[a-z0-9][a-z0-9._-]+$` |
| `mysqldump not found` (exit 5) | MySQL client tools not installed on PATH | Install client tools OR extend `resolveMysqldump()` in `BackupDatabase.php` with the new path |
| `Backup file suspiciously small` | mysqldump ran but produced garbage — often wrong credentials or table lock | Check `.env` DB creds; check `SHOW GRANTS`; try `mysqldump --version` manually |
| `db:backup only supports the mysql connection` | `database.default` is not `mysql` | Change to mysql in `.env`, or use a different backup tool |
