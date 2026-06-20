# PRE-STEP — IMP-04: Duplicate Page

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Effort:** 1 hari
**Priority:** MEDIUM
**Status:** COMPLETE ✅

---

## 1. Scope

Add a "Duplicate" button to the Pages admin list that clones an existing page — including
all of its blocks — into a new draft with a unique slug. No new database tables or
migrations required.

---

## 2. Files Changed

### Modified

| File | Change |
|------|--------|
| `app/Services/PageService.php` | Added `duplicate(Page $page): Page` method |
| `app/Http/Controllers/Admin/PageController.php` | Added `duplicate(Page $page)` action |
| `routes/admin.php` | Added `POST admin/pages/{page}/duplicate` → `admin.pages.duplicate` |
| `resources/views/backend/pages/index.blade.php` | Added "Duplicate" button with confirm dialog between Edit and Delete |

No new files created.

---

## 3. Implementation Detail

### PageService::duplicate()

```php
public function duplicate(Page $page): Page
{
    $copy = $page->replicate();
    $copy->title    = $page->title . ' (Copy)';
    $copy->slug     = $page->slug . '-copy-' . time();
    $copy->status   = 'draft';
    $copy->og_image = null;
    $copy->save();

    $page->blocks()->get()->each(
        fn ($block) => $block->replicate()->fill(['page_id' => $copy->id])->save()
    );

    return $copy;
}
```

**Key decisions:**
- Status always `draft` — admin must consciously publish the copy
- `og_image` cleared — physical file is not duplicated; avoids orphan file confusion
- `publish_at` not reset — column does not exist yet (IMP-03, scheduled for STEP 4.5)
- Blocks cloned with `replicate()` + `fill(['page_id' => $copy->id])` — preserves all block fields

### Controller

After duplication, redirects to the copy's edit page (not the index) with a flash
message naming the duplicate:

```
Page duplicated. You are now editing "My Page (Copy)".
```

### Route

`POST /admin/pages/{page}/duplicate` is placed **before** the resource route to
avoid any wildcard routing conflicts.

### View

Duplicate form button inserted between Edit and Delete using `confirm()` browser dialog:

```html
<form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">
    @csrf
    <button onclick="return confirm('Duplicate \"...\"?')" class="admin-btn-soft">
        Duplicate
    </button>
</form>
```

---

## 4. Routes Added

| Name | Method | URI | Controller |
|------|--------|-----|------------|
| `admin.pages.duplicate` | POST | `/admin/pages/{page}/duplicate` | `PageController@duplicate` |

---

## 5. Impact Summary

| Area | Impact |
|------|--------|
| DB | None — no migration |
| Routes | 1 new admin route |
| Frontend (public) | None |
| Admin UI | "Duplicate" button appears on every row of the Pages list |
| Security | CSRF-protected POST; admin middleware already applied |

---

## 6. Rollback

```bash
git revert HEAD
```

---

## 7. Next Step

**PRE-STEP IMP-01 — Admin Activity Audit Log** (2 hari, prasyarat untuk STEP 8 Plugin Security)
