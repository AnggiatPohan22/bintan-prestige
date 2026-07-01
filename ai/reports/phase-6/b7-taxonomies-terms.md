# B7 — Taxonomies & Terms

> **Task:** B7 — Taxonomies & Terms
> **Status:** ✅ DONE — 2026-07-01
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ 3 new tables — **APPROVED by owner 2026-07-01** ("lanjut ke B7")
> **Skills:** database-architecture-skill · backend-skill

---

## What was built

### Skema Database (3 tabel baru)

#### `taxonomies` (migration `2026_07_01_000001`)

Tabel definisi sistem klasifikasi konten (kategori, tag, region, dll).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| slug | varchar unique | identifier unik, e.g. `category`, `region` |
| label_singular | varchar | e.g. `Category` |
| label_plural | varchar | e.g. `Categories` |
| description | text nullable | deskripsi opsional |
| is_hierarchical | boolean default false | true = kategori bertingkat, false = flat tag |
| content_type_ids | json nullable | NULL = semua content types; array ID = dibatasi |
| sort_order | uint default 0 | urutan di UI |
| timestamps | | |
| deleted_at | softDeletes | |

#### `terms` (migration `2026_07_01_000002`)

Nilai-nilai dalam satu taksonomi (e.g. "North Bintan", "Adventure").

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| taxonomy_id | FK → taxonomies CASCADE | jika taksonomi dihapus paksa, terms ikut terhapus |
| parent_id | uint nullable (no FK) | self-referential untuk hierarki; PHP handle orphan cleanup |
| name | varchar | nama tampilan |
| slug | varchar | unik PER taksonomi (bukan global) |
| description | text nullable | |
| sort_order | uint default 0 | |
| timestamps | | |
| deleted_at | softDeletes | |
| UNIQUE(taxonomy_id, slug) | | slug unik dalam satu taksonomi |

**Keputusan `parent_id` tanpa MySQL FK:** Self-referential FK di MySQL bisa kompleks saat soft-delete/restore karena anak-anak bisa merujuk parent yang sudah di-soft-delete. PHP handle cleanup saat taxonomy/term dihapus.

#### `content_entry_term` (migration `2026_07_01_000003`)

Pivot many-to-many antara content entries dan terms.

| Column | Type | Notes |
|--------|------|-------|
| content_entry_id | FK → content_entries CASCADE | jika entry dihapus paksa, pivot ikut hilang |
| term_id | FK → terms CASCADE | jika term dihapus paksa, pivot ikut hilang |
| PRIMARY KEY (content_entry_id, term_id) | | composite PK |

**Tidak ada timestamps** — pivot ini murni asosiasi, tidak butuh audit waktu.

---

### Models

#### `Taxonomy` (`app/Models/Taxonomy.php`)
- `terms()` HasMany → Term
- `rootTerms()` HasMany → Term (whereNull parent_id)
- `appliesToType(int $contentTypeId): bool` — cek apakah taksonomi berlaku untuk content type tertentu
- `scopeOrdered()` — sort by sort_order + label_singular

#### `Term` (`app/Models/Term.php`)
- `taxonomy()` BelongsTo → Taxonomy
- `parent()` BelongsTo → Term (nullable)
- `children()` HasMany → Term (via parent_id)
- `entries()` BelongsToMany → ContentEntry via `content_entry_term`
- `scopeRoots()` — whereNull parent_id
- `scopeOrdered()` — sort by sort_order + name

#### `ContentEntry` (modified)
- Ditambahkan `terms()` BelongsToMany → Term via `content_entry_term`

---

### Controllers

#### `TaxonomyController`
CRUD lengkap: index, create, store, edit, update, destroy (soft), restore, forceDelete.

`destroy()` — soft-delete terms sebelum soft-delete taxonomy (cascade PHP-level).
`restore()` — restore taxonomy + terms yang di-soft-delete dalam window 5 detik.
`forceDelete()` — MySQL CASCADE handle penghapusan terms + pivot rows.

