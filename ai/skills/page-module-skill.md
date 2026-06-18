# Page Module Skill

## Main Goal
Build the Generic Pages module: slug-based pages (About, Contact, Privacy, etc.)
managed entirely from the admin dashboard. No hardcoded page content in Blade.

## Required References
- `AGENTS.md` — master rules and authority order
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — admin CMS rules
- `ai/skills/backend-skill.md` — Laravel MVC patterns
- `ai/skills/database-architecture-skill.md` — schema and migration rules
- `ai/skills/page-builder-skill.md` — block editor (pages use blocks)
- `ai/skills/cms-architect-skill.md` — overall CMS architecture

---

## Database Schema

### Table: `pages`
```
id                  bigint unsigned PK
title               varchar(255)
slug                varchar(255) unique
template_id         bigint unsigned nullable FK → page_templates.id
status              enum('draft','published') default 'draft'
meta_title          varchar(255) nullable
meta_description    text nullable
og_image            varchar(255) nullable
sort_order          int unsigned default 0
created_at          timestamp
updated_at          timestamp
```

### Indexes
- UNIQUE on `slug`
- INDEX on `status`
- INDEX on `sort_order`

### Foreign Keys
- `template_id` → `page_templates.id` ON DELETE SET NULL

---

## Model: `app/Models/Page.php`

```php
protected $fillable = [
    'title', 'slug', 'template_id', 'status',
    'meta_title', 'meta_description', 'og_image', 'sort_order',
];

protected $casts = [
    'status' => 'string',
    'sort_order' => 'integer',
];

// Relationships
public function template(): BelongsTo    // → PageTemplate
public function blocks(): HasMany        // → PageBlock (sort_order ASC)

// Scopes
public function scopePublished($q)       // status = published
public function scopeOrdered($q)         // order by sort_order ASC

// Helpers
public function isPublished(): bool
public function getUrlAttribute(): string  // route('pages.show', $this->slug)
```

---

## Slug Rules
- Auto-generate from `title` using `Str::slug()` on create
- Slug is editable but must stay unique
- Reserved slugs that must NOT be used: `admin`, `products`, `api`, `login`, `register`
- Validate reserved slugs in `StorePageRequest` and `UpdatePageRequest`

---

## Form Request: `app/Http/Requests/Admin/StorePageRequest.php`
```php
'title'            => ['required', 'string', 'max:255'],
'slug'             => ['nullable', 'string', 'max:255', 'unique:pages,slug', Rule::notIn(['admin','products','api','login','register'])],
'template_id'      => ['nullable', 'integer', 'exists:page_templates,id'],
'status'           => ['required', 'in:draft,published'],
'meta_title'       => ['nullable', 'string', 'max:255'],
'meta_description' => ['nullable', 'string', 'max:500'],
'og_image'         => ['nullable', 'image', 'max:2048'],
'sort_order'       => ['nullable', 'integer', 'min:0'],
```

## Form Request: `app/Http/Requests/Admin/UpdatePageRequest.php`
```php
// Same as Store but slug unique rule ignores current page:
'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages','slug')->ignore($this->page), ...]
```

---

## Admin Controller: `app/Http/Controllers/Admin/PageController.php`

Methods: `index`, `create`, `store`, `edit`, `update`, `destroy`

Rules:
- Middleware: `auth` + `admin` (same as all other admin controllers)
- `store`: auto-generate slug if empty, upload og_image via `ImageOptimizationService`
- `update`: re-generate slug only if title changed AND slug was auto-generated; never override manual slug
- `destroy`: soft-delete if possible; hard-delete only if no blocks attached
- Return redirect with flash message on store/update/destroy

---

## Service: `app/Services/PageService.php`

```php
public function generateSlug(string $title, ?int $ignoreId = null): string
public function storeOgImage(UploadedFile $file): string   // uses ImageOptimizationService
public function deleteOgImage(Page $page): void
public function reorder(array $ids): void                  // bulk sort_order update
```

---

## Admin Blade Views

