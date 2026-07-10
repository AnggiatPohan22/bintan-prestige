# Runbook: Deploy Checklist

> Enforced by AGENTS.md §8 (Environment isolation). Every push to production —
> including hotfixes — follows this checklist. Skipping steps is a bug.

---

## Pre-deploy (do these BEFORE touching production)

- [ ] **Local test suite green:** `php artisan test`
- [ ] **PHPStan clean:** `vendor/bin/phpstan analyse --no-progress`
- [ ] **No stale caches locally:** `php artisan optimize:clear` (never
      `config:cache` in dev — AGENTS.md §8)
- [ ] **Frontend built:** `npm run build`
- [ ] **Merged to `develop`** and CI green
- [ ] **CHANGELOG.md updated** with what's shipping
- [ ] **Migration review:** run `php artisan migrate --pretend` and read the
      SQL Laravel will run. If any ALTER, DROP, RENAME, or DEFAULT change is
      listed, treat as ⚠️ (owner approval + extra dump).

---

## On the deploy target

### 1. Maintenance mode + snapshot

```bash
php artisan down --render="errors::503"          # nice 503 page

# Take a labelled dump — this is your rollback anchor
php artisan db:backup deploy-$(date +%Y%m%d-%H%M%S) \
    --purpose="pre-deploy snapshot, $(git rev-parse --short HEAD)"
```

### 2. Pull code

```bash
git fetch --tags
git checkout <release-tag>          # or the latest develop merge commit
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. Review + apply migrations

```bash
# ALWAYS pretend first, ALWAYS
php artisan migrate --pretend

# Read the output. If anything looks wrong, STOP.
# If migrations touch existing tables, take an additional backup:
php artisan db:backup pre-<task-id>-<ts> --purpose="specific ALTER, e.g. add column X"

php artisan migrate --force
```

### 4. Clear caches (never `config:cache` in dev; production is fine)

```bash
php artisan optimize:clear
php artisan config:cache            # production only
php artisan route:cache             # production only
php artisan view:cache              # production only
```

### 5. Smoke test BEFORE lifting maintenance

```bash
# From the deploy target (not the LB / CDN)
curl -sf http://127.0.0.1/health > /dev/null && echo "OK: /health"
curl -sf http://127.0.0.1/ > /dev/null && echo "OK: /"
curl -sf http://127.0.0.1/sitemap.xml > /dev/null && echo "OK: sitemap"

# Full suite (SQLite in-memory) — regression fence check
php artisan test --testsuite=Unit
```

### 6. Lift maintenance

```bash
php artisan up
```

### 7. Post-deploy verification (5-minute soak)

- [ ] Homepage loads in EN and ID (`/` + `/id`)
- [ ] One page loads: `/pages/{a-real-slug}`
- [ ] Sitemap: `/sitemap.xml` returns XML with `xhtml:link`
- [ ] Admin login works
- [ ] Watch `storage/logs/laravel.log` for 5 minutes — no new errors

If any of these fail: go straight to `rollback.md` §2.

---

## Backups & retention

Every deploy leaves at least ONE backup on disk:
`storage/app/db-backups/pre-deploy-<ts>.sql`.

- Keep for ≥90 days on the host.
- Rsync/rclone to offsite storage weekly.
- **Never delete** files matching `pre-deploy-*.sql` less than 90 days old.

---

## Emergency stop

If mid-deploy you realise something is wrong (e.g. migration output looks
weird), do NOT try to "just finish it":

```bash
# Roll code back to the last-good tag
git checkout <last-good-tag>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear

# Roll DB back or restore from the pre-deploy backup you just took
# See runbooks/rollback.md §1 for detail

# Only THEN lift maintenance
php artisan up
```
