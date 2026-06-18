# Bintan Prestige CMS — Codex Master Guardrails

File ini adalah instruksi utama Codex untuk repository ini. Codex memuat file
ini secara otomatis pada awal setiap sesi atau perintah baru dari root project.

## 1. Tujuan Utama

Codex hanya melanjutkan dan memperkuat sistem yang sudah dibangun oleh Claude.
Jangan menulis ulang, mengganti, menyederhanakan, atau menghapus implementasi
existing kecuali owner meminta perubahan tersebut secara eksplisit.

Utamakan perubahan kecil, kompatibel, dapat diaudit, dan mudah di-rollback.

## 2. Instruksi Wajib Sebelum Bekerja

Sebelum mengubah file apa pun:

1. Verifikasi branch aktif dan `git status`.
2. Baca `AGENTS.md` dan `CLAUDE.md` bila tersedia.
3. Baca hanya guideline dan skill di `ai/` yang relevan dengan task.
4. Baca report terakhir yang berkaitan dengan task.
5. Audit git history dan implementasi existing yang terdampak.
6. Identifikasi fitur selesai, pattern arsitektur, risiko regression, dan pekerjaan tersisa.
7. Tampilkan daftar pasti file yang direncanakan untuk diubah.
8. Jelaskan alasan perubahan dan dampaknya secara terperinci.
9. Tunggu approval owner jika perubahan menyentuh protected area, schema, security,
   auth, kontrak route, atau perilaku existing.

Instruksi terbaru owner tetap memiliki prioritas tertinggi.

## 3. Protected Files dan Directories

Jangan mengubah, memindahkan, rename, mengganti, atau menghapus area berikut
tanpa permintaan eksplisit owner dan approval atas daftar file yang spesifik:

- `AGENTS.md`
- `CLAUDE.md`
- `AGENTS.override.md`
- `.claudeignore`
- `.claude/**`
- `.codex/**`
- `ai/guidelines/**`
- `ai/skills/**`
- `database/**`
- `app/Http/Controllers/**`
- seluruh file konfigurasi di root project
- file existing lain yang tidak termasuk dalam scope task yang disetujui

Jika protected area perlu diubah, jangan langsung mengedit. Laporkan:

- masalah yang ditemukan;
- alasan teknis perubahan diperlukan;
- file dan bagian yang terdampak;
- alternatif tanpa mengubah protected area;
- risiko regression;
- rencana test dan rollback.

Lanjutkan hanya setelah owner memberikan approval eksplisit.

## 4. Batas Pengembangan Codex

- Fokus pada pengembangan lanjutan yang diminta owner.
- Pertahankan route, controller, model, service, class, method, field, dan kontrak existing.
- Pertahankan UI, data flow, validation, authorization, dan fallback existing kecuali
  task secara eksplisit meminta perubahan.
- Jangan membuat asumsi bahwa implementasi Claude salah hanya karena berbeda dari
  preferensi Codex.
- Jangan melakukan refactor di luar scope.
- Jangan mencampur cleanup dengan feature atau bug fix.
- Jangan hardcode konten yang seharusnya dimiliki CMS.
- Jangan mengubah package atau dependency tanpa approval.
- Jangan membuat perubahan diam-diam. Setiap perubahan harus dijelaskan dan dapat
  ditelusuri ke instruksi owner.

File baru boleh dibuat hanya bila diperlukan oleh task dan berada di luar protected
area. Sebelum membuatnya, tetap tampilkan path, tujuan, integrasi, test, dan rollback.

## 5. Database, Migration, dan Data Safety

Database dan seluruh `database/**` dilindungi secara default.

Tanpa approval eksplisit, jangan:

- membuat atau mengubah migration;
- mengubah schema, index, foreign key, atau column;
- menjalankan migration yang mengubah database;
- mengubah factory atau seeder;
- menjalankan command yang menghapus atau membangun ulang data.

Command berikut dilarang:

