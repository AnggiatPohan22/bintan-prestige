# Runbooks

Operational runbooks. **Read the relevant one BEFORE running the command
it describes, not while the site is on fire.**

| Runbook | When to read |
|---|---|
| [`rollback.md`](rollback.md) | Migration failed / data corrupted / bad deploy needs undoing |
| [`deploy-checklist.md`](deploy-checklist.md) | Any push to production, including hotfixes |
| [`db-backup.md`](db-backup.md) | Before any ALTER on an existing table, and as a scheduled routine |

Master rules: see `AGENTS.md` §8 (Critical Safety Rules) and §13
(Data-Safety Runbook quick-reference).