#### `TermController`
CRUD lengkap nested di bawah taxonomy: index, create, store, edit, update, destroy (soft), restore, forceDelete.

`destroy()` — soft-delete children terms sebelum soft-delete term parent.
`authorizeTerm()` — verifikasi `term.taxonomy_id === taxonomy.id`.

#### `ContentEntryController` (modified)
- `create()` dan `edit()`: query `taxonomiesForType()` — semua taksonomi yang berlaku untuk content type ini (whereNull OR whereJsonContains)
- `store()`: setelah entry dibuat, `$entry->terms()->sync($termIds)`
- `update()`: `$entry->terms()->sync($termIds)` (sync otomatis detach yang lama)
- Private `taxonomiesForType()` — eager-load terms dan children

---

### Form Requests (4 baru + 2 diupdate)

- `StoreTaxonomyRequest` — auto-generate slug dari label_singular; content_type_ids di-cast ke int[]
- `UpdateTaxonomyRequest` — sama, dengan `->ignore($taxonomyId)` pada unique rule
- `StoreTermRequest` — auto-generate slug dari name; empty parent_id → null
- `UpdateTermRequest` — sama dengan `->ignore($termId)` pada unique rule
- `StoreContentEntryRequest` — ditambahkan `terms` + `terms.*` rules
- `UpdateContentEntryRequest` — sama

---

### Routes

```
GET    admin/taxonomies                                 admin.taxonomies.index
GET    admin/taxonomies/create                          admin.taxonomies.create
POST   admin/taxonomies                                 admin.taxonomies.store
GET    admin/taxonomies/{taxonomy}/edit                 admin.taxonomies.edit
PUT    admin/taxonomies/{taxonomy}                      admin.taxonomies.update
DELETE admin/taxonomies/{taxonomy}                      admin.taxonomies.destroy
PATCH  admin/taxonomies/{id}/restore                    admin.taxonomies.restore
DELETE admin/taxonomies/{id}/force-delete               admin.taxonomies.force-delete

GET    admin/taxonomies/{taxonomy}/terms                admin.taxonomies.terms.index
GET    admin/taxonomies/{taxonomy}/terms/create         admin.taxonomies.terms.create
POST   admin/taxonomies/{taxonomy}/terms                admin.taxonomies.terms.store
GET    admin/taxonomies/{taxonomy}/terms/{term}/edit    admin.taxonomies.terms.edit
PUT    admin/taxonomies/{taxonomy}/terms/{term}         admin.taxonomies.terms.update
DELETE admin/taxonomies/{taxonomy}/terms/{term}         admin.taxonomies.terms.destroy
PATCH  admin/taxonomies/{taxonomy}/terms/{id}/restore   admin.taxonomies.terms.restore
DELETE admin/taxonomies/{taxonomy}/terms/{id}/force-delete admin.taxonomies.terms.force-delete
```

---

### Views (9 baru + 2 diupdate)

