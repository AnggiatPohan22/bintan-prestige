# Phase 8 — A0 Architecture Decision Record
# Bintan Prestige CMS — Operational Maturity

> **Status:** ✅ LOCKED (owner sign-off 2026-07-11).
> **Author of decisions:** Architecture pass 2026-07-09 → refined and approved 2026-07-11.
> **Approval wording (verbatim from owner):** *"gunakan opsi no 1"* (naming choice)
> + *"approved"* (Decisions 1–8) — 2026-07-11.

## 0. Purpose

This file locks the eight architecture decisions that govern Phase 8
(Operational Maturity). Every subsequent Phase 8 task (A1 → C4) inherits these
decisions. Decisions can only be changed by the owner writing an explicit
override in a subsequent conversation, followed by an updated ADR entry here.

Sources:
- Grand plan: `ai/reports/phase-8/phase-8-grand-plan.md` (§3 decisions).
- Living handoff: `ai/reports/phase-8/phase-8-progress-handoff.md`.
- Master rules: `AGENTS.md` §2 (authority order), §8 (safety), §9 (approvals).

---

## Naming choice — LOCKED ✅ (Option 1)

The branch `feature/phase-8-a1-auto-backup` (merged into `develop` at
`008e242` on 2026-07-11) is **retroactively categorised as "A0 pre-groundwork"**:
it delivered a manual `php artisan db:backup <task-id> --purpose=…` command +
four operational runbooks (`docs/runbooks/{README,db-backup,deploy-checklist,rollback}.md`).
This is groundwork that proves the `mysqldump`-wrapper approach — it is **not**
the destructive-command guards or the scheduled BackupService.

Proper Phase 8 numbering therefore restarts at **A1 = destructive-command
guards** per grand plan §5.

---

## Decision 1 — Backup engine — LOCKED ✅

**Chosen:** Own thin `BackupService` wrapping `mysqldump`, zero packages.

- Uses `mysqldump --single-transaction --routines --triggers` (InnoDB-safe).
- Output: gzipped to `storage/app/backups/db/{name}-{YYYYMMDD-HHMMSS}.sql.gz`
  + SHA-256 checksum file adjacent (`.sha256`).
- Retention: prune by count (see Decision 2 defaults) — configurable in
  `config/backup.php`.
- Verification step: gunzip + head-check + row-count sample after each run;
  failed verify → `backups.status = failed`.

Rejected: `spatie/laravel-backup` — battle-tested but adds a package
(⚠️ AGENTS.md §9 gate) and still shells out to `mysqldump`; no material win.

Landing task: A2 (BackupService core + `backups` table).

---

## Decision 2 — Media backup size strategy — LOCKED ✅

**Chosen:** Weekly ZIP manifest of `storage/app/public` with per-file
SHA-256; skip files whose hash matches the prior manifest (incremental de
facto). No external tools required beyond `zip`.

**Retention defaults** (tunable in `config/backup.php`):

| Bucket | Default kept |
|---|---|
| Daily DB | **14** |
| Weekly DB | **8** |
| Weekly media | **4** |

Rejected: `rsync`-style incremental — more efficient at scale but drags in
another binary dependency and complicates portable restore.

Landing task: B1 (media/storage backup) — implements manifest + retention.

---

## Decision 3 — Restore path — LOCKED ✅

**Chosen:** CLI-first, admin wraps it later. `A backup that has never been
restored is not a backup.`

Command shape: `php artisan backup:restore {id}` performs, in order:

1. Integrity check (checksum verify + gunzip head).
2. **Automatic safety snapshot of the CURRENT DB** (invokes `db:backup` with
   task-id `pre-restore-{ts}`).
3. Typed confirmation prompt — user must type `RESTORE` verbatim (`--force`
   flag skips this in automated tests only).
4. `mysql < …` import.
5. Post-restore smoke: `migrate:status`, sample row counts against a fixed
   set of tables (pages, content_entries, products, users), audit-log entry.
