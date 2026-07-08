# B6 — Sidecar Index

> **Task:** B6 — Query sidecar + indexing (`content_entry_index`)
> **Status:** ✅ DONE — 2026-07-01
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new table `content_entry_index` — **APPROVED by owner 2026-07-01** ("lanjutkan B6")
> **Skills:** database-architecture-skill · backend-skill

---

## What was built

### `content_entry_index` table (migration `2026_06_30_224940`)

Tabel proyeksi (projection cache) yang menyimpan nilai field `is_filterable = true` dari `content_entries.data` JSON ke kolom terindeks terpisah. Ini memungkinkan filtering dan sorting entri tanpa harus men-scan kolom JSON.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| content_entry_id | FK → content_entries **CASCADE** DELETE | index row adalah disposable — dihapus otomatis bersama entri |
| field_key | varchar(100) | key field yang diindeks |
| value_string | varchar(500) nullable | untuk tipe string, select, email, url, toggle (bool), relationship (CSV) |
| value_number | decimal(15,6) nullable | untuk tipe number/float/integer |
| value_date | date nullable | untuk tipe date/datetime |
| **UNIQUE(content_entry_id, field_key)** | compound unique | satu baris per entry × field |
| index(field_key, value_string) | | kueri filter string cepat |
| index(field_key, value_number) | | kueri filter numerik cepat |
| index(field_key, value_date) | | kueri filter tanggal cepat |
| **No timestamps** | | tabel cache/projection — tidak butuh audit time |

#### Keputusan Desain

**CASCADE, bukan RESTRICT** — Berbeda dengan `content_entries` yang RESTRICT dari `content_types`, baris index adalah derivative/cache. Jika entry dihapus paksa, MySQL menghapus baris index otomatis. Tidak ada data yang terancam hilang.

**3 value columns, bukan 1** — Satu kolom `value` (varchar semua) akan kehilangan kemampuan filtering numeric dan date-range yang akurat. Dengan 3 kolom terpisah, `WHERE value_number BETWEEN 1 AND 5` atau `WHERE value_date >= '2026-01-01'` bekerja secara native tanpa CAST.

**No timestamps** — Ini adalah projection, bukan entity. Tidak ada use-case untuk "kapan index row ini dibuat/diubah" — yang penting adalah nilainya sesuai dengan entri.

---

### `ContentEntryIndex` Model (`app/Models/ContentEntryIndex.php`)

Model Eloquent minimal:
- `$timestamps = false`
- `$table = 'content_entry_index'`
- Casts: `value_number → float`, `value_date → date` (Carbon)
- `entry()` BelongsTo ContentEntry

---

### `ContentEntryIndexService` (`app/Support/ContentEntryIndexService.php`)

Service class yang melakukan proyeksi nilai field ke sidecar.

**`sync(ContentEntry $entry): void`**

1. Load relasi `contentType` jika belum di-load (`loadMissing`)
2. Query semua filterable fields untuk content type tersebut (1 query dengan `whereHas`)
3. Per field: baca `entry->fieldValue($key)`, project ke kolom yang tepat via `projectValue()`
4. Upsert (`updateOrCreate`) baris index untuk setiap field yang nilainya non-null
5. Delete baris stale (field tidak lagi filterable, atau nilainya kini null)

**`projectValue(string $type, mixed $raw): ?array|null`**

Memetakan nilai raw ke array kolom berdasarkan `cast` dari katalog `config/field-types.php`:

| Cast | Kolom | Notes |
|------|-------|-------|
| `string` | value_string | default untuk text, email, url, select, radio, color |
| `float` / `integer` | value_number | `is_numeric($raw) ? (float) $raw : null` |
| `date` / `datetime` | value_date | `new \DateTime($raw)->format('Y-m-d')` |
| `boolean` | value_string | `'1'` atau `'0'` |
| `array` | value_string | CSV IDs untuk relationship; `implode(',', $raw)` |

Nilai `null`, `''`, atau array kosong → return `null` → tidak membuat baris index.

**`remove(ContentEntry $entry): void`**

Helper untuk menghapus semua baris index suatu entry (dipanggil manual jika diperlukan, CASCADE menangani hard delete otomatis).

