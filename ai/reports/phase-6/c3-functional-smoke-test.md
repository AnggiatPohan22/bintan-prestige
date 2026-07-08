# C3 — Functional Smoke Test (Stage C Release Audit)

> **Task:** C3 — Functional Smoke Test
> **Status:** ✅ DONE — 2026-07-07
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (audit; hanya test baru)
> **Skills:** testing-qa-skill

---

## Ringkasan

Smoke test end-to-end untuk wiring Phase 6: schema, registry↔view, guard admin,
route publik, dan command scheduler. Semua hijau — tidak ada regresi.

---

## Cakupan (`C3SmokeTest`, 7 test / 51 assertions)

### 1. Schema Phase 6 lengkap
Verifikasi 10 tabel Phase 6 ada: `content_types`, `field_groups`, `fields`,
`content_entries`, `content_entry_index`, `taxonomies`, `terms`,
`content_entry_term`, `content_entry_relations`, `content_entry_revisions`.
Plus kolom morph `page_blocks.blockable_type` / `blockable_id` (A3).

### 2. Registry block ↔ file view (paling penting)
Untuk **setiap** block terdaftar di `config('blocks')`, dipastikan view render
`frontend.blocks.{kebab}` **ada**. Ini menangkap partial hilang — termasuk
`content-query` + `content-field` (B13/B14). Semua block punya view.

### 3. Bridge block terdaftar
`content_query` + `content_field` ada di registry.

### 4. Guard route admin
`admin.content-types.index` + `admin.taxonomies.index`:
- guest → redirect `login`
- non-admin → 403
- admin → 200

### 5. Route publik + draft guard
- `/blog` (archive) → 200, tampil entri published, sembunyikan draft
- `/blog/live` (single published) → 200
- `/blog/hidden` (draft) → 404
- `/blog/missing` (slug tak ada) → 404

### 6. Scheduler command
`content-entries:publish-scheduled` → sukses.

---

## Regresi Phase 1–5

Full suite **838 → 845** (C3 menambah 7 test) tetap **hijau** — modul lama (Pages,
Products, Menu, Builder Phase 5, Theme, Plugin, SEO) tidak terpengaruh. Route
`fallback` (B11) terbukti tidak menggeser route eksplisit (diuji juga di B11).

---

## Report (AGENTS.md §11)

### Changed
- `tests/Feature/Phase6/C3SmokeTest.php` — **baru** (7 smoke test)
- `ai/reports/phase-6/c3-functional-smoke-test.md` — **baru** (laporan ini)

### Impact
- **DB/Routes/Frontend:** tidak ada perubahan kode aplikasi — murni verifikasi.

### Verification
- C3 tests: **7/7 pass**, 51 assertions.
- Full suite: **845/845 pass**. PHPStan level 5: **0 errors**.

### Catatan
- Bug ditemukan & diperbaiki saat menulis test (test-only): `actingAs` persist antar
  iterasi loop → fase guest/non-admin/admin dipisah. Bukan bug aplikasi.

### Next
- **C4 — Documentation**: dokumen arsitektur modul content modeling, update CHANGELOG
  Phase 6, finalisasi handoff + release gate summary.
