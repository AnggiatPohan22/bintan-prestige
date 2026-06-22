# Phase 5 — Progress Handoff & Living Reference
# Bintan Prestige CMS

> **Dokumen ini adalah source of truth untuk Phase 5.**
> Di-update setiap kali task selesai. AI agents WAJIB membaca file ini
> sebelum memulai pekerjaan apapun yang menyentuh Phase 5 code.
>
> Last updated: Stage C2 — 2026-06-23

---

## 1. Phase 5 Overall Status

| Stage | Task | Status | Report |
|-------|------|--------|--------|
| A | A1 Backend Readiness Audit | ✅ DONE | a1-backend-readiness-audit.md |
| A | A2 Admin Dashboard UX Refactor | ✅ DONE | a2-admin-ux-refactor-plan.md |
| A | A3 Block Library Expansion | ✅ DONE | a3-block-library-plan.md |
| A | A4 Content & Data Efficiency | ✅ DONE | a4-efficiency-review.md |
| A | A5 Frontend Polish | ✅ DONE | a5-frontend-polish-plan.md |
| B | B0 Architecture Decision | ✅ DONE | b0-builder-architecture.md |
| B | B1 Builder Shell & Canvas | ✅ DONE | b1-builder-shell.md |
| B | B2 Block Insertion & Ordering | ✅ DONE | b2-dnd-ordering.md |
| B | B3 Block Settings Panel | ✅ DONE | b3-block-settings.md |
| B | B4 Inline Editing | ✅ DONE | b4-inline-editing.md |
| B | B5 Reusable Patterns | ✅ DONE | b5-reusable-patterns-saved-blocks-report.md |
| B | B6 Templates Integration | ✅ DONE | b6-templates-integration-report.md |
| B | B7 Responsive Preview | ✅ DONE | b7-responsive-preview-report.md |
| C | C1 Static Analysis & Code Quality | ✅ DONE | c1-static-analysis-code-quality-report.md |
| C | C2 Performance Audit | ✅ DONE | c2-performance-audit-report.md |
| C | C3 Functional Smoke Test | ⏳ PENDING | — |
| C | C4 Architecture Documentation | ⏳ PENDING | — |

---

## 2. Architecture Decisions (from B0)

### 2.1 Canvas Model

- **Block tree format:** `{ _cid, id, type, label, data, is_visible, sort_order, children[] }`
  - `_cid` — client-only incrementing integer for Alpine `x-for` tracking, never sent to server
  - `id` — DB id (null for unsaved new blocks)
  - `children[]` — nested nodes (supported for `group` / `columns` types, max 2 levels deep)
- **Storage:** `pages` + `page_blocks` table; nested via `page_blocks.parent_block_id` (migration `2026_06_21_000003`)
- **Data column format:** JSON in `page_blocks.data` column
- **Nesting:** Supported — Group / Columns can contain child blocks. Max depth 2.
- **Loading:** Tree embedded in Blade as `@json` on builder load (no AJAX round-trip)
- **Saving:** `POST /admin/pages/{page}/blocks/save-tree` — full tree payload, wrapped in DB transaction with revision snapshot before replace

### 2.2 Live Preview Approach

- **Method:** iframe with `srcdoc` refresh
- **Preview route:** `POST /admin/pages/{page}/preview-payload` → `Frontend\PageController::previewPayload()`
- **Sync mechanism:** Alpine `$watch` + 800ms debounce → `fetch()` POST → sets `iframe.srcdoc`
- **Media changes:** trigger immediate refresh (discrete events, not streaming)
- **Cross-origin:** no restriction — same admin domain

### 2.3 Drag-and-Drop

- **Library:** `@alpinejs/sort` v3.15.12 (already in `package.json`, no new package)
- **Integration:** `x-sort` directive on container, `@sort` event fires updated order into Alpine store
- **Nesting:** supported for Columns → Group → leaf blocks

### 2.4 Layout

