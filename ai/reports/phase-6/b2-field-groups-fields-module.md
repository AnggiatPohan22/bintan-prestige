# B2 — Field Groups + Fields Module

> **Task:** B2 — Field Groups + Fields CRUD admin module
> **Status:** ✅ DONE — 2026-07-01
> **Commit:** `6cc92fe`
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new tables `field_groups`, `fields` — **APPROVED by owner 2026-06-30** ("Approve B2")
> **Skills:** database-architecture-skill · backend-skill · admin-dashboard-skill

---

## What was built

### `field_groups` table (migration `2026_06_30_000003`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| content_type_id | FK → content_types CASCADE DELETE | grup dihapus otomatis saat content type dihapus |
| label | varchar(150) | nama tampilan, e.g. "Property Details" |
| key | varchar(100) | snake_case identifier, auto-generated dari label |
| description | text nullable | catatan opsional |
| sort_order | unsigned int default 0 | urutan tampil di form |
| timestamps | | |
| **UNIQUE(content_type_id, key)** | compound unique | key unik per content type, bukan global |
| index(content_type_id, sort_order) | | |

### `fields` table (migration `2026_06_30_000004`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| field_group_id | FK → field_groups CASCADE DELETE | field dihapus otomatis saat grup dihapus |
| type | varchar(50) | validasi via `FieldTypeRegistry::keys()` — bukan FK DB |
| key | varchar(100) | snake_case identifier, auto-generated dari label |
| label | varchar(150) | nama tampilan field |
| instructions | text nullable | teks bantuan untuk editor |
| is_required | boolean default false | |
| is_filterable | boolean default false | auto-inherit dari katalog saat create |
| settings | json nullable | konfigurasi per-tipe (options, min/max, dll) |
| default_value | json nullable | |
| conditional_logic | json nullable | dipakai di B3/B4+ |
| sort_order | unsigned int default 0 | |
| timestamps | | |
| **UNIQUE(field_group_id, key)** | compound unique | key unik per group, bukan global |
| index(field_group_id, sort_order) | |
| index(type) | | filter by field type |

### Models

**`FieldGroup`** (`app/Models/FieldGroup.php`)
- `belongsTo ContentType`
- `hasMany Field` (default ordered by sort_order)
- `scopeOrdered()` → order by sort_order, label
- Casts: `content_type_id` integer, `sort_order` integer

**`Field`** (`app/Models/Field.php`)
- `belongsTo FieldGroup`
- `typeDefinition(): ?array` — baca definisi dari `FieldTypeRegistry::get($this->type)`
- `typeLabel(): string` — label tipe untuk tampil di tabel
- Casts: JSON columns (settings, default_value, conditional_logic), booleans, integers

**`ContentType`** (modified)
- Tambah `entries(): HasMany<ContentEntry>` *(disatukan saat B2, dipakai penuh di B4)*
- Tambah `fieldGroups(): HasMany<FieldGroup>`

### FormRequests

**StoreFieldGroupRequest / UpdateFieldGroupRequest**
- `prepareForValidation`: auto-generate `key` dari `label` via `Str::slug($label, '_')` (snake_case)
- `key` regex: `^[a-z][a-z0-9_]*$` — harus mulai huruf, snake_case
- `key` unique: `Rule::unique('field_groups')->where('content_type_id', ...)` — unique per tipe, bukan global
- UpdateRequest: `->ignore($groupId)` untuk allow update tanpa conflict

**StoreFieldRequest / UpdateFieldRequest**
- `prepareForValidation`: auto-generate `key` dari `label`; auto-inherit `is_filterable` dari katalog (`FieldTypeRegistry::get($type)['is_filterable']`) bila belum di-set
- `type` validasi: `Rule::in(FieldTypeRegistry::keys())` — hanya tipe dalam katalog yang valid
- `key` unique: `Rule::unique('fields')->where('field_group_id', ...)` — unique per group

### Controller

**FieldGroupController** — `index`, `create`, `store`, `edit`, `update`, `destroy`, `reorder`
- Ownership check: `abort(404)` jika `$fieldGroup->content_type_id !== $contentType->id`

**FieldController** — `create`, `store`, `edit`, `update`, `destroy`, `reorder`
- Dual ownership check: group harus milik type, field harus milik group

### Admin Views

