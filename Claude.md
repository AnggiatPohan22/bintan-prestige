# Bintan Prestige CMS — Session Bootstrap
**Version:** 1.0

**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Phase:** Phase 6 — Flexible Content Modeling (next). Phase 5 (Visual Page Builder) complete — 2026-06-23.
**Status:** Core CMS + page builder + theme system + plugin system + visual builder complete. See AGENTS.md §4 for full phase history.

---

## CRITICAL: Load These Files at Start of Every Session

Read these files IN ORDER before starting any task:

1. `AGENTS.md` — Architecture rules, authority order, safety rules
2. `DESIGN-SYSTEM.md` (root) — ALL design tokens, admin + frontend aesthetic ⭐
3. `ai/skills/COMPONENT-LIBRARY.md` — Copy-paste component catalog ⭐
4. Task-relevant skill file(s) from `ai/skills/` (see Skill Map in AGENTS.md §3)

Do NOT skip steps 2 and 3 when working on ANY UI task.

---

## When Generating Frontend UI

ALWAYS:
- Use colors from DESIGN-SYSTEM.md (never hardcode hex values)
- Use spacing from design tokens (4px scale: p-1/p-2/p-4/p-6/p-10/p-20)
- Use components from `ai/skills/COMPONENT-LIBRARY.md` where possible
- Follow `ai/skills/frontend-design-skill.md` for patterns and guidelines
- Mobile-first responsive (375px → 768px → 1024px)
- Accessibility WCAG AA (contrast, alt text, focus states, semantic HTML)
- Lazy-load images below the fold
- No inline CSS except dynamic background-image values

NEVER:
- Hardcode colors (#hex in style attributes or class names)
- Use arbitrary spacing (p-7, m-13, gap-11 — not in the scale)
- Generate generic AI styling (intentional design only)
- Skip hover states on interactive elements
- Skip focus:ring-2 on buttons and inputs
- Put queries in Blade templates (backend data only, prepared by controllers)

---

## When Generating Admin Dashboard UI

ALWAYS:
- Dark theme: bg-gray-900 base, bg-gray-800 sidebar/cards
- Accent: bg-amber-400 / text-amber-400 for primary actions
- 3-column layout awareness: sidebar (25%) + main (55%) + right panel (20%)
- Instant feedback: always add loading states, toast notifications, error messages
- Form inputs: bg-gray-900 border-gray-700 focus:border-amber-400

NEVER:
- Light theme for admin (dark only unless explicitly requested)
- Skip loading/error states on data-fetching actions
- Create admin buttons without focus:ring-2 focus:ring-amber-400

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
| Frontend / Blade / UI | `frontend-design-skill.md` (consolidated) |
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
| Design / Brand / Tokens | `DESIGN-SYSTEM.md` (root) |
| Component Library | `COMPONENT-LIBRARY.md` |
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