---

### `ContentEntryObserver` (`app/Observers/ContentEntryObserver.php`)

Observer yang menghubungkan model events ke service:

```php
public function saved(ContentEntry $entry): void     // create + update
public function restored(ContentEntry $entry): void  // soft delete restore
```

**Tidak ada handler untuk `deleted`** (soft delete) — baris index **tetap ada** saat entry di-soft-delete. Ini berguna untuk mempertahankan searchability di trash view. Pada `forceDelete`, MySQL CASCADE menghapus baris otomatis.

---

### `AppServiceProvider` (modified)

```php
ContentEntry::observe(ContentEntryObserver::class);
```

Ditambahkan sebelum observer lainnya di `boot()`.

---

## Field Type Projection Map (lengkap)

| Field Type | is_filterable | cast | Kolom Index |
|-----------|:---:|------|-------------|
| text | ✅ | string | value_string |
| textarea | ✅ | string | value_string |
| richtext | — | string | (tidak diindeks) |
| number | ✅ | float | value_number |
| email | ✅ | string | value_string |
| url | ✅ | string | value_string |
| toggle | ✅ | boolean | value_string ('1'/'0') |
| select | ✅ | string | value_string |
| radio | ✅ | string | value_string |
| checkbox | — | array | (tidak diindeks) |
| date | ✅ | date | value_date |
| datetime | ✅ | datetime | value_date |
| image | — | integer | (tidak diindeks) |
| gallery | — | array | (tidak diindeks) |
| file | — | integer | (tidak diindeks) |
| relationship | ✅ | array | value_string (CSV IDs) |
| color | — | string | (tidak diindeks) |
| repeater | — | array | (tidak diindeks) |

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_06_30_224940_create_content_entry_index_table.php` — **baru**
- `app/Models/ContentEntryIndex.php` — **baru** (no-timestamp model)
- `app/Support/ContentEntryIndexService.php` — **baru** (projection + stale cleanup)
- `app/Observers/ContentEntryObserver.php` — **baru** (saved + restored hooks)
- `app/Providers/AppServiceProvider.php` — register ContentEntry observer
- `tests/Feature/Phase6/B6SidecarIndexTest.php` — **baru** (13 tests)

### Impact
- DB: **1 tabel baru** `content_entry_index`. CASCADE pada content_entry_id. Tidak ada impact ke tabel lain.
- Routes: tidak ada perubahan.
- Frontend: tidak ada perubahan tampilan.
- Security: tidak ada input user baru. Observer hanya membaca `entry->data` dan menulis ke index table.
- Performance: setiap `ContentEntry::save()` kini memicu 1 extra query (filterableFields) + N upserts (N = jumlah filterable fields). Untuk CMS dengan writes yang tidak sering, ini acceptable.

### Verification
- B6 tests: **13/13 pass**, 23 assertions.
  - text/number/date/toggle indexing — masing-masing verifikasi kolom yang tepat
  - non-filterable field tidak diindeks
  - null value tidak menghasilkan baris
  - update memperbarui baris yang ada
  - null value cleanup menghapus baris stale
  - multiple fields menghasilkan multiple rows
  - hard delete → CASCADE menghapus rows
  - soft delete → restore → rows di-re-sync
  - entry tanpa field groups → 0 rows
  - relationship array → CSV string di value_string
- Full suite: **728/728 pass**, 3878 assertions. PHPStan level 5: **0 errors**.

### Bug Fixed During Task
`assertDatabaseHas` dengan `value_date: '2026-08-15'` gagal karena SQLite menyimpan DATE column sebagai `'2026-08-15 00:00:00'`. Fix: gunakan model-based assertion dengan `$row->value_date->toDateString()` untuk perbandingan format-agnostic.

### Rollback
```bash
php artisan migrate:rollback --step=1  # drops content_entry_index
git revert <commit-hash>
```

### Next
- **B7 — Taxonomies & Terms** (⚠️ schema gate: new tables `taxonomies`, `terms`, `content_entry_term`): admin CRUD untuk taksonomi + term, dan attachment ke content entries.
