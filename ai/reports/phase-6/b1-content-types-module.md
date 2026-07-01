# B1 — Content Types Module

> **Task:** B1 — Content Types CRUD admin module
> **Status:** ✅ DONE — 2026-06-30
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new table `content_types` — **APPROVED by owner 2026-06-30** ("lanjut B1")
> **Skills:** database-architecture-skill · backend-skill · admin-dashboard-skill

---

## What was built

### `content_types` table (migration `2026_06_30_000002`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| slug | varchar(100) unique | kebab-case; auto-generated from `label_singular` |
| label_singular | varchar(150) | e.g. "Blog Post" |
| label_plural | varchar(150) | e.g. "Blog Posts" |
| icon | varchar(50) default 'file-lines' | Font Awesome icon name |
| description | text nullable | |
| is_public | boolean default true | false = no frontend routes |
| has_archive | boolean default true | true = `GET /{route_base}` registered at B10 |
| route_base | varchar(100) unique nullable | reserved-prefix guarded; auto-filled from label_plural |
| supports | json nullable | array of: title\|slug\|editor\|excerpt\|featured_image\|revisions\|scheduling\|seo |
| menu_position | unsigned int default 0 | ordering in sidebar |
| is_active | boolean default true | |
| timestamps + softDeletes | | |
| index(is_active, menu_position) | | |

### `ContentType` model (`app/Models/ContentType.php`)
- `SUPPORTS` const — valid feature flags (referenced by FormRequests and views)
- `RESERVED_PREFIXES` const — slugs/route_bases that must not collide with existing routes
- `supports(string $feature): bool` — check feature flag
- Scopes: `active()`, `ordered()`, `public()`
- Casts: `supports → array`, booleans, menu_position → integer
- Traits: `SoftDeletes`, `HasFactory`

### FormRequests
- `StoreContentTypeRequest` — `prepareForValidation` auto-fills slug from `label_singular` and `route_base` from `label_plural` (when is_public+has_archive). Reserved-prefix `Rule::notIn` guard on both slug and route_base.
- `UpdateContentTypeRequest` — same rules with `unique()->ignore($id)`.

### Controller (`ContentTypeController`)
CRUD: `index`, `create`, `store`, `edit`, `update`, `destroy` (soft delete), `restore`, `forceDelete`.
No service class needed (pure CRUD, no side-effects at B1 — sidecar indexing comes at B5).

### Admin views
- `backend/content-types/index.blade.php` — two tables: active (paginated, searchable) + archived (paginated, restore/force-delete).
- `backend/content-types/form.blade.php` — shared form partial; supports fieldset with checkboxes; Alpine.js toggles show/hide route_base when is_public=false.
- `backend/content-types/create.blade.php` + `edit.blade.php` — extend `layouts.admin`.

### Routes (`routes/admin.php`)
```
Resource: admin.content-types.{index,create,store,edit,update,destroy}
Extra:    PATCH admin/content-types/{id}/restore     → content-types.restore
          DELETE admin/content-types/{id}/force-delete → content-types.force-delete
```

### Sidebar (`components/admin/sidebar.blade.php`)
"Content Types" link added to the Content group, `active-group` detection updated.

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_06_30_000002_create_content_types_table.php` — **new**
- `app/Models/ContentType.php` — **new**
- `app/Http/Requests/Admin/StoreContentTypeRequest.php` — **new**
- `app/Http/Requests/Admin/UpdateContentTypeRequest.php` — **new**
- `app/Http/Controllers/Admin/ContentTypeController.php` — **new**
- `resources/views/backend/content-types/*.blade.php` — **new** (4 files)
- `routes/admin.php` — ContentTypeController import + resource + restore + force-delete routes
- `resources/views/components/admin/sidebar.blade.php` — "Content Types" nav link + active-group detection
- `tests/Feature/Admin/ContentTypeTest.php` — **new** (11 tests)

### Impact
- DB: **new table** `content_types` (migration applied dev MySQL). `down()` = `dropIfExists`.
- Routes: 8 new admin routes under `['auth','admin']` middleware.
- Frontend: none (public routing registered at B10).
- Security: `StoreContentTypeRequest` + `UpdateContentTypeRequest` validate all input; `RESERVED_PREFIXES` guard prevents route collisions; soft delete + forceDelete both guarded behind `['auth','admin']`.

### Verification
- B1 tests: **11/11 pass**, 49 assertions.
- Full suite: **653/653 pass**, 3681 assertions. PHPStan level 5: **0 errors**.
- Dev MySQL: migration applied (`167ms`), table verified.

### Rollback
`php artisan migrate:rollback --step=1` drops `content_types`.

### Next
- **B2 — Field Groups + Fields module** (⚠️ schema gate: new tables `field_groups`, `fields`).

---

## Post-Release Fix Log

> Catatan perbaikan bug yang ditemukan saat pengujian manual owner. Tidak menimpa laporan asli di atas.

### 2026-07-01 — Fix #1: Delete/Archive content type tidak berfungsi

**Gejala:** Klik tombol "Archive" pada content type → modal konfirmasi muncul, klik "Hapus" → tidak terjadi apa-apa, content type tidak ter-soft-delete.

**Akar masalah:** Bukan di modul B1. Bug di handler modal konfirmasi global `resources/views/layouts/admin.blade.php` (diperkenalkan commit `3fc70e2`, 2026-06-26). Handler tombol "Yes" memanggil `closeConfirmModal()` — yang men-set `_confirmCallback = null` — **sebelum** mengeksekusi callback, sehingga `if (_confirmCallback) _confirmCallback()` selalu melihat null dan submit form tidak pernah terjadi. Ini memengaruhi **semua** delete berbasis modal `data-confirm` (bukan hanya content types).

**Perbaikan:** Capture callback ke variabel lokal sebelum `closeConfirmModal()`:
```js
const cb = _confirmCallback;
closeConfirmModal();
if (cb) cb();
```

**File:** `resources/views/layouts/admin.blade.php`
**Impact:** memperbaiki delete di seluruh modul (content types, pages, products, dll) yang memakai modal konfirmasi. Tidak ada perubahan schema/route. Tidak bisa di-unit-test (JS murni) — diverifikasi manual.
**Commit:** lihat git log fix delete modal ordering.
