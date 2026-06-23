# Menu Manager Skill

## Main Goal
Build the Menu Manager: admin controls all navigation (header, footer, mobile)
from the dashboard. No hardcoded nav items in Blade. Frontend reads from `menus` + `menu_items` tables.

## Required References
- `AGENTS.md` — master rules
- `ai/skills/backend-skill.md` — Laravel MVC patterns
- `ai/skills/frontend-design-skill.md` — frontend brand and rendering rules
- `ai/skills/database-architecture-skill.md` — schema rules
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — admin UX rules

---

## Database Schema

### Table: `menus`
```
id          bigint unsigned PK
name        varchar(255)          -- admin label, e.g. "Header Navigation"
location    varchar(50) unique    -- header|footer|mobile
is_active   tinyint(1) default 1
created_at  timestamp
updated_at  timestamp
```

### Table: `menu_items`
```
id              bigint unsigned PK
menu_id         bigint unsigned FK → menus.id ON DELETE CASCADE
parent_id       bigint unsigned nullable FK → menu_items.id ON DELETE SET NULL
label           varchar(255)
link_type       enum('page','product','category','destination','url','anchor')
link_target     varchar(255)     -- slug, ID, URL, or #anchor depending on link_type
open_new_tab    tinyint(1) default 0
sort_order      int unsigned default 0
is_visible      tinyint(1) default 1
created_at      timestamp
updated_at      timestamp
```

### Indexes
- UNIQUE on `menus.location`
- INDEX on `menu_items.menu_id`
- INDEX on `menu_items.parent_id`
- INDEX on `menu_items.sort_order`

---

## Menu Locations

Three fixed locations (seeded, not created by admin):
| location  | name               | Used in |
|-----------|--------------------|---------|
| `header`  | Header Navigation  | `frontend/partials/header.blade.php` |
| `footer`  | Footer Navigation  | `frontend/partials/footer.blade.php` |
| `mobile`  | Mobile Navigation  | `frontend/partials/header.blade.php` (mobile drawer) |

Seed these three menus on first run. Admin can edit items, but cannot delete these menus.

---

## Link Types

| link_type     | link_target value         | Resolved URL |
|---------------|---------------------------|--------------|
| `page`        | slug string               | `route('pages.show', slug)` |
| `product`     | slug string               | `route('products.show', slug)` |
| `category`    | category slug             | `route('products.index', ['category' => slug])` |
| `destination` | destination slug          | `route('products.index', ['destination' => slug])` |
| `url`         | full URL string           | used as-is |
| `anchor`      | #section-id string        | used as-is |

URL resolution happens in the view or a helper — never in the Model.

---

## Model: `app/Models/Menu.php`

```php
protected $fillable = ['name', 'location', 'is_active'];

protected $casts = ['is_active' => 'boolean'];

// Relationships
public function items(): HasMany     // → MenuItem, sorted by sort_order ASC

// Scopes
public function scopeActive($q)      // is_active = 1
public function scopeAtLocation($q, string $location)

// Static helper
public static function forLocation(string $location): ?self
```

## Model: `app/Models/MenuItem.php`

```php
protected $fillable = [
    'menu_id', 'parent_id', 'label', 'link_type',
    'link_target', 'open_new_tab', 'sort_order', 'is_visible',
];

protected $casts = [
    'open_new_tab' => 'boolean',
    'is_visible'   => 'boolean',
    'sort_order'   => 'integer',
];

// Relationships
public function menu(): BelongsTo
public function parent(): BelongsTo     // → MenuItem
public function children(): HasMany     // → MenuItem (parent_id = this->id, ordered)

// Helpers
public function resolveUrl(): string    // resolves link_type + link_target to final URL
public function hasChildren(): bool
```

---

## Admin Controller: `app/Http/Controllers/Admin/MenuController.php`

```
index()                              // list all 3 menus
edit(Menu $menu)                     // edit items for one menu
update(Request $request, Menu $menu) // save menu name + is_active
```

## Admin Controller: `app/Http/Controllers/Admin/MenuItemController.php`

```
store(Request $request, Menu $menu)
update(Request $request, Menu $menu, MenuItem $item)
destroy(Menu $menu, MenuItem $item)
reorder(Request $request, Menu $menu)       // bulk sort_order from array
toggleVisible(Menu $menu, MenuItem $item)
```

---

## Service: `app/Services/MenuService.php`

