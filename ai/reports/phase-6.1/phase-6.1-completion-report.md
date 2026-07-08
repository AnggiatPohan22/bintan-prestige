# Phase 6.1 — Completion Report (Dashboard & Media UX)
**Date:** 2026-07-08 | **Branch:** `feature/phase-6.1-dashboard-media-ux`

## Task: Interim dashboard & media improvements before Phase 7

### Step-by-step (as implemented)

**S1 — Sidebar accordion + sticky** (`969f21a`)
1. Root cause dicari lebih dulu: `.admin-body` dan `.admin-shell` memakai
   `overflow-x: hidden`, yang membuat keduanya menjadi scroll container dan
   **mematikan `position: sticky`** milik `.admin-sidebar` (sidebar tampak
   "tertinggal" saat scroll + ruang kosong di bawahnya).
2. Fix: ganti ke `overflow-x: clip` (memotong overflow tanpa membuat scroll
   container) — sidebar kini sticky penuh viewport, gap bawah = 0.
3. Alpine `adminSidebar` diubah dari state-per-grup (banyak grup bisa terbuka)
   menjadi **accordion `openGroup` tunggal**: klik satu grup menutup grup lain,
   grup aktif dari route selalu terbuka saat load, grup terakhir diingat via
   localStorage untuk halaman tanpa grup aktif (Dashboard). `aria-expanded`
   kini dinamis di 8 tombol grup.
   Verified via DOM: scroll 700px → sidebar top 0/gap 0; klik Design menutup
   Content; klik ulang meng-collapse.

**S2 — Struktur koleksi media** (`cbe40a8`) ⚠️ schema (additive)
1. Migration `media.collection` varchar(50) NULL + index.
2. `config/media.php`: katalog koleksi — hero, product, category, destination,
   logo, icon, gallery, section, content, general (default).
3. `MediaService::store($file, $user, $collection)` → path
   `media/{collection}/YYYY/MM/{uuid}.webp`; koleksi tak dikenal jatuh ke
   default (tidak bisa menulis di luar folder media).
4. UI library: filter koleksi (termasuk "Uncategorized" untuk file lama),
   selector koleksi di upload modal, label koleksi di card + panel detail.
   File lama TIDAK dipindah (zero broken URL). +3 test.

**S3 — Picker modal: upload + tema** (`f00a59e`)
1. `AdminAppearanceComposer` di-bind juga ke `backend.media.picker` — halaman
   picker (iframe) kini menerima CSS vars appearance & `data-admin-mode`,
   mengikuti tema light/dark dashboard.
2. Upload zone di dalam picker: drag-drop / choose file → `admin.media.store`
   (JSON, membuat record Media pada koleksi terpilih); upload tunggal langsung
   ter-auto-select ke field pemanggil.
3. Event `open-media-picker` membawa `collection` hint; hero→hero,
   image→content, gallery→gallery, background→section; quick/batch upload blok
   juga menandai koleksinya.
4. **Regresi maroon ditemukan & di-root-cause via binlog:** preset `navy-light`
   yang saya simpan di DB bukan preset resmi, sehingga aksi preset di halaman
   appearance menimpanya dengan `full-light` (maroon) pada 12:17. Fix
   permanen: `navy-light` didaftarkan sebagai preset resmi di
   `config/admin_palettes.php` (54 token) + dijadikan `defaults.light_preset`,
   lalu palette DB di-apply ulang dari preset. Navy kini tahan terhadap aksi
   preset apa pun.

**S4 — Komponen `<x-admin.media-image-field>`** (`18b30a8`)
Preview + input path + Upload (uploadQuick → record Media + koleksi) + tombol
Media Library (dengan collection hint). Modal picker + script `imageUploader`
ikut ter-include `@once` — komponen bisa dipakai form mana pun.

**S5 — Category image** (`18b30a8`) ⚠️ schema (additive)
1. Migration `categories.image` varchar(500) NULL (paritas `destinations.image`).
2. Model/StoreRequest/UpdateRequest/CategoryService menerima path string.
3. Form create/edit kategori memakai komponen S4 (koleksi `category`).
   Catatan: field ditambahkan di `create.blade.php` + `edit.blade.php`
   (form-shell) — `categories/form.blade.php` adalah file LEGACY tak terpakai.
