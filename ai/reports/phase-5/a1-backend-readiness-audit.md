# A1 — Backend Readiness Audit for Visual Page Builder

## Date: 2026-06-21
## Branch: feature/phase-5-stage-a-foundation
## HEAD: becab233169161a4331ed4ccbcefe14e6cfec859

---

## 1. Block Storage Model

### Current structure

**Table:** `page_blocks`

**Columns (confirmed via `Schema::getColumnListing()`):**
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint unsigned | PK |
| `page_id` | bigint unsigned | FK → pages.id (cascadeOnDelete) |
| `block_type` | varchar(50) | e.g. `hero`, `text`, `gallery` |
| `label` | varchar(255) nullable | Admin display name |
| `data` | JSON nullable | All block-specific fields |
| `sort_order` | unsigned int | Position in page block list |
| `is_visible` | boolean | Toggle without delete |
| `created_at` / `updated_at` | timestamp | Eloquent timestamps |

**Data format:** Block attributes stored as **JSON in `data` column**. Shared `background` object present in every block type. Per-block specific fields inside same JSON.

**Sample record (live data):**
```json
{
  "id": 1,
  "page_id": 1,
  "block_type": "hero",
  "label": "Hero",
  "data": {
    "image": "media/2026/06/5f670892-35ae-4d53-8e36-9cd5a4e11fe7.webp",
    "title": "Explore Lagoy Bay Activity",
    "cta_url": null,
    "cta_text": null,
    "subtitle": null,
    "background": {
      "size": "cover", "color": null, "image": null,
      "repeat": "no-repeat", "opacity": "100", "position": "center"
    },
    "min_height": "large",
    "has_overlay": "1",
    "overlay_opacity": "40",
    "background_color": "#0f0f0f"
  },
  "sort_order": 0,
  "is_visible": true
}
```

**Related tables in DB:**
- `pages` — parent page with status/slug/template
- `page_blocks` — block list (this table)
- `page_revisions` — snapshots of full block tree
- `page_sections` — legacy hardcoded sections (separate system, coexists)
- `page_section_media` — legacy media per section
- `page_templates` — template definitions
- `page_views` / `page_view_daily_stats` — analytics

### Nesting support

**FLAT — confirmed via `Schema::getColumnListing('page_blocks')`.**

No `parent_id`, `parent_block_id`, `depth`, or `path` column exists. Blocks are a flat ordered list per page. No tree/hierarchical structure.

### Gap for visual builder

To support column layouts, groups, and nested containers (needed for Stage B builder), a `parent_block_id` nullable FK column is required. This is a **schema change** and requires owner approval before implementation.

---

## 2. Block Type Registry

### Current registry pattern

Block types are **hardcoded as a PHP constant** in the controller:

```php
// app/Http/Controllers/Admin/PageBlockController.php, lines 13–17
public const BLOCK_TYPES = [
    'hero', 'text', 'image', 'gallery', 'cta',
    'products_grid', 'faq', 'testimonials', 'map', 'divider',
    'contact_form',
];
```

No `config/blocks.php` or `config/page-builder.php` exists. Default field schemas and validation rules are in `app/Services/PageBlockService.php` (methods `defaultDataFor()` and `rulesFor()`).

### Block types found — 11 types

| Block Type | Registry | Admin Form | Frontend Render | Key Fields | Background Support |
|------------|----------|------------|-----------------|------------|--------------------|
| `hero` | `PageBlockController::BLOCK_TYPES` | `backend/pages/partials/blocks/hero.blade.php` | `frontend/blocks/hero.blade.php` | title, subtitle, image, cta_text, cta_url, overlay_opacity, has_overlay, min_height | YES (shared bg object) |
| `text` | same | `…/blocks/text.blade.php` | `frontend/blocks/text.blade.php` | heading, body_html (rich, sanitized) | YES |
| `image` | same | `…/blocks/image.blade.php` | `frontend/blocks/image.blade.php` | src, alt, caption, width_class (full/half/third) | YES |
| `gallery` | same | `…/blocks/gallery.blade.php` | `frontend/blocks/gallery.blade.php` | images[], columns(1–4), gap, aspect_ratio, lightbox_enabled, autoplay | YES |
| `cta` | same | `…/blocks/cta.blade.php` | `frontend/blocks/cta.blade.php` | title, description, button_text, button_url, style(dark/light/gold) | YES |
| `products_grid` | same | `…/blocks/products-grid.blade.php` | `frontend/blocks/products-grid.blade.php` | category_id, destination_id, limit(3–12), show_price | YES |
| `faq` | same | `…/blocks/faq.blade.php` | `frontend/blocks/faq.blade.php` | source(inline/ids), faq_ids[], items[{question,answer}] | YES |
| `testimonials` | same | `…/blocks/testimonials.blade.php` | `frontend/blocks/testimonials.blade.php` | items[{name,text,rating,avatar}] | YES |
| `map` | same | `…/blocks/map.blade.php` | `frontend/blocks/map.blade.php` | embed_url, address, zoom(1–20) | YES |
| `divider` | same | `…/blocks/divider.blade.php` | `frontend/blocks/divider.blade.php` | style(line/space/gold-line) | YES |
| `contact_form` | same | `…/blocks/contact-form.blade.php` | `frontend/blocks/contact-form.blade.php` | form_definition_id, title, description | YES |

