# Bintan Prestige CMS — AI Agent Master Rules

This file is the master rule for all AI agents working on this project,
including Claude, Codex, Qwen, Gemini, Cursor, and any local AI assistant.

Read this file first. Then read **only** the skill files relevant to your task.
Do not read all skill files at once — use the Skill Map in Section 3.

---

## 1. Project Identity & Goal

**Project:** Bintan Prestige CMS
**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Architecture:** Laravel MVC with Service Layer and Support Classes

**Ultimate Goal:**
Build a WordPress-like CMS where the complete website — pages, navigation,
layout, content blocks, and appearance — is fully managed from one admin
dashboard. No hardcoded frontend content. Backend controls everything.

**Current Phase:** Phase 5 — Visual Builder (next)
Phase 4 (Plugin & Module System) complete at v4.0.0 (2026-06-21).

---

## 2. Authority Order

When instructions conflict, follow this order:

1. Owner's latest explicit instruction
2. This AGENTS.md
3. `ai/guidelines/*`
4. `ai/skills/*`
5. `docs/*`
6. Existing code structure
7. Laravel official conventions

Never ignore existing working features.

---

## 3. Skill Map — Load Only What the Task Needs

Before working, read this file + the skill(s) below that match your task.
**Do not load all skills.** One or two files per task is enough.

| Task Type | Read These Skill Files |
|-----------|----------------------|
| Frontend / Blade / UI | `frontend-skill.md` + `uiux-skill.md` |
| Backend / Controller / Service | `backend-skill.md` |
| Database / Migration / Model | `database-architecture-skill.md` |
| Security fix or audit | `security-skill.md` |
| SEO / Schema / Sitemap | `seo-ai-discovery-skill.md` |
| Page Builder / Blocks | `page-builder-skill.md` + `cms-architect-skill.md` |
| Visual Builder / Phase 5 | `phase5-visual-builder-skill.md` + `page-builder-skill.md` |
| Pages module (About, Contact, etc.) | `page-module-skill.md` + `backend-skill.md` |
| Menu / Navigation | `menu-manager-skill.md` + `backend-skill.md` |
| Media Library | `media-library-skill.md` |
| Performance / Cache | `performance-skill.md` |
| Testing / QA | `testing-qa-skill.md` |
| Documentation | `documentation-skill.md` |
| Design / Brand / Tokens | `design-system-skill.md` + `uiux-skill.md` |
| Admin Dashboard | `admin-dashboard-skill.md` |
| Product / Tour / Activity | `product-management-skill.md` |
| Travel Business Logic | `travel-business-skill.md` |
| Component Library | `component-library-skill.md` |

> Skill files live in: `ai/skills/`
> Guidelines live in: `ai/guidelines/`
> Detail rules are in those files. This file is the constitution, not the manual.

---

## 4. CMS Architecture Phases

**Phase 1 — COMPLETE ✅**
Backend data syncs to frontend.
Products, categories, destinations, bookings, page sections, global settings (13 modules), user roles, site assets.

**Phase 2 — COMPLETE ✅**
Full website builder from admin dashboard. Goal: WordPress-like control.
- Generic Pages module (About, Contact, Privacy, etc.)
- Block editor (Hero, Text, Image, Gallery, CTA, Products, FAQ blocks)
- Menu manager (header, footer, mobile navigation)
- Media library (central asset manager)
- Template system (page templates selectable from admin)
- Preview/draft mode

**Phase 3 — COMPLETE ✅**
Theme system — design tokens, token editor, Google Fonts integration,
theme export/import (ZIP), extended token inheritance.

**Phase 4 — COMPLETE ✅** (v4.0.0 — 2026-06-21)
Plugin & Module System — plugin registry/lifecycle, hook & filter event system,
content revision history, content scheduling, admin audit log, SEO manager
(sitemap/robots.txt/redirects/noindex), contact form builder plugin,
analytics dashboard plugin, plugin security & sandboxing.
- Test suite: 596 tests / 2765 assertions / 0 failures
- PHPStan: level 5 / 0 errors / no ignores / no baseline

