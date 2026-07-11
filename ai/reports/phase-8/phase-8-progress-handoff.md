# Phase 8 — Progress Handoff & Living Reference
# Bintan Prestige CMS — Operational Maturity

> **Living source of truth for Phase 8.** Created at A0 sign-off (2026-07-11).
> Read order every session: `AGENTS.md` → `ai/reports/phase-8/phase-8-grand-plan.md`
> (the constitution) → **this file** → task-relevant skill file(s).
> Do one `⏳ TODO` task per session; stop at `⚠️` gates for owner approval;
> run the §12 Documentation Sync Matrix; write the report in AGENTS.md §11 format
> inside `ai/reports/phase-8/`.
>
> Authority order applies (AGENTS.md §2). Schema changes require explicit owner
> approval (AGENTS.md §9). Media Library one-door rule (AGENTS.md §8) applies
> to any new upload surface. i18n one-door rule (`ai/skills/i18n-skill.md`)
> applies to any new user-facing string.

---

## 0. How To Use This File

- The **grand plan** (`phase-8-grand-plan.md`) is the constitution — *what* and *why*, do not edit unless the owner asks.
- The **ADR** (`a0-architecture-decisions.md`) is the locked decision set — Decisions 1–8, immutable without owner override.
- **This handoff** is the running state — task status, data model deltas, sync matrix. Update after every relevant task.
- Task reports live at `ai/reports/phase-8/[task-id]-[name].md`.

---

## 1. Phase 8 Overall Status

**Baseline as of A0 sign-off (2026-07-11):**
- `develop` HEAD `008e242` (post-B6.1 + A0-pre-groundwork merges).
- Test suite: **1003/1003 pass**, 4644 assertions.
- PHPStan L5: **0 errors**.
- App timezone: `Asia/Jakarta` (WIB). Locales: `en` (default, unprefixed) + `id` (`/id/…`).

**After A1 (2026-07-11):** `feature/phase-8-a1-guards` — merged into `develop` at `0ca0f01`. Test suite 1017/1017, PHPStan L5 = 0.

**After A2 (2026-07-11):** `feature/phase-8-a2-backup-service` — Test suite **1032/1032**, 4703 assertions, PHPStan L5 = 0. Migration applied + rollback verified on dev MySQL. `backup:snapshot` proven end-to-end. Scheduler registered at `03:00 Asia/Jakarta`. Ready to merge into `develop`. **Milestone M1 (Never again) — CLOSED.**

### Stage A — Foundation, Decisions & Guards
| Task | Name | Status | Gate |
|---|---|---|---|
| A0-pre | Manual `db:backup` command + 4 runbooks | ✅ SHIPPED (2026-07-11, merged as `008e242`) | — |
| A0 | Architecture Decision Record (Decisions 1–8 locked) | ✅ DONE (2026-07-11) | Owner sign-off wording *"approved"* |
| A1 | Destructive-command guards (G1 block / G2 auto pre-migrate / G3 dev config-cache warn) | ✅ DONE (2026-07-11) | ⚠️ touches artisan lifecycle (approach pre-approved in Decision 6) |
| A2 | `BackupService` core + `backups` table + scheduled daily | ✅ DONE (2026-07-11) | ⚠️ new `backups` table (owner-approved, pre-ALTER backup filed, rollback verified) |

### Stage B — Build the Modules
| Task | Name | Status | Gate |
|---|---|---|---|
| B1 | Media/storage backup (weekly ZIP manifest + retention) | ⏳ TODO | — |
| B2 | Off-machine target (second disk via Laravel filesystems + failure alert) | ⏳ TODO | ⚠️ new filesystem credentials |
| B3 | Backups admin UI (list/verify/download/trigger) | ⏳ TODO | — |
| B4 | Restore engine (`backup:restore` CLI-first, admin wrapper LAST) | ⏳ TODO | ⚠️ sign-off before admin wrapper |
| B5 | Export bundles (`content:export` + admin UI, versioned ZIP) | ⏳ TODO | — |
| B6 | Import engine (dry-run diff, id-remap, media via MediaService, conflict policy) | ⏳ TODO | — |
| B7 | Ops / health dashboard (`HealthCheckService` + admin page + widget + alert mail) | ⏳ TODO | — |
| B8 | Media ops carry-overs (safe orphan purge, collection re-assign, media:organize, SVG sanitizer decision) | ⏳ TODO | — |
| B9 | Production pre-flight (`app:preflight` command + docs) | ⏳ TODO | — |

