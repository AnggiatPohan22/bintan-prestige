# B9 — Phase 4 Reuse Wiring

> **Task:** B9 — Hook content entries into revisions, scheduling, audit log, SEO meta
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** ⚠️ new table `content_entry_revisions` — **APPROVED by owner 2026-07-02**
> (owner memilih "tabel baru content_entry_revisions" — terisolasi, tidak mengganggu relasi lain,
> punya tabel sendiri agar perubahan layout/kolom entry masa depan tidak merusak logic existing).
> Tiga bagian lain (audit, scheduling, SEO) **tanpa schema baru** — murni reuse Phase 4.
> **Skills:** backend-skill · database-architecture-skill

---

## Ringkasan

B9 menghubungkan content entries ke empat sistem yang sudah dibangun di Phase 4,
**tanpa membangun ulang**:

| Sistem | Cara reuse | Schema baru? |
|--------|-----------|:---:|
| Revisions | Tabel baru `content_entry_revisions` (terisolasi, snapshot JSON) | ✅ 1 tabel |
| Audit log | `AuditLog` sudah polymorphic — tinggal wire observer | ❌ |
| Scheduling | Kolom `status` + `published_at` sudah ada — tambah command | ❌ |
| SEO meta | Kolom `seo` JSON sudah ada — tambah helper resolver | ❌ |

---

## 1. Revisions — `content_entry_revisions` (tabel baru, disetujui)

### Skema (migration `2026_07_02_000001`)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| content_entry_id | FK → content_entries **CASCADE** | terisolasi; revisi hilang bersama entry, tidak menyentuh page_revisions |
| revision_number | uint | nomor urut per entry |
| snapshot | json | **satu kolom fleksibel** — seluruh state revisionable (core + data + seo) |
| created_by | FK users **nullOnDelete** | revisi tetap ada walau author dihapus |
| created_at | timestamp useCurrent | tanpa updated_at |
| index(content_entry_id, revision_number) | | |

**Kenapa satu kolom `snapshot` JSON (bukan meniru split content/meta page_revisions):**
Content entry menyimpan semua di kolom + `data` JSON. Satu snapshot JSON menangkap
seluruh state, sehingga **menambah kolom entry di masa depan tidak butuh perubahan
schema revisi** — persis yang owner minta (tabel sendiri, tahan perubahan layout).

### `ContentEntryRevision` model
`$timestamps = false`, casts `snapshot → array`, `created_at → datetime`.
`entry()` + `author()` BelongsTo.

### `ContentEntryRevisionService`
- **`snapshot(entry, ?authorId)`** — simpan state saat ini sebagai revisi baru,
  prune ke maksimal **20** terbaru. No-op jika tidak ada auth & authorId null (console).
- **`restore(entry, revision)`** — **snapshot state sekarang dulu** (restore jadi
  reversible), lalu terapkan snapshot lama. Hanya key yang ada di snapshot yang
  diterapkan (`array_key_exists`) — snapshot lama yang belum punya kolom baru tidak
  menimpa kolom itu dengan null.
- **`buildSnapshot()`** membaca dari instance `fresh()` supaya semua kolom ter-load
  dengan nilai asli — mencegah `Model::only()` mengarang `null` untuk kolom NOT NULL
  (mis. `sort_order`) yang belum ada di memori model baru.

### Wiring
- `ContentEntryController::store()` → snapshot revisi #1 setelah create.
- `ContentEntryController::update()` → snapshot setelah update.
- `ContentEntryController::restoreRevision()` → restore + guard cross-entry (404).
- Route: `POST content-types/{content_type}/entries/{entry}/revisions/{revision}/restore`
  → `admin.content-types.entries.revisions.restore`.
- `ContentEntry::revisions()` HasMany (order revision_number desc).
- Panel **Revision History** di `edit.blade.php` (nomor, waktu, author, tombol Restore
  dengan konfirmasi modal).

---

## 2. Audit Log — reuse polymorphic `AuditLog`

