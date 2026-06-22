# B1 — Visual Builder Shell & Canvas

**Date:** 2026-06-21
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Commit:** `e83a32a`
**Status:** COMPLETE ✓ — superseded structure by **B-LAYOUT (2026-06-22)**

> **Update (B-LAYOUT, 2026-06-22):** the builder view was refactored. The single
> `backend/builder/index.blade.php` was split into a thin wrapper + 5 partials,
> the 3-panel layout became a `20:60:20` flex grid (full-viewport, `min-h-0`),
> and the preview iframe now uses a CSS `max-width` device frame (no
> `transform:scale`, no JS measurement) with a `sandbox` attribute. See
> `docs/visual-builder-structure.md` and `ai/reports/phase-5/b-layout-canvas-fix.md`.
> Sections below are annotated where they changed.

---

## Deliverable

Fullscreen visual page builder accessible at `GET /admin/pages/{page}/builder`.
Entry point: "Visual Builder" button on the page edit screen.

---

## Files Changed

| File | Type | What changed |
|---|---|---|
| `app/Http/Controllers/Admin/PageBuilderController.php` | New | `show()` + `saveTree()` |
| `resources/views/layouts/builder.blade.php` | New | Standalone fullscreen layout (no sidebar/navbar) |
| `resources/views/backend/builder/index.blade.php` | New | 3-panel builder UI + Alpine `pageBuilder()` — **since B-LAYOUT: thin wrapper only, `@include`s the partials below** |
| `routes/admin.php` | Modified | 2 new routes |
| `resources/views/backend/pages/edit.blade.php` | Modified | "Visual Builder" button in page header |

**Since B-LAYOUT (2026-06-22)** the UI lives in partials:

| File | What |
|---|---|
| `backend/builder/partials/topbar.blade.php` | Top bar (back, status, panel/device toggles, Preview, Save) |
| `backend/builder/partials/panel-left.blade.php` | Inserter + Block-List tree (drag-sort) |
| `backend/builder/partials/canvas.blade.php` | Device iframe (`max-width` frame, `sandbox`) + overlays |
| `backend/builder/partials/panel-right.blade.php` | Block Settings (B3 placeholder) |
| `backend/builder/partials/alpine-component.blade.php` | The `<script>` Alpine `pageBuilder()` store |

---

## Architecture Implemented

### Routes

```
GET  /admin/pages/{page}/builder           → PageBuilderController::show()
POST /admin/pages/{page}/blocks/save-tree  → PageBuilderController::saveTree()
```

Both routes are behind existing `auth` + `admin` middleware.

### Layout: `layouts/builder.blade.php`

Standalone fullscreen layout — no sidebar, no admin navbar.
Loads same Vite bundle (`app.css` + `app.js`) as the rest of the admin,
so Alpine, `@alpinejs/sort`, and all Tailwind utilities are available.
Body: `h-screen overflow-hidden bg-slate-950` (dark shell).

### Controller: `PageBuilderController`

**`show(Page $page)`**
- Loads all blocks (`ordered()`, including hidden ones) via one flat query
- Builds nested JSON tree (`buildBuilderTree()`) using the same parent-grouping
  pattern as `PageController::buildTree()`
- Node shape: `{ id, type, label, data, is_visible, sort_order, children[] }`
- Passes `$tree`, `$registry` (from `config('blocks')`), and `$page` to view

**`saveTree(Request $request, Page $page): JsonResponse`**
- Accepts `POST { blocks: [{ block_type, label, data, is_visible, children[] }] }`
- Validates: max 200 nodes, max 5 nesting levels, `block_type` in registry,
  nesting rules (only `group`/`columns` can have children; `columns` only accepts
  `group` children)
- DB transaction: `saveRevision()` → `delete all blocks` → `insertNodes()` (recursive)
- Returns `{ success: true, tree: [...] }` with fresh IDs from DB

**`buildBuilderTree()`** — private, mirrors `PageController::buildTree()` but returns
plain arrays (not model collections) for JSON serialization.

**`validateNodes()`** — recursive validator; reuses same rules as
`PageController::transientTree()` so server behaviour is consistent between
preview and save.

**`insertNodes()`** — recursive insert: parent inserted first → child inserted with
`parent_block_id` from parent's new DB id. Calls
`PageBlockService::validateAndSanitizeData()` on every node before insert.

### Alpine Component: `pageBuilder(cfg)`

Registered via `document.addEventListener('alpine:init', ...)` in a `@push('scripts')`
block — fires before `Alpine.start()` so the component is available when Alpine
processes the DOM.

**Config injected via `Js::from()`:**
```js
cfg = { tree, csrf, previewUrl, saveUrl, registry }
```

