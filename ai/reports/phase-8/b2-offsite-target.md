# Phase 8 — B2: Off-Machine Target (Cloudflare R2 via S3)

**Branch:** `feature/phase-8-b2-offsite-target` (base: `develop` @ `64beb05`).
**Date:** 2026-07-11.
**Owner sign-off:**
- *"Present B2 — Saya sudah ada Cloudflare R2 saat ini"* (chose R2 as target).
- *"Approved, waive 24h window"* (package addition + waived AGENTS.md §9 review window).

**Test baseline:** 1046 → **1057** (11 new fences). PHPStan L5: 0 errors.

## Task: Phase 8 B2 — offsite mirror to Cloudflare R2 (S3-compatible)

### Package addition (owner-approved, 24h window waived)

Two new Composer dependencies were added under an explicit AGENTS.md §9
approval:

- **`league/flysystem-aws-s3-v3` (^3.25)** — the S3 adapter for
  Flysystem 3. Officially "suggested" by `league/flysystem` (already
  installed). Used by tens of thousands of Laravel apps.
- **`aws/aws-sdk-php`** — transitive dependency. Official AWS SDK.

**Why the justification stands** (per AGENTS.md §9 "no clean way in
≤50 LOC"): Cloudflare R2 is accessible only via an S3-compatible API.
A hand-rolled S3 v4 signing client would be several hundred lines and
still lack retry / streaming / pooling that the SDK gives for free. The
two packages are battle-tested, actively maintained, and required by
zero-package-count alternatives no longer being real options.

### Changed

- `config/filesystems.php` — new `r2` disk. Uses the `s3` driver with
  R2-tuned defaults (`region=auto`, `use_path_style_endpoint=true`,
  `visibility=private`, `throw=true`). Credentials read from
  `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` / `R2_BUCKET` /
  `R2_ENDPOINT`, all of which fall back to the pre-existing generic
  `AWS_*` env vars if unset.
- `config/backup.php`:
  - New `disks.db_offsite` / `disks.media_offsite` — leave `null` to
    disable offsite copy for that type.
  - New `offsite` block: `copy_after_snapshot` (default `true`),
    `retry_attempts` (default `3`), `retry_delay_seconds` (default `30`),
    `alert_email` (optional, falls back to `mail.from.address`).
- `app/Services/BackupService.php`:
  - `copyToOffsite(Backup $backup): Backup` — streams the local artifact
    + `.sha256` sidecar to the configured offsite disk via
    `readStream()` / `writeStream()`, then recomputes SHA-256 from the
    remote to catch transport corruption. Retries with the configured
    delay. On final failure, records `meta.offsite_error` +
    `meta.offsite_last_attempt_at` + sends alert mail (best-effort).
  - `copyToOffsiteIfConfigured(Backup $backup): Backup` — wrapper the
    snapshot pipelines call. No-op when the master toggle is off or the
    type has no offsite disk configured.
  - `dumpDatabase()` + `snapshotMedia()` now call
    `copyToOffsiteIfConfigured()` right after prune. Offsite copy failure
    NEVER changes the local backup's `status` — the local artifact stays
    usable regardless.
  - New private helpers: `offsiteDiskFor(type)`, `hashRemoteFile(disk,
    path)` (streaming SHA-256 for gigabyte-scale files),
    `markOffsiteFailed(...)`, `offsiteAlertRecipient()`.
- `app/Console/Commands/BackupSyncOffsite.php` — new
  `backup:sync-offsite [backup_id?] [--type=db|media] [--force]`.
  Idempotent retry command for when: (a) an earlier snapshot's offsite
  copy failed, or (b) an offsite disk was configured after backups
  already existed. Skips backups that already have
  `meta.offsite_verified_at` unless `--force`.
- `bootstrap/app.php` — registers `BackupSyncOffsite` command. No new
  scheduler entry (copy happens synchronously after each ok snapshot).
- `tests/Feature/Phase8/BackupOffsiteTest.php` — new. 11 fence tests
  using two faked disks (`Storage::fake` for both local and offsite):
  - `copyToOffsite`: writes files + sidecar to offsite disk; updates
    `meta.offsite_disk` / `offsite_path` / `offsite_verified_at` /
    `offsite_attempts` on success.
  - `copyToOffsite`: no-op when offsite disk not configured / status not
    ok.
  - `copyToOffsite`: records `meta.offsite_error` + `offsite_last_attempt_at`
    when source file is missing (post-retry failure).
  - `copyToOffsiteIfConfigured`: skips when master toggle is off /
    offsite disk not configured.
  - `backup:sync-offsite`: registration, id-specific sync, already-verified
    skip semantics, invalid `--type` guard.

### Impact

- **DB:** none. Uses existing `backups.meta` json for offsite tracking.
  No schema change.
- **Routes:** none.
- **Frontend:** none. Admin surface for offsite status lands in B3.
- **Scheduler:** unchanged — offsite copy happens synchronously after
  each ok snapshot, so the existing daily DB + weekly media jobs cover
  it. `backup:sync-offsite` is a manual retry only.
- **Packages:** 2 new (owner-approved, §9 waived) —
  `league/flysystem-aws-s3-v3` + `aws/aws-sdk-php`.
- **Test suite:** 1046 → 1057 (+11). PHPStan L5: 0 errors.
- **Env vars (new, all opt-in):**
  - `BACKUP_DB_OFFSITE_DISK` / `BACKUP_MEDIA_OFFSITE_DISK` — set to
    `r2` (or any Laravel disk name) to enable per type.
  - `BACKUP_OFFSITE_COPY_AFTER_SNAPSHOT` (bool, default true).
  - `BACKUP_OFFSITE_RETRY_ATTEMPTS` (default 3).
  - `BACKUP_OFFSITE_RETRY_DELAY` (default 30 s).
  - `BACKUP_OFFSITE_ALERT_EMAIL` — optional; falls back to
    `mail.from.address`.
  - `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` / `R2_BUCKET` /
    `R2_ENDPOINT` (and optionally `R2_DEFAULT_REGION` /
    `R2_USE_PATH_STYLE_ENDPOINT`).

### `.env` setup instructions for the owner

Add to `.env` (production and/or dev):

```dotenv
# Cloudflare R2 offsite backup target
BACKUP_DB_OFFSITE_DISK=r2
BACKUP_MEDIA_OFFSITE_DISK=r2

R2_ACCESS_KEY_ID=<your-r2-token-id>
R2_SECRET_ACCESS_KEY=<your-r2-secret>
R2_BUCKET=bintan-prestige-backups
R2_ENDPOINT=https://<your-account-id>.r2.cloudflarestorage.com
# Defaults are already right for R2; only set these to override:
# R2_DEFAULT_REGION=auto
# R2_USE_PATH_STYLE_ENDPOINT=true

# Optional — alert email for final offsite failures (falls back to MAIL_FROM_ADDRESS)
# BACKUP_OFFSITE_ALERT_EMAIL=ops@bintanprestige.example
```

**Where to get the credentials:**
1. Cloudflare Dashboard → R2 → Create bucket (`bintan-prestige-backups`).
2. R2 → Manage R2 API Tokens → "Create API token" with permissions
   "Object Read + Write" scoped to the bucket.
3. Copy Access Key ID + Secret Access Key + the S3 Endpoint URL for
   your account.

**Verify after `.env` is set:**
```bash
php artisan backup:sync-offsite
# → should copy every ok backup to R2 and print "ok=N, failed=0, skipped=0"

php artisan backup:snapshot --purpose="B2 end-to-end test"
# → should print the local OK line, then no error (offsite runs silently
#   after; meta.offsite_verified_at will be populated on the new row).
```

### Verification during B2 development

- Full suite: **1057/1057 pass** (baseline 1046 + 11 B2 fences).
- PHPStan level 5: **0 errors**.
- Live R2 not exercised in this branch (needs owner-provided credentials
  in `.env`). Fake-disk regression fences cover every code path.

### Rollback

Single-branch rollback:
```bash
git checkout develop
git branch -D feature/phase-8-b2-offsite-target
composer remove league/flysystem-aws-s3-v3 aws/aws-sdk-php
```

If merged then reverted:
- No DB rollback (uses existing `backups` table and `meta` json).
- R2 bucket + tokens can be revoked / deleted by the owner.
- Any offsite-only artifacts on R2 can be manually deleted via
  Cloudflare dashboard.

### Guardrails observed

- **Package approval** documented + owner sign-off recorded above.
- **No schema change** — offsite tracking uses existing `backups.meta`.
- **Streaming I/O** — `readStream()` / `writeStream()` for
  gigabyte-scale files. `hashRemoteFile()` also streams.
- **Retry with delay** — configurable; default 3 attempts × 30 s.
- **Post-upload verification** — SHA-256 recomputed from remote after
  every successful write.
- **Best-effort alerts** — mail failure is logged and does not throw.
- **Fail-safe defaults** — offsite is DISABLED unless the owner
  explicitly sets `BACKUP_DB_OFFSITE_DISK` / `BACKUP_MEDIA_OFFSITE_DISK`.
- **Regression fences** — 11 new tests covering success, no-op, failure,
  master-toggle-off, and the command surface.

### What B2 does NOT include (deferred)

- **Admin UI to view offsite status / manually trigger** — B3.
- **Restore FROM offsite** — B4 (restore engine reads from any Backup
  row, whether the local file is still present or only the offsite copy
  is; B4 will decide the lookup order).
- **Off-machine test rehearsal against real R2** — C3 (owner runs this
  once credentials are configured; test fences here are enough to trust
  the code path until then).

### Milestone impact

- **M2 (Recoverable)** — B1 + B2 shipped. Remaining: B3 (admin UI) + B4
  (restore engine). Half of M2 is code-complete; the other half is UX +
  the restore path.

### Next

Handoff §1 flips B2 → ✅ DONE (with note about `.env` credentials the
owner needs to add before offsite copies actually leave the box). Next
task is **B3** (Backups admin UI — list / verify / download / trigger).
No new schema; no new packages; no owner gate.
