# Database Architecture Skill

## Main Goal
Protect database structure and make future features scalable.

## Rules
- Do not modify migrations without approval.
- Do not drop columns/tables.
- Add new migrations only when required and approved.
- Respect existing relationships.
- Add indexes only with clear reason.
- Use nullable/default values carefully.
- Document every schema change.

## Required Before Schema Change
- Existing table check
- Relationship check
- Frontend impact
- Admin impact
- Seeder impact
- Rollback plan