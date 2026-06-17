# Bintan Prestige CMS — Claude Session Context

**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Branch:** develop
**Phase:** Phase 2 — Building WordPress-like website builder
**Status:** ~80% complete. Core CMS done. Building page builder, menus, media library.

---

## What This Project Is

Travel CMS for Bintan, Indonesia.

**Ultimate goal:** WordPress-like admin where the complete website — pages,
blocks, navigation, layout, appearance — is 100% managed from one backend
dashboard. No hardcoded frontend content. Backend controls everything.

---

## Before You Do Anything

1. Read `AGENTS.md` — the master rules file
2. Read only the skill file(s) that match the current task (see Skill Map below)
3. Inspect the existing files that will be affected
4. Describe your plan before touching any code

---

## Skill Map — Load Only What's Needed

| Task | Skill Files to Read |
|------|-------------------|
| Frontend / Blade / UI | `frontend-skill.md` + `uiux-skill.md` |
| Backend / Controller | `backend-skill.md` |
| Database / Model | `database-architecture-skill.md` |
| Security | `security-skill.md` |
| SEO / Schema | `seo-ai-discovery-skill.md` |
| Page Builder / Blocks | `page-builder-skill.md` + `cms-architect-skill.md` |
| Pages module | `page-module-skill.md` + `backend-skill.md` |
| Menu / Navigation | `menu-manager-skill.md` |
| Media Library | `media-library-skill.md` |
| Performance | `performance-skill.md` |
| Testing / QA | `testing-qa-skill.md` |
| Documentation | `documentation-skill.md` |
| Design / Brand | `design-system-skill.md` |
| Admin Dashboard | `admin-dashboard-skill.md` |
| Products / Tours | `product-management-skill.md` |

> Skill files are in `ai/skills/`. Guidelines are in `ai/guidelines/`.

---

## Current Active Modules (Phase 1 — Done)

- Products, Categories, Destinations (with soft delete + restore)
- Product: Prices, Images, Features, FAQs, Itineraries, Notes, Highlights
- Bookings + Booking Items
- Page Sections + Page Section Media
- Global Settings: 13 modules (brand colors, navigation, SEO, tracking, footer, etc.)
- Site Assets, Site Settings
- User Management (superadmin, admin, editor roles)

## Building Now (Phase 2 — Current)

- Generic Pages module
- Block editor
- Menu manager
- Media library
- Template system
- Preview / draft mode

---

## Critical Safety Rules

- Never change DB schema without approval
- Never rename routes, controllers, models, or columns without approval
- Never rebuild existing modules from zero
- Never hardcode content that exists in the CMS
- Always list files that will change before changing them
- Always work section by section

---

## Report Format (After Every Task)

```
## Task: [name]

### Changed
- `file.php` — what changed

### Impact
- DB: none | migration: [name]
- Routes: none | added: [path]
- Frontend: none | [what changed]
- Security: none | [validation used]

### Rollback
[git command or manual step]

### Next
[recommended next step]
```

---

## Git Workflow

```bash
# Always work on a branch, never directly on develop
git checkout develop && git pull origin develop
git checkout -b feature/[task-name]

# Commit focused changes
git add [specific files]
git commit -m "type: short description"
git push origin feature/[task-name]

# Then open PR → merge to develop
```