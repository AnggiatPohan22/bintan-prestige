# Phase 8 — Operational Maturity — Grand Plan
# Bintan Prestige CMS

> **Status:** PLANNING (not started). Sequenced **after Phase 7 (i18n)**.
> **Author:** Architecture planning pass — 2026-07-09
> **Depends on:** Phase 7 COMPLETE (Internationalization)
> **Stack:** Laravel 13.8 | PHP 8.3 | Tailwind | Alpine.js | MySQL 8
> **Location:** `ai/reports/phase-8/phase-8-grand-plan.md`
>
> This document is the **constitution for Phase 8**. Per-task detail will live
> in `ai/reports/phase-8/phase-8-progress-handoff.md` (created at A0 approval).
> Authority order applies (AGENTS.md §2); schema changes need approval (§9);
> the Media Library one-door standard (§8) applies to any new upload surface.

---

## 0. Why This Phase (One Paragraph)

On **2026-07-07 the dev database was wiped** by a cached-config `migrate:fresh`
and was only recovered because MySQL binlogs happened to reach back far enough —
a full day of forensic replay work that should never be the recovery plan
(post-mortem: `ai/reports/phase-6/post-release-db-recovery.md`). The CMS is now
feature-complete for its market (builder, content modeling, media, i18n after
Phase 7) but operationally fragile: **no automated backups, no restore path, no
export/import, no health visibility, and destructive commands with no guard
rails**. Phase 8 turns a powerful CMS into a *dependable* one: the owner can
sleep knowing the site backs itself up, can move content between environments,
and can see problems on a dashboard before visitors do.

**Phase 8 Goal:** Automated, verified backups with a practiced restore path;
content export/import between environments; a monitoring/health dashboard in
admin; and safety guards that make the 2026-07-07 incident structurally
impossible to repeat.

---

## 1. Scope

### 1.1 In Scope

1. **Safety guards first** — block `migrate:fresh`/`db:wipe` outside sqlite/CI
   without an explicit flag; automatic pre-migration dump before any ALTER on a
   MySQL connection; config-cache footgun warning in dev.
2. **Automated backups** — scheduled DB dumps (`mysqldump`) + media/storage
   snapshots, retention policy, integrity verification, off-machine target
   support (any S3-compatible/secondary disk via Laravel filesystems).
3. **Restore path** — admin-listed backups with download; guided restore flow
   with dry-run verification; documented runbook rehearsed at least once (C3).
4. **Content export/import** — portable bundles (JSON + media manifest ZIP) for
   pages+blocks, content types+fields+entries, taxonomies, menus, settings,
   translations — for staging→production moves and content seeding.
5. **Monitoring & health dashboard** — admin page + dashboard widget: last
   backup age, scheduler heartbeat, queue/failed jobs, storage free space,
   `laravel.log` error tail, DB size, media orphan count; optional email alert
   on red status.
6. **Media ops (staged carry-overs from Phase 6.1)** — orphan purge made safe
   (trash folder + review list instead of hard delete — this deleted a real
   file on 2026-07-08), media collection re-assignment UI, optional
   `media:organize` command, SVG sanitizer decision for `icon`/`logo`.
7. **Production pre-flight** — documented + scripted checklist (env, caches,
   `schedule:run` cron, storage link, backup target) closing the Phase 6
   release-gate leftover.
8. **Release audit (Stage C)** — including a **real restore rehearsal** as the
   headline smoke test.

### 1.2 Out of Scope (explicitly NOT this phase)

- **Public REST/GraphQL content API.** Listed as a Phase 8 candidate since
  Phase 6 — **deferred to a standalone follow-up** unless the owner pulls it in
  at A0. Export/import covers the migration use case; an API is a product
  feature, not operational maturity.
- High-availability / multi-server / Docker orchestration — single-VPS reality.
- APM integrations (Sentry/Telescope in prod) — candidate at A0 if owner wants
  a package; default is zero-package log surfacing.
- Auto-translation, marketing automation, analytics expansion.

### 1.3 Guardrails