```php
public function getStructuredItems(Menu $menu): Collection  // nested parent → children
public function reorder(Menu $menu, array $ids): void
public function resolveUrl(MenuItem $item): string          // link_type → final URL
public function getNavForLocation(string $location): ?Collection  // cached
```

### Caching
Menu data changes rarely. Cache per location:
```php
Cache::remember("menu:{$location}", 3600, fn() => ...);
Cache::forget("menu:{$location}");  // on any menu item change
```

---

## Admin Blade Views

### `resources/views/backend/menus/index.blade.php`
- Cards for each of 3 menu locations
- Each card: menu name, item count, active status, "Edit Items" button
- Admin cannot delete menu cards (locations are fixed)

### `resources/views/backend/menus/edit.blade.php`
Shows items for one menu in a flat list (no drag-and-drop):

```
[ + Add Menu Item ] button → opens inline form / modal

For each item (ordered by sort_order):
┌────────────────────────────────────────────────────┐
│  ↑ ↓  [page] "About Us" → /pages/about  [Edit][✕] │
│        └─ [page] "Our Team" → /pages/team [Edit][✕]│  (children indented)
└────────────────────────────────────────────────────┘
```

- Parent items shown flat with children indented below them (max 1 level deep for Phase 2)
- `link_type` select triggers label change for `link_target` field (use Alpine.js)
- Sort with Up/Down buttons (no drag-and-drop until Phase 3)

### `resources/views/backend/menus/partials/item-form.blade.php`
Fields:
- label (text)
- link_type (select: page, product, category, destination, url, anchor)
- link_target (text, label changes based on link_type: "Page slug", "URL", etc.)
- parent_id (select: existing items in this menu, or "None" for top-level)
- open_new_tab (checkbox)
- is_visible (toggle)

---

## Routes

In `routes/admin.php`, inside existing auth+admin middleware group:
```php
Route::resource('menus', MenuController::class)->only(['index', 'edit', 'update']);

Route::prefix('menus/{menu}/items')->name('admin.menu-items.')->group(function () {
    Route::post('/', [MenuItemController::class, 'store'])->name('store');
    Route::put('{item}', [MenuItemController::class, 'update'])->name('update');
    Route::delete('{item}', [MenuItemController::class, 'destroy'])->name('destroy');
    Route::post('reorder', [MenuItemController::class, 'reorder'])->name('reorder');
    Route::post('{item}/toggle-visible', [MenuItemController::class, 'toggleVisible'])->name('toggle-visible');
});
```

---

## Frontend Integration

### Update `resources/views/frontend/partials/header.blade.php`

Replace hardcoded nav items with:
```blade
@php
    $headerMenu = app(\App\Services\MenuService::class)->getNavForLocation('header');
    $mobileMenu = app(\App\Services\MenuService::class)->getNavForLocation('mobile');
@endphp

@foreach($headerMenu as $item)
    @if($item->is_visible)
        @if($item->hasChildren())
            {{-- Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open">{{ $item->label }}</button>
                <div x-show="open">
                    @foreach($item->children as $child)
                        @if($child->is_visible)
                            <a href="{{ $child->resolveUrl() }}"
                               @if($child->open_new_tab) target="_blank" rel="noopener" @endif>
                                {{ $child->label }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ $item->resolveUrl() }}"
               @if($item->open_new_tab) target="_blank" rel="noopener" @endif>
                {{ $item->label }}
            </a>
        @endif
    @endif
@endforeach
```

### Update `resources/views/frontend/partials/footer.blade.php`

Same pattern with `getNavForLocation('footer')`.

### Important
- Do NOT remove existing Blade until new menu data is confirmed seeded and working
- Work section by section, test in local before removing old nav
- Keep existing CSS classes intact — only replace the PHP/Blade data source

---

## Seeder: `database/seeders/MenuSeeder.php`

```php
Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header Navigation', 'is_active' => true]);
Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer Navigation', 'is_active' => true]);
Menu::firstOrCreate(['location' => 'mobile'], ['name' => 'Mobile Navigation', 'is_active' => true]);
```

Run via `DatabaseSeeder.php`. Use `firstOrCreate` — safe to run multiple times.

---

## Security Checklist
- All admin routes behind `auth` + `admin` middleware
- `link_target` validated: URL type must be valid URL (`url` rule), slug types must be strings
- `open_new_tab` links must include `rel="noopener noreferrer"` in frontend
- Admin cannot delete the 3 fixed menu records (validate in controller: `abort_if(in_array($menu->location, ['header','footer','mobile']) && $request->isMethod('DELETE'), 403)`)
- Menu cache cleared on every item change

