# B14 — Builder Bridge: `content_field` Block

> **Task:** B14 — Builder bridge: `content_field` block (block terakhir Stage B / M4)
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (tanpa schema baru)
> **Skills:** page-builder-skill · backend-skill · security-skill

---

## Ringkasan

Block builder **`content_field`** menampilkan nilai **satu field** dari content
entry — entry **saat ini** (di body entry) atau entry **spesifik by ID**. Melengkapi
jembatan Phase 6 ↔ visual builder Phase 5. Dengan B13 (content_query) + B14
(content_field), builder kini bisa menyusun template dinamis penuh dari data entry.

**Milestone M4 (Public + visual) — semua block bridge selesai.**

---

## Definisi block (`config/blocks.php`)

| Field | Type | Keterangan |
|-------|------|-----------|
| `field_key` | text | key field yang ditampilkan (mis. `star_rating`) |
| `entry_id` | number (opsional) | entry spesifik; kosong = entry saat ini (di body) |
| `label` | text (opsional) | label custom; default = label field |
| `show_label` | toggle | tampilkan label |

Didaftarkan di `PageBlockService::defaultDataFor()` + `rulesFor()` (`entry_id`
exists:content_entries). Idempotent terhadap kontrak registry (`PageBlockManagementTest` hijau).

## `ContentFieldResolver` (shared)

`resolve(array $data, ?ContentEntry $currentEntry): ?array` → `{label,type,text,html,href,show_label}` atau null.

- **Target entry:** `entry_id` (jika ada) → entry **published + public** by id; else entry saat ini.
- **Lookup field def** by key (untuk type + label). Key orphan (tanpa Field def) → type `text`, label = key.
- **Format per tipe (aman):**
  - `toggle` → "Yes"/"No"
  - `richtext` → HTML disanitasi via `InlineContentSanitizer::richtext()`
  - `url` → href **hanya** jika `http(s)://` valid; selain itu render plain text (**tidak pernah** `javascript:` link)
  - `email` → href `mailto:` hanya jika email valid
  - `checkbox`/`gallery`/`relationship` (array) → CSV
  - lainnya → teks di-escape Blade

Dipakai bersama: `PageRenderData` (pages — hanya block ber-`entry_id`) &
`ContentEntryController::resolveBridgeBlocks()` (entry body — entry saat ini sebagai konteks, rekursif ke children).

## Render partial

`frontend/blocks/content-field.blade.php` — label opsional + nilai type-aware.
richtext via `{!! !!}` (sudah disanitasi); url/email via `href` aman; sisanya `{{ }}` (escaped).

---

## Report (AGENTS.md §11)

### Changed
- `config/blocks.php` — definisi block `content_field`
- `app/Services/PageBlockService.php` — `content_field` di `defaultDataFor()` + `rulesFor()`
- `app/Support/ContentFieldResolver.php` — **baru** (resolver bersama, format aman per tipe)
- `app/Support/PageRenderData.php` — `prepareContentFieldBlocks()` + inject resolver
- `app/Http/Controllers/Frontend/ContentEntryController.php` — `resolveBridgeBlocks()` (current entry + rekursif)
- `app/Models/PageBlock.php` — `@property resolvedField`
- `resources/views/frontend/blocks/content-field.blade.php` — **baru**
- `tests/Feature/Phase6/B14ContentFieldBlockTest.php` — **baru** (9 tests)

### Impact
- **DB/Routes:** tidak ada perubahan.
- **Builder:** block "Content Field" baru di kategori `content` (page + entry builder).
- **Frontend:** menampilkan nilai field entry di body/page.
- **Security:** `entry_id` exists:content_entries; entry spesifik wajib **published +
  public**; richtext disanitasi; url/email href diguard (anti `javascript:`); nilai
  di-escape Blade. Tidak ada query di Blade.
- **Phase 5:** tidak ada perubahan perilaku page builder.

### Verification
- B14 tests: **9/9 pass**, 18 assertions:
  - registry terdaftar
  - baca field entry saat ini; null untuk key kosong/nilai hilang/tanpa entry
  - toggle → Yes/No
  - href aman hanya untuk url valid (javascript: → plain text)
  - label custom override; entry spesifik override current; gate published+public
  - render nilai entry saat ini di body
- Kontrak registry (`PageBlockManagementTest`) tetap hijau.
- Full suite: **824/824 pass**, 4109 assertions. PHPStan level 5: **0 errors**.

### Catatan teknis
Larastan salah menganggap `Field::...->first()` non-null; digunakan `optional($field)`
agar null-safe tanpa `@phpstan-ignore` (menjaga kebijakan no-ignore/no-baseline).

### Rollback
```bash
git revert 283de3b
```
(Tanpa migration — murni kode.)

### Next
- **Stage B SELESAI (B1–B14).** Lanjut **Stage C — Release Audit**: C1 static analysis
  & code quality, C2 performance audit, C3 functional smoke test, C4 documentation.

---

## Post-Release Fix Log

> Catatan peningkatan dari pengujian manual owner. Tidak menimpa laporan asli di atas.

### 2026-07-07 — Enhance #1: field picker (dropdown) + nilai muncul di preview builder

**Permintaan owner:** (1) tidak semua user hafal *field key*, jadi block content_field
sebaiknya menampilkan **dropdown field otomatis** untuk dipilih; (2) nilai field yang
dipilih harus **muncul di preview builder** supaya bisa dicek sebelum Save.

**Perubahan:**
- **Dropdown field:** `content_field.field_key` di `config/blocks.php` diubah dari
  input `text` → `select` dengan `optionsFrom: 'entry_fields'`.
  `ContentEntryBuilderController::show()` kini menyediakan option source
  **`entry_fields`** = daftar field content type entry ini (`label (key)`), terurut
  `sort_order`. `PageBuilderController` menyediakan `entry_fields = []` (di Page tak
  ada "current entry" → pakai kolom Entry ID).
- **Preview resolusi:** `previewPayload()` kini memanggil `resolveBridgeBlocks()`
  (rekursif) → set `resolvedField` (content_field, konteks entry saat ini) +
  `resolvedEntries` (content_query) pada transient tree, sehingga nilainya **terender
  di iframe preview** sebelum tree disimpan.

**File:**
- `config/blocks.php` — field_key → select optionsFrom entry_fields
- `app/Http/Controllers/Admin/ContentEntryBuilderController.php` — inject resolver,
  option `entry_fields`, `resolveBridgeBlocks()` di previewPayload
- `app/Http/Controllers/Admin/PageBuilderController.php` — `entry_fields => []`
- `tests/Feature/Phase6/B14ContentFieldBlockTest.php` — +2 test (nilai muncul di
  preview; builder mengekspos option `entry_fields`)

**Impact:** tidak ada schema. Phase 5 page builder aman (hanya penambahan option
source kosong). Suite hijau, PHPStan 0 errors.
