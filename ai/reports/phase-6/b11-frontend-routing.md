# B11 — Public Frontend Routing for Content Entries

> **Task:** B11 — Frontend routing + controllers
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ route ordering — diselesaikan dengan `Route::fallback()` (tanpa schema, tanpa mengubah route existing)
> **Skills:** backend-skill · seo-ai-discovery-skill

---

## Masalah: route ordering

Content entries butuh URL dinamis berbasis `route_base` content type:
- `/{route_base}` → archive (mis. `/blog`)
- `/{route_base}/{slug}` → single (mis. `/blog/hello-world`)

`route_base` bersifat **dinamis** (dibuat admin), jadi tidak bisa didaftar statis
tanpa risiko bentrok dengan route existing (`/`, `/products`, `/pages/...`,
`/forms/...`, sitemap, robots) maupun route admin.

## Solusi: `Route::fallback()`

`Route::fallback()` **selalu menjadi match prioritas TERENDAH** di Laravel —
diproses hanya setelah semua route lain (termasuk admin) gagal match, tanpa
peduli urutan pendaftaran. Ini menjadikannya aman secara ordering by-design:

```php
Route::fallback([FrontendContentEntryController::class, 'resolve']);
```

Lapisan pengaman berlapis:
1. **Fallback = prioritas terendah** → route eksplisit selalu menang.
2. **`route_base` UNIQUE** (migration) → resolusi deterministik, tak ada dua tipe
   berbagi base.
3. **`RESERVED_PREFIXES`** (B1) → `route_base` tidak boleh `admin`, `api`, `pages`,
   `products`, `preview`, `media`, `sitemap`, dll → tidak mungkin membajak route inti.
4. Path tak dikenal tetap **404** (dilempar dari controller) → perilaku lama terjaga.

---

## Controller: `Frontend\ContentEntryController`

**`resolve(Request)`** — pecah `request()->path()` jadi segment, dispatch:
- 1 segment → `archive($seg0)`
- 2 segment → `single($seg0, $seg1)`
- selain itu → 404

**`archive(routeBase)`** — cari content type `public()` + `route_base` + `has_archive`;
kalau tidak ada → 404. List entri `published()->ordered()->paginate(12)`. Render
`frontend.content-entries.archive` dengan SEO type-level.

**`single(routeBase, slug)`** — cari type `public()` + `route_base`; cari entri
`published()` by slug (else 404). Untuk type support `editor`, bangun block-body
tree (`buildTree`) dari rail morph (B10). SEO via `ContentEntry::seoMeta()` (B9).

Hanya entri **published** yang publik — draft & scheduled-future → 404 (via
`scopePublished`, konsisten dengan Pages).

## Model helper

`ContentEntry::publicUrl()` → `url('{route_base}/{slug}')`, atau `null` bila tak
routable (tanpa route_base/slug).

## Views

- `frontend/content-entries/archive.blade.php` — grid kartu entri + pagination.
- `frontend/content-entries/single.blade.php` — header (title/excerpt + back link) +
  render block body builder (block partials `frontend.blocks.*`, sama seperti pages).

---

## Report (AGENTS.md §11)

### Changed
- `routes/frontend.php` — import + `Route::fallback()` di paling bawah
- `app/Http/Controllers/Frontend/ContentEntryController.php` — **baru** (resolve/archive/single/buildTree)
- `app/Models/ContentEntry.php` — `publicUrl()` helper
- `resources/views/frontend/content-entries/archive.blade.php` — **baru**
- `resources/views/frontend/content-entries/single.blade.php` — **baru**
- `tests/Feature/Phase6/B11FrontendRoutingTest.php` — **baru** (14 tests)

### Impact
- **DB:** tidak ada perubahan.
- **Routes:** 1 fallback route. **Tidak ada route existing yang diubah/dipindah** —
  fallback prioritas terendah, jadi ordering aman tanpa menyentuh yang lain.
- **Frontend publik:** content entries kini punya archive + single page. Block body
  builder (B10) tampil publik.
- **Security:** hanya published yang publik (draft/future → 404). Tidak ada input
  user baru. Path arbitrer tetap 404.
- **SEO:** single pakai `seoMeta()` (title→entry title, description→excerpt fallback);
  archive pakai label + description type. Canonical via `publicUrl()`.

### Verification
- B11 tests: **14/14 pass**, 19 assertions:
  - archive list published (sembunyikan draft); 404 untuk non-archive/non-public/unknown base
  - single render published; 404 untuk draft/future/unknown-slug
  - single render block body builder untuk type editor
  - **route ordering regression**: `/`, `/products`, `/pages/{slug}` tetap menang atas fallback
  - `publicUrl()` helper + null-case
- Full suite: **799/799 pass**, 4024 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert e09b1ca
```
(Tanpa migration — murni kode. Menghapus fallback mengembalikan perilaku 404 lama.)

### Next
- **B12 — Template resolution + render**: pilih template render per-entry (`template`
  string) / per-type / default, dan perkaya single view (layout template, structured
  data schema per type). Saat ini single memakai layout default + block body.

---

## Post-Release Fix Log

> Catatan dari pengujian manual owner. Tidak menimpa laporan asli di atas.

### 2026-07-07 — Fix #1: single 404 karena `published_at` future + slug salah (bukan bug routing)

**Gejala:** `/articles/hidden-beaches-of-bintan-island/` → 404, walau status entry
`published`.

**Audit data MySQL asli menemukan DUA penyebab, keduanya bukan bug routing:**
1. **`published_at` di masa depan.** `app.timezone = UTC`; `now()` = 04:54 UTC tapi
   `published_at` entry = 07:22 (disimpan sebagai UTC dari input datetime-local waktu
   lokal). `scopePublished` (`published_at <= now`) menyembunyikannya seperti scheduled →
   `isPublished()` = false → 404. **Ini penyebab utama.**
2. **Slug salah di URL.** Slug asli = `hidden-beaches-of-bintan` (title diedit menjadi
   "…Island" belakangan, tapi slug sengaja tidak ikut berubah agar link stabil).
   URL owner memakai `-island` yang tidak ada.

**Perbaikan:**
- **Builder publish = live now:** `ContentEntryBuilderController::updateStatus()` kini
  meng-clear `published_at` yang null **atau future** ke `now()` saat memilih "Published"
  (tanggal past yang sah tetap dipertahankan; untuk publish terjadwal gunakan status
  "Scheduled"). Menghilangkan jebakan timezone future-date.
- **Discoverability URL:** halaman edit entry menampilkan **Public URL** + tombol
  **"View live"** (saat published) atau badge **"Not live — status is …"** (saat draft),
  supaya owner tidak menebak slug.
- Data entry #2 owner diset `published_at = now()` agar langsung live.

**File:**
- `app/Http/Controllers/Admin/ContentEntryBuilderController.php` — `updateStatus()` future-clear
- `resources/views/backend/content-entries/form.blade.php` — panel Public URL + "View live"
- `tests/Feature/Phase6/B10EntryBuilderTest.php` — +4 test (publish clears future date,
  preserve past date, edit page shows public URL/View live, draft flagged Not live)

**Catatan:** `app.timezone` tetap UTC (tidak diubah tanpa approval). Untuk kenyamanan,
owner bisa mempertimbangkan set `APP_TIMEZONE=Asia/Jakarta` agar input tanggal sesuai
waktu lokal — perlu keputusan terpisah karena memengaruhi semua timestamp.

**Impact:** tidak ada schema. Suite 831/831, PHPStan 0 errors.