4. Frontend: `CategoryDestinationDisplayState::category()` kini punya
   `media` state; header landing kategori (`/products?category=…`,
   `entity-context.blade.php`) menampilkan gambar seperti destinasi; SEO
   metadata ikut memakai gambar. `categories.image` terdaftar di
   `MediaService::DIRECT_REFERENCES` (usage tracking + delete guard). +1 test.

**S6 — Destinations → picker + builder collection** (`470fe1c`)
1. Form destinasi memakai komponen S4 (`image_path`, koleksi `destination`);
   service tetap menerima upload file legacy. File lama milik modul
   (`destinations/…`) dibersihkan saat diganti; aset `media/…` TIDAK dihapus
   service (milik Media Library, dilindungi delete-guard reference check).
2. Upload cepat di visual builder menandai koleksi `content`.

**S9 — Fix: PNG upload gagal karena profil warna non-standar** (`8f381fd`)
1. Owner melaporkan upload PNG (Logo) di Media Library gagal. Log:
   `imagecreatefrompng(): gd-png: libpng warning: iCCP: known incorrect sRGB
   profile` di `ImageOptimizationService.php:56`.
2. Root cause: PNG dengan profil ICC non-standar (umum pada ekspor
   Photoshop/Canva) memicu **warning** libpng yang non-fatal, tapi Laravel
   mengubah semua warning menjadi `ErrorException` → upload 500. Bug **lama**
   (service Phase 1, tidak disentuh Phase 6.1); baru terpicu karena owner
   mengupload PNG bermasalah.
3. Fix: `@`-suppress ketiga `imagecreatefrom*` + throw error bersih hanya bila
   decode benar-benar `false`. Karena Page/Product/Media semua lewat optimizer
   ini, semua jalur upload ikut terperbaiki. +1 regression test (PNG dengan
   chunk iCCP rusak, dibangun deterministik di test).
4. **Temuan pendukung:** upload JPG owner SEBELUMNYA berhasil —
   `Bintan-Lagoi-Bay-1.jpg` (koleksi category) & `danau biru.jpg` (koleksi
   destination) tersimpan rapi di folder koleksi masing-masing. Ini
   mengonfirmasi pipeline koleksi + upload Phase 6.1 berfungsi; hanya PNG yang
   terganjal bug optimizer lama.

### Impact
- DB: 2 migrasi additive — `media.collection`, `categories.image`. Nullable,
  tanpa menyentuh data/kolom existing.
- Routes: none (endpoint media existing dipakai ulang).
- Frontend: landing kategori kini bisa menampilkan gambar; tidak ada perubahan
  visual lain.
- Security: koleksi divalidasi whitelist (anti path traversal — ada testnya);
  picker tetap di balik auth+admin; delete-guard media diperluas ke
  categories.image.
- Tests: 846 → **851/851 pass** (4.229 assertions; +5 test baru). PHPStan level 5: **0 errors**.

### Insiden yang ditemukan selama sesi (bukan disebabkan pekerjaan ini)
1. **Palet admin ter-revert ke maroon** pukul 12:17 (browser admin lain aktif
   menimpa via halaman appearance) — root cause & fix permanen di S3 #4.
2. **18 file media lama terhapus dari disk** pada waktu yang sama — pola cocok
   dengan tombol "Clean orphan files". Satu file TERDAFTAR ikut terhapus:
   `Pearl2.webp` (`media/2026/06/e3073294….webp`) — tidak ada salinan lain di
   disk; jika masih punya sumbernya, upload ulang. Follow-up keamanan purge
   dicatat di plan §5 (staged): purge harus tampilkan daftar file + soft-delete
   ke folder trash, bukan hard delete.

### Rollback
- Per commit: `git revert 969f21a cbe40a8 f00a59e 18b30a8 470fe1c`
- Skema: `php artisan migrate:rollback --step=2` (dua migrasi Phase 6.1)

### Next
- Staged improvements di `phase-6.1-plan-handoff.md` §5 (swap penuh
  products/page-sections/global-assets ke komponen, purge safety, multi-select
  picker untuk gallery, dsb.)
- Lanjut **Phase 7 — Internationalization** setelah owner puas dengan QA manual.