### `resources/views/backend/pages/index.blade.php`
- Table: title, slug, status badge, template name, updated_at, actions
- Status filter dropdown (all / published / draft)
- Search by title
- Pagination
- Empty state with "Create your first page" CTA
- "View on site" link for published pages

### `resources/views/backend/pages/create.blade.php`
Extends `backend/pages/form.blade.php`

### `resources/views/backend/pages/edit.blade.php`
Extends `backend/pages/form.blade.php`
Includes block editor section below the main form (see page-builder-skill.md)

### `resources/views/backend/pages/form.blade.php`
Sections (use accordion or tabs):
1. **Basic** — title, slug (editable, auto-generated), status, sort_order
2. **Template** — template_id selector with preview image
3. **SEO** — meta_title, meta_description, og_image upload

---

## Routes

### Admin (routes/admin.php)
```php
Route::resource('pages', PageController::class)->except(['show']);
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
```
All under existing `Route::middleware(['auth','admin'])->name('admin.')` group.

### Frontend (routes/frontend.php)
```php
Route::get('/pages/{page:slug}', [FrontendPageController::class, 'show'])->name('pages.show');
```
Add this AFTER existing product routes to avoid slug conflicts.

---

## Frontend Controller: `app/Http/Controllers/Frontend/PageController.php`

```php
public function show(Page $page)
{
    // 1. Abort 404 if not published
    abort_if(! $page->isPublished(), 404);

    // 2. Eager load blocks + template
    $page->load(['blocks' => fn($q) => $q->visible()->ordered(), 'template']);

    // 3. Load global settings (already cached via GlobalSettingsService)
    $settings = app(GlobalSettingsService::class)->all();

    // 4. Determine template blade file
    $template = $page->template?->blade_file ?? 'frontend.templates.default';

    return view($template, compact('page', 'settings'));
}
```

---

## Frontend Blade: `resources/views/frontend/pages/show.blade.php`

- Extends `layouts.frontend`
- Uses `@foreach($page->blocks as $block)` to render blocks
- Each block rendered via: `@include('frontend.blocks.' . $block->block_type, ['block' => $block])`
- SEO meta from `$page->meta_title`, `$page->meta_description`, `$page->og_image`
- Falls back to global SEO defaults if page fields are empty

---

## Sidebar Addition

Add "Pages" to `resources/views/backend/partials/sidebar.blade.php`:
```html
<!-- Under Content section or between FAQs and Settings -->
<a href="{{ route('admin.pages.index') }}">Pages</a>
```

---

## SEO Handling

Priority order:
1. `$page->meta_title` → if empty, use `$page->title . ' | ' . $settings['site_name']`
2. `$page->meta_description` → if empty, use `$settings['seo_default_description']`
3. `$page->og_image` → if empty, use `$settings['og_image']`

---

## Security Checklist
- All admin routes behind `auth` + `admin` middleware
- `og_image` upload validated: `image|max:2048`
- Slug sanitized with `Str::slug()`
- Reserved slugs rejected in FormRequest
- Frontend shows only `status = published` pages (abort 404 otherwise)
- No DB queries in Blade

---

## Module Pattern Checklist (from AGENTS.md §6)
- [ ] Migration created and approved
- [ ] Model with relationships, casts, scopes
- [ ] StorePageRequest + UpdatePageRequest
- [ ] Admin PageController (CRUD)
- [ ] PageService (slug, image, reorder)
- [ ] Admin Blade: index, create, edit, form
- [ ] Frontend FrontendPageController
- [ ] Frontend Blade: show (block loop)
- [ ] SEO meta injection
- [ ] Routes added (admin + frontend)
- [ ] Sidebar updated
- [ ] Security check done
- [ ] Tests written (see testing-qa-skill.md)
- [ ] Documentation updated

---

## Forbidden
- Hardcoding page titles or content in Blade
- DB queries inside Blade templates
- Reusing the `page_sections` system for generic pages — they are separate
- Building Phase 3 drag-and-drop (not requested)
- Modifying existing migrations
- Changing existing routes without approval
