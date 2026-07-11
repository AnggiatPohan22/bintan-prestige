# Phase 8 — B1: Media/Storage Backup (Weekly ZIP + Dedup)

**Branch:** `feature/phase-8-b1-media-backup` (base: `develop` @ `9e7e588`).
**Date:** 2026-07-11.
**Owner sign-off:** *"approved option a"* (2026-07-11) — full ZIP with dedup skip when unchanged.
**Test baseline:** 1032 → **1046** (14 new fences). PHPStan L5: 0 errors.

## Task: Phase 8 B1 — media snapshot pipeline

### Design (locked at approval — ADR §2 with a documented deviation)

- **Option (a) chosen over true incremental.** Each snapshot writes a
  FULL ZIP of every source file — no cross-backup chain. When the
  current manifest's `total_sha256` matches the previous ok backup's
  embedded manifest, we record a `skipped` row instead of rewriting the
  ZIP. This gives the "incremental de facto" outcome ADR §2 asked for,
  while keeping restore trivial (1 ZIP file to unpack, no chain-walk).
- Same `Backup` table as A2 (`type='media'`). New `STATUS_SKIPPED`
  constant on the model. `Backup` schema untouched.
- Media source paths come from `config('backup.media.source_paths')`
  (defaults to `['storage/app/public']`; comma-separated env override
  via `BACKUP_MEDIA_SOURCE_PATHS`).

### Changed

- `app/Models/Backup.php` — added `STATUS_SKIPPED = 'skipped'`. No
  schema change (status column is varchar(16)).
- `config/backup.php` — new `media` block (source_paths, follow_symlinks,
  exclude_patterns) + new `schedule.media_weekly_at` (default `04:00`)
  + `schedule.media_weekly_day` (default `0` = Sunday).
- `app/Services/Backup/MediaSnapshotter.php` — new. Pure helper (no DB,
  no config reads):
  - `computeManifest($sourcePaths, $rootBase, $excludes, $followSymlinks)`:
    walks with `RecursiveDirectoryIterator`, hashes each file with
    SHA-256, sorts by path (determinism for dedup), computes
    `total_sha256` as SHA-256-of-`{path}:{sha256}\n`-lines.
  - `writeZip($manifest, $zipPath)`: writes the ZIP with each file at
    its manifest-relative path + embedded `manifest.json`.
  - `readEmbeddedManifest($zipPath)`: extracts and JSON-decodes
    `manifest.json` from an existing ZIP.
- `app/Services/BackupService.php` — extended:
  - Added `MediaSnapshotter` to the constructor (DI).
  - New `snapshotMedia(?string $purpose): Backup` — walks, hashes,
    dedup-compares against the newest ok media backup, writes ZIP or
    records `skipped`, verifies, prunes.
  - `prune()` now type-aware: media type reads
    `backup.retention.media_weekly` (default 4).
  - Filename generator upgraded to millisecond precision
    (`YYYYMMDD-HHMMSS-mmm`) so two same-second invocations produce
    distinct paths (removes an overwrite hazard).
- `app/Console/Commands/BackupSnapshotMedia.php` — new
  `backup:snapshot-media [--purpose=…]`. Distinguishes ok / skipped /
  failed on stdout; exits non-zero on failed.
- `bootstrap/app.php` — registers the command; schedules it
  `->weeklyOn(day, at)->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer()`.
- `tests/Feature/Phase8/BackupServiceMediaTest.php` — new. 12 fence
  tests. Fixture builds a real per-test scratch dir under
  `storage/framework/testing/` and points the walker at it:
  - Ok snapshot: records `ok`, gzip → ZIP file + `.sha256` sidecar,
    includes all source files + `manifest.json` embedded.
  - Failed snapshot: no `source_paths` configured; source dir empty.
  - Dedup: second call = `skipped`; changed file → new ZIP; added
    file → new ZIP.
  - `verify()`: true on ok; false when ZIP missing.
  - `prune()`: uses `media_weekly` retention (3 pruned out of 5, keep 2);
    does not touch db-type rows.
- `tests/Feature/Phase8/BackupSnapshotMediaCommandTest.php` — new. 2
  surface tests (command registration + non-zero exit on no-source).

### Impact

- **DB:** none. Uses existing `backups` table (type='media' + new
  `skipped` status).
- **Routes:** none.
- **Frontend:** none. Admin surface lands in B3.
- **Filesystem:** new directory
  `storage/app/private/backups/media/` created on demand.
- **Scheduler:** new weekly job Sunday 04:00 WIB.
- **Test suite:** 1032 → 1046 (+14). PHPStan L5: 0 errors. Duration ~207 s.
- **Env vars (new, opt-in):**
  - `BACKUP_MEDIA_SOURCE_PATHS` — comma-separated relative paths.
  - `BACKUP_MEDIA_FOLLOW_SYMLINKS` — bool (default false).
  - `BACKUP_MEDIA_WEEKLY_AT` — HH:MM (default `04:00`).
  - `BACKUP_MEDIA_WEEKLY_DAY` — 0-6 (default `0` = Sunday).

### Verification on dev

- Full suite: **1046/1046 pass** (baseline 1032 + 14 B1 fences).
- PHPStan level 5: **0 errors**.
- Real snapshot on `storage/app/public/`: id=2, 21 660 304-byte ZIP,
  68 files, SHA-256 sidecar written.
- Dedup smoke: second invocation → id=3, `status=skipped`,
  `meta.previous_backup_id=2`, no new ZIP.
- `php artisan schedule:list` shows
  `0 4 * * 0 php artisan backup:snapshot-media --purpose="Scheduled weekly"`
  next due in 16 hours.

### Rollback

Single-branch rollback:
```bash
git checkout develop
git branch -D feature/phase-8-b1-media-backup
```

Runtime cleanup (if the branch is merged then reverted):
```bash
rm -rf storage/app/private/backups/media
```
No DB rollback (uses existing `backups` table). Media backup rows can
be left as history.

### Guardrails observed

- Zero new packages (Decision 8). Uses PHP core `ZipArchive` +
  `hash_file()` + SPL iterators.
- No schema change. `STATUS_SKIPPED` fits in the existing varchar(16).
- Regression fences ship with the code — 14 new tests across
  `snapshotMedia()` (ok / failed / skipped / changed), `verify()`,
  `prune()`, command surface.
- Filename layout matches ADR §4 style with a small millisecond
  extension: `{disk}/backups/media/{YYYYMMDD-HHMMSS-mmm}.zip`.
- Restore path stays trivial — one ZIP per backup, no chain.

### Deferred (documented, not scope)

- **True per-file incremental / chain-restore** — spec preserved in
  ADR §2 for a possible B1.5 refinement. Not needed until media dir
  grows to a scale where 4× weekly full-ZIPs is a problem.
- **Off-machine copy** — B2 (new disk credentials → separate ⚠️ gate).
- **Admin UI to browse / verify / download / trigger** — B3.
- **Restore engine** — B4.

### Next

Handoff §1 flips B1 → ✅ DONE. Next task is **B2** (Off-machine target /
second-disk copy via Laravel filesystems). B2 gate = new disk
credentials in `.env` / `config/filesystems.php` (⚠️ needs owner
approval per grand plan §7 / AGENTS.md §9).
