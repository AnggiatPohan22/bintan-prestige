# Phase 6.1 — Dashboard & Media UX (Interim, before Phase 7)
# Plan + Progress Handoff

> **Source of truth untuk Phase 6.1.** Update file ini setiap task selesai.
> Baca `AGENTS.md` → file ini sebelum menyentuh kode Phase 6.1.
>
> **Branch:** `feature/phase-6.1-dashboard-media-ux` (dari `develop`)
> **Created:** 2026-07-08 | **Owner request:** perbaikan dashboard sebelum Phase 7
> **Last updated:** 2026-07-08 — plan drafted, implementation starting.

---

## 0. Goals (dari owner)

1. Sidebar admin rapi: **fixed/sticky di kiri** saat konten di-scroll, **tanpa
   ruang kosong** di bawah sidebar; grup menu **accordion** — buka satu grup,
   grup lain menutup otomatis (menu tidak memanjang ke bawah).
2. **Category punya image** — sync dengan card frontend (paritas dengan
   Destinations yang sudah punya `image`).
3. Semua opsi **Choose file / upload image masuk ke Media Library** dulu —
   satu pintu, mudah di-trace.
4. **Modal Media Library** (dipakai builder/hero dsb.) dilengkapi **opsi upload**
   yang tersimpan ke Media Library + tampilan **mengikuti tema light/dark**
   dashboard, lebih interaktif.
5. **Struktur penyimpanan media** rapi berdasarkan posisi/koleksi:
   Hero, Product, Logo, Icon, dst.

---

## 1. Temuan Inspeksi (baseline — kenapa rencana ini tepat sasaran)

| Temuan | File | Implikasi |
|---|---|---|
| `.admin-shell` pakai `overflow-x-hidden` → **mematahkan `position: sticky`** sidebar (`.admin-sidebar` sudah `lg:sticky top-0 h-screen`) | `resources/css/admin.css` (~line 153) | Fix root cause: ganti ke `overflow-x: clip` (tidak membuat scroll container) |
| Sidebar Alpine `adminSidebar` menyimpan open-state **per grup** di localStorage → banyak grup bisa terbuka bersamaan | `resources/views/components/admin/sidebar.blade.php` | Ubah ke single `openGroup` (accordion), persist grup terakhir |
| Picker modal = iframe ke `admin.media.index?picker=1` → view `backend/media/picker.blade.php` **standalone tanpa CSS variable appearance** dan **tanpa upload** | `picker.blade.php`, `partials/picker-modal.blade.php` | Inject appearance vars + `data-admin-mode` ke halaman picker; tambah drop-zone upload → `admin.media.store` (JSON) |
| `MediaService::store()` hardcode folder `media/YYYY/MM` — belum ada konsep koleksi | `app/Services/MediaService.php:44` | Tambah param `collection` → `media/{collection}/YYYY/MM`; kolom `collection` di tabel `media` |
| `uploadQuick`/`uploadBatch` **sudah** membuat record Media (bagus) tapi tidak membawa konteks posisi | `MediaController` | Teruskan `collection` dari field pemanggil |
| `categories` tidak punya kolom image; `destinations.image` sudah ada dan tampil di entity context (`product-entity__media`) — **card kategori frontend tidak punya gambar** | `CategoryDestinationDisplayState::category()` vs `::destination()` | Paritas: `categories.image` + media state kategori |
| Input file mentah tersebar: destinations, products, page-sections, pages (og), settings/global-assets | grep `type="file"` | Komponen reusable `<x-admin.media-image-field>`; swap bertahap (lihat §4 staging) |
| `MediaService::DIRECT_REFERENCES` melacak pemakaian path per tabel/kolom | `MediaService.php:24` | Setiap kolom image baru **wajib** didaftarkan di sini |

---

## 2. Task Breakdown & Status

Legend: `⏳ TODO` · `🔨 IN PROGRESS` · `✅ DONE` · `⚠️ schema (approved via owner request 2026-07-08)`

| # | Task | Scope | Status |
|---|------|-------|--------|
| S1 | Sidebar accordion + sticky fix | `admin.css` (.admin-shell overflow → clip), `components/admin/sidebar.blade.php` (Alpine single-open) | ⏳ |
| S2 | Media collections (struktur folder) ⚠️ schema | Migration `media.collection` (varchar 50, nullable, index); `config/media.php` katalog koleksi; `MediaService::store($file,$user,$collection)`; path `media/{collection}/YYYY/MM`; filter koleksi di library + picker | ⏳ |
| S3 | Picker modal: upload + theme | `picker.blade.php` — appearance vars + mode + upload drop-zone (POST `admin.media.store` JSON, `collection` context, auto-select hasil upload); modal shell tetap | ⏳ |
| S4 | Komponen `<x-admin.media-image-field>` | Preview + tombol Media Library (open-media-picker + collection) + tombol Upload (uploadQuick) + hidden path input + alt; dipakai form apa pun | ⏳ |
| S5 | Category image ⚠️ schema | Migration `categories.image` (varchar 500 nullable); model fillable; Store/UpdateCategoryRequest + CategoryService; form admin pakai S4; frontend: `CategoryDestinationDisplayState::category()` media state (paritas destination); daftarkan `categories.image` di DIRECT_REFERENCES | ⏳ |
| S6 | Destinations form → S4 component | Ganti raw file input dengan komponen (path-based); service tetap back-compat menerima file | ⏳ |
| S7 | Upload lain ter-trace ke Media Library | Products thumbnail/gallery, page-sections image, pages og_image, global-assets: upload existing tetap jalan **tapi** ikut tercatat sebagai record Media (registrasi via MediaService) — swap penuh ke komponen dicatat sebagai staged improvement (§5) | ⏳ |
| S8 | Docs + verifikasi | Report step-by-step, update handoff ini, AGENTS.md/Claude.md sync, suite hijau, PHPStan 0 | ⏳ |

