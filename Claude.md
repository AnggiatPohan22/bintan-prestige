# Bintan Prestige CMS — Session Bootstrap
**Version:** 1.0

**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Phase:** Phase 7 — Internationalization COMPLETE ✅ (2026-07-10). Phase 6.1 (Dashboard & Media UX) complete — 2026-07-08. Phase 6 (Flexible Content Modeling) complete — 2026-07-07.
**Status:** Core CMS + page builder + theme system + plugin system + visual builder + content modeling + **multi-language (en/id)** complete. UI scheme: Navy + gold (DB-stored). See AGENTS.md §4 for full phase history.

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
- Use semantic CSS classes from `admin.css` — `admin-btn-primary`, `admin-input`, `admin-card`, etc.
- Sidebar dark (`bg-slate-900`), content light (`bg-slate-50`), cards white (`admin-card`)
- Primary action: `admin-btn-primary` (indigo-600) — never `btn-primary` (legacy emerald)
- Form inputs: `admin-input` — never `form-input` (legacy) or hardcoded `$inputClass` PHP vars
- Focus rings: `focus:ring-4 focus:ring-indigo-100` on inputs; checkboxes use `text-indigo-600 focus:ring-indigo-500`
- Instant feedback: loading states, toast notifications, error messages on every data action
- Badges: use `admin-badge-success/warning/danger/info` — never add `style=` overrides on top
- Image/file inputs: ALWAYS `<x-admin.media-image-field>` (single) or `<x-admin.media-gallery-field>` (multiple) — these route through the Media Library. Store the returned path string (`string(500)`), FormRequest rule `string`, register the column in `MediaService::DIRECT_REFERENCES`. See `ai/skills/media-library-skill.md` ⭐ Canonical Image Input Standard.

NEVER:
- Use `btn-primary` / `btn-secondary` (legacy emerald — inconsistent with admin-btn-primary indigo)
- Use `form-input` / `form-label` / `form-textarea` (legacy — use admin-* classes)
- Hardcode `$inputClass` PHP variable in Blade files
- Add `style="color/background"` inline overrides on top of admin badge classes
- Skip loading/error states on data-fetching actions
- Create interactive elements without a visible focus ring
- Use a raw `<input type="file">` for images in admin — always the Media Library picker (only exception: favicon .ico/.svg)

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
| Any image/file upload field | `media-library-skill.md` (⭐ Canonical Image Input Standard) |
| Performance | `performance-skill.md` |
| Testing / QA | `testing-qa-skill.md` |
| Documentation | `documentation-skill.md` |
| Design / Brand / Tokens | `DESIGN-SYSTEM.md` (root) |
| Component Library | `COMPONENT-LIBRARY.md` |
| Admin Dashboard | `admin-dashboard-skill.md` |
| Products / Tours | `product-management-skill.md` |
| **Multi-language / locale / translation (Phase 7)** | `i18n-skill.md` |

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