`ContentEntryObserver` kini juga meng-handle event lifecycle:
```php
created  → AuditLog::record('created', $entry, null, attrs)
updated  → AuditLog::record('updated', $entry, oldChanged, changes)
deleted  → AuditLog::record('deleted', $entry, attrs, null)
```
`AuditLog::record()` no-op jika tidak ada user login (aman untuk seeder/console/test
yang tidak actingAs). Label otomatis pakai `title` entry (resolveLabel). Tidak ada
tabel baru — `audit_logs` sudah polymorphic (`auditable_type='ContentEntry'`).

Observer projection hooks (`saved`, `restored` untuk index + relations dari B6/B8)
tetap jalan berdampingan tanpa konflik.

---

## 3. Scheduling — reuse `status` + `published_at`

`PublishScheduledContentEntries` command (`content-entries:publish-scheduled`):
```php
ContentEntry::where('status', 'scheduled')
    ->whereNotNull('published_at')
    ->where('published_at', '<=', now())
    ->update(['status' => 'published']);
```
Berbeda dengan pages (yang null-kan `publish_at` terpisah), `published_at` di entry
**adalah** timestamp publish, jadi dipertahankan — hanya `status` yang di-flip.
Didaftarkan di `bootstrap/app.php` `->everyMinute()` bersebelahan dengan
`pages:publish-scheduled`.

---

## 4. SEO Meta — reuse kolom `seo` JSON

`ContentEntry::seoMeta()` mengembalikan meta efektif dengan fallback (sama seperti
SEO manager Phase 4 untuk pages):
```php
title       => seo['title']       ?? $this->title
description  => seo['description']  ?? $this->excerpt
canonical    => seo['canonical']    ?? null
og_image     => (int) seo['og_image'] ?? null
```
Rendering meta ke `<head>` frontend adalah B11–B12; helper ini adalah "wiring"-nya.

---

## Report (AGENTS.md §11)

### Changed
- `database/migrations/2026_07_02_000001_create_content_entry_revisions_table.php` — **baru**
- `app/Models/ContentEntryRevision.php` — **baru**
- `app/Support/ContentEntryRevisionService.php` — **baru** (snapshot + prune + restore)
- `app/Models/ContentEntry.php` — `revisions()` HasMany + `seoMeta()`
- `app/Observers/ContentEntryObserver.php` — audit created/updated/deleted
- `app/Http/Controllers/Admin/ContentEntryController.php` — snapshot on store/update, restoreRevision, revisions ke edit
- `app/Console/Commands/PublishScheduledContentEntries.php` — **baru**
- `bootstrap/app.php` — register command + schedule everyMinute
- `routes/admin.php` — revisions.restore route
- `resources/views/backend/content-entries/edit.blade.php` — revision history panel
- `tests/Feature/Phase6/B9Phase4WiringTest.php` — **baru** (10 tests)

### Impact
- **DB:** 1 tabel baru `content_entry_revisions` (terisolasi, CASCADE hanya ke
  content_entries). Tidak menyentuh `page_revisions` maupun tabel lain.
- **Routes:** 1 route baru (`revisions.restore`).
- **Frontend admin:** panel Revision History di halaman edit entry.
- **Security:** restore di-guard (revisi harus milik entry-nya, else 404) + di dalam
  middleware `['auth','admin']`. Audit hanya tercatat untuk user login.
- **Performance:** setiap create/update entry menambah 1 insert revisi + prune query.
  Setiap create/update/delete menambah 1 audit insert (hanya saat login). Acceptable
  untuk CMS write-jarang.

### Verification
- B9 tests: **10/10 pass**, 26 assertions:
  - revisi #1 saat create, revisi bertambah saat update
  - prune ke 20 (revisi tertua terhapus, nomor mulai dari 6)
  - restore mengembalikan field + reversible (state sebelum restore ter-snapshot)
  - guard cross-entry revision → 404
  - audit created/updated/deleted + label pakai title
  - scheduler hanya publish entry yang jatuh tempo (scheduled + due), bukan future/draft
  - seoMeta fallback ke title/excerpt + prefer nilai eksplisit
- Full suite: **771/771 pass**, 3977 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
php artisan migrate:rollback --step=1  # drops content_entry_revisions
git revert df353f0
```

### Next
- **B10 — Entry body via builder** (§3.2 = Option A, polymorphic page_blocks sudah
  siap sejak A3): content type dengan support `editor` memakai visual builder Phase 5
  untuk body block tree. Milestone M4 (Public + visual) dimulai.
