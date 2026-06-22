# B3 — Block Settings Panel (schema-driven)

**Date:** 2026-06-22
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Status:** **B3.1 + B3.2 COMPLETE ✓** — all 19 blocks editable in the builder

---

## Deliverable

The builder's right panel now edits the selected block's fields live. Selecting a
block in the Block List renders its editable fields; changing a field updates the
in-memory tree, refreshes the iframe preview (debounced), and is persisted by the
existing `save-tree` endpoint. **No route, controller, schema, auth, or package
change** — the design is config + frontend only.

## Approach: schema-driven

Each block type declares a `fields` array in `config/blocks.php`. The registry is
already passed to Alpine (`cfg.registry`), so the panel reads the schema directly.
A single generic renderer (`partials/panel-right.blade.php`) loops the fields and
binds each control with `x-model="selectedNode().data[field.key]"`.

### Field shape

```php
['key' => 'level', 'type' => 'select', 'label' => 'Semantic Level',
 'default' => 'h2', 'options' => ['h2' => 'H2 — Main section', /* … */]],
```

Optional keys: `default, options, min, max, maxlength, rows, placeholder, help`.

### Field types (B3.1)

`text` · `url` · `number` · `textarea` · `richtext` (HTML, sanitized server-side
on save) · `select` · `toggle`.

## Files changed

| File | Change |
|---|---|
| `config/blocks.php` | Added `fields` schema to 8 blocks: heading, text, cta, divider, video_embed, map, group, columns (keys/options derived from the existing form partials in `backend/pages/partials/blocks/*`) |
| `resources/views/backend/builder/partials/panel-right.blade.php` | Placeholder → empty-state + schema-driven field form (`x-if` guard so `x-model` never hits a null node) |
| `resources/views/backend/builder/partials/alpine-component.blade.php` | Added `selectedNode()`, `fieldsFor(type)`, `applyFieldDefaults(node)`; called defaults in `addBlock`/`selectBlock` |
| `docs/visual-builder-structure.md` | B3 section updated |

## Key behaviours

- `applyFieldDefaults()` seeds every schema field with its default on select/add, so
  selects show the right value and the preview is consistent.
- Editing the **Block Label** (admin-facing name) is also supported.
- Blocks with no `fields` yet show "This block has no inline settings yet" and remain
  editable via the form editor on the Page Edit screen.
- Security: `richtext`/HTML is still sanitized by `PageBlockService::validateAndSanitizeData()`
  on save — client HTML is never trusted.

## Verification

- `php artisan config:clear` + `php -l config/blocks.php` → no syntax errors; 8 blocks × fields confirmed
- `php artisan view:cache` → all partials compile
- `php artisan test --filter="Builder|PageBlock|Block"` → **50 passed / 535 assertions**
- `npm run build` → new classes compiled

## B3.2 — COMPLETE ✓

Added field types and wired the remaining 11 blocks:

- **`image`** — path input + direct upload (`upload-quick`) + Media Library picker.
  Reuses the shared `backend/media/partials/picker-modal` via the
  `open-media-picker` → `media-picker-selected` event protocol. A pending-setter
  (`pickImage(setter)` / `onMediaPicked`) routes the chosen path to the right
  field, so it works for images nested inside repeaters (e.g. gallery).
- **`color`** — color swatch + hex text input (hero `background_color`).
- **`range`** — slider with live value + suffix (hero `overlay_opacity`).
- **`repeater`** — array of objects with add/remove and a `max`; sub-fields render
  through the same `builder-field` partial (so a repeater row can contain an image).
  Blocks: gallery, button_group, stats, tour_itinerary, pricing_table, faq, testimonials.
- **`list`** — array of plain strings (pricing_table plan `features`).
- **`showIf: {key, value}`** — conditional field visibility (faq inline vs. ids).
- **Dynamic `optionsFrom`** selects — `categories` / `destinations` / `forms`,
  passed from `PageBuilderController@show` as `$fieldOptions` → `cfg.options`.

### Reusable renderer

`resources/views/backend/builder/partials/builder-field.blade.php` renders ONE
control, parameterised by `$f` (field-definition var) and `$model` (Alpine lvalue
expression). The top-level loop calls it with `selectedNode().data[field.key]`;
repeater rows call it with `item[sub.key]`. One source of truth for every control.

### Files (B3.2)