### Stage C — Release Audit
| Task | Name | Status |
|---|---|---|
| C1 | Static Analysis & Code Quality (PHPStan L5 / 0; command/service coverage) | ⏳ TODO |
| C2 | Performance & Safety Audit (backup runtime + guards proven) | ⏳ TODO |
| C3 | **Restore & import rehearsal** (full backup → restore into scratch DB → smoke green; export dev → import into scratch → diff clean) | ⏳ TODO |
| C4 | Documentation (`docs/modules/operations.md`, `ai/skills/operations-skill.md`, CHANGELOG, AGENTS/Claude sync, Phase 9 prep) | ⏳ TODO |

---

## 2. Milestones (demoable checkpoints)

1. **M1 — Never again:** A0 → A1 → A2. *Exit:* daily verified DB backups exist; `migrate:fresh` can no longer nuke MySQL by accident.
2. **M2 — Recoverable:** B1 → B2 → B3 → B4. *Exit:* full backup set off-machine; restore rehearsed once end-to-end.
3. **M3 — Portable:** B5 → B6. *Exit:* content moves dev→scratch cleanly.
4. **M4 — Observable & tidy:** B7 → B8 → B9. *Exit:* dashboard green, media ops debts cleared, pre-flight scripted.
5. **M5 — Ship:** C1 → C4 with the restore rehearsal (C3) as the centerpiece.

M1 alone removes the single biggest existential risk to the project.

---

## 3. Architecture Decisions (A0) — LOCKED ✅

All eight decisions signed off 2026-07-11 (*"approved"*). Full record in
`ai/reports/phase-8/a0-architecture-decisions.md`. Summary:

1. **Backup engine** — own thin `mysqldump` wrapper; zero packages.
2. **Media backup strategy** — weekly ZIP manifest with per-file SHA-256 + incremental skip; retention 14 daily DB / 8 weekly DB / 4 weekly media (all tunable).
3. **Restore path** — CLI-first (`backup:restore`), typed `RESTORE` confirmation, automatic pre-restore safety snapshot, post-checks; admin wrapper LAST.
4. **Export/import** — versioned ZIP with `manifest.json` + one JSON per domain + media; skip-by-default conflicts, per-domain overwrite opt-in; id remap by natural key; media via `MediaService`.
5. **Monitoring** — `HealthCheckService` with ≥7 pull-based checks; Ops Dashboard page + widget; opt-in daily digest + critical alert mail.
6. **Safety guards** — G1 block `migrate:fresh`/`migrate:reset`/`db:wipe` on MySQL (env-var override); G2 auto pre-migrate `db:backup` (env-var skip); G3 dev config-cache warn.
7. **REST/GraphQL Content API** — deferred to a standalone follow-up phase; not Phase 8.
8. **Zero new Composer/NPM packages** for Phase 8 (same bar as Phase 7).

---

## 4. Data Model (proposed — confirm DDL at each task's gate)

```
config/backup.php            [NEW config — A2]     retention, disk names, toggles
config/safety.php            [NEW config — A1]     auto_backup_before_migrate,
                                                    allow_destructive fallback
config/ops.php               [NEW config — B7]     dashboard thresholds + mail

backups                      [NEW table ⚠️ A2]
  id, type (db|media), disk, path, size_bytes,
  checksum_sha256, status (ok|failed|pruned),
  purpose string(255) nullable, meta json nullable,
  created_at, updated_at
  index(type, status, created_at)
```

**No schema change to** any Phase 1–7 table for Phase 8.

**Backup file layout on disk:**
```
storage/app/backups/
  db/               *.sql.gz + *.sql.gz.sha256
  media/            *.zip    + *.zip.sha256 + manifest .json inside
  restore-reports/  {ts}.md
```

**Backfill/DB safety:** no `migrate:fresh` / `migrate:reset` ever. All
migrations additive + reversible. `php artisan db:backup pre-a2-backups-table
--purpose="A2 pre-ALTER"` before the `backups` table migration.

---

## 5. Reuse Ledger (don't fork these)

- `db:backup` command + `scripts/backup-db.sh` (A0-pre-groundwork) — extend, don't replace, when A2 introduces `BackupService`.
- Runbooks (`docs/runbooks/`) — extend, don't rewrite; C4 will add restore + operations chapters.
- Scheduler (Phase 4) — reuse for daily backup, weekly media, health-check heartbeat.
- Audit log (Phase 4) — backup / restore / import actions must be audited (polymorphic).
- Media Library (Phase 6.1) — B5/B6 route media through `MediaService`; B8 hardens orphan logic.
- Mail config — B7 uses `config('mail.from.address')` for digest + critical alerts.

