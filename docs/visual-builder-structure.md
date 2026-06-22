# Visual Builder — Structure Reference

> Core structure file for the Phase 5 Visual Page Builder.
> Read this before maintaining, updating, or scaling the builder UI.
>
> **Stack:** Laravel 13.8 · Tailwind CSS · Alpine.js
> **Route:** `GET admin/pages/{page}/builder` → `PageBuilderController@show`
> **Branch:** `feature/phase-5-stage-b-visual-builder`

---

## 1. File tree

```
routes/admin.php
  └─ admin.pages.builder ............. GET admin/pages/{page}/builder

app/Http/Controllers/Admin/PageBuilderController.php
  ├─ show()        ... builds $tree from page blocks, passes $page/$tree/$registry
  └─ saveTree()    ... persists the edited block tree (backend only)

config/blocks.php  ................... block registry ($registry: type → label/category/desc)

resources/views/layouts/builder.blade.php
  └─ HTML shell. <body class="h-screen overflow-hidden …">, @yield('content'),
     @stack('head'), @stack('scripts'). Loads Vite (app.css/app.js) + Font Awesome.

resources/views/backend/builder/
  ├─ index.blade.php ................. WRAPPER ONLY. Extends layouts.builder,
  │                                    defines the full-viewport flex grid, and
  │                                    @includes the partials below. No heavy logic.
  └─ partials/
     ├─ alpine-component.blade.php ... The <script> Alpine.data('pageBuilder').
     │                                 All builder state + behaviour lives here.
     ├─ topbar.blade.php ............. Top bar: back, title/status, panel toggles,
     │                                 device toggles, Preview, Save. (flex-none)
     ├─ panel-left.blade.php ......... LEFT 20%. Add-Block inserter + Block-List tree
     │                                 (drag-sort via x-sort). Mobile = overlay drawer.
     ├─ canvas.blade.php ............. CENTER 60%. Device iframe + loading/empty/error
     │                                 overlays. The iframe is the live preview.
     └─ panel-right.blade.php ........ RIGHT 20%. Block Settings (B3 placeholder).
                                       Mobile = overlay drawer.
```

---

## 2. Layout diagram (lg+ desktop)

```
┌──────────────────────────────────────────────────────────────────────┐
│  TOPBAR (flex-none, h ≥ 14)   back · title · toggles · device · save   │
├───────────────┬──────────────────────────────────────┬────────────────┤
│  LEFT  20%    │            CANVAS  60% (flex-1)        │  RIGHT  20%    │
│  lg:w-[20%]   │                                        │  lg:w-[20%]    │
│  flex-none    │   ┌──────────────────────────────┐     │  flex-none     │
│               │   │  device wrapper (max-width)  │     │                │
│  [Add Block]  │   │  ┌────────────────────────┐  │     │ Block Settings │
│  [Block List] │   │  │  <iframe> live preview │  │     │   (B3 soon)    │
│               │   │  │  h-full · scrolls own  │  │     │                │
│  scroll-y     │   │  │  content (hero→footer) │  │     │                │
│  (own column) │   │  └────────────────────────┘  │     │                │
│               │   └──────────────────────────────┘     │                │
└───────────────┴──────────────────────────────────────┴────────────────┘
        the row is:  flex  flex-1  min-h-0  overflow-hidden
```

**Below `lg` (tablet/phone editor viewport):** LEFT and RIGHT become fixed
overlay **drawers** (`absolute inset-y-0`, `w-72` / `w-80`) floating over the
canvas, dimmed by a backdrop. They start collapsed (see `init()`), so the canvas
is immediately usable. This keeps the canvas from being squeezed on small screens.

---

## 3. Alpine state (`pageBuilder`, in `partials/alpine-component.blade.php`)

| Property | Purpose |
|---|---|
| `tree` | In-memory block tree (`{ _cid, id, type, label, data, is_visible, children[] }`). |
| `isDirty / isSaving / saveError` | Save state for the topbar. |
| `isRefreshing / previewError` | Live-preview fetch state for the canvas overlays. |
| `activeTab` | `'insert'` \| `'tree'` — left-panel tab. Does **not** affect panel width. |
| `selectedCid` | `_cid` of the selected block (insert-after target). |
| `previewMode` | `'desktop'` \| `'tablet'` \| `'mobile'` — drives **iframe width only**. |
| `leftCollapsed / rightCollapsed` | Panel minimize toggles (also drawer open/close on mobile). |
| `_cid / _refreshTimer` | Internal counters/timers. |