- **Builder layout:** Fixed `20:60:20` (left panel : canvas : right panel)
- **Device preview:** CSS-only `max-width` on iframe — no JS scaling, no ResizeObserver
- **Device widths:** Desktop (fluid), Tablet (768px), Mobile (375px)
- **Status bar:** Canvas shows active device label below iframe (added B7)

### 2.5 Inline Editing

- **Approach:** `contenteditable` elements in the canvas iframe; on blur, values written to Alpine store
- **Sanitization:** `App\Support\InlineContentSanitizer` (server-side, applied on every save and preview)
- **Field types:**
  - `plaintext` → `strip_tags()` — no HTML at all
  - `richtext` → allowlist-based tag + attribute filter

### 2.6 Architecture Constraints (PRESERVED — do not change without owner approval)

1. `page_templates` = frontend layout-shell allowlist only. Not storage for reusable designs.
2. Reusable builder designs live in `builder_templates` (block forests + metadata).
3. Persisted page structure = `pages` + `page_blocks` with `parent_block_id` nesting.
4. `BuilderTreeSanitizer` = shared server contract for page save, patterns, and templates. Never fork it.
5. Applying a layout shell must preserve blocks. Applying a reusable template may replace tree only after user confirmation.
6. Fixed `20:60:20` layout and CSS-only device sizing. No JS scaling.
7. Public rendering driven by existing frontend page render and theme hierarchy.

---

## 3. Block Registry

### 3.1 Registry Location

- **File:** `config/blocks.php`
- **Pattern:** Array keyed by block type slug. Each entry has `label`, `icon`, `category`, `description`, `keywords`, `supports`, `fields[]`.
- **API endpoint:** `GET /admin/api/block-types` → `PageBlockController::apiTypes()` (returns registry JSON for builder UI)

### 3.2 Block Inventory (19 block types)

| Block Type | Category | Admin Form | Frontend Render | Inline Edit | Style Controls |
|------------|----------|------------|-----------------|-------------|----------------|
| `group` | Layout | `config/blocks.php` fields | `frontend/blocks/group.blade.php` | No | Background, Margin, Padding, Z-index, CSS ID/Classes, Responsive hide, Custom CSS |
| `columns` | Layout | `config/blocks.php` fields | `frontend/blocks/columns.blade.php` | No | Same as group |
| `divider` | Layout | `config/blocks.php` fields | `frontend/blocks/divider.blade.php` | No | Style selector |
| `hero` | Content | `config/blocks.php` fields | `frontend/blocks/hero.blade.php` | Partial (title, subtitle, cta_text: plaintext) | Background color, min height, overlay |
| `heading` | Content | `config/blocks.php` fields | `frontend/blocks/heading.blade.php` | Full (text: plaintext) | Level, alignment |
| `text` | Content | `config/blocks.php` fields | `frontend/blocks/text.blade.php` | Full (body_html: richtext) | Background |
| `stats` | Content | `config/blocks.php` fields | `frontend/blocks/stats.blade.php` | No | Heading, alignment |
| `faq` | Content | `config/blocks.php` fields | `frontend/blocks/faq.blade.php` | No | Source (inline/library) |
| `image` | Media | `config/blocks.php` fields | `frontend/blocks/image.blade.php` | No | Width, alt, caption |
| `gallery` | Media | `config/blocks.php` fields | `frontend/blocks/gallery.blade.php` | No | Columns, gap, aspect ratio, lightbox |
| `video_embed` | Media | `config/blocks.php` fields | `frontend/blocks/video-embed.blade.php` | No | Aspect ratio |
| `cta` | Conversion | `config/blocks.php` fields | `frontend/blocks/cta.blade.php` | Partial (title, description, button_text: plaintext) | Style (dark/light/gold) |
| `button_group` | Conversion | `config/blocks.php` fields | `frontend/blocks/button-group.blade.php` | No | Alignment |
| `pricing_table` | Conversion | `config/blocks.php` fields | `frontend/blocks/pricing-table.blade.php` | No | Plans repeater |
| `contact_form` | Conversion | `config/blocks.php` fields | `frontend/blocks/contact-form.blade.php` | No | Form definition selector |
| `products_grid` | Travel | `config/blocks.php` fields | `frontend/blocks/products.blade.php` | No | Category/destination filter |
| `tour_itinerary` | Travel | `config/blocks.php` fields | `frontend/blocks/tour-itinerary.blade.php` | No | Timeline repeater |
| `testimonials` | Travel | `config/blocks.php` fields | `frontend/blocks/testimonials.blade.php` | No | Repeater |
| `map` | Travel | `config/blocks.php` fields | `frontend/blocks/map.blade.php` | No | Embed URL, zoom |

