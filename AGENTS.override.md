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

## 7. Git, Claude Baseline, dan Recovery

### 7.1 Working Branch

Codex hanya boleh menjalankan STEP 9 pada branch:

`feature/phase-4-step9-release-gate`

Sebelum melakukan inspeksi atau perubahan, jalankan:

```bash
git branch --show-current
git status --short
git log -10 --oneline --decorate
```

Rules:

* Jangan bekerja langsung di `develop` atau `main`.
* Jangan otomatis membuat, checkout, reset, merge, rebase, atau menghapus branch.
* Jika branch aktif bukan `feature/phase-4-step9-release-gate`, STOP dan laporkan.
* Jika terdapat dirty worktree, identifikasi setiap file dan tentukan apakah perubahan tersebut berasal dari owner, Claude, atau proses sebelumnya.
* Jangan melakukan `git stash`, checkout file, restore file, reset, atau clean tanpa instruksi eksplisit owner.
* Jangan menimpa branch Claude, branch backup, `develop`, atau `main`.

### 7.2 Claude Baseline

Implementasi yang diwarisi dari Claude adalah baseline yang harus dipertahankan.

Sebelum perubahan STEP 9, Codex wajib mengidentifikasi:

* branch atau commit sumber terakhir dari Claude;
* commit parent atau baseline tempat branch STEP 9 dibuat;
* perbedaan antara baseline Claude dan current HEAD;
* file existing yang telah dibangun Claude dan berhubungan dengan task;
* test baseline dan report terakhir yang mendokumentasikan implementasi Claude.

Jangan menggunakan baseline lama yang berasal dari phase atau task lain.

Baseline aktif STEP 9 harus dicatat menggunakan hasil Git aktual:

```text
Phase: Phase 4
Task: STEP 9 — Phase 4 Final Release Gate
Working branch: feature/phase-4-step9-release-gate
Claude baseline branch: <ISI DARI HASIL INSPEKSI GIT>
Claude baseline commit: <ISI HASH COMMIT AKTUAL>
Restore branch: backup/pre-phase4-step9-claude-baseline
Baseline tests: 589 tests, 2721 assertions
```

Nilai placeholder tidak boleh ditebak. Isi hanya setelah diverifikasi melalui Git history.

### 7.3 Restore Branch

Sebelum perubahan production code, konfigurasi, dokumentasi protected, atau test baru, pastikan tersedia restore branch yang menunjuk tepat ke Claude baseline commit:

`backup/pre-phase4-step9-claude-baseline`

Codex tidak boleh membuat restore branch secara otomatis sebelum menampilkan:

* baseline commit yang dipilih;
* alasan commit tersebut merupakan baseline Claude;
* status apakah branch restore sudah ada;
* command yang akan digunakan;
* dampak command;
* cara memverifikasi pointer branch.

Command yang diperbolehkan setelah approval owner:

```bash
git branch backup/pre-phase4-step9-claude-baseline <CLAUDE_BASELINE_COMMIT>
```

Verifikasi:

```bash
git show --no-patch --oneline backup/pre-phase4-step9-claude-baseline
git rev-parse backup/pre-phase4-step9-claude-baseline
git rev-parse <CLAUDE_BASELINE_COMMIT>
```

Kedua hash hasil `rev-parse` harus sama.

Rules:

* Jangan checkout restore branch untuk pekerjaan normal.
* Jangan commit pada restore branch.
* Jangan merge restore branch.
* Jangan rebase, force-update, rename, atau delete restore branch.
* Jangan membuat restore branch dari current HEAD jika current HEAD sudah mengandung perubahan Codex.
* Jika baseline tidak dapat dibuktikan dengan aman, STOP sebelum editing.

### 7.4 Recovery Policy

Recovery harus dilakukan secara non-destructive terlebih dahulu.

Sebelum melakukan recovery, tampilkan:

```text
Masalah:
Current branch:
Current HEAD:
Claude baseline commit:
Restore branch:
File yang perlu dipulihkan:
Perubahan yang akan dipertahankan:
Perubahan yang akan dibuang:
Command recovery:
Dampak:
Risiko:
```

Prioritas recovery:

