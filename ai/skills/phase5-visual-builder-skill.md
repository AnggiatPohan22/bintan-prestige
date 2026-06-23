# Phase 5 — Visual Page Builder & Foundation Hardening
### Master Skill & Execution Plan for Bintan Prestige CMS

> **For AI agents (Claude, Codex, Gemini, Qwen):**
> Read `AGENTS.md` first, then this file. This document supersedes generic
> assumptions about Phase 5. It is split into two stages. **Stage A must be
> fully completed and approved before any Stage B work begins.**
>
> Authority order, the 11-step CMS Module Pattern, the Critical Safety Rules,
> and the Approval Gates from `AGENTS.md` all remain in force throughout.

---

## 0. Why this document exists

The owner wants to build a WordPress-like **visual editing experience** (drag-and-drop,
live preview, inline editing). But before adding that layer, the project must be
**hardened and tidied** so the new builder is built on a clean, efficient foundation —
not on top of inconsistent admin UI, incomplete blocks, and inefficient content patterns.

Building the visual builder first and cleaning later would double the rework.
Therefore Phase 5 is intentionally two stages:

| Stage | Name | Purpose | Gate |
|-------|------|---------|------|
| **A** | Foundation Hardening | Audit + clean + standardize before building | Owner approval required to enter Stage B |
| **B** | Visual Page Builder | Drag-and-drop, live preview, inline editing | Built on the cleaned foundation |

---

## 1. Context snapshot

**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL
**Architecture:** MVC + Service Layer + Support Classes
**Completed:** Phases 1–4 (data sync, website builder, theme system, plugin system)

**Already exists (do not rebuild — extend):**
- Block editor with: Hero, Text, Image, Gallery, CTA, Products, FAQ blocks
- Page module, Menu manager, Media library, Template system, Preview/draft mode
- Theme system (design tokens, fonts, export/import)
- Plugin system (hooks/filters, revisions, scheduling, audit log, SEO, analytics)

**Known pain points to fix in Stage A (owner-reported):**
1. Admin dashboard menu/navigation too long and inefficient
2. Admin content layouts inefficient (tables, forms, list density)
3. Block library incomplete — too few block types
4. Frontend output not yet as polished/attractive as WordPress themes
5. General lack of consistency that would make a visual builder harder to build

---

# STAGE A — FOUNDATION HARDENING

> **Execution model for Stage A:**
> Each task below begins with an **audit-only inspection** (read-only).
> The agent produces findings + a proposed plan, then **STOPS** for owner approval
> before changing any code. No schema changes, renames, or package installs
> without explicit approval per `AGENTS.md` Section 9.

---

## A1. Backend supporting-features audit

**Goal:** Confirm the backend can support a visual builder before building the UI.

**Inspect and report (read-only):**

1. **Block storage format**
   - How are page blocks currently persisted? (JSON column? separate table? serialized?)
   - Is each block self-describing (type + attributes + order)?
   - Is the structure a flat list or a nested tree? (Visual builders need nesting for columns/groups.)
   - Can blocks be reordered by an `order`/`position` field reliably?

2. **Block schema definition**
   - Is each block type defined in one place (a registry), or scattered?
   - Does each block declare its editable fields somewhere the UI can read?
   - Is there a single source of truth mapping `block type → admin fields → frontend render`?

3. **Draft vs published**
   - Can a page hold an unpublished draft block tree separate from the live one?
   - Does the existing Preview/draft mode (Phase 2) support previewing arbitrary draft JSON?

4. **Rendering pipeline**
   - How does a block JSON node become frontend HTML? (Blade component per block?)
   - Is there a consistent `BlockRenderer` / `renderBlock()` path, or is rendering ad-hoc?

5. **Media integration**
   - Can the media library return a picker payload (id, url, alt) usable by a JS canvas?

6. **Revisions (Phase 4)**
   - Can the existing revision system snapshot a full block tree for undo/restore?