**Phase 5 — CURRENT 🔨**
Visual Page Builder & Foundation Hardening.
Stage A: admin UX refactor, block library expansion, backend readiness, efficiency review, frontend polish.
Stage B: drag-and-drop builder, live preview, inline editing, reusable patterns.

**Phase 6 — FUTURE**
Flexible Content Modeling — custom content types & fields from admin.

**Phase 7 — FUTURE**
Internationalization — multi-language content for Bintan tourism market.

**Phase 8 — FUTURE**
Operational Maturity — backup/restore, import/export, monitoring dashboard.

---

## 5. Protected Existing Modules

Do not rebuild these from zero. Extend them safely only:

- Products, Categories, Destinations (with soft delete + restore)
- Product: Prices, Images, Features, FAQs, Itineraries, Notes, Highlights
- Bookings + Booking Items
- Page Sections + Page Section Media
- General FAQs
- Global Settings (13 modules)
- Site Assets, Site Settings
- User Management (is_admin, admin_role, admin_status)

---

## 6. CMS Module Pattern

Every new module must follow this sequence:

1. Migration (new table or column)
2. Model + relationships
3. Form Request (validation)
4. Controller — Admin CRUD
5. Service (business logic, when needed)
6. Admin Blade views
7. Frontend rendering (Blade component or section)
8. SEO handling (meta, schema)
9. Security check (auth, policy, validation)
10. Documentation update
11. Tests

---

## 7. Laravel MVC Rules

- Controllers handle request flow only
- Models handle relationships, casts, scopes, domain data
- Form Requests handle validation (when large or reusable)
- Policies handle authorization
- Views render prepared data only — no queries in Blade
- Services handle reusable business logic
- JavaScript must not process heavy business data
- Support classes prepare display-ready data for Blade

---

## 8. Critical Safety Rules

**Never do these without explicit approval:**
- Delete existing features or UI sections
- Rewrite the full project or any full module
- Change database schema or existing migrations
- Rename routes, controllers, models, tables, or columns
- Replace existing backend logic without analysis
- Remove existing documentation
- Install new packages
- Change authentication or authorization logic

**Always do these:**
- Inspect existing files before editing
- List files that will change before changing them
- Hardcode nothing — use CMS data
- Work section by section
- Keep commits focused on one concern

---

## 9. Approval Required Before Acting

Ask for explicit approval before:

- Any database schema change (new table, new column, index)
- Editing existing migrations
- Renaming anything (route, controller, model, view, column)
- Removing any feature, page, or UI section
- Installing new Composer or NPM packages
- Changing auth or security-sensitive logic
- Moving or deleting existing documentation

Write the plan first. Wait for approval. Then implement.

---

## 10. Required Workflow Before Every Edit

1. Read AGENTS.md (this file) ← already done
2. Read the relevant skill file(s) from the Skill Map above
3. Inspect existing files affected by the task
4. List files that will change
5. Describe the plan briefly
6. Confirm with owner if schema or breaking change is involved
7. Implement in small steps
8. Report using the format below

---

## 11. Report Format

Use this after every completed task. Keep it short — use the template:

```
## Task: [name]

### Changed
- `path/to/file.php` — what changed

### Impact
- DB: none | migration added: [name]
- Routes: none | added: [route]
- Frontend: none | [section] now shows [X]
- Security: none | validated via [FormRequest/Policy]

### Rollback
`git revert [hash]` or [manual step]

### Next
[recommended next task]
```

---

## 12. Git Workflow

- Check current branch before starting
- Create a feature branch for each task
- Keep commits focused and small
- Do not mix unrelated changes in one commit
- Always document rollback steps

**Branch naming:**
- `feature/[feature-name]`
- `docs/[doc-update-name]`
- `fix/[issue-description]`
- `refactor/[area-name]`

---

## 13. Final Principle

Improve this project safely, incrementally, and transparently.

**The goal is to build a complete WordPress-like CMS where any part of
the website — pages, blocks, navigation, layout, content, and appearance —
can be created and managed entirely from the admin dashboard.**

Build toward this goal section by section, without breaking what already works.
Every task should leave the project in a better state than it was found.