---

## Module Pattern Checklist (from AGENTS.md §6)
- [ ] Migrations: menus + menu_items
- [ ] Models: Menu + MenuItem (with resolveUrl helper)
- [ ] MenuController (index, edit, update)
- [ ] MenuItemController (store, update, destroy, reorder, toggleVisible)
- [ ] MenuService (getStructuredItems, reorder, resolveUrl, getNavForLocation with cache)
- [ ] Admin Blade: menus/index, menus/edit, partials/item-form
- [ ] MenuSeeder for 3 fixed locations
- [ ] Frontend: header.blade.php + footer.blade.php updated
- [ ] Sidebar updated
- [ ] Cache invalidation on change
- [ ] Security checks
- [ ] Tests

---

## Forbidden
- Deleting the 3 fixed menu locations from admin
- Hardcoding nav items back into Blade after this module is live
- Nesting menu items more than 1 level deep in Phase 2
- Querying menus directly in Blade — use MenuService
- **Editing/creating/ordering menu links anywhere other than Menu Manager.** Global Assets →
  Header/Footer is **appearance-only** (CTA, sticky, colors, footer logo/toggles, footer layout
  blocks). Its controller must NOT validate or write the link keys (`navigation.header.items`,
  `footer.menu.quick_links`, `footer.menu.utility_links`).

---

## Implemented UI Pattern (the standard for this project)

This module is the reference build. Real schema/behavior as shipped (overrides the simplified
examples above where they differ):

- **Locations:** `header`, `footer_quick`, `footer_utility` (the footer has two link lists). The
  header menu also drives the mobile drawer — no separate `mobile` menu.
- **menu_items columns:** `link_type` + polymorphic `nullableMorphs('linkable')` + `url` + `target`
  (`_self|_blank`) + `is_active` + `sort_order` + self `parent_id` (cascade). Category/destination
  resolve by **ID** to the product-listing filter (`/products?category[]=` / `?destination[]=`),
  NOT by slug — the frontend `ProductController` filters by id.
- **`MenuItem::resolveUrl()`** returns **relative** internal URLs (`route(..., absolute: false)`) so
  active-link highlighting keeps working.
- **`MenuService::tree($location)`** returns the exact array shape the header/footer already consume
  (`['label','url','is_external','children'=>[...]]`), cached; shared to views via the
  `AppServiceProvider` composer (`headerMenu`, `footerQuickLinks`, `footerUtilityLinks`) with a
  legacy-settings fallback.

### Drag-and-drop (now allowed, replaces Phase-3 deferral)
- Use **`@alpinejs/sort`** (registered in `resources/js/app.js` via `Alpine.plugin(sort)`).
- Container: `x-sort="persistOrder($el)" x-sort:config="{ handle: '[data-handle]', animation: 150 }"`;
  each row: `x-sort:item="{id}" data-id="{id}"` with a `[data-handle]` grip.
- On drop, collect ordered `data-id`s and `fetch()` POST to the reorder route with `{ids}` + CSRF;
  the controller returns JSON when `$request->expectsJson()`. Root list and each children list are
  independent sort groups.

---

## Admin CRUD Module UI Pattern (reusable for other modules)

Use this as the standard "builder" layout for future admin screens (pages, sections, etc.):

1. **Index = cards**, one per entity: icon, status pill, count, a few preview chips, and a clear
   `Manage →` affordance (whole card is the link).
2. **Editor = two columns** on `lg`: left is the editable list, right is a **sticky live preview**
   that mirrors the real frontend rendering.
3. **List = drag-sortable** (`@alpinejs/sort`, handle-only) with compact rows: type icon, title,
   resolved target, and right-aligned icon actions (edit / toggle / delete).
4. **Add & edit = one shared slide-over drawer** (right side, `x-transition` translate), driven by an
   `Alpine.data()` component registered on `alpine:init` and `@push('scripts')`. The drawer binds the
   form `action`/`_method` reactively so a single form handles both store and update.
5. **Feedback** via a small auto-dismiss toast for AJAX actions; full reload for create/edit/delete.
6. Keep Blade query-free (prepare data in the controller/service); never reorder by full-page form
   posts when a drag interaction is available.
