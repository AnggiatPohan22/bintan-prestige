# Phase 5 Stage B — Session Handoff Prompt

> Copy seluruh isi blok di bawah ini ke session baru.

---

## CONTEXT — Bintan Prestige CMS

**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js | MySQL  
**Branch aktif:** `feature/phase-5-stage-a-foundation`  
**Merge target:** `develop`  
**Repo path (local):** `C:\laragon\www\bintan-prestige`

---

## ATURAN WAJIB (baca sebelum apapun)

1. Baca `AGENTS.md` — master rules (approval gates, 11-step pattern, safety rules)
2. Baca `ai/skills/phase5-visual-builder-skill.md` — master plan Phase 5
3. Jangan ubah schema DB, rename route/controller/model, atau install package tanpa approval eksplisit
4. Selalu list file yang akan berubah sebelum mulai coding
5. Report format wajib setelah setiap task (lihat `CLAUDE.md`)

---

## STATUS PHASE 5 STAGE A — COMPLETE ✓

Owner telah approve Stage A. Semua task committed di branch `feature/phase-5-stage-a-foundation`.

| Commit | Task | Ringkasan |
|---|---|---|
| `76bf1dc` | A2 | Dark sidebar, compact navbar, shared admin components |
| `28b41e2` | A3 | 19 block types, Group/Columns nesting, migration `2026_06_21_000003` (pending run) |
| `a878b6f` | A4 | Bulk revision restore (CASE-WHEN), form pagination, withCount cleanup |
| `149f2ec` | A5 | Token consistency: `--frontend-gold-dark`, charcoal headings, gold focus rings |

**Test suite:** 610 tests, 3072 assertions — semua pass  
**PHPStan:** 0 errors  
**Migration pending:** `2026_06_21_000003_add_parent_block_id_to_page_blocks_table.php` — belum dijalankan di dev DB, hanya di test (RefreshDatabase). Jalankan via proses deployment normal sebelum manual QA.

---

## ARSITEKTUR PENTING YANG SUDAH ADA

### Block system
- Registry: `config/blocks.php` — 19 types, tiap type punya `category`, `description`, `keywords`, `supports`
- Defaults + validation: `app/Services/PageBlockService.php`
- Frontend rendering: `app/Support/PageRenderData.php` + `app/Http/Controllers/Frontend/PageController.php`
- Block tree dibangun in-memory dari 1 flat query — tidak ada N+1
- Nesting: `page_blocks.parent_block_id` (nullable self-FK). Container: `group`, `columns`
- Admin forms: `resources/views/backend/pages/partials/blocks/`
- Frontend partials: `resources/views/frontend/blocks/`

### Revision system (Phase 4)
- `PageRevision` — snapshot meta + content, max 20 per page
- Restore: `PageController::restoreRevision()` — bulk insert + CASE-WHEN update

### Design tokens
- File: `resources/css/frontend-theme.css`
- Token utama: `--frontend-gold: #c8a24a`, `--frontend-gold-dark: #b08735`, `--frontend-charcoal: #17130c`, `--frontend-black: #090806`
- Font: `--frontend-font-display: "Forum"` (headings), `--frontend-font-body: "Montserrat"` (body)

### Transient preview (sudah ada sejak A1)
- Route: `POST admin/pages/{page}/preview-payload`
- Controller: `PageController::previewPayload()` — render page dengan block tree dari request body (tanpa persist)
- Berguna untuk Stage B live preview

---

## YANG HARUS DILAKUKAN SELANJUTNYA — STAGE B

> **Stage B boleh dimulai karena Stage A sudah owner-approved.**  
> Branch Stage B: `feature/phase-5-stage-b-visual-builder`  
> Buat branch baru dari `feature/phase-5-stage-a-foundation`.

### B0 — Architecture Decision (MULAI DI SINI)

Ini **audit + keputusan arsitektur dulu**, bukan coding. Hasilkan report `ai/reports/phase-5/b0-builder-architecture.md` lalu **STOP untuk owner approval** sebelum mulai B1.

Pertanyaan yang harus dijawab dan didokumentasikan:

**1. Canvas model**
- Builder mengedit in-memory JSON block tree (load dari draft page)
- Node format: `{ id, type, data, children[] }`
- Saving: kirim tree ke `previewPayload` untuk preview, ke `PageBlockController` untuk persist

**2. Live preview approach** — pilih satu:
- **Iframe preview** (recommended): arahkan ke route `admin/pages/{page}/preview`, refresh via `postMessage` setelah setiap perubahan. Reuses Blade renderer yang sudah ada.
- **In-canvas render**: lebih cepat tapi preview ≠ production. Tidak recommended.

**3. Drag-and-drop library**
- Alpine.js tidak punya native DnD
- Kandidat: **SortableJS** (ringan, sudah dipakai di banyak Laravel project)
- **Ini perlu package install → wajib approval owner sebelum `npm install`**
- Sebutkan alternatif: HTML5 Drag API (tanpa package, lebih terbatas)

**4. Inline text editing**
- `contenteditable` untuk Text/Heading blocks
- Sanitize server-side — jangan percaya HTML dari client

**Deliverable:** `ai/reports/phase-5/b0-builder-architecture.md`  
**STOP setelah B0. Tunggu approval sebelum B1.**

---

### B1–B7 (setelah B0 approved)

Sesuai `ai/skills/phase5-visual-builder-skill.md`:

- **B1** — Builder shell & canvas (route, left panel, center canvas/iframe, right settings, top bar)
- **B2** — Block insertion & ordering (inserter dari registry, DnD reorder, move in/out container)
- **B3** — Block settings panel (render fields dari registry schema, live preview update)
- **B4** — Inline editing (contenteditable + server-side sanitize)
- **B5** — Reusable patterns & saved blocks
- **B6** — Templates integration
- **B7** — Responsive & preview controls (desktop/tablet/mobile toggle)

---

## FILES KUNCI UNTUK REFERENSI

```
AGENTS.md                                          ← master rules
CLAUDE.md                                          ← project context
ai/skills/phase5-visual-builder-skill.md           ← Phase 5 master plan
ai/reports/phase-5/a1-backend-readiness-audit.md   ← backend findings
ai/reports/phase-5/a3-block-library-plan.md        ← block registry & nesting design
ai/reports/phase-5/a4-efficiency-review.md         ← query efficiency findings
ai/reports/phase-5/a5-frontend-polish-plan.md      ← token consistency findings

config/blocks.php                                  ← block registry (19 types)
app/Services/PageBlockService.php                  ← defaults, validation, sanitization
app/Support/PageRenderData.php                     ← FAQ + Products data prep
app/Http/Controllers/Frontend/PageController.php   ← renderPage, previewPayload, buildTree
app/Http/Controllers/Admin/PageBlockController.php ← CRUD blocks, apiTypes()
resources/views/frontend/blocks/                   ← 19 frontend block partials
resources/views/backend/pages/partials/blocks/     ← 19 admin form partials
resources/css/frontend-theme.css                   ← design tokens
```

---

## PERINTAH VERIFIKASI

Jalankan ini setelah setiap batch sebelum commit:

```bash
php artisan test
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint [files yang diubah]
```

Sebelum manual QA, jalankan migration dulu:
```bash
php artisan migrate
```
