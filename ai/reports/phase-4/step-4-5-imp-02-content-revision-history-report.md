# STEP 4.5 / IMP-02 — Content Revision History

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 4 hari
**Priority:** HIGH
**Status:** COMPLETE ✅

---

## 1. Scope

Snapshot the full page state (meta fields + all blocks) every time a page is saved.
Admins can browse the revision list, preview any snapshot in a modal, and one-click
restore to any prior revision. Max 20 revisions per page — oldest pruned automatically.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_20_000001_create_page_revisions_table.php` | New `page_revisions` table |
| `app/Models/PageRevision.php` | Model: `page()`, `author()` relations; no-timestamp, JSON casts |
| `database/factories/PageFactory.php` | Factory for `Page` (with `published()` / `draft()` states) |
| `database/factories/PageBlockFactory.php` | Factory for `PageBlock` |
| `tests/Feature/Phase4/PageRevisionTest.php` | 12 tests (K1–K12) |

### Modified

| File | Change |
|------|--------|
| `app/Models/Page.php` | Added `revisions()` HasMany relation |
| `app/Services/PageService.php` | Added `saveRevision()` call at top of `update()`; new public `saveRevision()` method |
| `app/Http/Controllers/Admin/PageController.php` | `edit()` passes `$revisions`; new `restoreRevision()` method |
| `routes/admin.php` | Added `POST admin/pages/{page}/revisions/{revision}/restore` |
| `resources/views/backend/pages/edit.blade.php` | Added "Revisions" accordion section (between Blocks and Danger Zone) |

---

## 3. Database Schema

```sql
CREATE TABLE page_revisions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id          BIGINT UNSIGNED NOT NULL,
    revision_number  INT UNSIGNED NOT NULL DEFAULT 1,
    content_snapshot JSON NOT NULL,
    meta_snapshot    JSON NULLABLE,
    created_by       BIGINT UNSIGNED NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id)    REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_revision_page (page_id, revision_number)
);
```

---

## 4. Snapshot Content

| Column | What it stores |
|--------|---------------|
| `content_snapshot` | `[{block_type, label, data, sort_order, is_visible}, …]` — all blocks at save time |
| `meta_snapshot` | `{title, slug, status, template_id, meta_title, meta_description}` |

Snapshot is taken **before** `$page->update()` applies new values, so each revision
contains the state that existed at the start of that save.

---

## 5. PageService::saveRevision()

```
saveRevision(Page $page, ?int $authorId = null)
    │
    ├─ Guard: return early if not authenticated and no authorId given
    ├─ max(revision_number) + 1  →  new revision_number
    ├─ content_snapshot = $page->blocks()->get([…])->toArray()
    ├─ meta_snapshot    = $page->only([title, slug, status, …])
    ├─ PageRevision::create(…)
    └─ Prune: keep newest 20, delete rest
```

Called automatically from `PageService::update()`. Also called manually from
`PageController::restoreRevision()` to snapshot the pre-restore state.

---

## 6. Restore Flow

```
POST admin/pages/{page}/revisions/{revision}/restore
    │
    ├─ Guard: abort(404) if revision.page_id ≠ page.id
    ├─ saveRevision(page)  ← snapshot current state first (makes restore undoable)
    ├─ $page->update(meta fields from meta_snapshot)
    ├─ $page->blocks()->delete()
    ├─ foreach content_snapshot → PageBlock::create(…)
    └─ redirect admin.pages.edit with success flash
```

---

## 7. Revisions Accordion (edit view)

Hidden when the page has no revisions. When visible:

- Position: between Content Blocks and Danger Zone
- Shows: revision number, timestamp, author name, meta title at save time, block count
- **Preview button** → Alpine.js modal with read-only meta snapshot (title, slug, status, meta title) + numbered block list (type, label, hidden badge)
- **Restore button** → Alpine.js confirm modal → `POST .../restore`

---

## 8. Test Coverage (K1–K12)

| Test | Contract |
|------|----------|
| K1 | Updating a page creates a `page_revisions` row |
| K2 | Revision `meta_snapshot` captures state before update |
| K3 | Revision `content_snapshot` captures blocks before update |
| K4 | `revision_number` increments per page across multiple saves |
| K5 | Max 20 revisions kept — oldest pruned when 21+ exist |
| K6 | Restore route redirects to `admin.pages.edit` |
| K7 | Restore updates page meta from `meta_snapshot` |
| K8 | Restore recreates blocks from `content_snapshot` |
| K9 | Restore saves current state as a new revision first |
| K10 | Edit view shows "Revisions" accordion + revision rows |
| K11 | Revision count is page-scoped (page B sees 0 of page A's revisions) |
| K12 | Deleting a page cascades and removes all its revisions |

---

## 9. Test Results

```
php artisan test tests/Feature/Phase4/PageRevisionTest.php
Tests:  12 passed
Assertions: 22

php artisan test tests/Feature/Phase4/ tests/Feature/Phase3/
Tests:  228 passed (no regressions)
Assertions: 531
```

---

## 10. Impact Summary

| Area | Impact |
|------|--------|
| DB | New table `page_revisions`; migration applied |
| Routes | `POST admin/pages/{page}/revisions/{revision}/restore` |
| Frontend (public) | None |
| Admin — Pages | "Revisions" accordion appears in page editor after first save |
| Page update | Auto-snapshot on every `PageService::update()` call |
| Factories | `PageFactory` + `PageBlockFactory` added (used by Phase 4 tests) |
| Tests | +12 passing (228 total Phase 3+4, no regressions) |

---

## 11. Rollback

```bash
git revert HEAD
php artisan migrate:rollback --step=1
```

---

## 12. Next Step

**STEP 4.6 — IMP-03: Content Scheduling** (3 hari): `publish_at` timestamp column on `pages`,
`scheduled` status, date-time picker in the page editor, `pages:publish-scheduled` artisan command
running every minute via the Laravel scheduler.
