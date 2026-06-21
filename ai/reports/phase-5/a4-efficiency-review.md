# A4 — Content & Data Efficiency Review

## Date: 2026-06-21
## Branch: feature/phase-5-stage-a-foundation
## HEAD audited: 28b41e2 (post-A3 commit)
## Status: AUDIT COMPLETE — awaiting owner approval before any code change

---

## 1. Scope

This is a read-only audit of:
- N+1 query patterns on admin list pages and frontend page rendering
- Pagination coverage across admin modules
- Block rendering query efficiency (including the new A3.4 nested tree)
- Image lazy-load coverage in frontend block partials
- Unbounded `->get()` calls that could grow with data

No code was changed during this audit. The findings and proposed fixes below
require owner approval before implementation.

---

## 2. Frontend page rendering — CLEAN

**File:** `app/Http/Controllers/Frontend/PageController.php` +
`app/Support/PageRenderData.php`

| Step | Query behaviour | Finding |
|---|---|---|
| Load page + template | `$page->load(['template'])` | 1 query ✓ |
| Load all blocks | `$page->blocks()->visible()->ordered()->get()` | 1 flat query for all blocks ✓ |
| Build in-memory tree | `buildTree()` — groups by `parent_block_id`, no additional queries | 0 extra queries ✓ |
| FAQ data | `prepareFaqBlocks()` — collects all FK IDs across all FAQ blocks, runs one batched `whereIn` | 1 query max ✓ |
| Products data | `prepareProductBlocks()` — deduplicates by configuration key, one query per unique config | 1 query per unique config ✓ |
| Block Blade partials | `group.blade.php` and `columns.blade.php` use `$block->relationLoaded('children')` — read from the in-memory tree | 0 queries in Blade ✓ |

**Verdict:** Frontend rendering is query-efficient. The A3.4 nesting did not
introduce any N+1 — the single flat query and in-memory tree build are the
correct patterns.

---

## 3. Admin edit page — one improvement opportunity

**File:** `app/Http/Controllers/Admin/PageController.php`

### 3a. Block loading on edit page

```php
$page->load(['blocks' => fn ($q) => $q->ordered()]);
```

Loads the full flat block list in one query — correct for the current flat-list
editor. After the pending A3.4 migration is applied, editors with nested blocks
will see all blocks loaded correctly because the flat list approach is
intentional for the admin editor (no tree build needed there).

**Finding:** no issue.

### 3b. Revision list on edit page (medium risk, grows with use)

```php
$revisions = $page->revisions()->with('author:id,name')->get();
```

This loads **all** revisions for a page without a limit. The current pruning cap
is 20 revisions per page (enforced by `PageService::saveRevision`). As long as
that cap holds, the unbounded `->get()` is safe (≤ 20 rows per page load).

**Finding:** safe today under the 20-revision cap. If the cap is ever raised or
removed, this should be paginated. Low priority.

### 3c. Revision restore — 2N queries (the only genuine N+1 found)

```php
// PageController::restoreRevision(), lines 133–155
foreach ($revision->content_snapshot as $snapshotIndex => $blockData) {
    $restoredBlocks[$snapshotIndex] = PageBlock::create([...]); // N INSERT queries
}
foreach ($revision->content_snapshot as $snapshotIndex => $blockData) {
    $restoredBlocks[$snapshotIndex]->update([...]); // N UPDATE queries
}
```

For a page with N blocks, restore issues `N INSERT` + `N UPDATE` queries.
The maximum block count after A3 is bounded by the 200-node transient limit,
but a page with 50 blocks would issue 100 queries per restore.

**Proposed fix (safe, no schema change):**
Replace with `PageBlock::insert([...all rows...])` (bulk insert, 1 query) then
a single `PageBlock::whereIn('id', ...)->update(...)` per distinct parent group
(typically 2–3 queries total for a nested page). This requires collecting
inserted IDs — either via `insertGetId` in a transaction loop, or via
`upsert`/`createMany` returning models.

The simplest safe approach is `PageBlock::insert()` for the root blocks, then
one batched update per parent-child mapping pass.

**Priority:** medium. The current behaviour is correct; it is just slow for
large pages.

---

## 4. Admin list pages — pagination coverage

| Controller | Query type | Paginated? | Finding |
|---|---|---|---|
| `PageController::index` | page list | `paginate(15)` ✓ | clean |
| `ProductController::index` | product list | `paginate(10)` ✓ | clean |
| `CategoryController::index` | category + archive | `paginate(10)` ✓ | clean |
| `DestinationController::index` | destination + archive | `paginate(10)` ✓ | clean |
| `FaqController::index` | FAQ list | `paginate(20)` ✓ | clean |
| `UserManagementController::index` | user list | `paginate(15)` ✓ | clean |
| `AuditLogController::index` | audit log | `paginate(50)` ✓ | clean |
| `FormSubmissionController::index` | submissions | `paginate(20)` ✓ | clean |
| `MediaController::index` | media grid | `paginate(24)` via `MediaService::list()` ✓ | clean |
| `MenuController::index` | menu list | `->get()` unbounded | CMS config table, typically < 10 rows — acceptable |
| `FormDefinitionController::index` | form list | `->get()` unbounded | **RISK** — grows with forms |
| `ThemeController::index` | theme list | `->get()` unbounded | config table, typically < 10 rows — acceptable |

### FormDefinitionController — missing pagination

```php
// FormDefinitionController.php:17
])->orderBy('name')->get();
```