**Shared background object** (on every block type):
```
background.color, background.image, background.position,
background.repeat, background.size, background.opacity
```

### Gap for visual builder

The Stage B visual builder JS panel needs to read available block types dynamically to render the "Insert Block" picker. Currently the registry is PHP-only (`const BLOCK_TYPES`). Options:

1. **Expose via JSON endpoint** — add `GET /admin/api/block-types` returning type list with labels and field schemas (safe, no schema change).
2. **Move to `config/blocks.php`** — cleaner, config-driven, lets skill file validate completeness (safe, no schema change, but requires refactor of `PageBlockService`).

For Stage A, option 2 (config extraction) is the recommended prep step. Stage B depends on it.

---

## 3. Draft vs Published

### Current mechanism

`pages.status` enum: `draft | published | scheduled`

- **Draft** — visible only in admin preview (`/admin/pages/{id}/preview`)
- **Scheduled** — auto-promoted to `published` at `publish_at` timestamp via `php artisan pages:publish-scheduled` command (file: `app/Console/Commands/PublishScheduledPages.php`)
- **Published** — accessible on public route `/pages/{slug}`

Frontend `PageController::show()` aborts 404 if page not published. Preview route is admin-only and injects `noindex, nofollow` into page `<meta>`.

Block visibility: individual blocks have `is_visible` flag. Blocks can be hidden per-block without affecting page status. There is **no separate block-level draft system** — block changes apply immediately on save.

### Revision system (Phase 4)

**Model:** `app/Models/PageRevision.php`

```
page_revisions: id, page_id, revision_number, content_snapshot (JSON array),
                meta_snapshot (JSON array), created_by (user FK), created_at
```

- `content_snapshot` = full blocks array: `[{block_type, label, data, sort_order, is_visible}, ...]`
- `meta_snapshot` = page metadata: `{title, slug, status, publish_at, template_id, meta_title, meta_description}`
- **Max 20 revisions per page** — oldest pruned automatically in `PageService::saveRevision()`
- Revisions created automatically before every `update()` call in `PageService`

### Gap for visual builder

The revision system already captures full block tree snapshots and can restore them — this is **READY** for visual builder Stage B. However:

- Block-level draft (staging changes before publish) is not yet supported. Currently saving a block = immediate live change on the page (for published pages). Stage B builder will need a "draft block tree" concept — where the builder holds an unsaved in-memory state that is committed only when the user clicks Save/Publish.
- The existing `PageRevision` model can serve as the "commit history" once the builder saves its tree state via `PageService::update()`.

---

## 4. Rendering Pipeline

### Current flow

```
GET /pages/{slug}
  → Frontend\PageController::show()
  → Page::where('slug', $slug)->firstOrFail()
  → Abort 404 if not published
  → $page->blocks → visible()->ordered() scope
  → Return view 'frontend.pages.show', compact('page')
  → view includes _blocks.blade.php

_blocks.blade.php:
  @forelse($page->blocks as $block)
    @includeIf('frontend.blocks.' . str_replace('_', '-', $block->block_type), [
        'block' => $block,
        'data'  => $block->data ?? [],
    ])
  @empty
    (empty state)
  @endforelse
```

Per-block Blade view receives `$block` (model) and `$data` (the JSON array) as variables.

### Per-block rendering

