# B2 — Drag-and-Drop Block Ordering & Insert-at-Position

**Date:** 2026-06-21
**Branch:** `feature/phase-5-stage-b-visual-builder`
**Commit:** `0a8419b`
**Status:** COMPLETE ✓

---

## Deliverable

Block List panel in the visual builder now supports drag-and-drop reordering
via `@alpinejs/sort` (already installed since Stage A). Blocks can also be
inserted at a specific position by selecting a block first.

---

## Files Changed

| File | Type | What changed |
|---|---|---|
| `resources/views/backend/builder/index.blade.php` | Modified | DnD markup, ghost styles, insert-at-position logic |

No controller, route, or schema changes.

---

## Drag-and-Drop Implementation

### Library: `@alpinejs/sort` v3.15.12

Already in `package.json` and loaded in `app.js` via `Alpine.plugin(sort)`.
No new packages installed.

### Markup pattern

```html
<ul x-sort="onSort($item, $position)">
    <template x-for="(block, index) in tree" :key="block._cid">
        <li x-sort:item="block._cid">
            <span x-sort:handle> <!-- drag only starts on this element -->
                <i class="fa-solid fa-grip-vertical"></i>
            </span>
            <!-- rest of row -->
        </li>
    </template>
</ul>
```

- `x-sort` on `<ul>` — enables drag sorting; callback called after each drag
- `x-sort:item="block._cid"` on `<li>` — identifies the element with its `_cid`
- `x-sort:handle` on grip icon — restricts drag initiation to the icon only;
  clicking the row for selection does not trigger drag

### `onSort` callback

```js
onSort(cidStr, newPos) {
    const cid    = +cidStr;           // x-sort passes item value as string
    const oldPos = this.tree.findIndex(n => n._cid === cid);
    if (oldPos === -1 || oldPos === newPos) return;
    const [item] = this.tree.splice(oldPos, 1);
    this.tree.splice(newPos, 0, item);
    this.scheduleRefresh();           // 800 ms debounced preview refresh
},
```

After drag ends, SortableJS (inside `@alpinejs/sort`) has already reordered
the DOM. We sync the Alpine `tree` array to match. Alpine reconciles via `_cid`
keys without re-rendering the elements.

### Visual feedback (CSS)

```css
.sortable-ghost  { opacity: .25; background: rgb(71 85 105 / .4); border-radius: .5rem; }
.sortable-chosen { opacity: .85; box-shadow: 0 8px 32px rgb(0 0 0 / .6); }
```

Injected via `@push('head')` in the builder layout stack.

---

## Insert-at-Position

### New state: `selectedCid`

```js
selectedCid: null,  // _cid of the block selected in the tree list (null = insert at end)
```

### Selection UX

- Clicking any block row in the Block List sets `selectedCid` to that block's `_cid`
- Clicking the same row again deselects it (`selectedCid = null`)
- Selected row: amber left border + amber text + "↓" badge
- Action buttons (▲▼ 👁 🗑) use `.stop` modifier to prevent row click propagating to
  `selectBlock()`

### Insert-at-position logic in `addBlock`

```js
addBlock(type, label) {
    const node = { _cid: ++this._cid, id: null, type, label, ... };

    if (this.selectedCid !== null) {
        const idx = this.tree.findIndex(n => n._cid === this.selectedCid);
        if (idx !== -1) {
            this.tree.splice(idx + 1, 0, node);   // insert AFTER selected block
        } else {
            this.tree.push(node);
        }
    } else {
        this.tree.push(node);                      // default: append to end
    }

    this.selectedCid = node._cid;   // auto-select the new block
    this.activeTab   = 'tree';      // auto-switch to tree list to show result
    this.scheduleRefresh();
},
```

### Insert context banner (Add Block tab)

Above the block type grid, a small info bar shows:
- "Adding to end of page" — when no selection
- "Inserting after **[label]** ✕" — when a block is selected; ✕ resets to end

---

## Move-Up/Down Preserved

The ▲▼ buttons remain in the Block List as keyboard-accessible fallback for
precise positioning. They appear on hover alongside the new drag handle.
Both paths call `scheduleRefresh()` so the preview updates after either action.

---

## Verification

- `php artisan view:cache` → compiled successfully (no Blade parse errors)
- `php artisan test` → 610 tests / 3072 assertions / 0 failures

---

## Next

**B3 — Block Settings Panel**
- Right panel renders editable fields for the selected block
- Field schema read dynamically from `config/blocks.php`
- Changes update Alpine `tree[selected].data` and trigger live preview refresh
