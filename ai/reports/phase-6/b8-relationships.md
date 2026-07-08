# B8 — Relationships

> **Task:** B8 — Relationships (entry ↔ entry linking)
> **Status:** ✅ DONE — 2026-07-01
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new table `content_entry_relations` — **APPROVED by owner 2026-07-01** ("ok move ke B8")
> **Skills:** database-architecture-skill · backend-skill

---

## What was built

Field type `relationship` sudah ada sejak A4 (katalog) dan menyimpan array ID entry
target inline di `content_entries.data` JSON. B8 menambahkan **proyeksi ternormalisasi**
dari nilai tersebut ke tabel `content_entry_relations`, sehingga query relasi
(forward & reverse) bisa terindeks tanpa men-scan JSON. Polanya identik dengan
sidecar index B6: cache proyeksi yang disinkron saat save via observer.

### `content_entry_relations` table (migration `2026_07_01_000004`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| source_entry_id | FK → content_entries **CASCADE** | entri pemilik field relationship |
| target_entry_id | FK → content_entries **CASCADE** | entri yang ditaut |
| field_key | varchar(100) | field relationship mana yang menghasilkan link ini |
| sort_order | uint default 0 | posisi ID dalam array data (urutan dipertahankan) |
| index(source_entry_id, field_key) | | forward lookup: "target entri X via field Y" |
| index(target_entry_id) | | reverse lookup: "entri mana yang menaut ke Z" |
| UNIQUE(source_entry_id, field_key, target_entry_id) | `cer_source_field_target_unique` | satu link per (source, field, target) |
| **No timestamps** | | pure projection cache (seperti content_entry_index) |

#### Keputusan Desain

**CASCADE di kedua sisi** — Baris relasi adalah derivative/cache. Menghapus paksa
source ATAU target menghapus link otomatis, jadi tidak ada dangling reference. Ini
berbeda dengan `content_entries` yang RESTRICT dari content_types (data asli harus
dilindungi), tapi konsisten dengan B6/B7 pivot yang disposable.

**Filter ke entri yang ada** — `target_entry_id` adalah FK sungguhan. Jika array
data berisi ID yang tidak ada (typo/stale), insert akan gagal FK. Service menyaring
ID ke hanya yang benar-benar ada di `content_entries` (termasuk soft-deleted, karena
row-nya masih ada) sebelum insert. Ini juga mencegah self-link (source = target).

**Dua index terpisah** — forward (source+field) dan reverse (target) melayani dua
arah query berbeda: "apa yang ditaut entri ini" vs "siapa yang menaut ke entri ini".

---

### `ContentEntryRelation` Model (`app/Models/ContentEntryRelation.php`)

- `$timestamps = false`
- `source()` / `target()` BelongsTo → ContentEntry

### `ContentEntryRelationService` (`app/Support/ContentEntryRelationService.php`)

**`sync(ContentEntry $entry): void`**

1. Load contentType. Ambil semua field bertipe `relationship` untuk content type itu.
2. Per field: baca `entry->fieldValue($key)` → normalisasi ke list int positif unik.
3. Saring ke ID yang benar-benar ada (`existingTargetIds`) — buang dangling & self-link.
4. Hapus target yang tidak lagi ada untuk (source, field_key).
5. Upsert tiap target dengan `sort_order` = posisi dalam array.
6. Hapus baris untuk field_key yang bukan lagi field relationship (stale).

**`remove(ContentEntry $entry): void`** — hapus semua outgoing relation (CASCADE
menangani hard delete; ini untuk de-proyeksi eksplisit).

### `ContentEntryObserver` (modified)

Sekarang inject dua service:
```php
public function saved(ContentEntry $entry): void
{
    $this->indexService->sync($entry);
    $this->relationService->sync($entry);
}
```
Sama untuk `restored()`. Tidak ada risiko rekursi observer — service menulis ke
tabel `content_entry_relations` (via `ContentEntryRelation`), bukan menyimpan ulang
model ContentEntry.

### `ContentEntry` Model (modified)

- `relatedEntries()` BelongsToMany — entri yang **ditaut** entri ini (forward),
  di-order oleh `sort_order`, dengan pivot `field_key` + `sort_order`.
- `relatingEntries()` BelongsToMany — reverse: entri yang **menaut ke** entri ini.

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_07_01_000004_create_content_entry_relations_table.php` — **baru**
- `app/Models/ContentEntryRelation.php` — **baru** (no-timestamp model)
- `app/Support/ContentEntryRelationService.php` — **baru** (projection + FK-safe filter)
- `app/Observers/ContentEntryObserver.php` — sync relations alongside index
- `app/Models/ContentEntry.php` — `relatedEntries()` + `relatingEntries()`
- `tests/Feature/Phase6/B8RelationsTest.php` — **baru** (14 tests)

### Impact
- **DB:** 1 tabel baru `content_entry_relations`. CASCADE dua sisi ke content_entries.
  Tidak ada perubahan ke tabel lain.
- **Routes:** tidak ada perubahan. Relasi dikelola lewat field relationship di form
  entry yang sudah ada (B3/B4) — user memilih entri target, service memproyeksikan.
- **Frontend:** tidak ada perubahan tampilan. Query helper `relatedEntries()` siap
  dipakai controller frontend di B11-B12.
- **Security:** tidak ada input user baru. Service hanya membaca `entry->data` dan
  menulis proyeksi. Filter existingTargetIds mencegah FK violation & self-link.
- **Performance:** setiap `ContentEntry::save()` kini memicu extra query untuk field
  relationship + existence check + upserts. Untuk CMS write-jarang, acceptable.

### Verification
- B8 tests: **14/14 pass**, 25 assertions:
  - projeksi baris relasi + sort_order preserve urutan array
  - target tidak-ada disaring (tidak ada FK error)
  - self-link ditolak
  - update sync (tambah/hapus target), clearing menghapus semua
  - field non-relationship tidak menghasilkan baris
  - multiple relationship field di-key terpisah
  - CASCADE force-delete source & target (dua arah)
  - restore re-sync
  - forward (`relatedEntries`) + reverse (`relatingEntries`) query
  - entry tanpa field group → 0 relasi
- Full suite: **761/761 pass**, 3951 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
php artisan migrate:rollback --step=1  # drops content_entry_relations
git revert 6921f77
```

### Next
- **B9 — Phase 4 reuse wiring** (tanpa schema baru): hubungkan content entries ke
  sistem existing — revisions, content scheduling, audit log, SEO meta manager.
