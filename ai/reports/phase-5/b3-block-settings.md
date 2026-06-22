# B3 — Block Settings Panel (schema-driven)

**Date:** 2026-06-22
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Status:** **B3.1 COMPLETE ✓** · B3.2 pending

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

## B3.2 — pending scope

- `repeater` field type (array of sub-fields): gallery images, stats, pricing_table, button_group, testimonials, hero(slides), faq(items)
- `image` field type + media-library picker (reuse the `postMessage` pattern)
- Dynamic-option selects: products_grid (category/destination), contact_form (published forms) — needs the builder controller to pass those lists
- Nested **background styling** section (color/image/position/repeat/size/opacity) for blocks where `supports.background = true`

## Manual check (owner)

Open a page builder → Block List → add/select **Heading**, **CTA**, **Map**, **Columns** →
edit fields in the right panel → confirm the iframe preview updates and Save persists.
