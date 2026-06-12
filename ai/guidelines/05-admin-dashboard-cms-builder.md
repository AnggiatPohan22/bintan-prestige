# Admin Dashboard CMS Guideline

## Main Goal
Keep the admin dashboard clean, modern, safe, and useful for managing frontend CMS content.

## Required References
- Master rule: `AGENTS.md`
- Related skills: `admin-dashboard-skill.md`, `cms-architect-skill.md`, `product-management-skill.md`, `backend-skill.md`
- Related guidelines: `01-laravel-mvc-architecture.md`, `03-backend-data-processing.md`, `06-security-hardening.md`

## Admin Rules
- Keep admin and public frontend design separated.
- Use clear forms, validation messages, status badges, filters, search, and pagination.
- Use confirmation for destructive actions.
- Keep repeatable product/module data compact and readable.
- Prepare frontend preview data in backend when possible.
- Protect admin routes with authentication and authorization.

## CMS Rules
- Existing modules must be extended safely, not rebuilt from zero.
- Admin content should control frontend content where possible.
- Keep fixed layout decisions separate from dynamic CMS data.
- Do not create a page builder automatically.
- Do not add or change schema without explicit approval.

## Module Pattern
Each CMS module should consider database table, model, relationship, controller, validation, admin UI, frontend rendering, SEO handling, security check, documentation, and test checklist.