**State:**
- `tree` — in-memory JSON block tree (nodes tagged with `_cid` int key for Alpine)
- `isDirty` — true when tree differs from last saved state
- `isSaving` — true during fetch to save-tree
- `isRefreshing` — true during fetch to preview-payload
- `activeTab` — `'insert'` | `'tree'` (left panel tab)

**Key methods:**
- `init()` — clone initial tree from `cfg.tree`, assign `_cid`, trigger first preview
- `tagCids(nodes)` — recursively assign integer `_cid` (stable Alpine key)
- `scheduleRefresh()` — sets `isDirty`, debounces `refreshPreview()` by 800 ms
- `refreshPreview()` — POST to `previewPayload`, set `iframe#builder-preview.srcdoc`
- `serialize(nodes)` — maps Alpine `type` → wire `block_type` recursively
- `saveTree()` — POST to `save-tree`, updates tree with server IDs on success
- `addBlock(type, label)` — push new node to root level, schedule refresh
- `toggleVisible(node)` — flip `is_visible`, schedule refresh
- `moveUp/Down(index)` — swap array elements, force reactivity with spread, schedule refresh
- `removeBlock(index)` — splice array, schedule refresh

### UI: Three-panel layout

```
┌──────────────────────────────────────────────────────────────────────────┐
│ ← Back  Page Title  [Status]                  [Unsaved] [Preview] [Save] │
├──────────────────────┬───────────────────────────────────┬───────────────┤
│ [Add Block][List]    │                                   │ Block Settings│
│                      │  <iframe id="builder-preview"     │               │
│ 📐 Layout            │     srcdoc="..."                  │  (B3 scope)   │
│   [Group][Columns]   │     sandbox="allow-same-origin    │               │
│   [Divider]          │     allow-scripts allow-forms">   │               │
│                      │  </iframe>                        │               │
│ 📝 Content           │                                   │               │
│   [Heading][Text]... │                                   │               │
│                      │  ── Loading overlay ──            │               │
│ ...                  │  ── Empty state ──                │               │
│                      │                                   │               │
│ ── Block List tab ── │                                   │               │
│ 1. Hero    ▲▼ 👁 🗑   │                                   │               │
│ 2. Text    ▲▼ 👁 🗑   │                                   │               │
└──────────────────────┴───────────────────────────────────┴───────────────┘
```

**Left panel tabs:**
- `Add Block` — 19 block types grouped by category (layout / content / media /
  conversion / travel), each as a 2×grid card with icon + label. Click to append
  block to tree.
- `Block List` — flat list of root-level blocks with inline controls (move up/down,
  toggle visibility, delete). Block count shown in tab badge.

**Center panel:**
- `<iframe id="builder-preview">` — `srcdoc` set from `previewPayload` POST response
- Loading spinner overlay while fetch is in flight
- "No blocks yet" empty state when tree is empty

**Right panel:**
- Placeholder with link to form editor. Full block settings panel in B3.

**Top bar:**
- Dark (`bg-slate-900`) bar with page title, status badge, unsaved indicator,
  save error display, "Preview" (opens `pages.preview` in new tab), "Save" button

---

## DB change

Migration `2026_06_21_000003_add_parent_block_id_to_page_blocks_table` was
pending since Stage A (only run in test DB via `RefreshDatabase`). Executed
via `php artisan migrate` during B1 — now applied to dev DB.

---

## Verification

- `php artisan route:list --name=pages.builder` → confirmed registered
- `php artisan route:list --name=page-blocks.save-tree` → confirmed registered
- `php artisan test` → 610 tests / 3072 assertions / 0 failures
- `phpstan analyse` → 0 errors
- `pint` → auto-fixed and clean

---

## Limitations (out of B1 scope)

| Feature | Stage |
|---|---|
| Drag-and-drop reordering (replace move up/down) | B2 |
| Block inserter into specific position (not just append) | B2 |
| Nesting UI (insert into Group/Columns container) | B2 |
| Block settings panel (edit data fields) | B3 |
| Inline text editing on canvas | B4 |
| Reusable patterns | B5 |
| Templates integration | B6 |
| ~~Responsive preview toggles~~ | **Done early in B-LAYOUT** — desktop/tablet/mobile device toggles are live in the topbar (width-only via `deviceMaxWidth()`); B7 now only needs per-breakpoint verification |

---

## Next

**B2 — Block insertion & ordering**
- Replace move-up/down with `@alpinejs/sort` drag-and-drop (already installed)
- Insert at specific position (not just append to end)
- Move blocks into/out of Group and Columns containers