6. Written report to `storage/app/backups/restore-reports/{ts}.md`.

Admin UI (B4-late or B7) is a thin wrapper that queues the command. CLI-first
guarantees the tool works when the app itself is broken.

Landing task: B4 (restore engine).

---

## Decision 4 — Export/import bundle format — LOCKED ✅

**Chosen:** Versioned ZIP bundle.

Bundle contents:
```
manifest.json                — schema version, source app version, active
                               locales, counts, timestamp
domains/pages.json           — pages + page_blocks (both rails)
domains/content-model.json   — content_types, field_groups, fields, taxonomies, terms
domains/entries.json         — content_entries + content_entry_index +
                               content_entry_term + content_entry_relations
domains/menus.json           — menus + menu_items
domains/settings.json        — site_settings (allow-list; secrets never)
domains/translations.json    — translations sidecar (Phase 7)
media/…                       — referenced media files at original paths
```

**Domains NEVER exported:** `users`, `bookings`, `booking_items`, submissions,
`password_resets`, `personal_access_tokens`, `sessions`, `.env`, any secrets.

**Conflict policy on import: skip by default; per-domain `--overwrite=<domain>`
opt-in flag.** No auto-overwrite. Import always runs `--dry-run` first
implicitly and surfaces a create/update/skip diff before touching anything.

**Id remapping:** by natural key.
- Pages / ContentEntries: `translation_group_id` first, then `slug` per locale.
- ContentTypes: `slug`.
- FieldGroups + Fields: `key`.
- Taxonomies / Terms: `slug`.
- Menus: `location`; menu_items by path within tree.
- Media: routed through `MediaService::createFromPath(...)` — collections
  preserved (one door — AGENTS.md §8).

Landing tasks: B5 (export) + B6 (import).

---

## Decision 5 — Monitoring / health dashboard — LOCKED ✅

**Chosen:** `HealthCheckService` with small check classes, zero packages.

Minimum check set (at least seven):

1. `LastBackupAgeCheck` — red if latest ok backup > 26 h.
2. `SchedulerHeartbeatCheck` — cache key `ops.scheduler.heartbeat` touched
   every minute by a self-registered scheduled task; red if > 3 min stale.
3. `FailedJobsCheck` — `failed_jobs` count; warn ≥ 1, red ≥ 10.
4. `DiskFreeCheck` — `disk_free_space(...)` on storage root; warn < 20 %, red < 5 %.
5. `LogErrorTailCheck` — `laravel.log` last 24 h, count `ERROR|CRITICAL`; warn ≥ 5,
   red ≥ 25.
6. `DbSizeCheck` — DB size in MB (info level; red only if growth spike > 25 %
   over 24 h, comparing to a rolling record).