**Urutan eksekusi:** S1 → S2 → S3 → S4 → S5 → S6 → S7 → S8 (tiap task = 1 commit terfokus).

---

## 3. Keputusan Teknis

1. **Sticky fix pakai `overflow-x: clip`** (bukan menghapus overflow guard) —
   `clip` memotong overflow horizontal tanpa membuat scroll container, jadi
   `position: sticky` anak tetap bekerja. Fallback browser lama: aman (desktop
   admin modern).
2. **Accordion**: `openGroup` tunggal. Grup aktif (dari route) selalu terbuka
   saat load; klik grup lain → grup itu jadi satu-satunya yang terbuka; klik
   grup aktif → boleh collapse manual. localStorage menyimpan grup terakhir
   yang dibuka user (opsional restore saat halaman tanpa grup aktif).
3. **Koleksi media** = konvensi folder + kolom DB, **bukan** tabel baru:
   `hero`, `product`, `category`, `destination`, `logo`, `icon`, `gallery`,
   `section`, `content`, `general` (default). Path: `media/{collection}/YYYY/MM/uuid.webp`.
   File lama (tanpa koleksi) TIDAK dipindah — `collection` nullable, filter
   "Uncategorized" tersedia. Zero migrasi file fisik = zero risiko broken URL.
4. **Satu pintu upload**: semua tombol upload di admin memanggil endpoint
   MediaController (store/uploadQuick/uploadBatch) yang membuat record Media —
   tidak ada lagi `Storage::put` langsung dari controller modul lain (bertahap, §5).
5. **Back-compat**: field path berbentuk string relatif (`media/...`) konsisten
   dengan pola existing (`pages.og_image`, `destinations.image`). Tidak ada
   perubahan pada data lama.

---

## 4. Schema Changes (⚠️ approved via owner request 2026-07-08)

```
media       + collection VARCHAR(50) NULL, INDEX(collection)
categories  + image      VARCHAR(500) NULL   (paritas destinations.image)
```

Keduanya additive + nullable → tidak menyentuh data/kolom existing, rollback =
drop column. Tidak ada perubahan pada tabel protected lain.

---

## 5. Staged Improvements (improvisasi — dicatat, BELUM dikerjakan)

> Sesuai permintaan owner: ide perbaikan ditulis di sini agar rapi dan mudah
> di-upgrade nanti.

| Ide | Detail | Prasyarat |
|---|---|---|
| Swap penuh form Products/Page-Sections/Global-Assets ke `<x-admin.media-image-field>` | Ganti semua raw file input dengan picker component; FormRequest berubah dari `image` file rule → `string path` rule | S4 stabil + regression test per modul |
| Media detail: pindah/ubah koleksi dari UI library | Dropdown collection di panel detail media (`updateMeta`) — file tidak dipindah, hanya label koleksi | S2 |
| Bulk re-organize file lama ke folder koleksi | Command artisan `media:organize` dengan dry-run + update referensi via usageMap | Backup + usageMap solid |
| Picker: multi-select untuk gallery block | postMessage array; gallery block sudah punya uploadBatch | S3 |
| Media picker sebagai Alpine panel (tanpa iframe) | Hilangkan postMessage/iframe; render grid via fetch JSON | butuh endpoint JSON list |
| Foldering per-tahun bisa dimatikan via config | `config/media.php` `date_folders => true/false` | S2 |
| SVG upload untuk koleksi `icon`/`logo` | Butuh sanitizer SVG (jangan render inline tanpa sanitasi — media-library-skill Forbidden) | riset sanitizer |

---

## 6. Rules Phase 6.1

- Ikuti admin CSS classes (`admin-btn-primary`, `admin-input`, dsb.) — tanpa hex hardcode.
- Jangan sentuh: page builder 20:60:20 shell, BuilderTreeSanitizer, modul protected.
- Setiap kolom image baru → daftarkan di `MediaService::DIRECT_REFERENCES`.
- Test: tiap task menambah/menyesuaikan test; suite wajib hijau sebelum commit.
- PHPStan level 5 / 0 errors tetap hard gate.
- DB dev berisi data hasil recovery — **jangan** `migrate:fresh`; migrasi additive saja.

---

## 7. Report Files

- Plan + handoff: file ini.
- Task report: `ai/reports/phase-6.1/s{n}-{nama}.md` (format AGENTS.md §11)
  atau digabung di `phase-6.1-completion-report.md` bila task kecil.