### 3.3 Block Authoring Pattern (how to add a new block)

1. Add entry to `config/blocks.php` with slug key, label, icon, category, supports, fields
2. Create admin settings form partial at `resources/views/backend/pages/partials/blocks/{type}.blade.php`
3. Create frontend render at `resources/views/frontend/blocks/{type}.blade.php`
4. Register any sanitization rules in `PageBlockService::sanitizeInlineFields()` if the block has `inline` fields
5. Add to Tailwind safelist in `tailwind.config.js` if block uses dynamic Tailwind classes
6. Write Feature tests for block store/update/render

---

## 4. Admin Structure (from A2)

### 4.1 Sidebar Groups

The admin sidebar was refactored in A2. Structure after refactor (in `resources/views/components/admin/sidebar.blade.php`):
- **Content** — Pages, Blocks, Menus, Media
- **Products & Tours** — Products, Categories, Destinations
- **Forms** — Contact Forms
- **Appearance** — Theme, Widgets, Page Templates
- **Marketing** — SEO, Redirects, Analytics
- **System** — Settings, Plugins, Audit Log, Users

### 4.2 Shared Admin Components

| Component | Path |
|-----------|------|
| `x-admin.sidebar` | `resources/views/components/admin/sidebar.blade.php` |
| `x-admin.data-table` | `resources/views/components/admin/data-table.blade.php` |
| `x-admin.form-shell` | `resources/views/components/admin/form-shell.blade.php` |
| `x-admin.publish-box` | `resources/views/components/admin/publish-box.blade.php` |
| `x-admin.command-palette` | `resources/views/components/admin/command-palette.blade.php` |

---

## 5. Builder Routes & Files

### 5.1 Key Routes

```
GET  /admin/pages/{page}/builder          → admin.pages.builder      → PageBuilderController@show
POST /admin/pages/{page}/blocks/save-tree → admin.page-blocks.save-tree → PageBuilderController@saveTree
POST /admin/pages/{page}/preview-payload  → admin.pages.preview-payload → Frontend\PageController@previewPayload
GET  /admin/pages/{page}/preview          → admin.pages.preview      → Frontend\PageController@preview
GET  /admin/builder-patterns              → admin.builder-patterns.index → BuilderPatternController@index
POST /admin/builder-patterns              → admin.builder-patterns.store → BuilderPatternController@store
GET  /admin/builder-patterns/{id}         → admin.builder-patterns.show  → BuilderPatternController@show
DEL  /admin/builder-patterns/{id}         → admin.builder-patterns.destroy
GET  /admin/builder-templates             → admin.builder-templates.index → BuilderTemplateController@index
GET  /admin/builder-templates/{id}        → admin.builder-templates.show  → BuilderTemplateController@show
POST /admin/pages/{page}/builder/templates → admin.builder-templates.store → BuilderTemplateController@store
DEL  /admin/builder-templates/{id}        → admin.builder-templates.destroy
```

### 5.2 Key Files Map

