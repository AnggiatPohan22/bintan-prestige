# Page Builder Skill

## Main Goal
Build the Block Editor: flexible content blocks per page, managed from admin.
Each page has zero or more blocks. Each block has a type, data (JSON), and sort order.
Admin edits blocks. Frontend renders the correct Blade partial per block type.

## Required References
- `AGENTS.md` — master rules
- `ai/skills/page-module-skill.md` — pages are the parent of blocks
- `ai/skills/cms-architect-skill.md` — block type schema definitions
- `ai/skills/backend-skill.md` — Laravel MVC patterns
- `ai/skills/frontend-skill.md` — frontend rendering and brand rules
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — admin UX rules

---

## Database Schema

### Table: `page_blocks`
```
id              bigint unsigned PK
page_id         bigint unsigned FK → pages.id ON DELETE CASCADE
block_type      varchar(50)          -- hero|text|image|gallery|cta|products_grid|faq|testimonials|map|divider
label           varchar(255) nullable -- admin display name (e.g. "Hero section")
data            json nullable        -- block content, schema varies by block_type
sort_order      int unsigned default 0
is_visible      tinyint(1) default 1
created_at      timestamp
updated_at      timestamp
```

### Indexes
- INDEX on `page_id`
- INDEX on `sort_order`
- INDEX on `is_visible`

---

## Model: `app/Models/PageBlock.php`

```php
protected $fillable = [
    'page_id', 'block_type', 'label', 'data', 'sort_order', 'is_visible',
];

protected $casts = [
    'data'       => 'array',   // JSON auto-cast to/from PHP array
    'is_visible' => 'boolean',
    'sort_order' => 'integer',
];

// Relationships
public function page(): BelongsTo   // → Page

// Scopes
public function scopeVisible($q)    // is_visible = 1
public function scopeOrdered($q)    // order by sort_order ASC
```

---

## Block Type Schemas

Each `data` JSON field follows a fixed schema per `block_type`.
Always validate incoming data against the schema for the given block_type.

| block_type      | Required data keys                                              |
|-----------------|----------------------------------------------------------------|
| `hero`          | title, subtitle, image, cta_text, cta_url                     |
| `text`          | heading, body_html                                             |
| `image`         | src, alt, caption, width_class (full\|half\|third)            |
| `gallery`       | images[] (each: src, alt), columns (2\|3\|4), caption         |
| `cta`           | title, description, button_text, button_url, style (dark\|light\|gold) |
| `products_grid` | category_id (nullable), destination_id (nullable), limit (int 3–12), show_price (bool) |
| `faq`           | faq_ids[] (references faqs.id) OR items[] (each: question, answer) |
| `testimonials`  | items[] (each: name, text, rating, avatar)                    |
| `map`           | embed_url, address, zoom (int 1–20)                           |
| `divider`       | style (line\|space\|gold-line)                                |

---

## Admin Controller: `app/Http/Controllers/Admin/PageBlockController.php`

All routes are nested under a page. Methods:

```
store(Request $request, Page $page)      // create new block for page
update(Request $request, Page $page, PageBlock $block)  // edit block data
destroy(Page $page, PageBlock $block)    // delete block
reorder(Request $request, Page $page)   // bulk sort_order update from array of IDs
toggleVisible(Page $page, PageBlock $block)  // flip is_visible
```

Rules:
- All actions redirect back to `admin.pages.edit` with flash
- `store` auto-sets sort_order = max(existing) + 1
- `destroy` hard-deletes (no soft delete for blocks)
- `reorder` accepts `['ids' => [3, 1, 2]]` and updates sort_order in loop

---

## Service: `app/Services/PageBlockService.php`

```php
public function nextSortOrder(Page $page): int
public function reorder(Page $page, array $ids): void
public function validateBlockData(string $blockType, array $data): array   // returns validated data
public function defaultDataFor(string $blockType): array                   // empty schema for new block
```

---

## Admin Blade: Block Editor Inside Page Edit

Location: `resources/views/backend/pages/partials/block-editor.blade.php`

Included at bottom of `backend/pages/edit.blade.php`:
```blade
@include('backend.pages.partials.block-editor', ['page' => $page, 'blocks' => $page->blocks])
```

UI structure:
```
[ + Add Block ] dropdown with all block types

For each block (ordered by sort_order):
┌─────────────────────────────────────────────────┐
│  ↑ ↓  [hero] "Hero section"    [Edit] [Hide] [✕]│
│  (collapsed by default, expand on Edit click)    │
│  ... block form fields when expanded ...         │
└─────────────────────────────────────────────────┘
```