- `php artisan migrate:fresh`
- `php artisan db:wipe`
- `git reset --hard`
- `git clean -fd`

Gunakan pengecekan read-only seperti `php artisan migrate:status` bila dibutuhkan.

## 6. Controller dan Existing Architecture

Controller existing dilindungi. Untuk kebutuhan baru, audit lebih dahulu apakah
fitur dapat ditambahkan melalui extension point existing, service, support class,
request, view, component, atau file baru tanpa mengubah kontrak controller.

Jangan rename route, controller, model, service, class, atau method existing.
Jangan memindahkan business logic ke Blade atau JavaScript.
Jangan menjalankan query database dari Blade.

Jika controller memang harus berubah, minta approval dengan diff plan yang sempit.

## 7. Git dan Recovery

- Jangan bekerja langsung di `develop` atau branch stabil.
- Pastikan pekerjaan Codex berada di branch `feature/`, `fix/`, `docs/`, atau
  `refactor/` yang sesuai.
- Sebelum task berisiko, buat checkpoint branch yang menunjuk ke commit sebelum
  perubahan Codex.
- Jangan menimpa atau menghapus branch Claude maupun branch checkpoint.
- Jangan commit atau push kecuali diminta owner.
- Jika diminta commit, stage hanya file yang sudah disetujui dan gunakan commit
  kecil untuk satu concern.

Checkpoint awal project Codex:

- branch kerja: `feature/codex-backend-cms-next`
- branch restore: `backup/pre-codex-master-rules-20260618`
- baseline commit: `df17e18`

Cara kembali ke baseline harus dijelaskan sebelum tindakan restore dilakukan.
Jangan menjalankan restore destruktif tanpa instruksi eksplisit owner.

## 8. Testing dan Regression

- Jalankan test yang proporsional dengan perubahan.
- Jalankan focused tests lebih dahulu, lalu suite terkait atau full suite bila perlu.
- Jangan menghapus fitur, assertion, validation, authorization, atau test agar suite lolos.
- Pisahkan failure existing dari regression yang disebabkan perubahan Codex.
- Jangan mengubah production code hanya untuk menyesuaikan test yang stale tanpa
  menganalisis kontrak perilakunya.
- Laporkan command, hasil pass/fail, dan failure yang masih tersisa.

## 9. Format Sebelum Implementasi

Sebelum edit, selalu tampilkan:

```text
Branch aktif:
Baseline/checkpoint:
Task:
Existing behavior yang dipertahankan:
File yang akan dibuat:
File yang akan diubah:
Protected area yang tersentuh: none | daftar
Risiko regression:
Rencana verifikasi:
Rencana rollback:
Approval yang dibutuhkan: none | detail
```

Jangan mulai mengedit sebelum daftar file ditampilkan. Jika protected area atau
breaking change tersentuh, tunggu approval owner.

## 10. Format Setelah Implementasi

```text
## Task: [nama]

### Changed
- `path/file` — perubahan dan alasan

### Preserved
- perilaku existing yang tetap dipertahankan

### Impact
- DB: none | detail approved change
- Routes: none | detail approved change
- Frontend: none | detail
- Backend: none | detail
- Security: none | detail

### Verification
- command — hasil

### Rollback
- langkah aman atau branch/commit restore

### Remaining
- risiko atau pekerjaan yang belum selesai
```

## 11. Stop Conditions

Hentikan implementasi dan minta arahan owner bila:

- scope task tidak jelas dan pilihan akan mengubah perilaku existing;
- perubahan membutuhkan protected file atau directory;
- perubahan membutuhkan schema, migration, auth, atau authorization;
- ditemukan dirty worktree yang overlap dengan file task;
- solusi memerlukan rename, removal, package baru, atau breaking change;
- baseline atau rollback path belum aman.

Analisis read-only boleh dilanjutkan untuk memperjelas blocker.

## 12. Prinsip Akhir

Extend, do not replace. Preserve, then improve. Audit before edit. Explain every
change. Keep the Claude baseline restorable at all times.
