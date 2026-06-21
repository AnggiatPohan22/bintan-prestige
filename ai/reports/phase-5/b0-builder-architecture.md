# B0 — Visual Builder Architecture Decision

**Date:** 2026-06-21  
**Branch:** `feature/phase-5-stage-b-visual-builder`  
**Author:** Claude (architecture audit, pre-coding)  
**Status:** AWAITING OWNER APPROVAL — do not begin B1 until approved

---

## Purpose

This document answers the four architectural questions defined in
`ai/skills/phase5-visual-builder-skill.md §B0` before any builder code is written.
Each decision includes the reasoning, tradeoffs, and — where a new package or schema
change is involved — an explicit approval request.

---

## Existing foundation confirmed (Stage A deliverables)

The following infrastructure from Stage A is already in place and ready for Stage B:

| Capability | File / Route | Status |
|---|---|---|
| Block registry (19 types) | `config/blocks.php` | ✓ ready |
| Block JSON API | `GET /admin/api/block-types` → `PageBlockController::apiTypes()` | ✓ ready |
| Transient preview endpoint | `POST /admin/pages/{page}/preview-payload` → `PageController::previewPayload()` | ✓ ready |
| Nesting schema | `page_blocks.parent_block_id` (migration `2026_06_21_000003`) | ✓ migrated in tests, **pending `php artisan migrate` on dev DB** |
| In-memory tree builder | `PageController::buildTree()` + `transientTree()` | ✓ ready |
| Drag-sort library | `@alpinejs/sort` v3.15.12 (already in `package.json` + installed) | ✓ ready — **no new package needed** |
| Media picker | `postMessage` pattern in `backend/media/picker.blade.php` | ✓ ready |
| Revision snapshots | `PageRevision` — snapshots full block tree, max 20 per page | ✓ ready |

---

## Decision 1 — Canvas model

### Decision

The builder edits a **full in-memory JSON block tree** held in a single Alpine.js
`x-data` store. The tree is loaded from the page's saved blocks when the builder
opens, and written back to the database only on explicit Save/Publish.

### Node shape

```js
{
  _cid:      "uuid-v4",       // client-only ID for Alpine tracking (never sent to server)
  id:        42,              // DB id (null for unsaved new blocks)
  type:      "heading",       // matches config/blocks.php key
  label:     "Section Title",
  data:      { level: "h2", text: "...", alignment: "left" },
  is_visible: true,
  sort_order: 0,              // assigned by builder before saving
  children:  []               // nested nodes (only for group / columns types)
}
```

**Why `_cid` (client ID)?**  
Alpine `x-for` requires a stable key that doesn't change when the user reorders.
DB `id` is `null` for unsaved blocks, making it unreliable as a key. `_cid` is a
UUID generated on the client and is never sent to the server.

### Loading the tree

On builder page load, the initial tree is **embedded in the Blade template as
`@json`** (no extra AJAX round-trip). The controller prepares the tree the same way
`buildTree()` already does — one flat query, grouped in PHP.

### Saving the tree

A new endpoint is needed: `POST /admin/pages/{page}/blocks/save-tree`  
Payload: `{ blocks: [node, ...] }` (full tree, including `children[]`)  
Server behavior:
1. Validate the tree via the existing `transientTree()` validation logic (re-used)
2. Wrap in a DB transaction: snapshot revision → delete all existing blocks → bulk-insert new tree
3. Return JSON `{ success: true, blocks: [...with real ids...] }`

This is a **safe, no-schema-change** operation. Requires owner approval only as a new
route/controller method (per AGENTS.md §9 — not a schema/rename/package change).

**Approval needed:** Yes — new endpoint `POST /admin/pages/{page}/blocks/save-tree`

---

## Decision 2 — Live preview approach

### Decision: **Iframe with `srcdoc` refresh**

The builder opens a `<iframe>` in the center canvas. Whenever the block tree changes
(after a debounce of ~800 ms), the builder:

1. Sends a `fetch()` POST to the existing `preview-payload` endpoint with the current
   tree JSON.
2. Receives the full rendered HTML page as a response.
3. Sets `iframe.srcdoc = html` to display the result.

### Why `srcdoc` instead of a GET iframe pointing at the preview route?

| Approach | WYSIWYG | Reuses Blade | Requires saved state | Notes |
|---|---|---|---|---|
| `srcdoc` via fetch to `preview-payload` | ✓ real HTML | ✓ yes | ✗ no | **Recommended** |
| `<iframe src="/admin/pages/{id}/preview">` | ✓ real HTML | ✓ yes | ✓ must save first | Only shows saved state |
| In-canvas Alpine render | ✗ approximation | ✗ no | ✗ no | Preview ≠ production |

The `srcdoc` approach is the only one that shows **real Blade-rendered output for
unsaved changes** without requiring a save. The `previewPayload` endpoint already
exists and already handles the full rendering pipeline including theme tokens, SEO
meta, and nested block trees.

### Debounce strategy

Preview refresh is debounced 800 ms after the last change. This avoids a fetch on
every keypress while still giving near-live feedback. Media changes (image selection)
trigger an immediate refresh since they are discrete events, not streaming input.

### Cross-origin note

The preview iframe renders inside the same admin domain, so `srcdoc` and `postMessage`
communication between the builder and the iframe do not encounter cross-origin
restrictions.

---

## Decision 3 — Drag-and-drop library

### Decision: **`@alpinejs/sort` (already installed, no new package)**