- Use Alpine.js `x-data` / `x-show` for expand/collapse (already in project stack)
- Up/Down buttons POST to `admin.page-blocks.reorder` with simple IDs swap
- No drag-and-drop (Phase 3 only)
- "Hide" toggles `is_visible` via AJAX or form POST
- Each block shows its `label` (editable) and `block_type` badge

---

## Block Form Partials (Admin)

Location: `resources/views/backend/pages/partials/blocks/`

One partial per block type:
```
blocks/hero.blade.php
blocks/text.blade.php
blocks/image.blade.php
blocks/gallery.blade.php
blocks/cta.blade.php
blocks/products-grid.blade.php
blocks/faq.blade.php
blocks/testimonials.blade.php
blocks/map.blade.php
blocks/divider.blade.php
```

Each partial receives `$block` (PageBlock model) and renders fields for `data[*]`.
Field names use `data[key]` format so controller receives `$request->input('data')` as array.

Example for `hero`:
```blade
<input name="data[title]" value="{{ $block->data['title'] ?? '' }}">
<input name="data[subtitle]" value="{{ $block->data['subtitle'] ?? '' }}">
<input name="data[image]" value="{{ $block->data['image'] ?? '' }}">
<input name="data[cta_text]" value="{{ $block->data['cta_text'] ?? '' }}">
<input name="data[cta_url]" value="{{ $block->data['cta_url'] ?? '' }}">
```

---

## Frontend Block Partials

Location: `resources/views/frontend/blocks/`

One partial per block type. Rendered in `frontend/pages/show.blade.php`:
```blade
@foreach($page->blocks as $block)
    @if($block->is_visible)
        @include('frontend.blocks.' . $block->block_type, ['block' => $block, 'data' => $block->data])
    @endif
@endforeach
```

Frontend partials:
```
frontend/blocks/hero.blade.php
frontend/blocks/text.blade.php
frontend/blocks/image.blade.php
frontend/blocks/gallery.blade.php
frontend/blocks/cta.blade.php
frontend/blocks/products-grid.blade.php
frontend/blocks/faq.blade.php
frontend/blocks/testimonials.blade.php
frontend/blocks/map.blade.php
frontend/blocks/divider.blade.php
```

Rules per frontend block partial:
- Use `$data['key'] ?? ''` — never assume a key exists in JSON
- Use brand CSS classes from `frontend-skill.md` (black/gold/white luxury theme)
- `products_grid` block queries `Product` model with `category_id`/`destination_id` filters
- `faq` block resolves `faq_ids[]` via `Faq::whereIn('id', $data['faq_ids'])->get()`
- Empty/invalid blocks render nothing (no errors, no empty divs)

---

## Routes

Nested under pages in `routes/admin.php`:
```php
Route::prefix('pages/{page}/blocks')->name('admin.page-blocks.')->group(function () {
    Route::post('/', [PageBlockController::class, 'store'])->name('store');
    Route::put('{block}', [PageBlockController::class, 'update'])->name('update');
    Route::delete('{block}', [PageBlockController::class, 'destroy'])->name('destroy');
    Route::post('reorder', [PageBlockController::class, 'reorder'])->name('reorder');
    Route::post('{block}/toggle-visible', [PageBlockController::class, 'toggleVisible'])->name('toggle-visible');
});
```

---

## Security Checklist
- All admin routes behind `auth` + `admin` middleware (inherited from parent group)
- `data` JSON validated per block_type before storing — never store raw unvalidated JSON
- Image paths in block data must be validated URLs or storage paths (not user-injectable HTML)
- `body_html` in `text` block must be sanitized — strip disallowed tags (use `strip_tags` with allowed list or a sanitizer)
- Frontend only renders `is_visible = true` blocks
- Frontend only shows blocks of `published` pages

---

## Module Pattern Checklist (from AGENTS.md §6)
- [ ] Migration: page_blocks table
- [ ] Model: PageBlock (casts data as array)
- [ ] PageBlockController (store, update, destroy, reorder, toggleVisible)
- [ ] PageBlockService (nextSortOrder, reorder, validateBlockData, defaultDataFor)
- [ ] Admin partials: block-editor.blade.php + 10 block form partials
- [ ] Frontend partials: 10 block render partials
- [ ] Routes nested under pages
- [ ] Frontend page show.blade.php loops blocks
- [ ] Security: JSON validation + HTML sanitize for text block
- [ ] Tests: block CRUD, reorder, visibility toggle, frontend render

---

## Forbidden
- Drag-and-drop reordering (Phase 3 only — not now)
- Storing raw HTML in `data` without sanitization
- DB queries inside frontend block Blade partials (prepare data in controller)
- Using `page_sections` table for page blocks — they are separate systems
- Hardcoding block order or block content in Blade
