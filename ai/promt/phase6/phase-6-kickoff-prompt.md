# Phase 6 — Kickoff Prompt
# Paste ini di awal setiap sesi Claude Code Desktop untuk Phase 6

Kita mulai Phase 6 — Flexible Content Modeling untuk Bintan Prestige CMS.

## STARTUP (wajib, urut — jangan skip):
1. Baca `AGENTS.md` (master rules + authority order + safety §8/§9).
2. Baca `ai/reports/phase-6/phase-6-grand-plan.md` (konstitusi Phase 6 — scope, arsitektur, stages).
3. Baca `ai/reports/phase-6/phase-6-progress-handoff.md` (living source-of-truth — status task & decisions).
4. Baca skill file yang relevan dengan task dari Skill Map (jangan baca semua).

## ATURAN KERJA:
- Kerjakan HANYA SATU task '⏳ TODO' berikutnya dari handoff §1, sesuai urutan milestone §2.
- Sebelum tiap edit: inspect file existing, list file yang berubah, jelaskan plan singkat. Implement step kecil.
- Ikuti AGENTS.md §6 module pattern (migration → model → form request → controller → service → views → frontend → seo → security → docs → tests).
- STOP di tiap gate '⚠️' dan minta approval owner eksplisit sebelum: schema change apapun, ubah page_blocks, install package, route ordering. Jangan jalan tanpa kata "approved".
- Jaga hard gate: PHPStan level 5 / 0 error, test suite tetap hijau (baseline 627, no regression). Tulis test untuk tiap modul baru.
- Reuse, jangan fork: InlineContentSanitizer, BuilderTreeSanitizer, Media Library, Phase 4 revisions/scheduling/audit/SEO. Jangan sentuh protected modules (Products/Bookings/Page Sections/Global Settings) — boleh di-link via relationship field saja.
- No queries in Blade — siapkan data di service/PageRenderData.

## KEPUTUSAN A0 YANG MASIH PENDING:
- Entry-body model (handoff §3.2): tunggu owner pilih A (polymorphic page_blocks) atau B (kolom JSON body) SEBELUM mulai A3/B9. Sisanya (Hybrid storage, zero new package) sudah locked.

## SETELAH TIAP TASK SELESAI (wajib, tidak boleh dilewat):
1. Update status task di `ai/reports/phase-6/phase-6-progress-handoff.md` §1 (⏳ → ✅) + isi kolom Report.
2. Tulis report task di `ai/reports/phase-6/` dengan format nama file:
   `[task-id]-[nama-task-singkat].md` (contoh: `a1-debt-clearing-td04-td05.md`, `b1-content-types-module.md`).
3. Jalankan Documentation Sync Matrix (handoff §12) untuk task itu:
   - Modul/skill baru → buat skill file di `ai/skills/` + tambah row di Skill Map `AGENTS.md` §3 DAN `Claude.md`.
   - Route/tabel baru → update handoff §6/§7/§4 + docs reference.
   - Selesai task → append `docs/changelog/CHANGELOG.md`.
   - Selesai Phase → flip `AGENTS.md` §4 (Phase 6 COMPLETE, Phase 7 next) + update `Claude.md` Phase line.
4. Tulis report format AGENTS.md §11 (Task / Changed / Impact / Rollback / Next) di file report task §2 di atas.

## GIT:
- Branch dari develop: `git checkout develop && git pull`, lalu `git checkout -b feature/phase-6-[task]`.
- Commit fokus satu concern, dokumentasikan rollback.

## FOLDER STRUCTURE untuk Phase 6 reports:
```
ai/reports/phase-6/
├── phase-6-grand-plan.md            ← konstitusi (jangan ubah kecuali owner minta)
├── phase-6-progress-handoff.md      ← living doc (update setiap task selesai)
├── a0-architecture-decisions.md     ← report per task
├── a1-debt-clearing-td04-td05.md
├── a2-pagination-formrequest.md
├── b1-content-types-module.md
├── ...dst per task
└── c4-documentation.md
```

Mulai dengan konfirmasi: task '⏳ TODO' berikutnya yang akan dikerjakan, file yang akan berubah, dan apakah ada gate '⚠️' yang butuh approval saya dulu. Jangan tulis kode sampai saya jawab.
