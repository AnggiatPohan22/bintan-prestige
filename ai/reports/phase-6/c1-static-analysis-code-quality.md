# C1 — Static Analysis & Code Quality (Stage C Release Audit)

> **Task:** C1 — Static Analysis & Code Quality
> **Status:** ✅ DONE — 2026-07-07
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (audit; satu hardening fix di kode Phase 6)
> **Skills:** security-skill · testing-qa-skill

---

## Ringkasan

Audit statis & kualitas kode untuk seluruh Phase 6 (B1–B14). Semua gate hijau.
Satu temuan keamanan nyata di kode Phase 6 (**JSON-LD script breakout**) ditemukan
dan diperbaiki + regression test. Satu temuan pre-existing (Phase 4) dicatat untuk
keputusan owner.

---

## 1. PHPStan (level 5)

```
vendor/bin/phpstan analyse --level=5
```
**Hasil: 0 errors** — tanpa ignore, tanpa baseline (konsisten sejak Phase 4).

## 2. Test Suite

```
php artisan test
```
**Hasil: 836/836 pass**, 4142 assertions, 0 failures.
- Phase 6 tests: A3, B5–B14 (per-step) + Admin CRUD (ContentType, ContentEntry,
  FieldGroup, Field) + Frontend routing.

## 3. Audit Unescaped Output (`{!! !!}`)

Dua penggunaan di view Phase 6 — keduanya ditinjau:

| File | Ekspresi | Status |
|------|----------|--------|
| `frontend/blocks/content-field.blade.php:22` | `{!! $field['html'] !!}` | ✅ **Aman** — richtext disanitasi via `InlineContentSanitizer::richtext()` di `ContentFieldResolver` sebelum sampai Blade |
| `frontend/content-entries/single.blade.php:21` | `{!! json_encode($structured, …) !!}` | ⚠️ **DIPERBAIKI** (lihat §5) |

Tidak ada `{!! !!}` lain di view Phase 6 (content-types, content-entries, taxonomies,
fields, builder entry/topbar).

## 4. Debug / Dead Code Scan

- `dd()` / `dump()` / `var_dump()` / `ray()` di `app/`: **nihil**.
- `console.log` / `debugger` di view builder Phase 6: **nihil**.
- `TODO` / `FIXME` / `XXX` / `HACK` di service/controller Phase 6: **nihil**.
- Unused imports/dead methods: ditangkap PHPStan (0 errors).

---

## 5. Temuan Keamanan #1 — JSON-LD script breakout (Phase 6) → **FIXED**

**File:** `resources/views/frontend/content-entries/single.blade.php`

**Masalah:** JSON-LD di-`json_encode` dengan `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
saja. `json_encode` **tidak** meng-escape `<` dan `>` secara default, sehingga nilai
yang mengandung `</script>` (mis. di **title** atau **excerpt** entry) bisa menutup
tag `<script type="application/ld+json">` lebih awal → **stored XSS**.

**Contoh payload:** title = `Evil </script><script>alert(1)</script>`.

**Perbaikan:** tambahkan flag hex —
`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
`<`/`>`/`&`/`'`/`"` kini di-escape ke `<` dst. sehingga aman di dalam konteks `<script>`.

**Regression test:** `B12TemplateResolutionTest::test_structured_data_escapes_script_breakout`
— membuat entry dengan title berisi `</script><script>alert(1)</script>`, memuat single
page, memastikan string breakout mentah **tidak muncul** di HTML. Test ini **gagal**
tanpa fix, **lolos** dengan fix.

## 6. Temuan #2 — Structured data site-wide (Phase 4) → **DICATAT (butuh keputusan owner)**

**File:** `app/Support/StructuredDataBuilder.php:287`

Memakai pola flag lemah yang sama (`JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
tanpa `JSON_HEX_TAG`) untuk JSON-LD `@graph` site-wide (dirender di setiap halaman).
Risiko lebih rendah karena input mayoritas **admin-controlled** (nama bisnis, setting),
tapi idealnya diselaraskan dengan flag aman yang sama.

**Rekomendasi:** terapkan set flag `JSON_HEX_*` yang sama di `StructuredDataBuilder`.
**Tidak diubah di C1** karena kode Phase 4 di luar scope + butuh approval (AGENTS.md §8).
Diusulkan sebagai fix terpisah kecil.

---

## Report (AGENTS.md §11)

### Changed
- `resources/views/frontend/content-entries/single.blade.php` — JSON-LD hex-escape flags
- `tests/Feature/Phase6/B12TemplateResolutionTest.php` — +1 regression test (script breakout)
- `ai/reports/phase-6/c1-static-analysis-code-quality.md` — **baru** (laporan ini)

### Impact
- **DB/Routes:** tidak ada perubahan.
- **Security:** menutup vektor stored-XSS via JSON-LD di single entry.
- **Frontend:** JSON-LD tetap valid (hex escape transparan bagi parser JSON/crawler).

### Verification
- PHPStan level 5: **0 errors**.
- Full suite: **836/836 pass**, 4142 assertions (+1 regression test untuk script breakout).
- Manual review `{!! !!}`, debug, TODO: bersih.

### Next
- **C2 — Performance Audit**: route publik (archive/single/content_query) ≤300ms warm,
  cek N+1 pada entries + fields + relations + terms, verifikasi index sidecar, ukuran
  bundle asset.