**No `BlockRenderer` class** — rendering is handled by dynamic `@includeIf` per block type. 11 Blade views in `resources/views/frontend/blocks/`:

```
hero.blade.php, text.blade.php, image.blade.php, gallery.blade.php,
cta.blade.php, products-grid.blade.php, faq.blade.php, testimonials.blade.php,
map.blade.php, divider.blade.php, contact-form.blade.php
```

Each view uses `$data['field']` pattern with fallback (`$data['title'] ?? ''`). CSS variables from Phase 3 theme tokens used in several blocks (`var(--frontend-gold,#D4AF37)`).

### Gap for visual builder

The pipeline is **READY** for Stage B iframe live preview. The existing `admin/pages/{page}/preview` route renders the full page as HTML — the visual builder iframe can point to this route and refresh on block changes. No new render endpoint needed.

One gap: the preview route renders all `page->blocks` (the saved state). For "unsaved" changes during live editing, Stage B will need to send a transient block tree (POST payload) that the preview endpoint renders without saving. This requires adding a preview-with-payload endpoint.

---

## 5. Media Library Integration

### Current media picker

**Model:** `app/Models/Media.php`
- Attributes: `filename`, `original_name`, `mime_type`, `extension`, `size`, `width`, `height`, `path`, `disk`, `alt`, `caption`, `uploaded_by`
- `getUrlAttribute()` → returns `asset('storage/' . $this->path)`
- `isImage()` → checks `mime_type starts with 'image/'`

**Controller payload** (`MediaController::payload()`):
```php
[
    'id'       => $media->id,
    'path'     => $media->path,
    'url'      => $media->url,
    'filename' => $media->filename,
    'alt'      => $media->alt,
    'caption'  => $media->caption,
]
```

**Picker mechanism:**
1. Admin block triggers `open-media-picker` Alpine event
2. Modal opens with `resources/views/backend/media/picker.blade.php` inside an `<iframe>`
3. User selects media in the picker page
4. Picker fires `window.parent.postMessage({type: 'media-selected', media: {...}}, '*')`
5. Block Alpine component listens to `media-picker-selected.window` event and updates form fields

**Currently integrated in blocks:** `image.blade.php` (`imageUploader()` component), `gallery.blade.php` (`galleryBlock()` component), `hero.blade.php`, `background.blade.php` (shared partial).

### Gap for visual builder

**READY** — the media picker already uses postMessage and can be called from any Alpine.js component including a future visual builder canvas. The JSON payload `{id, url, alt, caption}` is exactly what a block attribute editor needs. No changes required for Stage A.

---

## 6. Admin Block Editor Current State

### Current editing UX

**Main view:** `resources/views/backend/pages/partials/block-editor.blade.php`

- **Add block:** HTML `<select>` dropdown listing all 11 BLOCK_TYPES → form POST to `admin/pages/{page}/blocks` (store)
- **Block list:** Accordion UI — each block shows type label, preview image (extracted from `data.image` or `data.src`), visibility badge
- **Edit panel:** Expandable accordion section per block with type-specific form partial
- **Reorder:** Up (▲) and Down (▼) form buttons — submits reorder request; **no drag-and-drop**
- **Delete:** Form-based `DELETE` with confirmation modal
- **Toggle visibility:** Form POST to `toggle-visible`

**Technology:** Pure Alpine.js (`x-data`, `x-show`, `@click`) for accordion toggling and image preview. No external JS libraries. No jQuery.

### Current JS/Alpine architecture

Alpine components used in block editor:
- `imageUploader()` — handles single image pick/upload (used in hero, image, background partials)
- `galleryBlock()` — handles multi-image gallery pick/upload/reorder
- Standard Alpine `x-data="{open: false}"` for accordion open/close state per block

Routes:
```
POST   admin/pages/{page}/blocks              → store (create block)
PUT    admin/pages/{page}/blocks/{block}      → update (save block data)
DELETE admin/pages/{page}/blocks/{block}      → destroy
POST   admin/pages/{page}/blocks/reorder      → reorder (submit full ID list)
POST   admin/pages/{page}/blocks/{block}/toggle-visible
```

### Gap for visual builder