| File | Keterangan |
|------|-----------|
| `backend/taxonomies/index.blade.php` | Daftar aktif + trash dengan tombol Terms/Edit/Delete |
| `backend/taxonomies/create.blade.php` | Wrapper → form partial |
| `backend/taxonomies/edit.blade.php` | Wrapper → form partial |
| `backend/taxonomies/form.blade.php` | Form: slug, labels, description, is_hierarchical, content type checkboxes |
| `backend/taxonomies/terms/index.blade.php` | Daftar terms + trash |
| `backend/taxonomies/terms/create.blade.php` | Wrapper → form partial |
| `backend/taxonomies/terms/edit.blade.php` | Wrapper → form partial |
| `backend/taxonomies/terms/form.blade.php` | Form: name, slug, description, parent select (jika hierarchical) |
| `backend/content-entries/partials/taxonomy-terms.blade.php` | Picker: flat = tag chips, hierarchical = tree dengan indentasi |
| `backend/content-entries/form.blade.php` | **diupdate** — `@include` taxonomy-terms partial |
| `components/admin/sidebar.blade.php` | **diupdate** — link "Taxonomies" + activeGroup rule |

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_07_01_000001_create_taxonomies_table.php` — **baru**
- `database/migrations/2026_07_01_000002_create_terms_table.php` — **baru**
- `database/migrations/2026_07_01_000003_create_content_entry_term_table.php` — **baru**
- `app/Models/Taxonomy.php` — **baru**
- `app/Models/Term.php` — **baru**
- `app/Models/ContentEntry.php` — tambah `terms()` BelongsToMany
- `app/Http/Requests/Admin/StoreTaxonomyRequest.php` — **baru**
- `app/Http/Requests/Admin/UpdateTaxonomyRequest.php` — **baru**
- `app/Http/Requests/Admin/StoreTermRequest.php` — **baru**
- `app/Http/Requests/Admin/UpdateTermRequest.php` — **baru**
- `app/Http/Requests/Admin/StoreContentEntryRequest.php` — tambah terms validation
- `app/Http/Requests/Admin/UpdateContentEntryRequest.php` — tambah terms validation
- `app/Http/Controllers/Admin/TaxonomyController.php` — **baru**
- `app/Http/Controllers/Admin/TermController.php` — **baru**
- `app/Http/Controllers/Admin/ContentEntryController.php` — taxonomy injection + terms sync
- `routes/admin.php` — 16 taxonomy + term routes baru
- `resources/views/components/admin/sidebar.blade.php` — Taxonomies link
- `resources/views/backend/taxonomies/` (5 files baru)
- `resources/views/backend/taxonomies/terms/` (4 files baru)
- `resources/views/backend/content-entries/partials/taxonomy-terms.blade.php` — **baru**
- `resources/views/backend/content-entries/form.blade.php` — @include taxonomy-terms partial
- `tests/Feature/Phase6/B7TaxonomiesTest.php` — **baru** (15 tests)

### Impact
- **DB:** 3 tabel baru. `taxonomies` dan `terms` menggunakan softDeletes. Pivot `content_entry_term` menggunakan CASCADE di kedua sisi — tidak ada data yang terancam dari operasi cascade karena pivot adalah asosiasi yang replaceable.
- **Routes:** 16 route baru di bawah `admin/taxonomies/*`.
- **Frontend admin:** sidebar "Content" group mendapat link "Taxonomies". Form content entry mendapat section "Taxonomy Terms" berisi checkboxes untuk setiap taksonomi yang berlaku.
- **Security:** semua input divalidasi via FormRequest. `authorizeTerm()` verifikasi ownership. Tidak ada raw SQL.
- **Performance:** `create()` dan `edit()` entry kini menjalankan 1 query tambahan (`taxonomiesForType`) + eager-load terms. Untuk CMS dengan sedikit taxonomies ini acceptable.

### Verification
- B7 tests: **15/15 pass**, 37 assertions
  - Taxonomy CRUD (create, unique slug, update, soft-delete + restore)
  - Term CRUD (create, slug unique per taxonomy, same slug allowed different taxonomy, soft-delete + restore)
  - Cascade force-delete taxonomy → terms terhapus
  - Cascade force-delete term → pivot rows terhapus
  - Term attachment saat entry dibuat
  - Term sync saat entry diupdate (lama detach, baru attach)
  - Term detach saat update tanpa terms
  - Validasi rejects invalid term ID (exists:terms,id)
  - Hierarchical parent_id
- Full suite: **743/743 pass**, 3915 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
php artisan migrate:rollback --step=3  # drops content_entry_term, terms, taxonomies
git revert 59d30a8
```

### Next
- **B8 — Relationships** (⚠️ schema gate: tabel `content_entry_relations`) — many-to-many antar content entries lintas content type. Berguna untuk "related hotels", "related tours", dll.
