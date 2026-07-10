# Runbook: Rollback

> When something has gone wrong. **Read this first.** Reading it while
> panicking is what causes the second incident.

**Golden rule:** never `migrate:fresh` / `migrate:reset` / `TRUNCATE`
anything — even in "recovery" mode. That is what caused the Phase 6 dev-DB
wipe. Restore into a **separate** database first, verify, THEN swap.

---

## 0. Before you touch anything

1. **Stop taking writes** if possible: put the site into maintenance mode
   (`php artisan down`) — better a 500 for users than corrupt data.
2. **Take a fresh backup** of the current (broken) state:
   ```bash
   php artisan db:backup panic-$(date +%Y%m%d-%H%M%S) --purpose="broken state snapshot before rollback"
   ```
   This is your safety net for the safety net.
3. **Read the situation** — do NOT run any recovery commands until you can
   describe what you're recovering from in one sentence.

---

## 1. "The migration I just ran broke something"

### Symptoms
- App boots but 500s on specific pages
- SQL errors in `storage/logs/laravel.log`
- Data looks wrong but tables exist

### Playbook

```bash
# See what ran
php artisan migrate:status

# Roll back the latest migration
php artisan migrate:rollback --step=1

# Verify the down() actually reverted the schema
php artisan migrate:status
```

If `migrate:rollback` itself fails (e.g. down() has a bug or hits an FK
constraint you didn't foresee):

```bash
# Restore from the pre-migration backup — the one you took because you read
# the AGENTS.md §8 rules.
LATEST_BACKUP=$(ls -t storage/app/db-backups/pre-*.sql | head -1)
echo "Restoring: $LATEST_BACKUP"

# Restore into a SEPARATE DB first
mysql -e "CREATE DATABASE bintan_prestige_verify"
mysql bintan_prestige_verify < "$LATEST_BACKUP"

# Diff row counts vs current DB (spot check)
for tbl in pages content_entries products site_settings; do
    echo "$tbl:"
    mysql -N -e "SELECT COUNT(*) FROM bintan_prestige.$tbl"           # current
    mysql -N -e "SELECT COUNT(*) FROM bintan_prestige_verify.$tbl"    # backup
done
```

Only after verification, swap:

```bash
# ONLY when the verify DB looks right
mysql -e "RENAME TABLE bintan_prestige.pages TO bintan_prestige_broken.pages"
# ... etc, table by table
```

---

## 2. "Deploy broke production"

### Symptoms
- Site down / 500s across the board
- Users can't log in / can't check out

### Playbook

```bash
# 1. Roll code back to the last known-good tag
git fetch --tags
git checkout <last-good-tag>
composer install --no-dev --optimize-autoloader
npm run build
php artisan optimize:clear

# 2. Roll DB back to that tag's schema
php artisan migrate:status
php artisan migrate:rollback --step=<n>          # count migrations run since last-good

# 3. If DB rollback fails: restore from the pre-deploy backup
LATEST_BACKUP=$(ls -t storage/app/db-backups/pre-deploy-*.sql | head -1)
# Restore into separate DB, verify, swap (see §1)

# 4. Maintenance off
php artisan up
```

**Post-mortem in the same session:** update `docs/runbooks/deploy-checklist.md`
with the specific gotcha you hit.

---

## 3. "Data was accidentally deleted / corrupted"

### Symptoms
- Rows missing, values zeroed, JSON columns garbled
- A single admin action wiped something they didn't mean to

### Playbook

**DO NOT** run any writes to `bintan_prestige` yet.

```bash
# 1. Screenshot the error / SHOW SQL / bad row values
# 2. Take a backup of the currently-broken DB (evidence + safety net)
php artisan db:backup corrupted-$(date +%Y%m%d-%H%M%S) --purpose="corrupted state, do not delete"

# 3. Find the most recent GOOD backup
ls -lt storage/app/db-backups/pre-*.sql | head -5

# 4. Restore into a recovery DB — NEVER into the live DB
mysql -e "CREATE DATABASE bintan_prestige_recovery"
mysql bintan_prestige_recovery < storage/app/db-backups/pre-<task-id>-<ts>.sql

# 5. Selectively import back what you need
mysqldump bintan_prestige_recovery <specific-table> \
    --where="<narrow-condition>" \
    | mysql bintan_prestige
```

**For very recent losses** (< 14 days ago) MySQL binlog is available:

```bash
# Find the binlog covering the incident window
ls -la storage/app/binlog-recovery/

# Replay a narrow time window into a recovery DB
mysqlbinlog \
    --start-datetime="2026-07-10 10:00:00" \
    --stop-datetime="2026-07-10 10:15:00" \
    --skip-gtids \
    storage/app/binlog-recovery/mysql-bin.000123 \
    | mysql bintan_prestige_recovery
```

Reference: `ai/reports/phase-6/post-release-db-recovery.md` — the full
Phase 6 §16 incident playbook, which this whole file is built on.

---

## 4. "The dev DB is gone" (the Phase 6 incident)

If someone ran `migrate:fresh` against MySQL (violating AGENTS.md §8):

1. **Stop.** Do not re-run anything. Do not commit anything.
2. Locate the last known backup in `storage/app/db-backups/`.
3. Locate the binlogs in `storage/app/binlog-recovery/`.
4. Follow §3 above (restore into a recovery DB, then selectively import).
5. Full playbook: `ai/reports/phase-6/post-release-db-recovery.md`.
6. After recovery, verify `tests/TestCase.php` still refuses non-sqlite test
   DBs — that guard is the reason this rule set exists.

---

## 5. Guards to re-verify after ANY recovery

- [ ] `.env` still points at `bintan_prestige` (or the intended target).
- [ ] `tests/TestCase.php` non-sqlite refusal guard intact.
- [ ] MySQL `log_bin = ON` still enabled.
- [ ] `storage/app/binlog-recovery/*` files intact.
- [ ] Full suite green: `php artisan test`.
- [ ] Admin login works with an existing account.
- [ ] Public pages render (sample: `/`, `/id`, `/products`, `/sitemap.xml`).

## 6. Communicating

Every rollback event ends with:

- A short entry in `docs/changelog/CHANGELOG.md` (what broke, when, what
  restored).
- A follow-up regression fence test that would have caught this — this is
  the Phase 7 C1/C2/C3 pattern applied post-mortem.
