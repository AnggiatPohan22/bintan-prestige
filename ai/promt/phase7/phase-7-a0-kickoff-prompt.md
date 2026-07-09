# Phase 7 — A0 Kickoff Prompt (Architecture Decision Record)
# Paste ini di awal sesi Claude Code baru untuk memulai Phase 7 Task A0.

Kita mulai **Phase 7 — Internationalization (i18n)** untuk Bintan Prestige CMS,
diawali dengan **Task A0 — Architecture Decision Record**. A0 = keputusan, BUKAN
kode. Tujuannya: kunci arsitektur, dapatkan sign-off owner, lalu buat handoff.
Jangan tulis migrasi/kode apa pun di A0.

## STARTUP (wajib, urut — jangan skip):
1. Baca `AGENTS.md` (master rules, authority order §2, safety §8/§9, PATENT RULE gambar §8).
2. Baca `ai/reports/phase-7/phase-7-grand-plan.md` (konstitusi Phase 7 — scope, §3 decisions, stages A/B/C).
3. Baca `ai/reports/phase-6/phase-6-progress-handoff.md` §16 (insiden DB wipe + recovery) dan §14 (Phase 7 prep notes).
4. Baca `ai/skills/media-library-skill.md` → ⭐ Canonical Image Input Standard (berlaku untuk field gambar baru apa pun).
5. Cek fakta i18n codebase sebelum menyimpulkan: `config/app.php` (locale), ada/tidak folder `lang/`, jumlah pemakaian `__()` di `resources/views/frontend/`, kolom locale di model. (Baseline saat plan ditulis: locale `en`, tidak ada `lang/`, 0 pemakaian `__()`.)

## TUGAS A0 (yang harus dihasilkan sesi ini):
1. **Sajikan keputusan A0** dari grand plan §3.1–§3.6 ke owner secara ringkas +
   rekomendasi + trade-off, MINTA owner approve/ubah masing-masing. Yang butuh
   keputusan eksplisit (grand plan §13 First Step):
   - (a) Locale set + **default locale**. Rekomendasi: `id` + `en`, default
     **`en` tanpa prefix** (semua URL & SEO lama tidak berubah). Owner boleh flip
     ke Indonesian-first (konsekuensi: makna semua URL unprefixed berubah).
   - (b) **URL strategy** — prefix `/{locale}/…`, default bare (§3.2). yes/no.
   - (c) **Dua bentuk terjemahan** (§3.3/§3.4): *dokumen* (pages, content_entries)
     = row-per-locale + `translation_group_id`; *atribut* (products, categories,
     destinations, page_sections, menus, terms, site_settings) = satu tabel
     `translations` polimorфik (protected modules extend-only). yes/no.
   - (d) **Menus** (§4): labels via sidecar (Opsi A, rekomendasi) atau menu-per-locale (Opsi B).
   - (e) **Fallback dokumen tak-diterjemahkan** (§3.5): hide/404 di locale itu
     (rekomendasi) atau render fallback default-locale.
   - (f) **Konfirmasi ZERO new package** (§3.6).
2. **STOP dan tunggu jawaban owner** untuk (a)–(f). Jangan lanjut tanpa kata
   "approved" per keputusan. Ini gate §9 (schema-heavy phase).
3. **Setelah owner sign-off:** buat `ai/reports/phase-7/phase-7-progress-handoff.md`
   sebagai living source-of-truth, berisi: keputusan A0 yang di-lock (dengan
   tanggal + kata-kata approval owner), tabel status task §1 (A0 ✅, A1/A2 + B1–B10
   + C1–C4 = ⏳), milestones §2, data model final (§4 plan), skill map rows yang
   akan ditambah, dan Documentation Sync Matrix. Pola: tiru
   `ai/reports/phase-6/phase-6-progress-handoff.md`.
4. Tulis report task A0: `ai/reports/phase-7/a0-architecture-decisions.md`
   (format AGENTS.md §11: Task / Changed / Impact / Rollback / Next).

## ATURAN KERJA (Phase 7):
- A0 tidak menyentuh kode/DB. Semua migrasi Phase 7 nanti: **additive + reversible**.
- ⚠️ **JANGAN `migrate:fresh`/`migrate:reset` di DB utama** — dev DB berisi data
  hasil recovery (Phase 6 §16). Sebelum ALTER tabel existing (B4/B5): `mysqldump` dulu.
- Protected modules (Products, Bookings, Page Sections, Global Settings) **extend-only**
  via `translations` sidecar — jangan rebuild, jangan ubah kolom asli (base column =
  default locale supaya modul tetap jalan walau Phase 7 di-revert).
- Field gambar baru apa pun WAJIB lewat Media Library standard (AGENTS.md §8 +
  `media-library-skill.md`). Tidak ada `<input type="file">` mentah.
- Hard gate: PHPStan level 5 / 0 error; test suite hijau (**baseline 866**, no regression).
- No queries in Blade. Reuse: `Route::fallback` (Phase 6 B11), sidecar `content_entry_index`,
  Phase 4 revisions/scheduling/audit/SEO, theme tokens.
- Admin UI TIDAK diterjemahkan (out of scope §1.2) — hanya konten + situs publik.

## GATE APPROVAL Phase 7 (untuk konteks — A0 hanya butuh sign-off keputusan):
- A0 = decision set (§3). A1 = edit `StructuredDataBuilder` (Phase 4). A2 = route
  prefix group. B1 = tabel `translations`. B4/B5 = ALTER + unique-index change di
  `pages`/`content_entries`. Semua butuh "approved" eksplisit di task masing-masing.

## GIT:
- Lanjut di branch `feature/phase-7-planning` (sudah ada, berisi grand plan Phase 7 & 8).
- Saat mulai task kode pertama (A1/A2), rename/lanjut ke `feature/phase-7-a1-foundation`
  atau buat branch task dari develop sesuai preferensi owner.
- Commit fokus satu concern; dokumentasikan rollback. Jangan commit ke develop langsung.

## FOLDER STRUCTURE Phase 7 reports:
```
ai/reports/phase-7/
├── phase-7-grand-plan.md            ← konstitusi (sudah ada — jangan ubah kecuali owner minta)
├── phase-7-progress-handoff.md      ← BUAT di A0 setelah sign-off (living doc)
├── a0-architecture-decisions.md     ← report A0 (buat sesi ini)
├── a1-...  a2-...  b1-... dst        ← report per task berikutnya
└── c4-documentation.md
```

Mulai dengan: konfirmasi kamu sudah baca STARTUP 1–5, lalu sajikan keputusan A0
(a)–(f) dengan rekomendasi + trade-off ringkas, dan **tunggu jawaban owner**.
Jangan buat handoff/kode sebelum owner approve.
