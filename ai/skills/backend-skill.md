# Backend Skill

## Main Goal
Implement Laravel backend behavior using MVC, safe validation, authorization, backend data preparation, and minimal changes.

## Required References
- `AGENTS.md`
- `ai/guidelines/01-laravel-mvc-architecture.md`
- `ai/guidelines/02-laravel-boost-workflow.md`
- `ai/guidelines/03-backend-data-processing.md`
- `ai/guidelines/06-security-hardening.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/security-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/skills/documentation-skill.md`

## Workflow
1. Inspect existing routes, controllers, models, requests, policies, views, migrations, and docs relevant to the task.
2. Use Laravel Boost or official Laravel docs when available.
3. Identify database, route, frontend, backend, security, and documentation impact before editing.
4. Keep controllers focused on request flow.
5. Put validation in Form Requests when validation becomes large or reusable.
6. Use Eloquent relationships, scopes, casts, and eager loading.
7. Prepare display-ready data before passing it to Blade.
8. Add authorization checks for sensitive actions.
9. Run or recommend focused tests.
10. Update documentation and report rollback steps.

## Forbidden
- Schema changes without approval.
- Route, model, controller, variable, table, or column renames without approval.
- Database queries in Blade.
- Heavy frontend JavaScript data processing.
- Raw SQL with user input.
- Replacing existing backend logic without analysis.
