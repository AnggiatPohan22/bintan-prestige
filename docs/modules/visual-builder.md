# Visual Builder — Module Reference

> **Developer reference for the Phase 5 Visual Page Builder.**
> Read this before maintaining, extending, or debugging the builder.
>
> Last updated: 2026-06-23 (Phase 5 C4)
> Stack: Laravel 13.8 · Tailwind CSS · Alpine.js · MySQL

---

## 1. Overview

The Visual Builder (`GET /admin/pages/{page}/builder`) is a full-viewport
page-composition UI that lets admins assemble, edit, and preview pages using
a library of 19 block types — without writing code.

**Separation of concerns:**

| Layer | What it owns |
|-------|-------------|
| `config/blocks.php` | Block registry — all types, fields, categories |
| `PageBuilderController` | Builder page load, tree save |
| `BuilderTreeSanitizer` | Shared server-side tree validation (save, patterns, templates) |
| `PageBlockService` | Per-block data sanitization + render data assembly |
| `InlineContentSanitizer` | HTML sanitization for richtext / plaintext inline fields |
| `BlockStyle` | Advanced CSS ID / class / custom CSS sanitization |
| `BuilderPatternService` | Reusable pattern save/load |
| `BuilderTemplateService` | Page template save/apply |
| Alpine store (`pageBuilder`) | All client-side builder state |
| `frontend/blocks/*.blade.php` | Public-facing block render |

---

## 2. Data Model

### 2.1 `pages` table

Owns page metadata: `slug`, `title`, `status`, `template` (layout shell key), `meta_*`.
No block data is stored here — only the page shell.

### 2.2 `page_blocks` table

```
id                   PK
page_id              FK → pages.id
parent_block_id      FK → page_blocks.id (nullable) — nesting
block_type           varchar (key into config/blocks.php)
label                varchar (user-editable label for the block list)
data                 JSON — all field values for this block type
is_visible           bool — soft-toggle visibility
sort_order           int — position within the parent (or root)
```

Indexes:
- `page_id`
- `parent_block_id`
- `(page_id, sort_order)` composite

Nesting: max 2 levels deep. Only `group` and `columns` blocks can be parents.

### 2.3 `builder_patterns` table

Reusable subtrees saved from any block or container. Never linked to a page — fully independent.

```
id, name, description, thumbnail, block_type, tree_data (JSON), created_at, updated_at
```

Indexes: `block_type`, `created_at`.

### 2.4 `builder_templates` table

Full page block forests saved as reusable starting points.

```
id, name, description, category, thumbnail, template_type, schema_version,
base_layout, template_data (JSON), created_at, updated_at
```

Indexes: `template_type`, `category`, `created_at`.

### 2.5 Block tree format (wire format)

The client Alpine store and every server endpoint use this canonical format:

```json
{
  "_cid": 3,           // client-only integer, never sent to server
  "id": 12,            // DB id (null = unsaved new block)
  "type": "heading",   // key into config/blocks.php
  "label": "My Heading",
  "data": { "text": "Hello", "level": "h2" },
  "is_visible": true,
  "sort_order": 0,
  "children": []       // nested blocks (group / columns only)
}
```

`_cid` is generated client-side and stripped before every server call.

---

## 3. Builder UI Layout

```
┌───────────────────────────────────────────────────────────────────────┐
│  TOPBAR (flex-none)  back · title · panel toggles · device · save      │
├─────────────────┬─────────────────────────────────────┬───────────────┤
│  LEFT  20%      │           CANVAS  60%                │  RIGHT  20%   │
│                 │                                      │               │
│  [Add Block]    │  ┌──────────────────────────────┐   │  Block        │
│  [Patterns]     │  │  device wrapper (max-width)  │   │  Settings     │
│  [Block List]   │  │  ┌────────────────────────┐  │   │  Panel        │
│                 │  │  │  <iframe srcdoc>        │  │   │               │
│  scroll-y       │  │  │  live preview           │  │   │  Schema-      │
│  (own column)   │  │  └────────────────────────┘  │   │  driven       │
│                 │  └──────────────────────────────┘   │  fields       │
│                 │  [ Desktop · Tablet · Mobile ]       │               │
└─────────────────┴─────────────────────────────────────┴───────────────┘
```