| Gap | Severity | Stage |
|-----|----------|-------|
| No drag-and-drop reordering (up/down only) | HIGH | B |
| No inline editing (editing opens form panel, not on-canvas) | HIGH | B |
| No live preview (preview opens separate tab) | HIGH | B |
| No block insertion panel / block library sidebar | HIGH | B |
| Block type list not JS-accessible (PHP constant only) | MED | A |
| No "columns" or "group" block for layout | MED | A-B |
| No reusable patterns / global blocks | LOW | B |

For Stage A (foundation hardening), the current form-based editor is kept as-is. Stage B replaces/extends the canvas area with the drag-drop builder while keeping form-based fallback.

---

## 7. Summary — Readiness Assessment

| Area | Status | Gap | Severity |
|------|--------|-----|----------|
| Block storage | NEEDS WORK | Flat list — no `parent_id` for nesting/columns | HIGH |
| Block registry | NEEDS WORK | Hardcoded PHP constant, not JS-accessible; no config file | MED |
| Draft/publish | READY | Status enum, preview route, scheduling all functional | — |
| Rendering pipeline | READY | Dynamic `@includeIf` reusable for iframe preview | — |
| Media picker | READY | postMessage pattern fully integrated with Alpine.js | — |
| Nesting support | NEEDS WORK | Requires `parent_block_id` schema change (owner approval) | HIGH |
| Editor UX | NEEDS WORK | Form-based, no drag-drop, no inline edit, no live preview | HIGH |

---

## 8. Ranked Backend Changes Needed Before Stage B

| Priority | Change | Type | Files | Risk |
|----------|--------|------|-------|------|
| 1 | Extract block types to `config/blocks.php` with labels + field schemas | **safe** | `config/blocks.php` (new), `PageBlockController.php`, `PageBlockService.php` | LOW |
| 2 | Add `GET /admin/api/block-types` JSON endpoint for JS block picker | **safe** | `PageBlockController.php`, `routes/admin.php` | LOW |
| 3 | Add `parent_block_id` nullable FK to `page_blocks` (nesting support) | **schema-change — needs approval** | new migration, `PageBlock.php`, `PageBlockService.php` | MED |
| 4 | Add `GET /admin/pages/{page}/preview-payload` endpoint (preview unsaved tree) | **safe** | `PageController.php` or new `PagePreviewController.php`, `routes/admin.php` | LOW |
| 5 | Standardize block labels + icons for block picker UI metadata | **safe** | `config/blocks.php` (tied to priority 1) | LOW |
| 6 | Add "columns" and "group" block types (layout containers for nesting) | **needs-approval** (new block types) | `PageBlockController.php`, `PageBlockService.php`, new Blade views | MED |

---

## 9. Recommendation

### Changes to do in Stage A (before visual builder)

1. **Extract block registry to `config/blocks.php`** — define each type with label, icon, default data. Refactor `PageBlockController::BLOCK_TYPES` and `PageBlockService::defaultDataFor()` to read from config. Makes block list JS-serializable.
2. **Add JSON endpoint for block type list** — `GET /admin/api/block-types` returns `[{type, label, icon, fields[]}]`. Required by Stage B JS inserter panel.
3. **Add preview-with-payload endpoint** — `POST /admin/pages/{page}/preview-payload` accepts a transient block tree JSON and renders the preview without saving. Required for Stage B live preview.
4. **Audit and expand block library** (Stage A3 task) — review existing 11 blocks for frontend quality and add layout blocks (columns, group) pending owner approval.

### Changes that can wait until Stage B

- Drag-and-drop reordering (SortableJS integration — requires owner approval for new package)
- Inline text editing (`contenteditable` approach)
- Reusable patterns / global block library
- Block instance duplication
- Responsive preview panel (mobile/tablet/desktop toggle)

### Changes that need owner approval (schema/package)

- `parent_block_id` column on `page_blocks` (schema change)
- Adding "columns" and "group" block types (new block types + Blade views = low risk, but clarify scope)
- SortableJS or similar DnD library (NPM package install)

---

## 10. Next Step

Stage A1 audit complete. The backend is partially ready for Phase 5 Visual Builder:
- Revision system, draft/publish, media picker, and rendering pipeline are solid foundations.
- Block storage (flat-only), block registry (PHP-only), and editor UX (form-based) need targeted improvements before Stage B.

**Owner approval needed before implementing any of the ranked changes above.**

Next task: Owner reviews this report → approves/modifies → proceed to **A2 (Admin UX Refactor audit)** or directly to highest-priority backend changes from Section 8.
