# B12 — Template Resolution + Render

> **Task:** B12 — Template resolution + render untuk single content entry
> **Status:** ✅ DONE — 2026-07-02
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (tanpa schema baru — memakai kolom `template` string yang sudah ada di B4)
> **Skills:** backend-skill · seo-ai-discovery-skill

---

## Ringkasan

Single content entry kini memilih **template render** berdasarkan kolom `template`
(override per-entry, sudah ada sejak B4) dan memancarkan **structured data JSON-LD**
sesuai schema type template. Melengkapi routing B11 dengan lapisan resolusi template
+ SEO structured data.

---

## `ContentEntryTemplateRegistry`

Memetakan string `template` entry → container layout + schema type. Entry template
merender **body saja** (block tree), jadi memetakan ke **kelas container** (bukan
view page penuh seperti `PageTemplateRegistry`). String tak dikenal / kosong → `default`.

| Key | Container | Schema type |
|-----|-----------|-------------|
| `default` | `mx-auto max-w-3xl px-6` | Article |
| `contained` | `mx-auto max-w-4xl px-6` | Article |
| `full-width` | `w-full` | WebPage |

Method: `keys()`, `keyFor($template)`, `containerFor($template)`, `schemaTypeFor($template)`.

**Resolusi:** `entry.template` (jika valid) → key; selain itu → `default`. (Default
per-content-type akan butuh kolom baru = schema gate → ditunda; per-entry sudah cukup.)

---

## Render

`Frontend\ContentEntryController::single()` meresolve `templateKey`, `templateContainer`,
`schemaType` lalu mengirim ke view.

`single.blade.php`:
- Header + block body memakai **container** hasil resolusi (lebar berbeda per template;
  `full-width` merender body tanpa constraint lebar).
- Memancarkan **JSON-LD** (`<script type="application/ld+json">`) dengan `@type`
  Article/WebPage: headline, description, url, datePublished, dateModified, author,
  mainEntityOfPage. Field kosong difilter (`array_filter`).
- Menampilkan tanggal publish.

---

## Report (AGENTS.md §11)

### Changed
- `app/Support/ContentEntryTemplateRegistry.php` — **baru**
- `app/Http/Controllers/Frontend/ContentEntryController.php` — resolusi template di `single()`
- `resources/views/frontend/content-entries/single.blade.php` — container dinamis + JSON-LD
- `tests/Feature/Phase6/B12TemplateResolutionTest.php` — **baru** (8 tests)

### Impact
- **DB:** tidak ada perubahan (pakai kolom `template` dari B4).
- **Routes:** tidak ada perubahan.
- **Frontend publik:** single entry kini responsif terhadap template + punya
  structured data untuk SEO/AI discovery.
- **Security:** `template` string divalidasi lewat registry (whitelist key) sebelum
  dipakai → tidak ada arbitrary view/class injection. JSON-LD di-`json_encode` (aman).
- **SEO:** JSON-LD Article/WebPage per entry meningkatkan crawlability & rich results.

### Verification
- B12 tests: **8/8 pass**, 23 assertions:
  - registry: resolusi key dikenal, fallback default (unknown/empty/null), schema types
  - render: default→max-w-3xl+Article, full-width→WebPage, contained→max-w-4xl+Article
  - invalid template → fallback default container
  - JSON-LD berisi headline + url yang benar
- Full suite: **807/807 pass**, 4047 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert 2395152
```
(Tanpa migration — murni kode.)

### Next
- **B13 — Builder bridge: `content_query` block**: block builder baru yang mengueri
  & menampilkan daftar content entries (mis. "3 blog terbaru") di dalam page/entry
  builder — memakai sidecar index (B6) + relations/terms untuk filter.