7. `MediaOrphanCountCheck` — count of media rows whose file is missing on disk
   (uses B8's safer orphan logic).

Surfaces: admin **Ops Dashboard** page (`/admin/ops`) + compact widget on the
main admin dashboard. Optional **daily digest mail** to
`config('mail.from.address')` (opt-in via `config/ops.php`), and a
**critical-red alert mail** on any transition to red.

Rejected for now: Telescope, Horizon, Sentry. Any addition needs a
package-gate approval (AGENTS.md §9).

Landing task: B7 (Ops / health dashboard).

---

## Decision 6 — Safety guards — LOCKED ✅ (A1 scope)

**Chosen:** Runtime guards baked into the artisan lifecycle.

**Guard G1 — Destructive-command block on MySQL:**
- Listens for `Illuminate\Console\Events\CommandStarting`.
- Refuses to run `migrate:fresh`, `migrate:reset`, `db:wipe` when the *default*
  DB connection driver is `mysql` / `mariadb`.
- Override: `APP_ALLOW_DESTRUCTIVE=true` env var. (No custom Symfony flag —
  keeps us out of Symfony option-parsing surgery on core commands.)
- Message points at `AGENTS.md` §8 + Phase 6 §16 incident.

**Guard G2 — Auto pre-migrate backup:**
- Same listener; on `migrate` (not fresh/reset) when driver = mysql/mariadb.
- Invokes `db:backup` with task-id `pre-migrate-{YYYYMMDD-HHMMSS}` and purpose
  `"auto pre-migrate snapshot"`.
- Toggle: `config('safety.auto_backup_before_migrate', true)` — default ON in
  all envs (dev too — safety must be uniform).
- Skip: `APP_SKIP_AUTO_BACKUP=true` for cases where a manual backup was just
  taken and we don't want a double.

**Guard G3 — Dev config-cache warning:**
- Same listener; on ANY command in `local` env, if
  `bootstrap/cache/config.php` exists → write a bright warning to stderr
  ("Config cache detected in local env — this is the exact mechanism behind
  the 2026-07-07 dev DB wipe. Run `php artisan optimize:clear` before
  continuing.").
- Warning only; does not abort.

**Regression fences (test in A1):**
- `migrate:fresh` on the `mysql` connection without the env var → throws.
- `migrate:fresh` on `sqlite` connection → runs (test-suite path stays green).
- `migrate:fresh` on `mysql` with `APP_ALLOW_DESTRUCTIVE=true` → runs.
- `migrate` on `mysql` triggers a `db:backup` call (fake/spy the command bus).
- `migrate` on `mysql` with `APP_SKIP_AUTO_BACKUP=true` → skips backup.

Landing task: **A1 (this branch — `feature/phase-8-a1-guards`).**

---

## Decision 7 — REST/GraphQL Content API disposition — LOCKED ✅

**Chosen:** Defer to a standalone follow-up phase (**not Phase 8**).

Rationale: Export/import (B5/B6) already covers the content-migration
use case, which is what actually blocks the owner today. A public read/write
API is a product feature (auth, rate limiting, versioning, docs), not
operational maturity. It deserves its own scoping pass.

Impact on Phase 8: none — nothing in §5 of the grand plan depends on the API.

---

## Decision 8 — Zero new Composer/NPM packages — LOCKED ✅

**Chosen:** Zero new packages for Phase 8.

Everything above is buildable with Laravel core + host binaries
(`mysqldump`, `mysql`, `gzip`, `zip`) that are already present.

Precedent: Phase 7 shipped an end-to-end multi-language stack with **zero**
new packages. Same bar applies to Phase 8. If any task hits a wall that
requires a package, it re-enters the approval flow (AGENTS.md §9 with the
24-hour review window).

---

## Cross-decision constraints

- **PHPStan level 5 / 0 errors** — unchanged from Phase 7.
- **Full suite green** — Phase 7 baseline is 1003 on `develop` after both
  merges. Phase 8 must not regress it; every task adds its own regression
  fence.
- **Protected modules stay protected** — AGENTS.md §5 rules unchanged.
- **Media Library one-door rule** — any upload surface added by B3/B5/B6 UI
  uses `<x-admin.media-image-field>` etc. (AGENTS.md §8).
- **i18n one-door rule** — any new user-facing string that could plausibly
  need translation follows `ai/skills/i18n-skill.md`.

---

## Approval gates that follow (per grand plan §7)

1. **A1** — guard hooks into artisan lifecycle. Approach approved in Decision 6.
2. **A2** — new `backups` table (⚠️ schema change).
3. **B2** — any new filesystem disk credentials (env addition).
4. **B4** — restore engine sign-off *before* the admin wrapper exists.
5. Zero-package confirmation on every task (Decision 8).

---

## Related documents

- Grand plan (constitution): `ai/reports/phase-8/phase-8-grand-plan.md`.
- Living handoff: `ai/reports/phase-8/phase-8-progress-handoff.md`.
- Runbooks (from A0 pre-groundwork): `docs/runbooks/{README,db-backup,deploy-checklist,rollback}.md`.
- Phase 6 §16 incident: `ai/reports/phase-6/post-release-db-recovery.md`.
