# B4 — Content Entries Module

> **Task:** B4 — Content Entries CRUD admin module
> **Status:** ✅ DONE — 2026-07-01
> **Commit:** `f5cfa1c`
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new table `content_entries` — **APPROVED by owner 2026-07-01** (dengan catatan scalability review)
> **Skills:** database-architecture-skill · backend-skill · admin-dashboard-skill

---

## What was built

### `content_entries` table (migration `2026_07_01_000001`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| content_type_id | FK → content_types **RESTRICT** DELETE | tidak cascade — proteksi data dari hapus tipe tidak sengaja |
| title | varchar(500) nullable | null jika content type tidak `supports('title')` |
| slug | varchar(200) nullable | null jika tidak public/support slug |
| excerpt | text nullable | fallback meta description |
| status | varchar(20) default 'draft' | string, bukan ENUM — mudah tambah status baru tanpa ALTER TABLE |
| published_at | timestamp nullable | dipakai untuk scheduled + published date |
| author_id | FK → users **SET NULL** DELETE | user dihapus tidak menghapus kontennya |
| template | varchar(100) nullable | override template per-entry |
| sort_order | unsigned int default 0 | ordering manual dalam content type |
| data | json nullable | semua custom field values (key → value) |
| seo | json nullable | SEO meta: title, description, og_image, canonical |
| timestamps + softDeletes | | |
| **UNIQUE(content_type_id, slug)** | compound unique | slug namespace per content type, bukan global |
| index(content_type_id, status, published_at) | | kueri paling umum: entries aktif suatu tipe urut tanggal |
| index(author_id) | | filter by author |

### Keputusan Desain Scalability (disetujui owner)

1. **FK RESTRICT, bukan CASCADE** — Soft-delete ContentType tidak menyentuh entries. Hard-delete diblokir di app layer dengan pesan error jelas (`ContentTypeController@forceDelete` kini cek `entries()->withTrashed()->exists()`).

2. **`data` JSON, bukan kolom dinamis** — Field baru ditambah kapan saja tanpa migrasi. Entry lama tidak punya key tersebut → di-treat sebagai null. Field dihapus meninggalkan orphaned key di JSON → harmless, aplikasi tidak membaca key yang tidak ada di definisi.

3. **`status varchar`, bukan MySQL ENUM** — Tambah status baru (`pending_review`, dll.) hanya butuh perubahan konstanta model, bukan `ALTER TABLE` yang mengunci tabel.

4. **Compound unique `(content_type_id, slug)`** — Hotel dan Villa bisa sama-sama punya slug `bintan-resort` tanpa konflik. MySQL unique index mengizinkan multiple NULL, jadi entries tanpa slug (`slug = null`) bisa banyak.

5. **`author_id SET NULL`** — Hapus user tidak menyebabkan konten mereka ikut terhapus. Content dipertahankan, author_id menjadi null.

6. **`seo` JSON terpisah** — SEO metadata dapat berkembang independen dari custom fields. Tidak ada percampuran concern.

### `ContentEntry` Model (`app/Models/ContentEntry.php`)

```php
// Status constants (string, not enum)
STATUS_DRAFT | STATUS_PUBLISHED | STATUS_SCHEDULED | STATUS_ARCHIVED
STATUSES = [...]

// Field value access
fieldValue(string $key): mixed        // baca dari JSON data bag
setFieldValue(string $key, mixed $v)  // tulis satu nilai (save() terpisah)

// Status helpers
isPublished() | isDraft() | isScheduled()
statusBadgeClass(): string  // → admin-badge-{success|info|warning|secondary}

// Scopes
scopePublished()  // status=published AND published_at <= now()
scopeForType(ContentType $type)
scopeOrdered()    // sort_order ASC, published_at DESC
```

### FormRequests

**StoreContentEntryRequest**
- `prepareForValidation`: auto-generate slug dari title (jika type supports slug); bersihkan array checkbox dari null/empty-string yang ditinggalkan `ConvertEmptyStringsToNull` middleware
- `status` validasi: `Rule::in(ContentEntry::STATUSES)`
- `published_at` validasi: `required_if:status,scheduled`
- `slug` validasi: unique per content_type_id
- `data.*`: `nullable` — per-field validation ditambahkan di B5

**UpdateContentEntryRequest** — sama dengan `->ignore($entryId)` pada slug unique rule.

### Controller (`ContentEntryController`)

`index`, `create`, `store`, `edit`, `update`, `destroy` (soft delete), `restore`, `forceDelete`.

`index`: filter by `?status=` query string + search by title/slug; eager load `author`; paginate(20).

`create`/`edit`: load field groups + fields via `contentType->fieldGroups()->with(['fields'])->ordered()->get()`.