- Backup/restore code must be **boring and dependency-light**: `mysqldump` /
  `mysql` binaries (proven in the 2026-07 recovery) via config paths.
- Restore is the most dangerous feature the CMS will ever have: every
  destructive step gated behind explicit typed confirmation + automatic
  pre-restore backup of the current state.
- Never store credentials in exported bundles; exports exclude `users`,
  bookings, submissions, and secrets by default.
- PHPStan level 5 / 0 errors; suite green; protected modules untouched.

---

## 2. Mental Model & Naming

| Concept | Our term | Notes |
|---|---|---|
| Scheduled full-DB dump + media manifest | **Backup** | point-in-time, restorable |
| Selected-content portable bundle | **Export bundle** | environment-agnostic, importable |
| Admin health overview | **Ops Dashboard** | read-only signals + actions |
| Pre-destructive automatic dump | **Safety snapshot** | invisible until needed |

> Backups answer "the server died"; export bundles answer "move this content
> to production". They are different artifacts with different lifecycles — do
> not merge them into one feature.

---

## 3. Key Architecture Decisions (the "A0" of Phase 8)

### 3.1 Backup engine — **thin `mysqldump` wrapper, zero packages (recommended)**

| Option | Pro | Con |
|---|---|---|
| **Own thin wrapper (recommended)** | Zero deps; exact control; binary path already proven in recovery | We own retention/verify code (~small) |
| `spatie/laravel-backup` | Battle-tested, notifications | New package (⚠️ §9), config surface, still shells out to mysqldump |

- Recommendation: own `BackupService` — `mysqldump --single-transaction` to
  `storage/app/backups/db/`, gzip, checksum, prune by retention config,
  optional copy to a second Laravel disk. Media backup = incremental copy or
  ZIP manifest of `storage/app/public` (decide size strategy at A0).
- Scheduler: daily DB dump (retention e.g. 14 daily + 8 weekly), weekly media
  snapshot — all in `config/backup.php` (owner-tunable), surfaced in admin.

### 3.2 Restore path — **guided, gated, rehearsed (recommended)**

- Admin lists backups (age, size, checksum-verified badge) + download.
- Restore flow: pick backup → integrity check → **automatic safety snapshot of
  current DB** → typed confirmation (`RESTORE`) → `mysql` import → post-restore
  smoke (`migrate:status`, counts) → report. Runs via queued command with a
  progress log, not a web request.
- A `backup:restore` artisan command is the actual engine; admin UI is a
  wrapper. CLI-first keeps it usable when the app itself is broken.

### 3.3 Export/import format — **versioned JSON bundle + media manifest (recommended)**

- ZIP: `manifest.json` (schema version, source app version, locale set,
  counts) + one JSON file per domain (pages, blocks, content model, entries,
  taxonomies, menus, settings, translations) + referenced media files under
  `media/` with original paths.
- Import: dry-run diff first (create/update/skip counts), id-remapping via
  slugs/keys/translation groups, media placed through **MediaService** (one
  door — collections preserved), never touching users/secrets.
- Conflict policy at A0: skip / overwrite / duplicate (recommend: skip by
  default, per-domain overwrite opt-in).

### 3.4 Monitoring — **pull-based checks, zero packages (recommended)**

- `HealthCheckService` with small check classes (backup age, scheduler
  heartbeat file, failed_jobs count, disk free, log error tail, DB size, media
  orphans). Each returns status (ok/warn/critical) + detail.
- Ops Dashboard admin page + compact widget on the main dashboard; optional
  daily digest / critical alert email (uses existing mail config).
  No Telescope/Horizon by default (⚠️ package gate if owner wants them).

### 3.5 Safety guards — **environment + connection guards (recommended)**

- Extend the existing sqlite test guard idea into runtime: a service provider
  hook that aborts `migrate:fresh` / `db:wipe` / `migrate:reset` on the MySQL
  connection unless `--force-destructive` (new flag) or env allowlist — the
  direct structural fix for the 2026-07-07 incident.
- `backup:snapshot` auto-invoked before `migrate` on MySQL (config-toggle).

---

## 4. Data Model (proposed — deliberately minimal)