| File | Deskripsi |
|------|-----------|
| `backend/field-groups/index.blade.php` | Daftar grup dengan count field + quick field preview |
| `backend/field-groups/form.blade.php` | Form bersama (label, key readonly setelah create, desc, sort_order) |
| `backend/field-groups/create.blade.php` | Extends layouts.admin, include form |
| `backend/field-groups/edit.blade.php` | Form grup + tabel fields inline (Add Field → FieldController@create) |
| `backend/fields/form.blade.php` | Type selector (18 pilihan), key readonly setelah create, label, instructions, is_required, is_filterable checkboxes |
| `backend/fields/create.blade.php` + `edit.blade.php` | Wrapper views |

### Routes (`routes/admin.php`)

```
admin/content-types/{ct}/field-groups                 → FieldGroupController@index   (field-groups.index)
admin/content-types/{ct}/field-groups/create          → FieldGroupController@create  (field-groups.create)
admin/content-types/{ct}/field-groups                 POST → @store                  (field-groups.store)
admin/content-types/{ct}/field-groups/{fg}/edit       → @edit                        (field-groups.edit)
admin/content-types/{ct}/field-groups/{fg}            PUT  → @update                 (field-groups.update)
admin/content-types/{ct}/field-groups/{fg}            DELETE → @destroy              (field-groups.destroy)
admin/content-types/{ct}/field-groups/reorder         POST → @reorder                (field-groups.reorder)
admin/content-types/{ct}/field-groups/{fg}/fields/create → FieldController@create   (field-groups.fields.create)
admin/content-types/{ct}/field-groups/{fg}/fields        POST → @store              (field-groups.fields.store)
admin/content-types/{ct}/field-groups/{fg}/fields/{f}/edit → @edit                  (field-groups.fields.edit)
admin/content-types/{ct}/field-groups/{fg}/fields/{f}    PUT → @update              (field-groups.fields.update)
admin/content-types/{ct}/field-groups/{fg}/fields/{f}    DELETE → @destroy          (field-groups.fields.destroy)
admin/content-types/{ct}/field-groups/{fg}/fields/reorder POST → @reorder           (field-groups.fields.reorder)
```

Content Types index: tambah tombol "Fields" → `field-groups.index`.

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_06_30_000003_create_field_groups_table.php` — **baru**
- `database/migrations/2026_06_30_000004_create_fields_table.php` — **baru**
- `app/Models/FieldGroup.php` — **baru**
- `app/Models/Field.php` — **baru**
- `app/Models/ContentType.php` — tambah `entries()` + `fieldGroups()` HasMany relations
- `app/Http/Requests/Admin/StoreFieldGroupRequest.php` — **baru**
- `app/Http/Requests/Admin/UpdateFieldGroupRequest.php` — **baru**
- `app/Http/Requests/Admin/StoreFieldRequest.php` — **baru**
- `app/Http/Requests/Admin/UpdateFieldRequest.php` — **baru**
- `app/Http/Controllers/Admin/FieldGroupController.php` — **baru** (CRUD + reorder + ownership check)
- `app/Http/Controllers/Admin/FieldController.php` — **baru** (CRUD + reorder + dual ownership check)
- `resources/views/backend/field-groups/*.blade.php` — **baru** (4 files)
- `resources/views/backend/fields/*.blade.php` — **baru** (3 files)
- `resources/views/backend/content-types/index.blade.php` — tambah tombol "Fields"
- `routes/admin.php` — 13 route baru nested di content-types
- `tests/Feature/Admin/FieldGroupTest.php` — **baru** (10 tests)
- `tests/Feature/Admin/FieldTest.php` — **baru** (10 tests)

### Impact
- DB: **2 tabel baru** `field_groups` + `fields`. Cascade delete berantai: hapus ContentType → hapus FieldGroups → hapus Fields.
- Routes: 13 route baru, semua di bawah middleware `['auth','admin']`.
- Frontend: tidak ada (field partials untuk entry form dibangun di B3).
- Security: compound unique index mencegah key duplicate per scope; ownership check `abort(404)` di kedua controller; `FieldTypeRegistry::keys()` membatasi tipe valid.

### Verification
- FieldGroup tests: **10/10 pass**, 26 assertions.
- Field tests: **10/10 pass**, 27 assertions.
- Full suite: **673/673 pass**, 3734 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
php artisan migrate:rollback --step=2  # drops fields, field_groups
git revert 6cc92fe
```

### Next
- **B3 — Field rendering engine** (no gate): Blade component + 18 type partials.