**Deliverable:** `ai/reports/phase-5/a1-backend-readiness-audit.md` containing:
- Current block storage model (with evidence: file paths, schema)
- Gaps that must close before Stage B (e.g. "blocks are flat, need nesting support")
- A ranked list of backend changes needed, each marked: `safe` / `needs-approval` / `schema-change`
- Recommendation: which backend adjustments belong in Stage A vs which can wait

**STOP after A1. Wait for owner approval of the readiness plan.**

---

## A2. Admin dashboard UX refactor

**Goal:** Make admin navigation and layout efficient and WordPress-like before the builder adds even more screens.

**Inspect (read-only):**
- Current admin sidebar/menu structure (file, route names, how items are grouped)
- Count of top-level menu items (owner reports it is too long)
- Layout patterns of admin index/list pages and edit forms

**Proposed standard (WordPress-inspired) — confirm with owner before building:**

1. **Grouped, collapsible sidebar**
   Consolidate the long flat menu into logical clusters with icons:
   - **Content** — Pages, Products, Destinations, Categories, Media, FAQs
   - **Design** — Themes, Tokens, Menus, Templates, Blocks
   - **Plugins** — installed plugins, plugin settings, hooks
   - **SEO** — sitemap, robots, redirects, meta
   - **Analytics** — dashboard, page views
   - **Settings** — global settings (13 modules grouped into tabs, not 13 menu items)
   - **Users** — user management, roles
   Sub-items expand under each group; collapsed by default to shorten the menu.

2. **Command palette / quick search** (optional, high value)
   A `Ctrl/Cmd+K` search to jump to any admin screen — removes reliance on a long menu.

3. **Consistent list-page pattern**
   Every index page: search box, filters, bulk actions, pagination, column sorting,
   row quick-actions (edit/duplicate/delete), consistent density. Build one shared
   Blade partial / component so all modules reuse it.

4. **Consistent edit-form pattern**
   Standard two-column layout: main content left, settings/publish box right
   (mirrors WordPress edit screens). Reusable form shell component.

5. **Global Settings consolidation**
   Group the 13 settings modules into a single tabbed Settings screen instead of
   13 separate menu entries.

**Rules:**
- This is UI restructuring only — **do not rename routes, controllers, or change auth.**
  Navigation grouping is presentational; underlying routes stay the same.
- Reuse existing Blade + Alpine + Tailwind. No new packages without approval.
- Work module-by-module; keep each refactor a focused commit.

**Deliverable:** `ai/reports/phase-5/a2-admin-ux-refactor-plan.md` with before/after
menu structure, list of shared components to create, and files to touch.

**STOP after the plan. Wait for owner approval before implementing.**

---

## A3. Block system audit & library expansion

**Goal:** Reach WordPress/Gutenberg-level block coverage so users can build rich pages.

**Inspect (read-only):**
- Current 7 blocks (Hero, Text, Image, Gallery, CTA, Products, FAQ)
- How a new block is currently added end-to-end (registry → admin form → frontend render)
- Whether blocks support per-block style settings (spacing, background, alignment)

**Proposed block library expansion (confirm priority with owner):**

*Layout & structure blocks (needed for visual builder nesting):*
- Columns / Grid (2–4 columns, responsive)
- Group / Section (container with background + padding)
- Spacer / Divider

*Content blocks:*
- Heading (separate from Text, with level H1–H6)
- Button / Button Group
- List (bulleted/numbered)
- Quote / Testimonial
- Accordion / Tabs
- Table
- Video / Embed (YouTube, Vimeo, map embed)
- Icon + Text (feature item)
- Stats / Counter

*Travel-specific blocks (high value for Bintan Prestige):*
- Tour Itinerary timeline
- Booking / Inquiry widget
- Destination Map block
- Pricing Table
- Review / Rating block
- Image Carousel / Slider

**For every new block, follow the 11-step CMS Module Pattern (AGENTS.md §6):**
each block needs a registry entry, editable field schema, admin editing UI,
frontend Blade render, and tests. Keep the `block type → fields → render` mapping
in one registry so the visual builder (Stage B) can read it dynamically.