```
Builder Layout:
  resources/views/layouts/builder.blade.php

Builder Views (Admin):
  resources/views/backend/builder/index.blade.php
  resources/views/backend/builder/partials/topbar.blade.php
  resources/views/backend/builder/partials/panel-left.blade.php
  resources/views/backend/builder/partials/canvas.blade.php
  resources/views/backend/builder/partials/panel-right.blade.php
  resources/views/backend/builder/partials/builder-field.blade.php
  resources/views/backend/builder/partials/alpine-component.blade.php  ← Alpine store lives here
  resources/views/backend/builder/partials/template-library.blade.php

Builder PHP (Admin):
  app/Http/Controllers/Admin/PageBuilderController.php
  app/Http/Controllers/Admin/BuilderPatternController.php
  app/Http/Controllers/Admin/BuilderTemplateController.php
  app/Http/Requests/Admin/StoreBuilderPatternRequest.php
  app/Http/Requests/Admin/StoreBuilderTemplateRequest.php

Builder Models:
  app/Models/BuilderPattern.php
  app/Models/BuilderTemplate.php

Builder Services:
  app/Services/BuilderTreeSanitizer.php   ← shared sanitizer for save, patterns, templates
  app/Services/BuilderPatternService.php
  app/Services/BuilderTemplateService.php

Sanitization:
  app/Support/InlineContentSanitizer.php   ← richtext allowlist + plaintext strip
  app/Support/BlockStyle.php               ← Advanced tab CSS/class sanitization

Block Rendering (Frontend):
  resources/views/frontend/blocks/*.blade.php

Block Settings Forms (Admin):
  resources/views/backend/pages/partials/blocks/*.blade.php

Block Registry:
  config/blocks.php

Page Rendering Service:
  app/Support/PageRenderData.php
  app/Services/PageBlockService.php
  app/Services/PageService.php

Tests:
  tests/Feature/Admin/BuilderPatternTest.php
  tests/Feature/Admin/BuilderTemplateTest.php
  tests/Feature/Admin/PageBlockManagementTest.php
  tests/Feature/Admin/PageManagementTest.php
```

---

## 6. Caching & Performance Rules

### Cache Keys

| Key Pattern | Data | Duration | Invalidation |
|-------------|------|----------|--------------|
| `active_theme` | Active theme record | request-scoped | Theme/Widget saved/deleted (`AppServiceProvider`) |
| `site_settings.*` | SiteSetting values by key | request-scoped or cache::remember | On SiteSetting saved |

### Eager Loading Rules (from A4)

- `Page::with(['blocks' => fn($q) => $q->orderBy('sort_order'), 'blocks.children'])` — required for page render
- `PageBlock::with('children')` — required in tree traversal
- Never load blocks without ordering (`sort_order ASC`)

### Queries fixed in Stage A

- N+1 on page blocks resolved by eager loading `children` in the tree builder
- Block type config now loaded once from `config/blocks.php` (cached by Laravel config cache), not queried per-block

---

## 7. Sanitization Rules

### HTML Allowlist (richtext fields — `InlineContentSanitizer::richtext()`)

```
Tags: p, br, strong, b, em, i, u, a, h1, h2, h3, h4, h5, h6, ul, ol, li, span, blockquote
Attrs allowed:
  - class: alphanumeric + underscore/dash only
  - href (on <a>): http/https URLs only (FILTER_VALIDATE_URL + scheme check)
  - target (on <a>): _blank or _self only
  - rel: auto-added "noopener noreferrer" when target=_blank
```

### Stripped (no exceptions)

```
Elements dropped with content: script, style, iframe, object, embed, form, input,
  textarea, select, button, link, meta, base, svg, math
All on* event attributes, javascript: URIs, data: URIs
HTML comments stripped before processing
```

### Plaintext fields (`InlineContentSanitizer::plaintext()`)

`strip_tags()` — no HTML at all. Used for: heading text, titles, subtitles, button text, labels.

### Custom CSS (`BlockStyle::customCss()`)

Strips `<` and `>` characters; capped at 5000 chars. Injected inside `<style>` tag for group/columns blocks.

### CSS ID/Classes (`BlockStyle::cssId()`, `BlockStyle::cssClasses()`)

IDs: `[A-Za-z0-9_-]` only, must start with a letter.
Classes: `[A-Za-z0-9_-]` only, max 20 classes, max 20 chars each (via regex filter).

---

## 8. Theme Token Integration (from A5)

