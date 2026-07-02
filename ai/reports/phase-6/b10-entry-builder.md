# B10 — Entry Body via Visual Builder

> **Task:** B10 — Content entries dengan support `editor` memakai visual builder Phase 5
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (tanpa schema baru — memakai morph rail `page_blocks` yang sudah disiapkan di A3)
> **Skills:** page-builder-skill · phase5-visual-builder-skill · backend-skill

---

## Ringkasan

Content type dengan fitur `editor` kini bisa menyusun **body block-tree** memakai
visual builder Phase 5 yang sudah ada. Block tree disimpan di **rail morph
`page_blocks`** (`blockable_type`/`blockable_id`, `page_id` NULL) yang disiapkan di
A3 (dual-rail). **Builder halaman (Page) tidak tersentuh sama sekali** — pages tetap
pakai rail `page_id`/`hasMany`, entries pakai rail morph.

Milestone **M4 (Public + visual)** dimulai.

---

## Arsitektur (dual-rail, reuse Phase 5)

```
page_blocks
├── page_id + hasMany      → Pages (Phase 5, TIDAK diubah)
└── blockable morph rail   → ContentEntry (B10, page_id NULL)
```

- `ContentEntry::blocks()` = `morphMany(PageBlock, 'blockable')` order by sort_order.
- `ContentEntryBuilderController` meniru `PageBuilderController` tapi di rail morph.
- Partial builder generik (`alpine-component`, `panel-left`, `canvas`, `panel-right`,
  `picker-modal`) **dipakai ulang tanpa perubahan** — hanya `index`/`topbar` yang
  page-specific, jadi dibuat versi entry (`entry.blade.php` + `topbar-entry`).

---

## Yang dibangun

### Model & cleanup
- `ContentEntry::blocks()` MorphMany ke PageBlock.
- `ContentEntryObserver::forceDeleted()` → `$entry->blocks()->delete()`. Rail morph
  tidak punya FK (`blockable_id` bukan foreign key), jadi hard delete harus
  membersihkan blocks manual. **Soft delete tetap menyimpan blocks** → entry yang
  di-restore tetap punya body.

### `ContentEntryBuilderController`
- **`show(contentType, entry)`** — guard (404 kalau bukan editor / bukan pemilik),
  build tree dari `entry->blocks()`, render `backend.builder.entry`.
- **`saveTree(...)`** — validasi + `BuilderTreeSanitizer::sanitizeTree()`; dalam
  transaction: snapshot revisi (B9), hapus blocks lama, insert ulang di rail morph.
  Terima `template_id` (diabaikan — entries pakai kolom string `template`), balikan
  `{ success, tree, template_id: null }`.
- **`previewPayload(...)`** — sanitize posted blocks → transient PageBlock tree
  (in-memory, id sintetis negatif) → render `frontend.content-entries.preview`.

### Views
- `backend/builder/entry.blade.php` — mirror `index` dengan cfg entry (saveUrl +
  previewUrl entry, `layoutTemplates: []`, `storeBuilderTemplateUrl: null`), reuse
  semua partial generik. Tanpa template library.
- `backend/builder/partials/topbar-entry.blade.php` — back ke entry edit, judul +
  status entry, toggle panel/device, tombol Save. Tanpa Templates & external preview
  (route frontend entry menyusul di B11–B12).
- `frontend/content-entries/preview.blade.php` — shell HTML minimal (memuat
  `@vite app.css`) yang merender block partials standalone untuk iframe preview.

### UI entry edit
Tombol **"Edit Body in Builder"** + hint muncul di form edit **hanya** saat content
type support `editor`. Di-guard `$isEdit && supports('editor')` (aman di create).

### Routes
```
GET  content-types/{content_type}/entries/{entry}/builder                admin.content-types.entries.builder
POST content-types/{content_type}/entries/{entry}/builder/save-tree      admin.content-types.entries.builder.save-tree
POST content-types/{content_type}/entries/{entry}/builder/preview-payload admin.content-types.entries.builder.preview-payload
```

---

## Report (AGENTS.md §11)

### Changed
- `app/Models/ContentEntry.php` — `blocks()` MorphMany
- `app/Observers/ContentEntryObserver.php` — `forceDeleted()` block cleanup
- `app/Http/Controllers/Admin/ContentEntryBuilderController.php` — **baru** (show/saveTree/previewPayload)
- `routes/admin.php` — 3 route builder entry
- `resources/views/backend/builder/entry.blade.php` — **baru**
- `resources/views/backend/builder/partials/topbar-entry.blade.php` — **baru**
- `resources/views/backend/content-entries/form.blade.php` — tombol + hint builder
- `resources/views/frontend/content-entries/preview.blade.php` — **baru** (preview shell)
- `tests/Feature/Phase6/B10EntryBuilderTest.php` — **baru** (14 tests)

### Impact
- **DB:** tidak ada tabel/kolom baru — memakai morph `page_blocks` dari A3.
- **Routes:** 3 route baru di bawah `['auth','admin']`.
- **Frontend admin:** halaman builder untuk entry (reuse Phase 5) + tombol di edit form.
- **Frontend publik:** belum ada route publik entry (itu B11–B12); preview builder
  memakai shell standalone.
- **Builder Phase 5 (Page):** **NOL perubahan** — partial generik dipakai ulang,
  file page-specific tidak disentuh. Regression suite tetap hijau.
- **Security:** guard 404 (editor-only + ownership), semua di `['auth','admin']`,
  block data disanitasi via `BuilderTreeSanitizer` + `PageBlockService` (sama seperti pages).

### Verification
- B10 tests: **14/14 pass**, 28 assertions:
  - penyimpanan di rail morph (blockable_type/id set, page_id null)
  - guard: buka untuk editor type, 404 untuk non-editor, 404 untuk cross-type entry
  - saveTree persist/replace/nested children + snapshot revisi
  - force-delete membersihkan blocks; soft-delete mempertahankan
  - previewPayload render blocks + placeholder saat kosong
  - tombol builder muncul untuk editor type, tersembunyi untuk non-editor
- Full suite: **785/785 pass**, 4005 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert ad9027b
```
(Tanpa migration — rollback murni kode.)

### Next
- **B11 — Frontend routing + controllers** (⚠️ route ordering): route publik untuk
  archive + single entry per content type (`route_base`), dengan guard urutan agar
  tidak bentrok dengan route existing.
