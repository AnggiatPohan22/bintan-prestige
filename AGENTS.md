# Bintan Prestige CMS - AI Agent Master Rules

This project is an existing Laravel + TailwindCSS CMS for Bintan Prestige, a travel, taxi, hotel, activity, and destination website.

This file is the master rule for all AI agents working on this project, including Codex, Qwen, Claude, Gemini, Cursor, and any local AI assistant.

The AI must protect the existing project, follow Laravel conventions, improve the CMS safely, update documentation, and never rewrite the project without explicit approval.

---

## 1. Project Identity

Project Name: Bintan Prestige CMS
Stack: Laravel + TailwindCSS + MySQL
Architecture: Laravel MVC
Frontend: Public travel website
Backend: Admin dashboard / CMS
Primary Goal: Build a scalable CMS where backend data controls frontend content.

---

## 2. Master Priority Order

When there is conflict between instructions, follow this order:

1. User's latest explicit instruction
2. This AGENTS.md master rule
3. ai/guidelines/*
4. ai/skills/*
5. docs/*
6. Existing code structure
7. Laravel official conventions

Never ignore existing working features.

---

## 3. Required AI Knowledge Files

Before making meaningful changes, the AI must inspect relevant files from:

* ai/guidelines/
* ai/skills/
* docs/
* app/
* routes/
* resources/views/
* database/

The AI must use the correct skill depending on the task.

Examples:

* Frontend task: read frontend-skill.md, uiux-skill.md, design-system-skill.md
* Backend task: read backend-skill.md, cms-architect-skill.md, database-architecture-skill.md
* Security task: read security-skill.md
* SEO task: read seo-ai-discovery-skill.md
* Performance task: read performance-skill.md
* QA task: read testing-qa-skill.md
* Documentation task: read documentation-skill.md

---

## 4. Critical Safety Rules

* Do not delete existing features.
* Do not remove working logic.
* Do not rewrite the full project.
* Do not change database schema unless approved.
* Do not modify existing migrations unless approved.
* Do not rename routes, controllers, models, variables, tables, or columns unless approved.
* Do not replace existing backend logic without analysis.
* Do not hardcode data if database data already exists.
* Do not break frontend/backend sync.
* Do not remove existing documentation.
* Do not remove existing UI sections without approval.
* Do not remove existing product modules.
* Do not create a page builder automatically unless explicitly requested.
* Work section by section, not full-project rewrite.

---

## 5. Required Workflow Before Editing

Before editing code, the AI must provide:

1. Task understanding
2. Existing structure analysis
3. Files that will be changed
4. Files that will only be read
5. Database impact
6. Route impact
7. Frontend impact
8. Backend impact
9. Security impact
10. Documentation impact
11. Rollback note

If the task is small, the AI may summarize this briefly, but it must still understand the impact.

---

## 6. Laravel MVC Rules

The project must follow Laravel MVC structure.

Rules:

* Controllers handle request flow.
* Models handle relationships, casts, scopes, and domain data.
* Form Requests handle validation when validation becomes large or reusable.
* Policies/Gates handle authorization.
* Views render prepared data only.
* Services may be used for reusable business logic.
* Blade files must not contain database queries.
* JavaScript must not process heavy business data.
* Routes must stay clean and readable.
* Existing Laravel conventions must be preserved.

Preferred structure:

* app/Models
* app/Http/Controllers/Admin
* app/Http/Controllers/Frontend
* app/Http/Requests
* app/Policies
* app/Services
* resources/views/admin
* resources/views/frontend
* resources/views/components
* routes/web.php
* database/migrations
* database/seeders

---

## 7. Laravel Boost / AI Workflow Rules

When Laravel Boost or a Laravel-aware AI tool is available, the AI must use it to inspect:

* Laravel version
* Routes
* Models
* Controllers
* Views
* Database schema
* Logs
* Installed packages
* Official Laravel documentation

The AI must not guess Laravel syntax when official Laravel docs or Laravel Boost inspection can verify the correct approach.

---

## 8. Backend Data Processing Rules

All heavy data processing must happen in the backend.

Frontend should only receive clean, display-ready data.

Rules:

* Use Eloquent relationships.
* Use eager loading to avoid N+1 queries.
* Use pagination for large lists.
* Use backend filtering/searching.
* Prepare product prices in backend.
* Prepare SEO meta in backend.
* Prepare frontend sections in backend.
* Prepare schema JSON-LD in backend.
* Do not fetch all records then filter in frontend.
* Do not put business logic in Blade.
* Do not duplicate query logic across controllers.

---

## 9. Frontend UI Rules

Public frontend must follow the Bintan Prestige luxury travel style.

Rules:

* Use TailwindCSS only.
* Use reusable Blade components.
* Use mobile-first responsive design.
* Use premium spacing and clean visual hierarchy.
* Use optimized images.
* Use smooth but lightweight animations.
* Avoid heavy JavaScript.
* Avoid generic AI-looking UI.
* Use semantic HTML.
* Use consistent buttons, cards, badges, and section headings.

Frontend style:

* Luxury
* Clean
* Premium
* Modern
* Stylish
* Travel-focused

---

## 10. Admin Dashboard Rules

Admin dashboard must be clean, modern, and easy to use.

Rules:

* Keep frontend and admin design separated.
* Admin uses modern SaaS dashboard style.
* Forms must be clear and readable.
* Use validation messages.
* Use status badges.
* Use filters/search where needed.
* Use pagination for listing pages.
* Use confirmation for destructive actions.
* Use compact cards for repeatable product data.
* Admin dashboard should control frontend content where possible.

Important:

The admin dashboard may become a frontend builder/page builder in the future, but AI must not build this automatically unless the user explicitly requests it.

---

## 11. CMS Architecture Rules

Every CMS module should follow this pattern:

1. Database table
2. Model
3. Relationship
4. Controller
5. Validation
6. Admin UI
7. Frontend rendering
8. SEO handling
9. Security check
10. Documentation
11. Testing checklist

Existing modules must be protected:

* Products
* Categories
* Destinations
* Product Prices
* Product Images
* Product Features
* Product FAQs
* Product Itineraries
* Product Notes
* Page Sections

Do not rebuild existing modules from zero.

---

## 12. Security Rules

Security is mandatory.

The AI must check for:

* Exposed .env
* Exposed APP_KEY
* Exposed database credentials
* Exposed API keys or tokens
* APP_DEBUG=true in production
* Unprotected admin routes
* Missing CSRF protection
* Unsafe file upload
* Raw SQL with user input
* XSS risk
* Missing validation
* Missing authorization
* Suspicious functions such as eval, shell_exec, exec, system, passthru, or unsafe base64_decode usage
* Hidden admin routes
* Backdoor-like logic
* Public executable upload paths

Rules:

* Never commit secrets.
* Never hardcode credentials.
* Validate all input.
* Escape output by default.
* Protect admin routes with auth middleware.
* Use authorization for sensitive actions.
* Use safe upload validation.
* Rotate any exposed secret immediately.

---

## 13. SEO & AI Discovery Rules

Every public page should support:

* Meta title
* Meta description
* Canonical URL
* Open Graph
* Twitter/X card
* Robots meta
* Sitemap inclusion
* Clean slug
* Breadcrumb
* H1/H2 structure
* Image alt text
* Internal linking
* JSON-LD schema where relevant

AI discovery readiness:

* Important content must be visible in HTML.
* Avoid hiding critical content behind JavaScript only.
* Use clear headings.
* Use FAQ answer blocks.
* Use destination-specific content.
* Use product-specific detail pages.
* Use business credibility signals.
* Maintain robots.txt and sitemap.xml.
* Do not create spam or thin AI-generated pages.

---

## 14. Performance Rules

Frontend performance:

* Optimize images.
* Use WebP where possible.
* Use lazy loading for below-the-fold images.
* Avoid layout shift.
* Avoid oversized JavaScript.
* Use lightweight animations.
* Keep DOM clean.

Backend performance:

* Avoid N+1 queries.
* Use eager loading.
* Use indexes where appropriate.
* Use pagination.
* Cache stable public content where appropriate.
* Avoid repeated queries inside Blade.
* Use production optimization commands when deploying.

Target:

* Public pages should feel fast on mobile.
* Admin pages should not feel slow or confusing.
* Images should load smoothly without layout jump.

---

## 15. Testing & QA Rules

Before launch or major merge, the AI must check:

* Database structure
* Relationships
* CRUD flow
* Validation
* Authorization
* Upload handling
* Frontend rendering
* Responsive layout
* SEO meta
* Schema
* Sitemap
* Robots.txt
* Performance risks
* Security risks
* Documentation completeness

Final QA report should include score:

* Database: /100
* Backend: /100
* Frontend: /100
* Security: /100
* SEO: /100
* Performance: /100
* Documentation: /100
* Launch Readiness: /100

---

## 16. Documentation Rules

Documentation must be updated after every meaningful change.

Do not delete existing docs.

If old docs exist:

* Keep them.
* Move them to docs/_legacy/ only with approval.
* Or create an index that marks them as legacy.
* Extract relevant information into new canonical docs.
* Never overwrite docs without checking existing content.

Recommended docs structure:

docs/
├── README.md
├── _legacy/
├── architecture/
├── database/
├── modules/
├── frontend/
├── admin/
├── security/
├── performance/
├── seo/
├── qa/
└── changelog/

Every documentation update should include:

* What changed
* Why changed
* Files changed
* Database impact
* Route impact
* Frontend impact
* Backend impact
* Security impact
* Testing result
* Rollback note

---

## 17. Report Rules

After finishing a task, the AI must provide a report:

* Summary
* Files changed
* Files created
* Files deleted, if any
* Database impact
* Route impact
* Frontend impact
* Backend impact
* Security impact
* Performance impact
* SEO impact
* Documentation updated
* Testing performed
* Remaining risks
* Recommended next step

If no file should be changed, the AI must say so clearly.

---

## 18. Approval Required

The AI must ask for explicit approval before:

* Changing database schema
* Editing existing migrations
* Dropping tables or columns
* Renaming routes
* Renaming controllers
* Renaming models
* Removing features
* Rebuilding UI from zero
* Replacing existing logic
* Adding page builder functionality
* Installing new packages
* Changing authentication logic
* Changing deployment configuration
* Modifying security-sensitive files
* Moving or deleting existing docs

---

## 19. Git Safety Rules

Before major work:

* Check current branch.
* Recommend creating a feature branch.
* Avoid mixing unrelated changes.
* Keep commits focused.
* Do not overwrite uncommitted work.
* Do not delete files without explaining why.
* Document rollback steps.

Recommended branch naming:

* feature/ai-foundation
* feature/docs-restructure
* feature/security-baseline
* feature/performance-baseline
* feature/seo-ai-discovery
* feature/qa-launch-audit

---

## 20. Final Principle

The AI must improve the project safely, incrementally, and transparently.

The goal is not to rewrite Bintan Prestige CMS.

The goal is to make the existing Laravel CMS cleaner, safer, faster, more scalable, better documented, and easier to develop.
