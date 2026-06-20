# PRE-STEP — IMP-01: Admin Activity Audit Log

**Date:** 2026-06-20
**Branch:** `feature/phase-4-plugin-system`
**Effort:** 2 hari
**Priority:** HIGH (prasyarat untuk STEP 8 Plugin Security)
**Status:** COMPLETE ✅

---

## 1. Scope

Record every significant admin action (create, update, delete) to an `audit_logs` table.
Admin can view the log at `/admin/audit-logs` with filters by user, action type, subject
type, and date range. Observers are registered on Page, Product, Menu, and Theme models.

Phase 4 plugin activation/deactivation events will be appended to this same table via
`AuditLog::record()` in STEP 8.

---

## 2. Schema Change

### New table: `audit_logs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | auto-increment |
| `user_id` | BIGINT UNSIGNED FK → `users` | CASCADE DELETE |
| `action` | VARCHAR(100) | `'created'`, `'updated'`, `'deleted'`, `'plugin.activated'`, … |
| `auditable_type` | VARCHAR(255) | Short class name: `'Page'`, `'Plugin'`, `'Theme'`, … |
| `auditable_id` | BIGINT UNSIGNED NULL | PK of the affected record |
| `auditable_label` | VARCHAR(255) NULL | Human label (title/name/slug resolved automatically) |
| `old_values` | JSON NULL | Changed fields before update |
| `new_values` | JSON NULL | Changed fields after update |
| `ip_address` | VARCHAR(45) NULL | IPv4 or IPv6 |
| `user_agent` | VARCHAR(500) NULL | Truncated to 500 chars |
| `created_at` | TIMESTAMP | No `updated_at` — audit rows are immutable |

**Indexes:** `idx_audit_user`, `idx_audit_auditable`, `idx_audit_created`

Migration: `2026_06_23_000001_create_audit_logs_table.php`

---

## 3. Files Changed

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_23_000001_create_audit_logs_table.php` | Creates `audit_logs` table |
| `app/Models/AuditLog.php` | Eloquent model; `record()` static helper; auto-resolves `auditable_label` from title/name/slug/email; no-op when unauthenticated |
| `app/Observers/PageObserver.php` | created / updated / deleted hooks for Page |
| `app/Observers/ProductObserver.php` | created / updated / deleted hooks for Product |
| `app/Observers/MenuObserver.php` | created / updated / deleted hooks for Menu |
| `app/Observers/ThemeObserver.php` | created / updated / deleted hooks for Theme |
| `app/Http/Controllers/Admin/AuditLogController.php` | `index()` with 5 query filters; eager-loads user |
| `resources/views/backend/audit-logs/index.blade.php` | Table with filter bar; expandable before/after JSON diff per row using Alpine.js |

### Modified

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Registered 4 observers in `boot()`; added 4 `use` imports |
| `routes/admin.php` | Added `GET admin/audit-logs` → `admin.audit-logs.index`; added `use AuditLogController` |
| `resources/views/backend/partials/sidebar.blade.php` | Added "System" section with "Audit Log" link (`fa-shield-halved` icon) |

---

## 4. AuditLog::record() Contract

```php
AuditLog::record(
    action:    'updated',
    subject:   $page,
    oldValues: ['status' => 'draft'],
    newValues: ['status' => 'published'],
    label:     null,  // auto-resolved from $page->title
);
```

- Returns void; silently no-ops when `auth()->check()` is false
- `auditable_type` = `class_basename($subject)` (e.g. `'Page'`)
- `auditable_label` resolution order: `title` → `name` → `slug` → `email` → null
- For `updated` events, observers pass only the changed fields (not the full model)

---

## 5. Observer Pattern for `updated`

```php
public function updated(Page $page): void
{
    $changed = array_keys($page->getChanges());
    $old = array_intersect_key($page->getOriginal(), array_flip($changed));
    $new = $page->getChanges();

    AuditLog::record('updated', $page, $old, $new);
}
```

Only the diff is stored — not the entire model. Keeps `old_values` / `new_values` focused
and avoids logging large JSON blobs for every save.

---

## 6. Admin UI — `/admin/audit-logs`

**Filters (all via GET query string, auto-submit on change):**

| Filter | Input | Description |
|--------|-------|-------------|
| Admin User | `<select>` | All admin users from `users` table |
| Action | `<select>` | Distinct values from `audit_logs.action` |
| Type | `<select>` | Distinct values from `audit_logs.auditable_type` |
| From / To | `<input type="date">` | Date range on `created_at` |

**Table columns:** When / User (+ IP) / Action (badge) / Subject (type + ID + label) / Changes (show/hide diff)

**Pagination:** 50 records per page.

---

## 7. Routes Added

| Name | Method | URI | Controller |
|------|--------|-----|------------|
| `admin.audit-logs.index` | GET | `/admin/audit-logs` | `AuditLogController@index` |

---

## 8. Impact Summary

| Area | Impact |
|------|--------|
| DB | New `audit_logs` table; no existing tables altered |
| Routes | 1 new admin route |
| Frontend (public) | None |
| Admin sidebar | New "System" section with Audit Log link |
| Observers | Page, Product, Menu, Theme fire `AuditLog::record()` on every save/delete |
| Performance | Each write adds 1 INSERT query; indexed for fast reads by user/type/date |

---

## 9. Rollback

```bash
# If migration was run:
php artisan migrate:rollback --step=1

git revert HEAD
```

---

## 10. Next Step

**STEP 0 — Baseline characterization tests + Architecture Design**