---

## 6. Caching & Performance Rules

- Backup runtime measured in C2; retention prune runs after the dump, not inline with the request path.
- Health checks are cached per-request in `HealthCheckService` (no double dispatch); dashboard adds no heavy queries.
- Guard listener (A1) does zero DB work in its hot path — pure env/config reads.

---

## 7. Sanitization / Security Rules

- Export bundles **never** contain users, bookings, submissions, secrets, or `.env` — enforced by allow-list, not deny-list.
- Restore command requires typed `RESTORE` confirmation; `--force` only for CI / test harness.
- All admin surfaces (B3 / B5 / B6 / B7) behind existing `is_admin` guard + FormRequest validation.
- Destructive guard (A1) message points at `AGENTS.md` §8 + Phase 6 §16 incident, not a stack trace.

---

## 8. Skill Map row to add (at C4 → AGENTS.md §3 + CLAUDE.md)

```
| Backup / Restore / Ops / Monitoring | operations-skill.md |
```

New files this phase:
- `ai/skills/operations-skill.md` — canonical operations standard (backup + restore + guards + export + import + health checks). Mirrors the Media Library and i18n standards.
- `docs/modules/operations.md` — developer reference (C4). Extended runbooks live under `docs/runbooks/`.

---

## 9. Approval Gates (this phase)

1. **A0** — decision set ✅ signed off 2026-07-11.
2. **A1** — guard hooks into artisan lifecycle. Approach pre-approved via Decision 6; implementation ships in this branch.
3. **A2** — new `backups` table (⚠️ schema change — separate gate).
4. **B2** — any new filesystem disk credentials in `.env` / `config/filesystems.php`.
5. **B4** — restore engine sign-off *before* the admin wrapper exists.
6. Zero-package confirmation on every task (Decision 8).

---

## 10. Risks (grand plan §8 — watch list)

- Restore bug destroys good data → safety snapshot always-first; CLI-first; typed confirm; C3 rehearsal on scratch DB.
- Backups silently failing (false confidence) → checksum + verify step; dashboard "last backup age" red state; alert mail.
- Media backup too large for VPS → incremental manifest per Decision 2; retention tuning; off-machine target.
- Export/import id collisions → slug/key-based remap; dry-run diff mandatory; skip-by-default conflicts.
- Dump locks production DB → `--single-transaction` (InnoDB), off-peak schedule.
- Scope creep (API, APM, HA) → §1.2 of grand plan explicit; A0 dispositions recorded.

---

## 11. Definition of Done (Release Gate — grand plan §11)

- [ ] Daily automated, checksum-verified DB backups with retention + off-machine copy.
- [ ] Media/storage backup per Decision 2.
- [ ] Restore rehearsed successfully into a scratch DB from a real backup (C3).
- [ ] `migrate:fresh` / `db:wipe` on MySQL blocked without env override — proven by test (A1 fence).
- [ ] Export bundle from dev imports cleanly into a scratch environment (dry-run + real).
- [ ] Ops Dashboard live with ≥7 health checks + alert mail on critical.
- [ ] Media ops carry-overs (grand plan §10) cleared.
- [ ] Pre-flight command + runbooks published.
- [ ] PHPStan level 5 / 0 errors; suite green (baseline 1003 no regress).
- [ ] Docs + skill file + CHANGELOG + AGENTS/Claude sync complete.

---

## 12. Documentation Sync Matrix (run after EVERY relevant task)

| Trigger | Update |
|---|---|
| New table / column | this handoff §4 + task report Impact + (C4) module doc |
| New config file | this handoff §4 + skill file |
| New command / listener | this handoff §1 status + skill file + (C4) runbook |
| New skill/standard | `ai/skills/operations-skill.md` + Skill Map rows (§8) |
| Decision changed | ADR (`a0-architecture-decisions.md`) with dated override entry + this handoff §3 |
| Task complete | flip status ⏳→✅ in §1 + write `ai/reports/phase-8/[task-id]-*.md` |
| Release gate item met | §11 checkbox |

---

## 13. Kickoff & Report Location

- **A0 report:** `ai/reports/phase-8/a0-architecture-decisions.md` (this ADR — locked).
- **All task reports:** `ai/reports/phase-8/[task-id]-[name].md`.
- **Branch (current):** `feature/phase-8-a1-guards`.
- **Base branch:** `develop` (never rebase onto `main`).