**Key methods:** `init` (tag cids, collapse panels on small screens, first preview),
`refreshPreview` (POST tree → `srcdoc` into the iframe, preserves scroll),
`scheduleRefresh` (debounced 800 ms), `saveTree` (POST to `save-tree`),
`addBlock / selectBlock / onSort / toggleVisible / moveUp / moveDown / removeBlock`,
and **`deviceMaxWidth()`** → `{ desktop:'100%', tablet:'768px', mobile:'375px' }`.

> There is **no** canvas measurement, ResizeObserver, or transform/scale logic.
> Device sizing is pure CSS `max-width`. (Removed during the B-LAYOUT fix.)

---

## 4. Key CSS rules (do not break these)

| Rule | Where | Why |
|---|---|---|
| `body` = `h-screen overflow-hidden` | `layouts/builder.blade.php` | Locks the app to the viewport; the page itself never scrolls. |
| root = `flex h-screen flex-col` | `index.blade.php` | Topbar (flex-none) + 3-panel row (flex-1) stack vertically, full height. |
| row = `flex flex-1 min-h-0 overflow-hidden` | `index.blade.php` | **`min-h-0` is mandatory** — without it flex children ignore overflow and overflow the viewport (this caused the old white frame). |
| panels = `lg:flex-none lg:w-[20%]` + `shrink-0` | `panel-left/right` | Fixed proportional columns; width never changes on tab switch or content. |
| canvas = `flex-1 min-h-0 overflow-hidden` | `canvas.blade.php` | Takes the remaining 60%; clips the device frame. |
| device wrapper = `h-full w-full` + `:style="max-width:…"` | `canvas.blade.php` | Device width via CSS only; centered with `mx-auto`. |
| iframe = `h-full w-full` | `canvas.blade.php` | Fills the visible canvas height; **iframe** is the scroll container. |
| each panel = own `overflow-y-auto` | `panel-left` tabs | Independent column scrolling; topbar stays fixed. |

**Golden rule:** the rendered website inside the iframe must stay independent of
panel width. Never reintroduce `transform: scale()` driven by measured panel size.

---

## 5. How to extend safely

**Add a new block type** — register it in `config/blocks.php`
(`type → label, category, description`). It appears automatically in the left
inserter (grouped by `category`, ordered `layout, content, media, conversion,
travel`). Add the icon mapping in both the `match()` in `panel-left.blade.php`
and `blockIcon()` in `alpine-component.blade.php`. Provide the admin field
schema + frontend Blade render per the 11-step CMS Module Pattern (AGENTS.md §6).

**Add a topbar control** — edit only `partials/topbar.blade.php`. Keep the header
`flex-none`; never let it grow the row.

**Build the right-panel settings (B3)** — replace the placeholder in
`partials/panel-right.blade.php`. Read the selected block via `selectedCid`;
write changes into the matching `tree` node and call `scheduleRefresh()`.

**Change the grid ratio** — edit the two `lg:w-[20%]` values (panel-left /
panel-right). Canvas auto-fills the rest (`flex-1`). Keep left + right < 100%.

**Add a device size** — extend `deviceMaxWidth()` and add a topbar button that
sets `previewMode`. No other change needed.

---

## 6. Known issues / TODO

- **B3 — Right settings panel** is a placeholder; detailed editing still uses the
  form editor on the Page Edit screen.
- **Detachable/draggable panels** (Elementor-style float) are not implemented —
  intentional trade-off; static `20:60:20` grid is used instead.
- **Block-tree drag-and-drop** reorders top-level blocks only; nesting into
  container blocks (Group/Columns) is future work.
- **Desktop preview is fluid** (100% of the 60% canvas), matching Elementor's
  fluid desktop. Tablet/mobile are fixed widths (768 / 375) and centered.

---

## 7. Change history

- **B-LAYOUT** — Replaced absolute-drawer + JS-measured white device card +
  `transform:scale` iframe with a `20:60:20` flex grid and CSS `max-width`
  device mode. Split the 765-line monolith into wrapper + 5 partials.
  Fixes: white frame below the canvas; panels shrinking with the browser;
  site scaling with panel width.