```
backups                        [NEW ⚠️ — or filesystem-manifest only, decide A0]
  id, type (db|media), disk, path, size, checksum, status
  (ok|failed|pruned), created_at
  → Recommendation: YES table (dashboard queries, retention bookkeeping)

ops health                     no tables — computed live; scheduler heartbeat =
                               cache key / storage timestamp file

export bundles                 no tables — artifacts on disk, listed from
                               filesystem manifest
```

Everything else reuses existing infrastructure: scheduler (Phase 4), audit log
(Phase 4 — backup/restore/import actions are audited), Media Library (Phase
6.1), mail config.

---

## 5. Work Breakdown — Stages A / B / C

### Stage A — Foundation, Decisions & Guards

| Task | Name | Output | Approval gate |
|---|---|---|---|
| **A0** | Architecture Decision Record | Lock §3.1–§3.5 (+ REST-API in/out, media backup size strategy, conflict policy); create handoff; confirm zero packages | ⚠️ decision set |
| **A1** | Destructive-command guards | Runtime guard for `migrate:fresh`/`db:wipe` on MySQL; pre-migrate snapshot hook; dev config-cache warning | ⚠️ touches artisan flow |
| **A2** | `BackupService` core + `backups` table | Dump, gzip, checksum, prune, verify; `backup:snapshot` command; scheduled daily | ⚠️ new table |

### Stage B — Build the Modules

| Task | Name | Notes |
|---|---|---|
| **B1** | Media/storage backup | Weekly snapshot per §3.1 size strategy; manifest; retention |
| **B2** | Off-machine target | Second-disk copy via Laravel filesystems (S3-compatible/FTP/local2); failure alerting |
| **B3** | Backups admin UI | List/verify/download/trigger; wired into Ops Dashboard signals |
| **B4** | Restore engine | `backup:restore` command per §3.2 (safety snapshot, typed confirm, post-checks); admin wrapper LAST, CLI first |
| **B5** | Export bundles | `content:export` + admin UI; domain selection; manifest versioning |
| **B6** | Import engine | Dry-run diff, id-remap, media via MediaService, conflict policy; admin wrapper |
| **B7** | Ops / health dashboard | `HealthCheckService` + checks + admin page + dashboard widget + alert mail |
| **B8** | Media ops carry-overs | Safe orphan purge (trash + review list), collection re-assign UI, `media:organize` (dry-run first), SVG sanitizer decision for icon/logo |
| **B9** | Production pre-flight | Scripted checklist command (`app:preflight`) + docs; closes Phase 6 release-gate leftover |

### Stage C — Release Audit

| Task | Name | Pass criteria |
|---|---|---|
| **C1** | Static Analysis & Code Quality | PHPStan level 5 / 0 errors; command/service coverage |
| **C2** | Performance & Safety Audit | Backup runtime + size measured; dashboard adds no heavy queries; guards proven (attempted `migrate:fresh` on MySQL fails without flag) |
| **C3** | **Restore & import rehearsal** | Full backup → restore into a scratch DB → smoke green; export from dev → import into scratch → diff clean. *A backup that has never been restored is not a backup.* |
| **C4** | Documentation | `docs/modules/operations.md` (runbooks: backup, restore, move-to-prod); `ai/skills/operations-skill.md`; CHANGELOG; AGENTS/Claude sync; "Phase 9?" notes |

---

## 6. New Skill Files & Docs

| File | Purpose |
|---|---|
| `ai/skills/operations-skill.md` | Backup/restore/export rules, guard rails, runbook pointers |
| `ai/reports/phase-8/phase-8-progress-handoff.md` | Living source of truth (created at A0) |
| `docs/modules/operations.md` | Runbooks: nightly backup, disaster restore, staging→prod content move, pre-flight |

Skill Map row: `| Backup / Restore / Ops / Monitoring | operations-skill.md |`

---

## 7. Approval Gates

