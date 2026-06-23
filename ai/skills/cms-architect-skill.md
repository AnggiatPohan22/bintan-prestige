# CMS Architect Skill

## Main Goal
Design and build a WordPress-like CMS where the complete website is
managed from one admin dashboard. Backend controls everything.

## Required References
- `AGENTS.md` — master rules and skill map
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — admin CMS rules
- `ai/skills/backend-skill.md` — Laravel MVC patterns
- `ai/skills/database-architecture-skill.md` — schema and migration rules
- `ai/skills/admin-dashboard-skill.md` — admin UI patterns
- `ai/skills/frontend-design-skill.md` — frontend rendering patterns

---

## CMS Architecture — Three Phases

### Phase 1 — COMPLETE ✅ (backend → frontend sync)

Data-driven frontend using database content.

Built modules:
- Products (tours, taxi, activities, hotels)
- Categories + Destinations (with soft delete)
- Product: Prices, Images, Features, FAQs, Itineraries, Notes, Highlights
- Bookings + Booking Items
- Page Sections + Page Section Media (flexible content blocks)
- Global Settings (13 modules: brand colors, navigation, SEO, tracking, etc.)
- Site Assets + Site Settings
- User Management (superadmin, admin, editor)

### Phase 2 — CURRENT 🔨 (full website builder)

Any page, any block, any navigation — managed from admin.

**Modules to build:**
1. **Pages** — Generic pages with slug-based routing (About, Contact, Privacy, etc.)
2. **Block Editor** — Flexible content blocks per page (Hero, Text, Image, Gallery, CTA, FAQ, Products grid)
3. **Menu Manager** — Header, footer, and mobile navigation from admin
4. **Media Library** — Central asset manager with search, filter, reuse tracking
5. **Template System** — Page template selectable from admin
6. **Preview / Draft Mode** — Preview pages before publishing

**Database additions for Phase 2:**
```
pages              (id, title, slug, template_id, status, meta_title, meta_description, og_image)
page_blocks        (id, page_id, block_type, label, data JSON, sort_order, is_visible)
menus              (id, name, location, is_active)
menu_items         (id, menu_id, parent_id, label, link_type, link_target, sort_order)
page_templates     (id, name, blade_file, preview_image, description)
```

### Phase 3 — FUTURE (visual builder — when explicitly requested)

Live drag-and-drop block editor. Inline editing. Real-time preview panel.
**Do not build Phase 3 until the owner explicitly requests it.**

---

## Block Type System (Phase 2)

Each block type has a fixed data schema stored as JSON in `page_blocks.data`:

| Block Type | Key Data Fields |
|------------|----------------|
| `hero` | title, subtitle, image, cta_text, cta_url |
| `text` | heading, body_html |
| `image` | src, alt, caption, width_class |
| `gallery` | image_ids[], columns, caption |
| `cta` | title, description, button_text, button_url, style |
| `products_grid` | category_id, destination_id, limit, show_price |
| `faq` | faq_ids[] or inline Q&A array |
| `testimonials` | testimonial_ids[] or inline array |
| `map` | embed_url, address, zoom |
| `divider` | style (line, space, gold-line) |

Block admin UI renders the correct form fields based on `block_type`.
Frontend renders the correct Blade partial based on `block_type`.

---

## Menu Item Link Types

```
page        → links to a Page by slug
product     → links to a Product by slug
category    → links to a Category by slug
destination → links to a Destination by slug
url         → external URL (string)
anchor      → in-page anchor (#section-id)
```

---

## CMS Module Pattern (Every New Module)

1. Migration (table + indexes)
2. Model + relationships
3. Form Request (validation)
4. Admin Controller (CRUD)
5. Service (business logic)
6. Admin Blade views
7. Frontend rendering (partial or component)
8. SEO handling
9. Security check
10. Documentation
11. Tests

---

## Frontend Rendering Rules

All frontend pages must:
1. Load page data from database (blocks, meta, template, status)
2. Only render `status = published` content
3. Render blocks in `sort_order` order
4. Apply template from page settings
5. Inject brand settings from CMS (colors, fonts, logos)
6. Use navigation from `menus` table
7. Use SEO meta from page settings or global defaults

No hardcoded content in Blade templates.
No hardcoded navigation items.
No hardcoded section order or visibility.

---

## Safety Rules

- Do not remove or rebuild existing Phase 1 modules
- Do not modify existing migrations
- Do not change page_sections behavior without approval
- Extend the existing system — do not replace it
- Phase 2 blocks and page_sections can coexist
- Do not build Phase 3 (drag-and-drop) unless explicitly requested