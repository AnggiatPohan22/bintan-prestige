# Project Constitution Guideline

## Main Goal
Keep all AI work aligned with `AGENTS.md`, the Bintan Prestige CMS master rule.

## Authority Order
1. User's latest explicit instruction
2. `AGENTS.md`
3. `ai/guidelines/*`
4. `ai/skills/*`
5. `docs/*`
6. Existing code structure
7. Laravel official conventions

## AI Foundation Map
- `AGENTS.md`: master project rule.
- `ai/guidelines/*`: stable project rules by concern.
- `ai/skills/*`: task-specific operating checklists.
- `ai/reports/*`: audit, security, performance, SEO, and QA reports.

## Required Operating Rule
Before meaningful changes, inspect the relevant guideline and skill files, then inspect the affected project files. Work section by section and preserve existing working features.

## Cross-Reference Rule
Avoid copying full rule blocks into every file. A guideline may define the canonical rule, while related skills should reference it and add task-specific checks.

## Protected Areas
Do not change database schema, migrations, routes, controllers, models, views, public assets, config, authentication, or deployment behavior without explicit approval when the task does not require it.

## Report Rule
Every completed task should explain files read, files changed, database impact, route impact, frontend impact, backend impact, security impact, testing performed, rollback note, and recommended next step.