`authorizeEntry()` private method: `abort(404)` jika `entry->content_type_id !== contentType->id`.

### `ContentTypeController` Guard (updated)

```php
public function forceDelete(int $id)
{
    $contentType = ContentType::onlyTrashed()->findOrFail($id);

    if ($contentType->entries()->withTrashed()->exists()) {
        return redirect()->back()->with('error', 'Cannot permanently delete ...');
    }

    $contentType->forceDelete();
}
```

### Admin Views

| File | Deskripsi |
|------|-----------|
| `backend/content-entries/index.blade.php` | Tabel entries dengan filter tab status (All/Draft/Published/Scheduled/Archived) + search + trash table |
| `backend/content-entries/form.blade.php` | Form utama — core fields (title/slug/excerpt), loop field groups + fields via `<x-admin.field-input>`, SEO section, publish panel (status select + published_at + template + sort_order) |
| `backend/content-entries/create.blade.php` + `edit.blade.php` | Wrapper extends layouts.admin |

**Form.blade.php** adalah titik pertemuan B2 + B3 + B4:
- Mengambil `$groups` (FieldGroup collection) dari controller
- Loop `$group->fields` → `<x-admin.field-input :field="$field" :value="$entry->fieldValue($field->key)" name-prefix="data" />`
- Jika content type tidak punya field groups → tampilkan CTA link ke field-groups admin

### Routes

```
admin/content-types/{ct}/entries                    GET  → @index         (entries.index)
admin/content-types/{ct}/entries/create             GET  → @create        (entries.create)
admin/content-types/{ct}/entries                    POST → @store         (entries.store)
admin/content-types/{ct}/entries/{entry}/edit       GET  → @edit          (entries.edit)
admin/content-types/{ct}/entries/{entry}            PUT  → @update        (entries.update)
admin/content-types/{ct}/entries/{entry}            DELETE → @destroy     (entries.destroy)
admin/content-types/{ct}/entries/{id}/restore       PATCH → @restore      (entries.restore)
admin/content-types/{ct}/entries/{id}/force-delete  DELETE → @forceDelete (entries.force-delete)
```

Content Types index: tambah tombol "Entries" (primary) → `entries.index`.

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_07_01_000001_create_content_entries_table.php` — **baru**
- `app/Models/ContentEntry.php` — **baru**
- `app/Models/ContentType.php` — `entries()` HasMany ditambahkan (sebelumnya disatukan di B2, kini dipakai penuh)
- `app/Http/Requests/Admin/StoreContentEntryRequest.php` — **baru**
- `app/Http/Requests/Admin/UpdateContentEntryRequest.php` — **baru**
- `app/Http/Controllers/Admin/ContentEntryController.php` — **baru**
- `app/Http/Controllers/Admin/ContentTypeController.php` — `forceDelete` kini cek entries sebelum hapus permanen
- `resources/views/backend/content-entries/*.blade.php` — **baru** (4 files)
- `resources/views/backend/content-types/index.blade.php` — tambah tombol "Entries"
- `routes/admin.php` — 8 route baru nested di content-types
- `tests/Feature/Admin/ContentEntryTest.php` — **baru** (14 tests)

### Impact
- DB: **1 tabel baru** `content_entries`. FK RESTRICT pada content_type_id, SET NULL pada author_id. Tidak ada cascade delete ke entries.
- Routes: 8 route baru, semua middleware `['auth','admin']`.
- Frontend: tidak ada (public routing di B10).
- Security: `StoreContentEntryRequest` + `UpdateContentEntryRequest` validasi semua input; compound unique mencegah slug duplicate per tipe; `authorizeEntry()` abort(404) jika entry milik tipe berbeda; `forceDelete` ContentType diblokir jika ada entries.

### Verification
- B4 tests: **14/14 pass**, 40 assertions. Covers: auth guard, index filtering, field groups di create form, store dengan auto-slug, checkbox cleanup, slug unique per-type, cross-type slug boleh sama, scheduled requires published_at, update data JSON, 404 cross-type, soft delete, restore, force delete entry, forceDelete ContentType diblokir.
- Full suite: **701/701 pass**, 3831 assertions. PHPStan level 5: **0 errors**.

### Bug Fixed During Task
`ConvertEmptyStringsToNull` middleware mengubah `''` menjadi `null` sebelum `prepareForValidation`. Filter `fn ($s) => $s !== ''` tidak menangkap null. Fix: `fn ($s) => $s !== null && $s !== ''`.

### Rollback
```bash
php artisan migrate:rollback --step=1  # drops content_entries
git revert f5cfa1c
```

### Next
- **B5 — Per-Field Validation Resolver** (no gate): validasi dinamis per field type di entry form.