`@alpinejs/sort` v3.15.12 is already present in `package.json` and installed in
`node_modules`. **No `npm install` and no owner approval for a new package is needed.**

### What `@alpinejs/sort` provides

- `x-sort` directive on a container → its children become drag-sortable
- `@sort` event fires with the updated order when a drag completes
- Supports nested containers (important for Columns > Group nesting)
- Native Alpine integration — no global event conflicts with existing `x-data` components
- Already used conceptually in other Alpine components in the project

### Usage pattern

```html
<ul x-sort="onSort($item, $position)">
  <template x-for="block in tree" :key="block._cid">
    <li x-sort:item="block._cid">
      <!-- block card -->
    </li>
  </template>
</ul>
```

The `onSort` callback updates `sort_order` in the Alpine store, which triggers a
debounced preview refresh.

### Comparison with SortableJS (rejected)

| | `@alpinejs/sort` | SortableJS |
|---|---|---|
| Already installed | ✓ yes | ✗ no (needs approval) |
| Alpine-native | ✓ yes | ✗ wrapper needed |
| Nested containers | ✓ yes | ✓ yes |
| Bundle size | Smaller (Alpine plugin) | ~45 KB gzip |
| Verdict | **USE THIS** | Unnecessary |

**Approval needed:** None. Package is already installed.

---

## Decision 4 — Inline text editing

### Decision: **`contenteditable` with server-side sanitization**

For text-like blocks (`heading`, `text`), the builder canvas shows the block output
inside an element with `contenteditable="true"`. The user edits text directly on the
canvas without opening a side panel.

### Flow

1. User clicks on a text block in the iframe canvas.
   - Because the iframe uses `srcdoc`, the builder JS can communicate with it via
     `postMessage` — the iframe signals which block was clicked.
   - Alternatively: the settings panel opens inline editing fields in the right
     sidebar (simpler for Phase B4, safer for Phase B1–B3 rollout).
2. On blur or Enter, the edited HTML is sent back to the Alpine store as `data.body_html`
   or `data.text`.
3. On save, the full tree is POSTed to `save-tree`. The server runs
   `PageBlockService::validateAndSanitizeData()` on all block data — this already
   strips disallowed HTML tags before persisting.

### Security invariant

**The client HTML is never trusted.** Even if a user manually crafts the `data.body_html`
payload, `PageBlockService::validateAndSanitizeData()` (already in place from Stage A)
runs server-side sanitization before any data is written. The sanitization allowlist
must be verified covers the block types that support inline HTML.

**Approval needed:** None for the approach. B4 implementation will need a PR review
of the contenteditable HTML → sanitize flow.

---

## New files and routes required for Stage B

| Item | Type | Approval needed |
|---|---|---|
| `POST /admin/pages/{page}/blocks/save-tree` | new route + controller method | Owner approval (new endpoint) |
| `resources/views/backend/builder/` | new Blade directory | No |
| `resources/js/builder.js` (Alpine store) | new JS file | No |
| Builder admin route `GET /admin/pages/{page}/builder` | new route | Owner approval |

No schema changes beyond what Stage A already delivered.  
No new Composer or NPM packages.

---

## What Stage B will NOT change

- Existing form-based block editor (`backend/pages/partials/block-editor.blade.php`) — kept as fallback
- Existing block CRUD routes (`store`, `update`, `destroy`, `reorder`) — still needed by form editor
- `config/blocks.php` — only read, not modified
- Database schema — Stage A migration covers all needed columns
- `PageBlockService` — only consumed, not changed (except possible minor sanitize-allowlist review in B4)

---

## Risk assessment

| Risk | Likelihood | Mitigation |
|---|---|---|
| `srcdoc` refresh flicker on fast typing | Medium | 800 ms debounce + CSS `opacity` fade during refresh |
| `@alpinejs/sort` nested DnD edge cases | Low | Limit nesting to 2 levels (Columns → Group → leaf); test with 4-column layout |
| `save-tree` bulk-replace loses data on network failure | Low | Transaction + revision snapshot before replace; server returns new IDs so client syncs |
| `contenteditable` XSS via crafted payload | Low but serious | Server-side sanitize is mandatory and already exists; B4 PR must prove coverage |
| Builder page load time (large tree) | Low | Tree embedded as `@json`; max 200 nodes enforced by `transientTree` |

---

## Recommended B1–B7 implementation order

| Stage | Deliverable | Key dependency |
|---|---|---|
| B1 | Builder shell: route, Blade layout, Alpine store, empty canvas | Approval of `save-tree` endpoint |
| B2 | Block insertion panel + `x-sort` reordering | B1 |
| B3 | Settings panel (field forms from registry) + `srcdoc` preview | B1 |
| B4 | Inline editing for text/heading blocks | B3 |
| B5 | Reusable saved patterns | B3 |
| B6 | Templates: start from template / save as template | B5 |
| B7 | Responsive preview toggles (desktop / tablet / mobile) | B3 |

---

## STOP — Owner approval required before B1

Please confirm:

1. **`save-tree` endpoint** — approve adding `POST /admin/pages/{page}/blocks/save-tree`
2. **Builder route** — approve adding `GET /admin/pages/{page}/builder`
3. **`srcdoc` preview approach** — approve or prefer direct `<iframe src>` after save
4. **`@alpinejs/sort`** — noted as already installed, no action needed from owner
5. **Inline editing scope for B4** — confirm `heading` and `text` blocks are the target (not `hero`, `cta`, etc.)

After owner approval, proceed to **B1: Builder shell & canvas**.