1. Buat branch penyelamatan dari kondisi current jika ada perubahan yang perlu dipertahankan.
2. Pulihkan file tertentu dari baseline hanya setelah diff diperiksa.
3. Gunakan revert commit untuk perubahan yang sudah committed.
4. Gunakan restore branch sebagai referensi, bukan sebagai target reset otomatis.
5. Gunakan reset hanya jika owner meminta secara eksplisit dan seluruh dampaknya sudah dijelaskan.

Command berikut tetap dilarang tanpa instruksi eksplisit owner:

```bash
git reset --hard
git clean -fd
git checkout -- .
git restore .
git branch -D
git push --force
git push --force-with-lease
```

Contoh pemulihan file tertentu yang hanya boleh dijalankan setelah approval:

```bash
git diff backup/pre-phase4-step9-claude-baseline -- path/to/file
git restore --source backup/pre-phase4-step9-claude-baseline -- path/to/file
```

Jangan memulihkan seluruh repository ketika hanya satu atau beberapa file yang terdampak.

### 7.5 Commit Policy

Codex tidak boleh commit atau push kecuali diminta owner.

Jika diminta commit:

* stage hanya file yang telah disetujui;
* jangan gunakan `git add .`;
* tampilkan `git diff --stat`;
* tampilkan `git diff --cached`;
* gunakan satu commit untuk satu concern;
* jangan memasukkan file owner atau Claude yang tidak terkait;
* jangan amend, squash, atau rewrite history tanpa instruksi eksplisit.

Suggested STEP 9 commit structure:

```text
test(phase4): add final release gate coverage
fix(phase4): resolve verified release blockers
docs(phase4): complete release gate documentation
```

Commit dapat digabung bila perubahan sangat kecil, tetapi setiap file tetap harus dijelaskan.

### 7.6 Release Integration Policy

STEP 9 memiliki dua approval gate yang berbeda:

#### Approval Gate 1

Keyword:

`approved`

Mengizinkan:

* focused test dan full test;
* PHPStan analysis;
* penambahan Q-series tests;
* minimal fixes dalam scope;
* update dokumentasi yang telah disetujui;
* pembuatan report dan final handoff.

Tidak mengizinkan:

* merge ke `develop`;
* merge ke `main`;
* pembuatan tag;
* push branch atau tag;
* package installation;
* schema atau migration changes.

#### Approval Gate 2

Keyword:

`approved release`

Mengizinkan proses release integration sesuai instruksi owner.

Intended flow:

```text
feature/phase-4-step9-release-gate
    → develop
    → main
    → annotated tag v4.0.0
```

Rules:

* Jangan merge seluruh flow dalam satu langkah tanpa checkpoint verifikasi.
* Setelah merge ke `develop`, jalankan ulang full test dan PHPStan.
* Setelah merge ke `main`, jalankan ulang full test dan PHPStan.
* Tag hanya boleh menunjuk ke final verified release commit di `main`.
* Jangan membuat tag jika working tree tidak bersih.
* Jangan memindahkan tag existing.
* Jangan push branch atau tag kecuali owner meminta secara eksplisit.
* Jangan menggunakan force merge, force push, destructive reset, atau history rewriting.

### 7.7 STEP 9 Protected Documentation Exception

Untuk STEP 9, file berikut tetap protected:

* `AGENTS.md`
* `AGENTS.override.md`
* `CLAUDE.md`
* `.claudeignore`
* `.claude/**`
* `.codex/**`
* `ai/skills/**`
* `ai/guidelines/**`

Exception terbatas:

* `AGENTS.md` boleh diperbarui hanya untuk menandai status Phase 4 setelah Release Gate berhasil dan setelah approval owner.
* `AGENTS.override.md` tidak boleh diubah selama pelaksanaan STEP 9, kecuali owner secara eksplisit meminta perubahan guardrail.
* Report baru di `ai/reports/phase-4/**` diperbolehkan sesuai deliverables STEP 9.
* `phase-4-progress-handoff.md` boleh diperbarui sesuai deliverables STEP 9.
* Skill dan guideline tidak boleh diubah hanya untuk menyesuaikan implementasi atau membuat Release Gate terlihat berhasil.

Perubahan terhadap protected documentation tidak boleh digunakan untuk menyembunyikan test failure, PHPStan error, incomplete implementation, atau release blocker.


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