- **Grid:** Fixed `20 : 60 : 20` (`lg:w-[20%]` / `flex-1` / `lg:w-[20%]`)
- **No JS scaling.** Device sizing is pure CSS `max-width` on the device wrapper.
- **Mobile editor:** LEFT and RIGHT become fixed overlay drawers (`absolute inset-y-0`).
- **Panels** have independent `overflow-y-auto` columns; only the iframe scrolls content.

**Critical CSS invariants (do not break):**

| Rule | Where |
|------|-------|
| `body` = `h-screen overflow-hidden` | `layouts/builder.blade.php` |
| root = `flex h-screen flex-col` | `index.blade.php` |
| row = `flex flex-1 min-h-0 overflow-hidden` | `index.blade.php` — `min-h-0` is mandatory |
| panels = `lg:flex-none lg:w-[20%] shrink-0` | `panel-left/right` |
| canvas = `flex-1 min-h-0 overflow-hidden` | `canvas.blade.php` |
| iframe = `h-full w-full` | `canvas.blade.php` |

---

## 4. Alpine Store (`pageBuilder`)

Defined in `resources/views/backend/builder/partials/alpine-component.blade.php`.

### State

| Property | Type | Purpose |
|----------|------|---------|
| `tree` | Array | In-memory block tree |
| `isDirty` | bool | Unsaved changes indicator |
| `isSaving / saveError` | bool / str | Save state for topbar |
| `isRefreshing / previewError` | bool / str | Live-preview fetch state |
| `activeTab` | str | `'insert'` \| `'patterns'` \| `'tree'` |
| `selectedCid` | int | Selected block `_cid` |
| `previewMode` | str | `'desktop'` \| `'tablet'` \| `'mobile'` |
| `leftCollapsed / rightCollapsed` | bool | Panel minimize/drawer toggles |
| `activeFieldTab` | str | `'layout'` \| `'style'` \| `'advanced'` for tabbed blocks |
| `_cid / _refreshTimer` | int / timer | Internal counters |

### Key methods

| Method | What it does |
|--------|-------------|
| `init()` | Tag cids, collapse on mobile, trigger first preview |
| `addBlock(type, parentCid)` | Insert block (container-aware) |
| `removeBlock(cid)` | Delete block + children from tree |
| `selectBlock(cid)` | Highlight block, open right panel |
| `toggleVisible(cid)` | Toggle `is_visible` |
| `moveUp / moveDown(cid)` | Reorder within siblings |
| `onSort(event)` | Handle `@alpinejs/sort` sort events |
| `onDragStart/Over/Drop` | Manual drag-drop for nesting |
| `moveNode(cid, targetCid, position)` | Re-parent block in the tree |
| `saveTree()` | POST `{ tree }` to `save-tree` endpoint |
| `refreshPreview()` | POST tree → `srcdoc` into iframe |
| `scheduleRefresh()` | Debounced 800ms preview refresh |
| `deviceMaxWidth()` | Returns `{ desktop:'100%', tablet:'768px', mobile:'375px' }` |
| `saveAsPattern()` | POST selected node subtree to patterns API |
| `loadPattern(id)` | GET pattern + insert cloned subtree |
| `openTemplateLibrary()` | Open template modal |
| `applyTemplate(id)` | GET template forest, replace tree after confirm |
| `selectedNode()` | Returns current block node from tree |
| `fieldTabs(node)` | Returns distinct tabs for the right panel |
| `applyFieldDefaults(node)` | Seeds `data` from registry field defaults |

### Live preview flow

```
Alpine $watch(tree) → scheduleRefresh (800ms debounce)
  → refreshPreview() → POST /admin/pages/{page}/preview-payload
  → server returns full HTML string
  → iframe.srcdoc = response
```

Media picker changes (image field) trigger immediate refresh (no debounce).

---

## 5. Routes

### Builder UI

| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| GET | `/admin/pages/{page}/builder` | `PageBuilderController` | `show` |
| POST | `/admin/pages/{page}/blocks/save-tree` | `PageBuilderController` | `saveTree` |
| POST | `/admin/pages/{page}/preview-payload` | `Frontend\PageController` | `previewPayload` |
| GET | `/admin/pages/{page}/preview` | `Frontend\PageController` | `preview` |