| File | Change |
|---|---|
| `config/blocks.php` | `fields` for the remaining 11 blocks |
| `partials/builder-field.blade.php` | New — reusable control set (text/url/number/textarea/richtext/select/color/range/toggle/image) |
| `partials/panel-right.blade.php` | repeater + list + showIf; delegates simple controls to builder-field |
| `partials/alpine-component.blade.php` | repeater/list helpers, media picker (`pickImage`/`onMediaPicked`/`uploadInto`), `selectOptions`, `showField`, `defaultFor` |
| `index.blade.php` | `.builder-input` styles, include picker modal, pass `options`+`uploadUrl`, listen `media-picker-selected` |
| `PageBuilderController.php` | pass `$fieldOptions` (categories/destinations/forms) |

### Verification (B3.2)

- `php -l config/blocks.php` clean; **19/19 blocks have fields**
- `php artisan view:cache` compiles all partials
- Controller render smoke test: `show()->render()` → 103 KB HTML, no errors
- `php artisan test --filter="Builder|PageBlock|Block"` → **50 passed / 535 assertions**
- PHPStan level 5 on the changed controller → 0 errors
- `npm run build` → new classes compiled

### B3 — Nesting (Group / Columns) — COMPLETE ✓

The backend, preview (`transientTree`) and frontend block partials already
rendered nested `children`, but the builder UI had no way to create nesting.
Added a hierarchical Block List and container-aware insertion:

- `flatList()` renders children indented under their container (folder icon +
  child count); `findCtx()` locates a node + its parent array at any depth.
- `addBlock` drops a block **inside** a selected Group/Columns, or as the next
  **sibling** of a selected leaf, else at root. Columns enforce Group-only
  children (also validated server-side in `transientTree`).
- **Drag-and-drop** (final UX): drag a row to reorder (amber line above/below) or
  drop onto a Group/Columns to nest (ring + "↳ inside" badge). Drop intent is
  derived from cursor position in the hovered row; `moveNode` re-parents via
  `findCtx`, `contains()` blocks cyclic drops, Columns enforce Group-only.
  Replaced the earlier indent/outdent/up-down arrow buttons (row actions are now
  just visibility + delete).
- `selectedNode()` is recursive, so the settings panel works for nested blocks.
- Note: empty Group/Columns render nothing on the page by design — the panel and
  Block List hint this so it doesn't look "broken".

Files: `partials/alpine-component.blade.php`, `partials/panel-left.blade.php`,
`partials/panel-right.blade.php`. Tests: 52 passed / 543 assertions.

### B3 — Group/Columns Layout/Style/Advanced tabs — COMPLETE ✓

Elementor-style tabbed settings for the container blocks (other blocks unchanged):

- **Tabs**: fields tagged with `'tab'` (layout|style|advanced); panel shows a
  switcher only when a block uses >1 tab (`fieldTabs()`/`activeFieldTab`).
- **Layout**: Content Width (Boxed/Standard/Full-width — full is truly
  edge-to-edge, no side padding), vertical spacing, column count/gap (Columns).
- **Style**: Background via a `background` field type — color + image (picker +
  upload) + position/size/repeat/opacity, bound to `data.background.*` (already
  rendered by the frontend partials). Slideshow/video deferred.
- **Advanced**: margin & padding (`box` 4-side), z-index, CSS id, CSS classes,
  hide on desktop/tablet/mobile, custom CSS (`code`). Applied on the frontend by
  `App\Support\BlockStyle::advanced()`; validated + normalized by
  `PageBlockService::advancedRules()`/`normalizeAdvanced()` so empty inputs never
  break Save. Tailwind safelist covers the runtime hide classes.

Security: `css_id`/`css_classes` sanitized to safe chars; `custom_css` strips
`<`/`>` (no `</style>` breakout) and is admin-only. New field types: `code`,
`box`, `background`. Round-trip + 158 tests verified; PHPStan level 5 clean.

### Still deferred (optional, future)

- Nested **background styling object** (`data[background][color|image|position|repeat|size|opacity]`)
  used by the shared `blocks/partials/background.blade.php` — a `fieldset`/group field
  type would cover it. Not blocking; blocks using flat style keys (e.g. hero) are done.
- Drag-reorder of repeater rows (currently add/remove + order as added).

## Manual check (owner)

Open a page builder → Block List → add/select **Heading**, **CTA**, **Map**, **Columns** →
edit fields in the right panel → confirm the iframe preview updates and Save persists.
