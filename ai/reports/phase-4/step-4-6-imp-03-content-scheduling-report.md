# STEP 4.6 / IMP-03 — Content Scheduling

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Duration:** 3 hari
**Priority:** MEDIUM
**Status:** COMPLETE ✅

---

## 1. Scope

Add the ability to schedule a page for future publication. Admins pick a date/time
in the page editor; the system automatically sets `status = scheduled`. A cron-driven
Artisan command (`pages:publish-scheduled`, runs every minute) flips scheduled pages
to `published` once their time arrives.

---

## 2. Files Changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_20_000002_add_publish_at_to_pages_table.php` | ALTER TABLE pages — add `publish_at TIMESTAMP NULL AFTER status` |
| `app/Console/Commands/PublishScheduledPages.php` | Artisan command: publish overdue scheduled pages |
| `tests/Feature/Phase4/ContentSchedulingTest.php` | 12 tests (L1–L12) |

### Modified

| File | Change |
|------|--------|
| `app/Models/Page.php` | Added `publish_at` to `$fillable`; cast `datetime`; added `scopeScheduled()` and `isScheduled()` |
| `app/Http/Requests/Admin/StorePageRequest.php` | Added `publish_at` nullable date rule; added `'scheduled'` to status `Rule::in(...)` |
| `app/Http/Requests/Admin/UpdatePageRequest.php` | Same as Store |
| `app/Services/PageService.php` | `store()` + `update()` resolve final status/publish_at via `resolveStatus()` + `resolvePublishAt()`; `saveRevision()` now includes `publish_at` in `meta_snapshot`; `duplicate()` explicitly sets `publish_at = null` |
| `bootstrap/app.php` | Registered `PublishScheduledPages` in `withCommands()`; added `withSchedule()` firing `everyMinute()` |
| `database/factories/PageFactory.php` | Added `scheduled(DateTimeInterface|string|null $publishAt)` factory state |
| `resources/views/backend/pages/index.blade.php` | Status badge logic split into published / scheduled (yellow) / draft; added "Scheduled" option to status filter |
| `resources/views/backend/pages/edit.blade.php` | Sticky header: conditional badge (scheduled = yellow); subtitle shows "Scheduled for: …"; Basic Info form: `datetime-local` picker + scheduled info; SEO form: carry-forward hidden `publish_at` input |

---

## 3. Database Schema

```sql
ALTER TABLE pages
    ADD COLUMN publish_at TIMESTAMP NULL AFTER status;
```

No new tables. Migration file: `2026_06_20_000002_add_publish_at_to_pages_table.php`

---

## 4. Status Resolution Logic (PageService)

```
publish_at provided + is FUTURE  → status = 'scheduled', publish_at saved
publish_at provided + is PAST    → status = 'published',  publish_at = null (immediate publish)
publish_at not provided          → use submitted status as-is, publish_at = null
```

Applied identically in both `store()` and `update()` via two private helpers:
- `resolveStatus(string $submitted, ?Carbon $publishAt): string`
- `resolvePublishAt(?Carbon $publishAt): ?Carbon`

---

## 5. Artisan Command

```
php artisan pages:publish-scheduled
```

```php
Page::where('status', 'scheduled')
    ->where('publish_at', '<=', now())
    ->update(['status' => 'published', 'publish_at' => null]);
```

Registered in `bootstrap/app.php`:

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('pages:publish-scheduled')->everyMinute();
})
```

---

## 6. UI — Page Index

- Status column: three distinct display paths
  - `admin-badge-success` → Published
  - Custom yellow inline style + `admin-badge-warning` base → Scheduled (+ "Publishes DD Mon YYYY, HH:MM" subtitle)
  - `admin-badge-warning` → Draft
- Status filter dropdown: added "Scheduled" option

---

## 7. UI — Page Editor

- **Sticky header badge**: conditional — shows "Scheduled" in yellow when `$page->isScheduled()`
- **Sticky header subtitle**: appends "· Scheduled for: DD Mon YYYY, HH:MM" when scheduled
- **Basic Information form**:
  - Status `<select>` gains an `<option value="scheduled">Scheduled</option>`
  - New `datetime-local` picker for `publish_at` (pre-filled with current value)
  - Contextual hint text and a "Scheduled for:" line when currently scheduled
- **SEO form**: added `<input type="hidden" name="publish_at">` carry-forward so saving SEO does not accidentally clear the scheduled date

---

## 8. Revision Snapshot (L12)

`PageService::saveRevision()` now includes `publish_at` in `meta_snapshot`:

```php
'meta_snapshot' => $page->only([
    'title', 'slug', 'status', 'publish_at',
    'template_id', 'meta_title', 'meta_description'
]),
```

---

## 9. Test Coverage (L1–L12)

| Test | Contract | Result |
|------|----------|--------|
| L1 | Future `publish_at` + status=scheduled stores both fields | ✅ |
| L2 | `pages:publish-scheduled` publishes pages past their `publish_at` | ✅ |
| L3 | Command does not publish pages whose `publish_at` is in the future | ✅ |
| L4 | Command sets `publish_at = null` after publishing | ✅ |
| L5 | Command does not affect already-published pages | ✅ |
| L6 | `publish_at` nullable — existing create/update still works | ✅ |
| L7 | Status badge shows "Scheduled" in page index | ✅ |
| L8 | Page edit view shows `publish_at` datetime picker | ✅ |
| L9 | Past `publish_at` → page status immediately becomes `published` | ✅ |
| L10 | Future `publish_at` → page status becomes `scheduled` | ✅ |
| L11 | `scopePublished()` excludes scheduled pages | ✅ |
| L12 | `publish_at` captured in `meta_snapshot` on `saveRevision()` | ✅ |

---

## 10. Test Results

```
php artisan test tests/Feature/Phase4/ContentSchedulingTest.php
Tests:  12 passed
Assertions: 31

php artisan test (full suite)
Tests:  536 passed (was 524 — +12 new, 0 regressions)
Assertions: 2,615 (was 2,584)
```

---

## 11. Impact Summary

| Area | Impact |
|------|--------|
| DB | ALTER TABLE pages — new `publish_at` column; migration applied |
| Routes | None added |
| Scheduler | `pages:publish-scheduled` registered via `withSchedule()` → runs every minute |
| Admin — Pages index | Scheduled badge (yellow) + filter option |
| Admin — Pages editor | datetime-local picker + scheduled info in header + carry-forward in SEO form |
| Page status values | Now `'draft'` \| `'published'` \| `'scheduled'` |
| Revision snapshots | `publish_at` now included in `meta_snapshot` |
| Duplicate page | `publish_at` explicitly cleared on duplicate |
| Tests | +12 passing (536 total, 0 regressions) |

---

## 12. Rollback

```bash
git revert HEAD
php artisan migrate:rollback --step=1
```

---

## 13. Next Step

**STEP 6 — Core Plugin: Contact Form Builder** (7 hari) *(STEP 5 SEO Manager dipindah ke setelah STEP 7)*:
- Form builder UI (text, email, phone, select, textarea, checkbox, file)
- `contact_form` block type di page block editor
- Submissions inbox (sortable, filterable, mark read/unread)
- Email notification ke admin (Laravel Mail + queue)
- Honeypot anti-spam
- Open Graph configurator (global + per-page override)
- JSON-LD templates (Organization, WebPage, BreadcrumbList)
- Redirect manager (`redirects` table, 301/302)