### Block Registry API

| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| GET | `/admin/api/block-types` | `PageBlockController` | `apiTypes` |

### Builder Patterns API

| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| GET | `/admin/builder-patterns` | `BuilderPatternController` | `index` |
| POST | `/admin/builder-patterns` | `BuilderPatternController` | `store` |
| GET | `/admin/builder-patterns/{id}` | `BuilderPatternController` | `show` |
| DELETE | `/admin/builder-patterns/{id}` | `BuilderPatternController` | `destroy` |

### Builder Templates API

| Method | URI | Controller | Action |
|--------|-----|------------|--------|
| GET | `/admin/builder-templates` | `BuilderTemplateController` | `index` |
| GET | `/admin/builder-templates/{id}` | `BuilderTemplateController` | `show` |
| POST | `/admin/pages/{page}/builder/templates` | `BuilderTemplateController` | `store` |
| DELETE | `/admin/builder-templates/{id}` | `BuilderTemplateController` | `destroy` |

All routes are auth-guarded. Unauthenticated requests redirect to login (302).

---

## 6. Block Registry (`config/blocks.php`)

### Structure

```php
return [
  'heading' => [
    'label'       => 'Heading',
    'icon'        => 'fa-heading',
    'category'    => 'content',
    'description' => 'A section heading.',
    'keywords'    => ['title', 'h1', 'h2'],
    'supports'    => ['inline_edit', 'responsive_hide'],
    'fields'      => [
      ['key' => 'text',  'type' => 'text',   'label' => 'Text'],
      ['key' => 'level', 'type' => 'select', 'label' => 'Level',
       'options' => ['h1','h2','h3','h4'], 'default' => 'h2'],
    ],
  ],
  // ...
];
```

### Field types supported

`text`, `url`, `number`, `textarea`, `richtext`, `code`, `select` (static or dynamic
`optionsFrom`), `toggle`, `color`, `range`, `image` (media picker), `box` (4-side
margin/padding), `background` (color + image + position/size/repeat/opacity),
`repeater` (array of objects), `list` (array of strings), plus `showIf` conditional
visibility.

Dynamic select sources (`categories`, `destinations`, `forms`) come from
`PageBuilderController@show` as `$fieldOptions` → passed to JS as `cfg.options`.

### Block Inventory (19 types)

| Type | Category | Inline Edit | Frontend View |
|------|----------|-------------|---------------|
| `group` | layout | No | `group.blade.php` |
| `columns` | layout | No | `columns.blade.php` |
| `divider` | layout | No | `divider.blade.php` |
| `hero` | content | Partial (plaintext) | `hero.blade.php` |
| `heading` | content | Full (plaintext) | `heading.blade.php` |
| `text` | content | Full (richtext) | `text.blade.php` |
| `stats` | content | No | `stats.blade.php` |
| `faq` | content | No | `faq.blade.php` |
| `image` | media | No | `image.blade.php` |
| `gallery` | media | No | `gallery.blade.php` |
| `video_embed` | media | No | `video-embed.blade.php` |
| `cta` | conversion | Partial (plaintext) | `cta.blade.php` |
| `button_group` | conversion | No | `button-group.blade.php` |
| `pricing_table` | conversion | No | `pricing-table.blade.php` |
| `contact_form` | conversion | No | `contact-form.blade.php` |
| `products_grid` | travel | No | `products-grid.blade.php` |
| `tour_itinerary` | travel | No | `tour-itinerary.blade.php` |
| `testimonials` | travel | No | `testimonials.blade.php` |
| `map` | travel | No | `map.blade.php` |

View naming: `str_replace('_', '-', $block_type) . '.blade.php'`
(e.g. `products_grid` → `products-grid.blade.php`).

---

## 7. Save Flow

```
Client: Alpine saveTree()
  → POST /admin/pages/{page}/blocks/save-tree
  → BuilderTreeSanitizer::sanitizeTree($tree)   ← validates types, structure, nesting
  → DB transaction:
      1. snapshot existing page_blocks → page_block_revisions (rollback guard)
      2. delete all page_blocks for this page
      3. insert root-level blocks (sort_order from array index)
      4. insert child blocks (parent_block_id set)
  → 200 JSON { success: true, tree: [...] }
```