**Deliverable:** `ai/reports/phase-5/a3-block-library-plan.md` — ranked block list,
the shared block-authoring pattern, and which blocks ship in Stage A vs Stage B.

**STOP after the plan. Wait for owner approval.**

---

## A4. Content & data efficiency review

**Goal:** Remove inefficiencies so admin screens and frontend stay fast as content grows.

**Inspect (read-only):**
- N+1 queries on admin list pages and frontend pages (use the existing Phase 4 / STEP 9B profiling approach)
- Whether large lists paginate or load everything
- Whether media/images are optimized and lazy-loaded on the frontend
- Whether block rendering causes repeated queries (e.g. Products block re-querying)

**Allowed without schema change (per AGENTS.md):**
- Add eager loading, remove duplicate queries, paginate, cache lookup data via existing abstractions.

**Needs separate approval:** any new index, schema change, or new package.

**Deliverable:** `ai/reports/phase-5/a4-efficiency-review.md` with measured findings
and a safe optimization plan. Reuse the STEP 9B methodology already proven in Phase 4.

---

## A5. Frontend polish baseline

**Goal:** Make the rendered website attractive and consistent before the builder lets users assemble pages.

**Inspect & propose (read-only first):**
- Consistency of spacing, typography scale, and color usage across rendered blocks (tie into the Phase 3 theme tokens)
- Responsive behavior of each block on mobile
- Default "good-looking" presets for each block so a user dragging a block gets an attractive result without manual styling

**Rule:** All visual styling must flow from the Phase 3 design tokens — no hardcoded
colors or fonts. This guarantees the visual builder output stays theme-consistent.

**Deliverable:** `ai/reports/phase-5/a5-frontend-polish-plan.md`.

---

## Stage A exit criteria

Stage A is complete and Stage B may begin only when **all** are true:

- A1–A5 reports created and owner-approved
- Admin navigation consolidated and consistent
- Block authoring pattern standardized with a single block registry
- Backend confirmed able to store/render a nestable block tree with draft/publish
- Efficiency review applied; no known N+1 on core pages
- Frontend renders block presets attractively and responsively from theme tokens
- Test suite green, PHPStan level 5 / 0 errors maintained

**The agent must produce a Stage A completion summary and STOP for owner sign-off
("approved enter stage B") before any Stage B code.**

---

# STAGE B — VISUAL PAGE BUILDER

> Begin only after `approved enter stage B`.
> Build on the cleaned foundation from Stage A.

---

## B0. Architecture decision (decide & document first)

The visual builder is essentially an **Alpine.js-driven canvas** in the admin that
edits a **JSON block tree** and renders a **live preview**. Decide and record:

1. **Canvas model**
   - The builder edits an in-memory JSON block tree (loaded from the page's draft).
   - Each node: `{ id, type, attributes, children[] }` (nesting enables columns/groups).
   - Saving writes the tree back to the draft store from Stage A.

2. **Live preview approach** (pick one, document tradeoffs):
   - **Iframe preview** pointing at the existing Preview route, refreshed/patched via `postMessage` — safest, reuses real frontend Blade rendering, guarantees WYSIWYG.
   - **In-canvas render** — faster feedback but risks preview ≠ production. *Iframe is recommended* because it reuses the real Blade block renderer from Stage A.

3. **Drag-and-drop library**
   - Alpine has no native DnD. The standard lightweight choice is **SortableJS**.
   - **This requires a new package → needs owner approval (AGENTS.md §9).**
     Propose it explicitly; do not install without approval.

4. **Inline text editing**
   - Use `contenteditable` for Text/Heading blocks, sanitize on save (server-side).
   - Never trust client HTML — sanitize through a server-side allowlist.

**Deliverable:** `ai/reports/phase-5/b0-builder-architecture.md`. STOP for approval
of the architecture (especially any new package) before building.

---

## B1. Builder shell & canvas

- Admin route + screen hosting the builder (reuse existing admin auth/policies).
- Left: block inserter panel (reads the Stage A block registry dynamically).
- Center: canvas showing the block tree (iframe preview recommended).
- Right: settings panel for the selected block (renders that block's field schema).
- Top bar: Save draft, Preview, Publish, Undo/Redo (backed by Phase 4 revisions).

## B2. Block insertion & ordering

- Insert blocks from the inserter into the tree at a chosen position.
- Drag-and-drop reordering (SortableJS, if approved).
- Move blocks into/out of container blocks (Columns, Group).

## B3. Block settings panel

- For the selected block, render its editable fields from the registry schema.
- Per-block style controls (spacing, background, alignment) flowing to theme tokens.
- Live update of the preview as fields change.

## B4. Inline editing

- Click-to-edit text directly on the canvas for text-like blocks.
- Server-side sanitization on save.

## B5. Reusable patterns & saved blocks

- Let users save a configured block (or group) as a reusable pattern.
- Pattern library in the inserter (WordPress "Patterns" parity).

## B6. Templates integration

- Builder can start a page from an existing Phase 2 template.
- Save a built page as a new template.

## B7. Responsive & preview controls

- Desktop/tablet/mobile preview toggles in the builder.
- Confirm each block renders correctly per breakpoint.

---

## Stage B verification (per AGENTS.md workflow)

For each builder module:
- Follow the 11-step CMS Module Pattern
- Focused tests, then full Phase test group, then full suite (must stay 0 failures)
- PHPStan level 5 / 0 errors maintained
- Functional checks: build a page end-to-end, save draft, preview, publish, verify frontend output matches preview
- No schema change, rename, or package install without approval

---

# WordPress feature-parity reference

Where Bintan Prestige stands toward WordPress-like capability after Phase 5:

| WordPress capability | Bintan Prestige equivalent | Status target |
|----------------------|----------------------------|---------------|
| Gutenberg block editor | Block editor (Phase 2) + Visual builder (Phase 5) | Phase 5 closes the gap |
| Block patterns / reusable blocks | B5 reusable patterns | Phase 5 |
| Live preview / Customizer | B0 iframe preview + Phase 3 tokens | Phase 5 |
| Wide block library | A3 expanded library | Stage A |
| Themes & design tokens | Phase 3 theme system | Done |
| Plugins & hooks | Phase 4 plugin system | Done |
| Media library | Phase 2 media library | Done |
| Menus | Phase 2 menu manager | Done |
| Revisions / autosave | Phase 4 revisions + B1 undo | Phase 5 |
| Custom post types / fields | *Deferred to Phase 6* | Future |
| Multi-language | *Deferred to Phase 7* | Future |

---

# Suggested Skill Map additions (for AGENTS.md §3)

| Task Type | Read These Skill Files |
|-----------|------------------------|
| Visual builder canvas / DnD | `phase5-visual-builder-skill.md` + `page-builder-skill.md` |
| Block authoring (new blocks) | `phase5-visual-builder-skill.md` (§A3) + `cms-architect-skill.md` |
| Admin UX refactor | `phase5-visual-builder-skill.md` (§A2) + `admin-dashboard-skill.md` + `frontend-design-skill.md` |

---

# Branch & reporting

- Stage A branch: `feature/phase-5-stage-a-foundation`
- Stage B branch: `feature/phase-5-stage-b-visual-builder`
- Merge target: `develop` (per owner convention)
- Reports go in `ai/reports/phase-5/`
- Use the AGENTS.md §11 Report Format after every task
- Do not mark Phase 5 complete, tag, or merge to develop without owner approval

---

# Critical reminders (inherited from AGENTS.md)

- No schema change, migration edit, rename, feature removal, or package install
  without explicit owner approval.
- Inspect before editing. List files that will change. Plan first, implement in small steps.
- Hardcode nothing — everything flows from CMS data and theme tokens.
- Keep the project in a better state than found, section by section.
