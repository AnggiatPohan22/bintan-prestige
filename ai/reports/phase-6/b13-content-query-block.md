# B13 — Builder Bridge: `content_query` Block

> **Task:** B13 — Builder bridge: `content_query` block
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (tanpa schema baru)
> **Skills:** page-builder-skill · backend-skill

---

## Ringkasan

Block builder baru **`content_query`** yang mengueri & menampilkan daftar content
entries yang published dari sebuah content type (mis. "3 blog terbaru"). Bisa
dipasang di **page builder** maupun di **body entry** (B10) — menyambungkan Phase 6
kembali ke visual builder Phase 5.

---

## Cara kerja (pola resolve-before-Blade)

Konsisten dengan block relasional lain (products_grid/faq): query di-*resolve* di
PHP sebelum Blade, hasilnya diset ke `$block->resolvedEntries`, render partial hanya
membaca. **Tidak ada query di Blade.**

```
config/blocks.php (content_query)
        │
        ├─ PageRenderData::prepareContentQueryBlocks()   → resolve saat render PAGE
        └─ ContentEntryController::resolveContentQueries() → resolve saat render ENTRY body
                    │
                    └─ ContentQueryResolver::resolve($data) → Collection<ContentEntry>
                                                                    │
                                       frontend/blocks/content-query.blade.php (baca resolvedEntries)
```

## Definisi block (`config/blocks.php`)

| Field | Type | Keterangan |
|-------|------|-----------|
| `content_type` | select (optionsFrom `content_types`) | tipe yang diquery |
| `heading` | text | judul section opsional |
| `orderby` | select | newest / oldest / title / sort_order |
| `columns` | select | 1 / 2 / 3 kolom |
| `limit` | number (1–24) | jumlah entri |
| `show_excerpt` | toggle | tampilkan excerpt |

Didaftarkan juga di `PageBlockService::defaultDataFor()` + `rulesFor()` (validasi:
`content_type` exists:content_types, whitelist orderby/columns, limit 1–24). Idempotent
terhadap `validateAndSanitizeData` (lolos kontrak registry test).

## `ContentQueryResolver` (shared)

`resolve(array $data): Collection<ContentEntry>`:
- `content_type` kosong/invalid → **empty**.
- Tipe harus `public()` → kalau tidak → empty.
- Query `entries()->published()->with('contentType')`, order sesuai `orderby`,
  `limit` di-clamp 1–24.

Dipakai bersama oleh PageRenderData (pages) dan ContentEntryController (entries)
sehingga block merender identik di mana pun dipasang.

## Option source builder

`content_types` ditambahkan ke `$fieldOptions` di **ContentEntryBuilderController**
dan **PageBuilderController** (additive — panel select terisi daftar public content
type; builder Page lainnya tidak diubah).

---

## Report (AGENTS.md §11)

### Changed
- `config/blocks.php` — definisi block `content_query`
- `app/Services/PageBlockService.php` — `content_query` di `defaultDataFor()` + `rulesFor()`
- `app/Support/ContentQueryResolver.php` — **baru** (resolver bersama)
- `app/Support/PageRenderData.php` — `prepareContentQueryBlocks()` + inject resolver
- `app/Http/Controllers/Frontend/ContentEntryController.php` — `resolveContentQueries()` (rekursif)
- `app/Models/PageBlock.php` — `@property resolvedEntries`
- `app/Http/Controllers/Admin/ContentEntryBuilderController.php` — option `content_types`
- `app/Http/Controllers/Admin/PageBuilderController.php` — option `content_types` (additive)
- `resources/views/frontend/blocks/content-query.blade.php` — **baru** (render partial)
- `tests/Feature/Phase6/B13ContentQueryBlockTest.php` — **baru** (8 tests)

### Impact
- **DB:** tidak ada perubahan.
- **Routes:** tidak ada perubahan.
- **Builder:** block baru "Content Query" muncul di kategori `content` (page + entry
  builder). Panel setting punya select content type.
- **Frontend:** block merender grid kartu entri di page/entry yang published.
- **Security:** `content_type` divalidasi `exists:content_types`, orderby/columns via
  whitelist. Resolver hanya menampilkan entri `public + published`. Tidak ada query
  di Blade.
- **Phase 5 (Page builder):** hanya penambahan option source `content_types` — tidak
  ada perubahan perilaku existing.

### Verification
- B13 tests: **8/8 pass**, 16 assertions:
  - block terdaftar di registry (field content_type ada)
  - resolver: hanya published, respect limit, orderby title, abaikan draft & non-public
  - render di **page** + render di **entry body**
- PageBlockManagementTest (kontrak registry) tetap **hijau** setelah pendaftaran block.
- Full suite: **815/815 pass**, 4077 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert b82aed1
```
(Tanpa migration — murni kode.)

### Next
- **B14 — Builder bridge: `content_field` block** (block terakhir M4): menampilkan
  nilai satu field dari content entry saat ini (atau entri terpilih) di dalam builder
  — melengkapi jembatan Phase 6 ↔ visual builder.