`BuilderTreeSanitizer` enforces:
- Block type must exist in `config/blocks.php`
- Max 2 levels of nesting
- Max node count (configurable)
- Children only allowed on `group` and `columns` blocks
- Labels sanitized (strip_tags)
- `data` field validated per-type by `PageBlockService::validateAndSanitizeData()`

---

## 8. Sanitization

### InlineContentSanitizer (`app/Support/InlineContentSanitizer.php`)

Applied on every save and every preview-payload render.

```php
// Plaintext: strips all HTML tags (used for heading text, titles, button labels)
InlineContentSanitizer::plaintext($string);  // → strip_tags()

// Richtext: allowlist-based (used for text block body_html)
InlineContentSanitizer::richtext($string);
```

Richtext allowlist:
- **Tags:** `p, br, strong, b, em, i, u, a, h1–h6, ul, ol, li, span, blockquote`
- **Stripped with content:** `script, style, iframe, object, embed, form, input, textarea, select, button, link, meta, base, svg, math`
- **`href`:** http/https URLs only; `javascript:` and `data:` URIs stripped
- **`target`:** `_blank` or `_self` only
- **`rel`:** `noopener noreferrer` auto-added when `target=_blank`
- **`class`:** alphanumeric + `_-` only
- **`on*` attributes:** all stripped

### BlockStyle (`app/Support/BlockStyle.php`)

Applied on save for Group/Columns Advanced tab fields.

```php
BlockStyle::cssId($value);       // [A-Za-z0-9_-], must start with letter → null if invalid
BlockStyle::cssClasses($value);  // space-separated; filters per-class, max 20 classes
BlockStyle::customCss($value);   // strips < and >, capped at 5000 chars
BlockStyle::advanced($block);    // assembles inline style + class string for frontend render
```

### BuilderTreeSanitizer (`app/Services/BuilderTreeSanitizer.php`)

Single shared contract for tree validation. Used by:
- `PageBuilderController::saveTree()`
- `BuilderPatternService::store()`
- `BuilderTemplateService::store()`

Never fork this class. All tree-persisting paths must go through it.

---

## 9. Responsive Preview & Hide Controls (B7)

### Device preview

The topbar has three device buttons: Desktop / Tablet / Mobile.
Clicking sets `previewMode` in the Alpine store.
`deviceMaxWidth()` returns the CSS `max-width` value applied to the device wrapper.

```js
deviceMaxWidth() {
  return { desktop: '100%', tablet: '768px', mobile: '375px' }[this.previewMode];
}
```

No JS scaling. No ResizeObserver. Pure CSS `max-width` centered with `mx-auto`.

A **status bar** below the iframe shows the active device label.

### Hide-on-device controls

Every block supports `hide_desktop`, `hide_tablet`, `hide_mobile` toggles in the
Advanced tab. These are stored in `page_blocks.data.hide_desktop` etc. and applied
during frontend render:

```php
// In BlockStyle::advanced()
if ($data['hide_desktop'] ?? false) $classes[] = 'hidden lg:hidden';
if ($data['hide_tablet'] ?? false)  $classes[] = 'md:hidden lg:block';
if ($data['hide_mobile'] ?? false)  $classes[] = 'sm:hidden md:block';
```

A **badge** in the block list (LEFT panel) indicates when a block is hidden on
one or more device sizes.

---

## 10. Extension Guide

### How to add a new block type

1. **Register** in `config/blocks.php`:

   ```php
   'my_block' => [
     'label'    => 'My Block',
     'icon'     => 'fa-star',
     'category' => 'content',
     'fields'   => [
       ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
     ],
   ],
   ```

2. **Admin settings form** (optional, for legacy form editor):
   `resources/views/backend/pages/partials/blocks/my_block.blade.php`

3. **Frontend render:**
   `resources/views/frontend/blocks/my-block.blade.php`
   *(note: underscore in type → hyphen in filename)*

4. **Sanitization** — if the block has inline-editable fields, register them in
   `PageBlockService::sanitizeInlineFields()`.

5. **Tailwind safelist** — if the block uses dynamic Tailwind classes (e.g.
   `text-{{ $data['color'] }}`), add them to the safelist in `tailwind.config.js`.