1. **A0** — decision set, incl. REST-API disposition and media backup strategy.
2. **A1** — guard hooks into artisan lifecycle.
3. **A2** — `backups` table (new schema).
4. **B2** — any new filesystem disk credentials/config.
5. **B4** — restore engine sign-off *before* the admin wrapper exists.
6. Zero-package confirmation (or explicit approval for spatie/backup at A0).

---

## 8. Risks & Mitigations

| Risk | Likelihood | Mitigation |
|---|---|---|
| Restore bug destroys good data | Low/Severe | Safety snapshot always-first; CLI-first; typed confirm; C3 rehearsal on scratch DB |
| Backups silently failing (worst outcome: false confidence) | Med | Checksum + verify step; dashboard "last backup age" red state; alert mail |
| Media backup too large for VPS disk | Med | A0 size strategy (incremental/manifest); retention tuning; off-machine target |
| Export/import id collisions corrupt content | Med | Slug/key-based remap; dry-run diff mandatory; skip-by-default conflicts |
| Dump locks production DB | Low | `--single-transaction` (InnoDB), off-peak schedule |
| Scope creep (API, APM, HA) | High | §1.2 explicit; A0 dispositions recorded |

---

## 9. Suggested Sequencing & Milestones

1. **M1 — Never again:** A0 → A1 → A2. *Exit:* daily verified DB backups exist;
   `migrate:fresh` can no longer nuke MySQL by accident.
2. **M2 — Recoverable:** B1 → B2 → B3 → B4. *Exit:* full backup set off-machine;
   restore rehearsed once end-to-end.
3. **M3 — Portable:** B5 → B6. *Exit:* content moves dev→scratch cleanly.
4. **M4 — Observable & tidy:** B7 → B8 → B9. *Exit:* dashboard green, media ops
   debts cleared, pre-flight scripted.
5. **M5 — Ship:** C1 → C4 with the restore rehearsal as the centerpiece.

M1 alone removes the single biggest existential risk to the project and can be
pulled forward (even run *during* Phase 7) if the owner wants — it is
deliberately independent of i18n.

---

## 10. Carry-over Items Absorbed by This Phase

| Item | Origin | Lands in |
|---|---|---|
| Orphan purge deleted a registered file → needs trash+review | Phase 6.1 incident (2026-07-08) | B8 |
| Media collection re-assign UI / `media:organize` / date-folders config | Phase 6.1 plan §5 | B8 |
| SVG sanitizer for icon/logo collections | Phase 6.1 plan §5 + skill exception | B8 (decision) |
| Production pre-flight (env, caches, cron) | Phase 6 release gate "pending owner pre-flight" | B9 |
| Periodic dev-DB dump habit | Recovery post-mortem lesson #4 | A2 (automated) |
| Content REST/GraphQL API | Phase 6 candidate | A0 disposition (default: defer) |

---

## 11. Definition of Done (Phase 8 Release Gate)

- [ ] Daily automated, checksum-verified DB backups with retention + off-machine copy.
- [ ] Media/storage backup per approved strategy.
- [ ] Restore rehearsed successfully into a scratch DB from a real backup (C3).
- [ ] `migrate:fresh`/`db:wipe` on MySQL blocked without explicit flag — proven by test.
- [ ] Export bundle from dev imports cleanly into a scratch environment (dry-run + real).
- [ ] Ops Dashboard live with ≥7 health checks + alert mail on critical.
- [ ] Media ops carry-overs (§10) cleared.
- [ ] Pre-flight command + runbooks published.
- [ ] PHPStan level 5 / 0 errors; suite green (Phase 7 baseline no regress).
- [ ] Docs + skill file + CHANGELOG + AGENTS/Claude sync complete.

---

## 12. First Concrete Step

After Phase 7 ships (or earlier for **M1 only**, owner's call): approve A0
decisions in §3 — backup engine (zero-package wrapper), restore semantics,
export format + conflict policy, media backup size strategy, REST-API
disposition. Then create the handoff, branch `feature/phase-8-a1-guards`, and
start with the guards — the incident-proofing comes before every other feature.

---

*End of Phase 8 Grand Plan. This is the constitution; task detail lives in the
Phase 8 handoff and skill files once A0 is approved.*