- **CSS custom properties:** `--color-primary`, `--color-secondary`, `--color-accent`, `--font-heading`, `--font-body`, `--radius-*`, `--spacing-*`
- **Token source:** `config/theme.php` defaults, overridden by active Theme record from database
- **Override mechanism:** `ThemeService` reads active theme → injects `<style>` with CSS custom properties in `<head>`
- **Block usage:** Frontend block Blade files use Tailwind utilities that reference design tokens via `tailwind.config.js` theme extension

---

## 9. Stage C — Release Audit Progress

### C1 — Static Analysis & Code Quality

- Status: ✅ DONE — 2026-06-23
- PHPStan: Level 5, **0 errors** (PASS)
- Test suite: **627 tests**, **3206 assertions**, **0 failures** (PASS)
  - Phase 4 baseline: 596 tests / 2765 assertions
  - Phase 5 added: +31 tests, +441 assertions
- Code quality: No dead code, no debug statements, no TODO/FIXME in Phase 5 files
- Security: All `{!! !!}` usages verified — 8/10 explicitly sanitized, 2/10 pre-Phase-5 admin-only widgets (deferred as TD-04, TD-05)
- Report: c1-static-analysis-code-quality-report.md

### C2 — Performance Audit

- Status: ✅ DONE — 2026-06-23
- Route timing (artisan serve, warm): `/` 291ms · `/products` 283ms · `/pages/{slug}` 194-222ms · `/sitemap.xml` 64ms — all ≤300ms ✅
- DB queries: page render 3-8 queries (no N+1) · builder open 5 queries · save-tree N+3 (by design) ✅
- Index coverage: all Phase 5 tables (parent_block_id, builder_patterns, builder_templates) properly indexed ✅
- Asset bundles: 3 entries, 212 KB + 128 KB CSS + 87 KB JS (uncompressed) — reasonable ✅
- Pre-release config: `APP_DEBUG=true` and `APP_ENV=local` must be flipped before production (not a code issue)
- Note: `BuilderPatternController::index()` loads all patterns unpaginated — fine at current scale, Phase 6 item
- Report: c2-performance-audit-report.md

### C3 — Functional Smoke Test

- Status: ⏳ PENDING

### C4 — Architecture Documentation

- Status: ⏳ PENDING

---

## 10. Known Technical Debt & Deferred Items

| # | Item | Severity | Deferred From | Notes |
|---|------|----------|---------------|-------|
| TD-03 | Child Theme Support | LOW | Phase 4 | Move to Phase 6 or 7 |
| TD-04 | Widget text content not sanitized (`widgets/text.blade.php`) | LOW | Phase 5 C1 | Admin-only input; no user-facing XSS. Add `InlineContentSanitizer::richtext()` in Phase 6. |
| TD-05 | DB query in Blade (`blocks/contact-form.blade.php` line 3) | LOW | Phase 5 C1 | Pre-Phase-2 pattern. Move `FormDefinition::find()` to `PageRenderData` in Phase 6. |

---

## 11. Release Gate Status

| Gate | Status | Evidence |
|------|--------|----------|
| Test suite | ✅ PASS | 627 tests, 3206 assertions, 0 failures |
| PHPStan | ✅ PASS | Level 5, 0 errors |
| Performance | ✅ PASS | All warm-run routes ≤300ms; no N+1; indexes complete |
| Smoke test | ⏳ PENDING | C3 not started |
| Architecture docs | ⏳ PENDING | C4 not started |
| **Release Gate** | **⏳ PENDING** | C2, C3, C4 outstanding |

---

## 12. Phase 6 Preparation Notes

> Will be completed at C4 — Architecture Documentation.

Preliminary notes from Phase 5 audit:
- TD-04 and TD-05 are natural Phase 6 targets (widget sanitization, Blade query cleanup)
- Builder architecture is extensible: new block types require only `config/blocks.php` + two Blade files
- `BuilderTreeSanitizer` is the single save gateway — Phase 6 content type additions should route through it
- Consider FormRequest for `PageBlockController` inline validation in Phase 6 (currently uses `$request->validate()`)