6. **Tests** — write Feature tests in `tests/Feature/Admin/` covering
   block store, update, and frontend render.

### How to change the grid ratio

Edit the two `lg:w-[20%]` values in `panel-left.blade.php` and `panel-right.blade.php`.
The canvas auto-fills the rest (`flex-1`). Keep left + right < 100%.

### How to add a device size

Add an entry to `deviceMaxWidth()` in `alpine-component.blade.php` and a topbar
button that sets `previewMode`. No other change needed.

---

## 11. Performance Notes

- **Block registry** loaded once from Laravel config cache per request.
- **Eager loading:** `Page::with(['blocks' => fn($q) => $q->orderBy('sort_order'), 'blocks.children'])` — never omit this.
- **Builder open:** ~5 queries (page + blocks + children + auth + settings).
- **save-tree:** N+3 queries (snapshot + delete + N inserts in a transaction) — by design.
- **Public page render:** 3–8 queries, no N+1 (verified in C2 audit).
- `BuilderPatternController::index()` loads all patterns unpaginated — fine at current scale (<100 patterns), add pagination if volume grows (Phase 6 item).

---

## 12. File Map

```
config/
  blocks.php                                    ← block registry

app/Http/Controllers/Admin/
  PageBuilderController.php                     ← builder load + save-tree
  BuilderPatternController.php                  ← patterns CRUD
  BuilderTemplateController.php                 ← templates CRUD

app/Http/Requests/Admin/
  StoreBuilderPatternRequest.php
  StoreBuilderTemplateRequest.php

app/Models/
  BuilderPattern.php
  BuilderTemplate.php
  PageBlock.php                                 ← has parent_block_id, children()

app/Services/
  BuilderTreeSanitizer.php                      ← shared tree validator
  BuilderPatternService.php
  BuilderTemplateService.php
  PageBlockService.php                          ← per-block data + render
  PageService.php

app/Support/
  InlineContentSanitizer.php                    ← richtext / plaintext
  BlockStyle.php                                ← CSS / class / advanced
  PageRenderData.php                            ← data assembler for public render

resources/views/layouts/
  builder.blade.php                             ← builder layout shell

resources/views/backend/builder/
  index.blade.php                               ← wrapper, 3-panel grid
  partials/
    alpine-component.blade.php                  ← Alpine store (all state + logic)
    topbar.blade.php                            ← back / title / device / save
    panel-left.blade.php                        ← insert / patterns / block list
    canvas.blade.php                            ← iframe + overlays + status bar
    panel-right.blade.php                       ← schema-driven settings panel
    builder-field.blade.php                     ← field renderer (all field types)
    template-library.blade.php                  ← template modal

resources/views/frontend/blocks/
  group.blade.php, columns.blade.php, ...       ← 19 public block renders

resources/views/frontend/pages/
  _blocks.blade.php                             ← block tree renderer (loops root blocks)

tests/Feature/Admin/
  BuilderPatternTest.php
  BuilderTemplateTest.php
  PageBlockManagementTest.php
  PageManagementTest.php
```

---

## 13. Architecture Constraints

These decisions were made in B0 and are **preserved** — do not change without owner approval.

1. `page_templates` = frontend layout-shell allowlist only. Not for reusable designs.
2. Reusable builder designs live in `builder_templates` (block forests + metadata).
3. Persisted page structure = `pages` + `page_blocks` with `parent_block_id` nesting.
4. `BuilderTreeSanitizer` = shared server contract for all tree-persisting paths. Never fork.
5. Applying a layout shell must **preserve blocks**. Applying a reusable template may **replace** the tree only after user confirmation.
6. Fixed `20:60:20` layout and CSS-only device sizing. No JS scaling, no ResizeObserver.
7. Public rendering driven by existing frontend page render and theme hierarchy.

---

## 14. Known Technical Debt

| # | Item | Deferred to |
|---|------|-------------|
| TD-04 | Widget text not sanitized (`widgets/text.blade.php`) — admin-only input, no user-facing XSS | Phase 6 |
| TD-05 | `FormDefinition::find()` query in `contact-form.blade.php:3` — move to `PageRenderData` | Phase 6 |
| — | `BuilderPatternController::index()` unpaginated — fine at current scale | Phase 6 |
