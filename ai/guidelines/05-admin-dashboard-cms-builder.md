# Admin Dashboard & CMS Builder Guideline

## Main Goal
Keep the admin dashboard clean, modern, safe, and powerful.
The admin dashboard IS the website builder — it controls all frontend content.

## Required References
- Master rule: `AGENTS.md`
- Skills: `admin-dashboard-skill.md`, `cms-architect-skill.md`, `backend-skill.md`
- Skills: `page-builder-skill.md`, `menu-manager-skill.md`, `media-library-skill.md`
- Guidelines: `01-laravel-mvc-architecture.md`, `03-backend-data-processing.md`, `06-security-hardening.md`

---

## CMS Builder Vision

The admin dashboard is the single control point for the entire website.
Every part of the public site — pages, content, navigation, appearance,
SEO, media — should be manageable from this dashboard.

**Current CMS modules (Phase 1 — complete):**
- Products, Categories, Destinations
- Bookings
- Page Sections
- Global Settings (13 modules)
- Site Assets
- User Management

**Being built now (Phase 2):**
- Pages (generic: About, Contact, Privacy, etc.)
- Block Editor (content blocks per page)
- Menu Manager (header, footer, mobile nav)
- Media Library (central asset manager)
- Template System (page template selector)
- Preview / Draft Mode

---

## Admin Dashboard Rules

- Keep admin and public frontend design completely separated
- Admin uses modern SaaS style (white cards, indigo/purple primary, soft gold accent)
- Forms must be clear, labeled, and readable
- Use validation messages inline with fields
- Use status badges (draft/published, active/inactive)
- Use filters and search for listing pages
- Use pagination for large lists
- Use confirmation dialogs for destructive actions
- Use compact cards for repeatable product data (prices, images, features, etc.)
- Show "View on site" link where possible for CMS content

---

## Page Builder / Block Editor Rules

Building a block editor for pages is a **current priority** — not optional.

Block editor principles:
- Each page has a list of blocks in sort_order
- Each block has a type (hero, text, image, gallery, cta, products, faq, etc.)
- Admin can add, edit, reorder, hide, or delete blocks per page
- Admin sees a clean form for each block type
- Frontend renders each block using the correct Blade partial
- Empty blocks are not shown on frontend
- Draft pages are not shown on frontend

Do not build drag-and-drop (Phase 3) until explicitly requested.
Simple sort_order input or up/down buttons is sufficient for Phase 2.

---

## Admin UX Principles

- User must always know what to do next
- Required fields must be clearly marked
- Save and Cancel must be obvious and accessible
- Long forms should use tabs or accordion sections
- Module lists should show key data at a glance (name, status, date)
- Empty states must guide the user (not just say "No data found")

---

## Module Pattern

Every admin CMS module must define:
- Database table and model
- Relationships
- Form validation (Form Request)
- Controller (Admin CRUD)
- Admin Blade views (index, create, edit)
- Frontend rendering
- SEO handling
- Security check (auth middleware, policies)
- Documentation
- Test checklist

---

## Safety

- Do not remove existing admin modules or routes
- Do not change existing admin UI without approval
- Do not mix frontend luxury styling into admin dashboard
- Do not add new modules without following the Module Pattern above
- Protect all admin routes with `auth` middleware and role check