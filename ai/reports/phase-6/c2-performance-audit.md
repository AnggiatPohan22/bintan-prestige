# C2 — Performance Audit (Stage C Release Audit)

> **Task:** C2 — Performance Audit
> **Status:** ✅ DONE — 2026-07-07
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (audit; dua optimasi N+1 di kode Phase 6)
> **Skills:** performance-skill · testing-qa-skill

---

## Ringkasan

Audit performa route publik Phase 6 (archive, single, content_query, content_field).
Dua **N+1 nyata** ditemukan dan diperbaiki, index database diverifikasi, dan tidak
ada bundle asset baru. Query count kini **konstan** terhadap jumlah entri/block
(diproteksi regression test).

---

## 1. N+1 #1 — Archive listing → **FIXED**

**Gejala:** `archive()` mem-paginate entri, lalu tiap kartu memanggil
`ContentEntry::publicUrl()` yang melakukan `loadMissing('contentType')` → **1 query
per entri** (O(N)). 15 entri = 15 query `content_types`.

**Fix:** eager-load `->with('contentType')` di query archive → `publicUrl()` melihat
relasi sudah dimuat → **1 batched query** untuk semua kartu.

**File:** `app/Http/Controllers/Frontend/ContentEntryController.php`

## 2. N+1 #2 — content_field blocks → **FIXED**

**Gejala:** tiap block `content_field` memanggil `findField()` yang query tabel
`fields` → **1 query per block** (O(N)). Body dengan 6 content_field = 6 query.

**Fix:** `ContentFieldResolver` kini **memoize** definisi field per `content_type_id`
(cache in-memory per request, `Field::…->get()->keyBy('key')`). Semua content_field
block dari content type yang sama → **1 query fields**.

**File:** `app/Support/ContentFieldResolver.php`

## 3. content_query — sudah efisien

`ContentQueryResolver` sudah eager-load `->with('contentType')` (dipakai `publicUrl()`
di kartu). Query per block config berbeda memang perlu 1 query masing-masing
(unavoidable, konfigurasi berbeda) — bukan N+1.

## 4. Index Database (diverifikasi)

| Tabel | Index | Melayani |
|-------|-------|----------|
| `content_entries` | `(content_type_id, status, published_at)` | query archive/published (scope utama) |
| `content_entries` | unique `(content_type_id, slug)` | resolusi single by slug |
| `content_entry_index` | unique `(content_entry_id, field_key)` + 3× `(field_key, value_*)` | filter sidecar (B6) |
| `content_entry_relations` | `(source_entry_id, field_key)` + `target_entry_id` | forward + reverse relation (B8) |
| `content_entry_revisions` | `(content_entry_id, revision_number)` | riwayat revisi (B9) |

Semua query hot-path Phase 6 ter-cover index.

## 5. Asset Bundle

Block `content_query` + `content_field` **server-rendered** (Blade) — **tidak ada
bundle JS/CSS baru**. `git diff` pada `resources/js` + `resources/css` untuk seluruh
Phase 6: kosong. Builder entry me-reuse asset Phase 5.

---

## Report (AGENTS.md §11)

### Changed
- `app/Http/Controllers/Frontend/ContentEntryController.php` — eager-load contentType (archive)
- `app/Support/ContentFieldResolver.php` — memoize field lookups per content type
- `tests/Feature/Phase6/C2PerformanceTest.php` — **baru** (2 query-count regression tests)
- `ai/reports/phase-6/c2-performance-audit.md` — **baru** (laporan ini)

### Impact
- **DB:** tidak ada perubahan schema (index sudah ada sejak B4/B6/B8/B9).
- **Performance:** query archive + entry body kini **O(1)** terhadap jumlah entri/block.
- **Routes/Frontend:** tidak ada perubahan perilaku, hanya jumlah query berkurang.

### Verification
- C2 tests: **2/2 pass**:
  - archive dengan 15 entri → `content_types` query ≤ 3 (bukan 15)
  - body dengan 6 content_field block → `fields` query = **1** (cached)
- Index sidecar/hot-path: diverifikasi dari migrasi.
- Full suite: **838/838 pass**. PHPStan level 5: **0 errors**.

### Next
- **C3 — Functional Smoke Test**: HTTP route checks (archive/single/draft-404/admin-guard),
  block registry + view files (content_query/content_field), migrasi Phase 6 confirmed Ran,
  regresi Phase 1–5 intact.