This is a list page with no pagination. It is the only list page that could
realistically grow large (each form definition added by editors). Recommend
`paginate(20)`.

**Priority:** low-medium. Zero risk today but correct practice.

---

## 5. ProductController::index — withCount overhead

```php
->withCount([
    'highlights', 'features', 'faqs',
    'itineraries', 'notes', 'images',
    'features as included_features_count' => ...,
    'features as excluded_features_count' => ...,
])
```

This adds **8 `COUNT` subqueries** to the product list query. These are
aggregated as correlated subqueries, not N+1, so they do not multiply with
row count. However, they add fixed overhead per request regardless of whether
the counts are displayed.

**Finding:** not an N+1, but the admin products list may be slow if the
`page_blocks_count` (or similar) is unused. Recommend auditing the product
index view to confirm all 8 counts are displayed; remove unused ones.

**Priority:** low. Investigate at A4 implementation time.

---

## 6. Image lazy loading — CLEAN

All static `<img>` tags in frontend block partials were inspected:

| Block | Static img | `loading="lazy"`? |
|---|---|---|
| `image.blade.php` | yes | yes ✓ |
| `gallery.blade.php` (thumbnail grid) | yes | yes ✓ |
| `gallery.blade.php` (lightbox modal) | Alpine `:src` binding | modal is on-demand; no `loading` attr needed ✓ |
| `testimonials.blade.php` (avatar) | yes | yes ✓ |
| `map.blade.php` (iframe) | iframe | `loading="lazy"` ✓ |
| `video-embed.blade.php` (iframe) | iframe | `loading="lazy"` ✓ |
| `hero.blade.php` | CSS background-image | not applicable ✓ |

**Verdict:** all visible image/iframe elements have `loading="lazy"`. No changes needed.

---

## 7. Block rendering — no Blade queries confirmed

Checked all frontend block partials for direct DB calls:

- No `Product::`, `Faq::`, `DB::`, or `->query()` calls found inside any
  `resources/views/frontend/blocks/*.blade.php`.
- Data-backed blocks (`products_grid`, `faq`) receive resolved data via
  `PageRenderData::prepare()` before the view is rendered.

**Verdict:** zero in-Blade queries. Clean.

---

## 8. AnalyticsDashboardController — bounded by date filter

```php
->get()  // last 30 days of daily stats
->get()  // top pages by period
```

Both `->get()` calls are scoped by date range clauses (`whereDate`, `whereBetween`).
They are not truly unbounded. No change needed, but adding explicit `->limit()`
guards would prevent accidental large result sets if date filters are ever
widened.

**Finding:** low risk, acceptable as-is.

---

## 9. Summary of findings

| # | Finding | Severity | Schema change? | Proposed fix |
|---|---|---|---|---|
| F1 | `restoreRevision()` — 2N queries (N INSERTs + N UPDATEs) | Medium | No | Bulk insert + batched parent update |
| F2 | `FormDefinitionController::index()` — unbounded `->get()` | Low | No | `paginate(20)` |
| F3 | `ProductController::index()` — 8 `withCount` subqueries, some may be unused | Low | No | Remove unused counts after view audit |
| F4 | Revision list on edit page — `->get()` without limit | Very Low | No | Already capped at 20 by saveRevision; monitor only |

No frontend N+1, no missing lazy-load, no Blade DB calls, no schema changes
identified as needed.

---

## 10. Proposed A4 implementation plan (pending approval)

### Batch 1 — safe, focused, no schema change

**F1 — restoreRevision bulk refactor**
- File: `app/Http/Controllers/Admin/PageController.php`
- Replace the two `foreach` loops (lines 133–155) with:
  1. `PageBlock::insert([...all rows with null parent_block_id...])` — 1 query
  2. Re-query inserted blocks by `page_id` to get new IDs — 1 query
  3. Build a `snapshotId → newId` map in memory
  4. Collect `[id => parent_block_id]` pairs where parent_block_id is not null
  5. One batched update via `upsert` or chunked `whereIn`/`update` — 1–2 queries
- Total: ~4 queries regardless of block count

**F2 — FormDefinitionController pagination**
- File: `app/Http/Controllers/Admin/FormDefinitionController.php`
- Replace `->get()` with `->paginate(20)->withQueryString()`
- Update the corresponding Blade view to render pagination links

**F3 — ProductController withCount audit**
- Inspect `resources/views/backend/products/index.blade.php` to list which
  counts are actually rendered
- Remove any `withCount` entries not displayed in the UI

### Files that will change

- `app/Http/Controllers/Admin/PageController.php` (F1)
- `app/Http/Controllers/Admin/FormDefinitionController.php` (F2)
- `app/Http/Controllers/Admin/ProductController.php` (F3)
- Matching Blade view for `FormDefinitionController` if pagination links need adding (F2)

### Files that will NOT change

- No migration, no model rename, no route change, no new package.
- Frontend rendering pipeline, `PageRenderData`, block partials — all clean,
  not touched.

---

## 11. Rollback

Each fix is a focused controller/view edit. Rollback with
`git revert <a4-commit>` or manually restore the original lines.
No migration is involved.

---

## 12. Readiness for A5

A4 implementation is optional before starting A5 (frontend polish). The only
finding that could affect A5 is F3 (product counts), and only if the
products block on the frontend is slow — which it is not (it uses
`PageRenderData::prepareProductBlocks()`, not the admin withCount query).

A5 can begin in parallel with A4 implementation if the owner prefers.
