# STAGE A1 — Backend Readiness Audit for Visual Page Builder
# Bintan Prestige CMS — Phase 5 Stage A
# New Claude Code session — paste this prompt and run

---

## Siapa kamu dan apa tugasmu

Kamu adalah Claude Code yang memulai Phase 5 Stage A untuk Bintan Prestige CMS.
Ini sesi baru. Kamu tidak punya memori sesi sebelumnya.

Tugas sesi ini ada empat — jalankan berurutan:
1. Sinkronisasi AGENTS.md ke status terkini (Phase 4 COMPLETE, Phase 5 CURRENT)
2. Pasang skill file Phase 5 ke `ai/skills/`
3. Jalankan audit read-only backend block system
4. Buat report `ai/reports/phase-5/a1-backend-readiness-audit.md`

Setelah report selesai: STOP. Jangan implementasi apapun. Tunggu owner approval.

---

## Aturan wajib sesi ini

DILARANG:
- Mengubah schema database, migration, model, controller, route apapun
- Menghapus, rename, atau replace file apapun
- Install package baru
- Commit atau push otomatis
- Mengubah kode produksi — ini AUDIT ONLY (read-only inspection)

BOLEH:
- Membaca semua file source code
- Menjalankan `php artisan tinker` untuk inspeksi data/schema
- Menjalankan `php artisan route:list` dan artisan info commands
- Membuat file baru HANYA di `ai/reports/` dan `ai/skills/`
- Update section tertentu di `AGENTS.md` (Phase status saja)

---

## FASE 1 — Baca konteks

```bash
cat AGENTS.md
cat CLAUDE.md 2>/dev/null || echo "[tidak ada CLAUDE.md]"
cat AGENTS.override.md 2>/dev/null || echo "[tidak ada override]"
ls ai/skills/
ls ai/reports/ 2>/dev/null
ls ai/reports/phase-4/ 2>/dev/null
cat ai/reports/phase-4/phase-4-progress-handoff.md 2>/dev/null || echo "[tidak ada handoff]"
```

Setelah membaca, tulis:

```
=== STATUS SAAT INI ===
Phase 1: COMPLETE ✅
Phase 2: COMPLETE ✅
Phase 3: COMPLETE ✅
Phase 4: COMPLETE ✅ (v4.0.0)
Phase 5: STARTING NOW
AGENTS.md perlu update: YA / TIDAK
===
```

---

## FASE 2 — Update AGENTS.md Phase status

AGENTS.md saat ini kemungkinan masih menunjukkan Phase 2 sebagai CURRENT.
Update HANYA Section 4 (CMS Architecture Phases) menjadi:

```markdown
## 4. CMS Architecture Phases

**Phase 1 — COMPLETE ✅**
Backend data syncs to frontend.
Products, categories, destinations, bookings, page sections, global settings (13 modules), user roles, site assets.

**Phase 2 — COMPLETE ✅**
Full website builder from admin dashboard. Goal: WordPress-like control.
- Generic Pages module (About, Contact, Privacy, etc.)
- Block editor (Hero, Text, Image, Gallery, CTA, Products, FAQ blocks)
- Menu manager (header, footer, mobile navigation)
- Media library (central asset manager)
- Template system (page templates selectable from admin)
- Preview/draft mode

**Phase 3 — COMPLETE ✅**
Theme system — design tokens, token editor, Google Fonts integration,
theme export/import (ZIP), extended token inheritance.

**Phase 4 — COMPLETE ✅** (v4.0.0 — 2026-06-21)
Plugin & Module System — plugin registry/lifecycle, hook & filter event system,
content revision history, content scheduling, admin audit log, SEO manager
(sitemap/robots.txt/redirects/noindex), contact form builder plugin,
analytics dashboard plugin, plugin security & sandboxing.
- Test suite: 596 tests / 2765 assertions / 0 failures
- PHPStan: level 5 / 0 errors / no ignores / no baseline

**Phase 5 — CURRENT 🔨**
Visual Page Builder & Foundation Hardening.
Stage A: admin UX refactor, block library expansion, backend readiness, efficiency review, frontend polish.
Stage B: drag-and-drop builder, live preview, inline editing, reusable patterns.

**Phase 6 — FUTURE**
Flexible Content Modeling — custom content types & fields from admin.

**Phase 7 — FUTURE**
Internationalization — multi-language content for Bintan tourism market.

**Phase 8 — FUTURE**
Operational Maturity — backup/restore, import/export, monitoring dashboard.
```

Juga update Section 3 (Skill Map) — tambahkan entry baru:

```markdown
| Visual Builder / Phase 5 | `phase5-visual-builder-skill.md` + `page-builder-skill.md` |
```

Gunakan `str_replace` yang presisi. HANYA ubah Section 4 dan tambah satu baris di Section 3.
Jangan ubah section lain.

Setelah update:

```bash
# Verifikasi perubahan
grep -A2 "Phase 4\|Phase 5\|Phase 6" AGENTS.md
```

---

## FASE 3 — Pasang skill file Phase 5

Cek apakah skill file sudah ada:

```bash
ls ai/skills/phase5-visual-builder-skill.md 2>/dev/null \
  && echo "SUDAH ADA — skip" || echo "BELUM ADA — perlu dibuat"
```

Jika belum ada, buat file `ai/skills/phase5-visual-builder-skill.md` dengan isi
lengkap dari dokumen Phase 5 master plan (owner akan provide atau sudah ada di uploads).

Jika owner belum menyediakan file tersebut, buat placeholder minimal:

```bash
mkdir -p ai/skills
```

```markdown
# Phase 5 — Visual Page Builder Skill File

> Placeholder — owner akan replace dengan dokumen lengkap.
> Lihat ai/reports/phase-5/ untuk detail audit per stage.

## Scope
- Stage A: Foundation Hardening (A1–A5)
- Stage B: Visual Page Builder (B0–B7)

## Rules
- Stage A must complete before Stage B begins
- Every task starts with read-only audit, then plan, then owner approval
- No schema change, rename, or package install without approval
```

---

## FASE 4 — Git state verification

```bash
git branch --show-current
git status --short
git log -3 --oneline --decorate
```

Konfirmasi kamu di branch `develop` (atau buat branch baru untuk Phase 5 Stage A):

```bash
# Jika belum di develop, checkout dulu
git checkout develop 2>/dev/null
git pull origin develop 2>/dev/null

# Buat branch Stage A
git checkout -b feature/phase-5-stage-a-foundation
git branch --show-current
```

---

## FASE 5 — Backend readiness audit (INTI SESI INI)

Ini adalah audit read-only. Baca kode, jalankan inspeksi, catat temuan.
JANGAN ubah kode produksi apapun.

---

### 5A — Block storage format

Temukan bagaimana block disimpan:

```bash
# Cari migration yang berhubungan dengan block/page content
grep -rn "block\|content\|section" database/migrations/ --include="*.php" \
  | grep -i "create\|table\|column\|json\|text" | head -20

# Cari model Block atau PageBlock
find app -name "*Block*" -o -name "*block*" | grep -v vendor | head -10

# Inspect model yang ditemukan
cat $(find app/Models -iname "*Block*" -o -iname "*Page*" | head -5) 2>/dev/null

# Cari schema dari tabel block
php artisan tinker --execute="
  \$columns = \Illuminate\Support\Facades\Schema::getColumnListing('page_blocks');
  echo 'page_blocks columns: ' . implode(', ', \$columns);
" 2>/dev/null || echo "Tabel mungkin bernama lain — cek manual"

# Cari semua tabel yang berhubungan dengan block/page
php artisan tinker --execute="
  \$tables = \Illuminate\Support\Facades\Schema::getTables();
  foreach(\$tables as \$t) {
    \$name = is_array(\$t) ? \$t['name'] : \$t;
    if(stripos(\$name,'block') !== false || stripos(\$name,'page') !== false || stripos(\$name,'section') !== false) {
      echo \$name . PHP_EOL;
    }
  }
" 2>/dev/null
```

Catat:
- Nama tabel yang menyimpan block
- Kolom yang ada (type, content/data, order/position, parent_id)
- Apakah data block disimpan sebagai JSON, serialized, atau kolom terpisah
- Apakah ada kolom `parent_id` atau `children` (nesting support)
- Contoh data dari satu record:

```bash
php artisan tinker --execute="
  # Ganti model name sesuai yang ditemukan
  \$block = \App\Models\PageBlock::first();
  if(\$block) { echo json_encode(\$block->toArray(), JSON_PRETTY_PRINT); }
  else { echo 'Tidak ada data block'; }
" 2>/dev/null
```

---

### 5B — Block type registry

Cari apakah ada satu tempat yang mendefinisikan semua block types:

```bash
# Cari registry, config, atau enum block types
grep -rn "block_types\|blockTypes\|BlockType\|BLOCK_TYPE\|registerBlock\|availableBlocks" \
  app/ config/ --include="*.php" | grep -v vendor | head -20

# Cari apakah block types didefinisikan di config
cat config/blocks.php 2>/dev/null || echo "Tidak ada config/blocks.php"
cat config/page-builder.php 2>/dev/null || echo "Tidak ada config/page-builder.php"

# Cari class yang mengatur block rendering
grep -rn "renderBlock\|BlockRenderer\|block_render\|blockComponent" \
  app/ --include="*.php" | grep -v vendor | head -20

# Cari Blade components untuk block
ls resources/views/components/blocks/ 2>/dev/null || \
ls resources/views/blocks/ 2>/dev/null || \
ls resources/views/frontend/blocks/ 2>/dev/null || \
echo "Cari lokasi block Blade views manual"

find resources/views -name "*block*" -type f 2>/dev/null | head -20
find resources/views -name "*block*" -type d 2>/dev/null | head -10
```

Catat:
- Lokasi block type definitions (satu tempat vs tersebar?)
- Apakah setiap block type mendeklarasikan field schema (editable attributes)?
- Mapping: `block type → admin editing form → frontend Blade render`
- Berapa block types yang saat ini terdaftar?
- List block types yang ditemukan beserta lokasi file-nya

---

### 5C — Draft vs published mechanism

```bash
# Cari mekanisme draft/publish di Page model
grep -rn "draft\|published\|status\|is_published\|preview" \
  $(find app/Models -iname "*Page*" | head -3) 2>/dev/null | head -20

# Cari preview route
php artisan route:list 2>/dev/null | grep -i "preview" | head -10

# Cari apakah block tree punya draft version terpisah
grep -rn "draft\|published\|version\|revision" \
  $(find app/Models -iname "*Block*" | head -3) 2>/dev/null | head -20

# Cari content revision system dari Phase 4
find app -iname "*Revision*" -o -iname "*revision*" | grep -v vendor | head -10
grep -rn "revision\|Revision\|snapshot\|history" \
  app/Models/ --include="*.php" | head -20
```

Catat:
- Apakah page punya status field (draft/published)?
- Apakah block tree bisa di-draft terpisah dari versi live?
- Bagaimana Phase 4 revision system menyimpan snapshot?
- Apakah revision bisa me-restore full block tree?

---

### 5D — Rendering pipeline

```bash
# Bagaimana block di-render ke frontend HTML?
# Cari controller yang render halaman publik
grep -rn "render\|block\|section" \
  $(find app/Http/Controllers -iname "*Page*" -o -iname "*Frontend*" | head -5) \
  2>/dev/null | head -20

# Cari Blade template yang loop blocks
grep -rn "@foreach.*block\|@each.*block\|renderBlock\|block->type" \
  resources/views/ --include="*.blade.php" | head -20

# Cari apakah ada BlockRenderer service
find app -iname "*BlockRender*" -o -iname "*block*render*" | grep -v vendor | head -10
cat $(find app -iname "*BlockRender*" | grep -v vendor | head -1) 2>/dev/null
```

Catat:
- Apakah ada satu `BlockRenderer` class atau rendering tersebar?
- Apakah setiap block type punya Blade component sendiri?
- Bagaimana block attributes diteruskan ke Blade?
- Apakah rendering pipeline bisa digunakan ulang untuk live preview (iframe)?

---

### 5E — Media library integration

```bash
# Inspect media model
cat $(find app/Models -iname "*Media*" | head -1) 2>/dev/null

# Cari media picker / selector di admin views
grep -rn "media-picker\|mediaPicker\|media_picker\|selectMedia\|openMediaLibrary" \
  resources/views/ --include="*.blade.php" | head -10
grep -rn "media-picker\|mediaPicker\|media_picker\|selectMedia\|openMediaLibrary" \
  resources/js/ public/js/ --include="*.js" 2>/dev/null | head -10

# Cari response format media (id, url, alt)
grep -rn "toArray\|toJson\|resource\|MediaResource" \
  $(find app -iname "*Media*" | grep -v vendor | head -3) 2>/dev/null | head -10
```

Catat:
- Apakah media library bisa mengembalikan payload `{id, url, alt, type}` untuk JS?
- Apakah media picker sudah bisa dipanggil dari Alpine.js component?

---

### 5F — Nesting support assessment

```bash
# Cek apakah block model punya parent_id / children relationship
grep -rn "parent\|children\|nested\|hasMany.*Block\|belongsTo.*Block" \
  $(find app/Models -iname "*Block*" | head -3) 2>/dev/null | head -10

# Cek apakah block table punya parent_id column
php artisan tinker --execute="
  \$tables = ['page_blocks','blocks','content_blocks','page_sections'];
  foreach(\$tables as \$t) {
    try {
      \$cols = \Illuminate\Support\Facades\Schema::getColumnListing(\$t);
      if(in_array('parent_id',\$cols) || in_array('parent_block_id',\$cols)) {
        echo \$t.': NESTING SUPPORTED'.PHP_EOL;
      } else {
        echo \$t.': flat (columns: '.implode(',',\$cols).')'.PHP_EOL;
      }
    } catch(\Throwable \$e) {}
  }
" 2>/dev/null
```

Catat:
- Apakah blocks flat (list) atau bisa nested (tree)?
- Kalau flat: apa yang perlu ditambahkan untuk nesting? (parent_id column = schema change = butuh approval)

---

### 5G — Admin block editor current state

```bash
# Inspect admin block editor views
find resources/views -path "*admin*" -name "*block*" | head -20
find resources/views -path "*admin*" -name "*editor*" | head -10

# Inspect Alpine.js / JS yang mengatur block editor
find resources/js/ public/js/ -name "*block*" -o -name "*editor*" 2>/dev/null | head -10

# Cari bagaimana block ditambahkan di admin saat ini
grep -rn "addBlock\|insertBlock\|newBlock\|createBlock" \
  resources/views/ resources/js/ public/js/ --include="*.blade.php" --include="*.js" \
  2>/dev/null | head -15
```

Catat:
- Bagaimana admin menambahkan block saat ini? (dropdown? modal? sidebar?)
- Apakah block bisa di-reorder? (drag? up/down button?)
- Apakah setiap block type punya form editing sendiri?
- Teknologi JS yang dipakai (Alpine.js murni? jQuery? Library DnD?)

---

### 5H — Existing block types deep inspection

Untuk setiap block type yang ditemukan, catat:

```
Block: [nama]
Registry location: [file:baris]
Admin form: [file path]
Frontend render: [file path]
Editable fields: [list fields]
Supports styling (spacing/bg/alignment): YES / NO / PARTIAL
```

---

## FASE 6 — Buat report

Buat file: `ai/reports/phase-5/a1-backend-readiness-audit.md`

```bash
mkdir -p ai/reports/phase-5
```

Report harus mencakup:

```markdown
# A1 — Backend Readiness Audit for Visual Page Builder
## Date: [tanggal]
## Branch: feature/phase-5-stage-a-foundation
## HEAD: [commit hash]

---

## 1. Block Storage Model

### Current structure
[tabel, kolom, format data — dengan evidence file path]

### Nesting support
[flat / nested — evidence]

### Gap for visual builder
[apa yang kurang untuk mendukung nesting, jika ada]

---

## 2. Block Type Registry

### Current registry pattern
[satu tempat / tersebar — evidence]

### Block types found
| Block Type | Registry | Admin Form | Frontend Render | Fields | Styling Support |
|------------|----------|------------|-----------------|--------|-----------------|
| Hero       | [file]   | [file]     | [file]          | [list] | YES/NO          |
| Text       | ...      | ...        | ...             | ...    | ...             |
| ...        |          |            |                 |        |                 |

### Gap for visual builder
[apakah registry bisa dibaca dinamis oleh JS inserter panel?]

---

## 3. Draft vs Published

### Current mechanism
[bagaimana draft/publish bekerja — evidence]

### Revision system (Phase 4)
[bagaimana revision menyimpan block tree]

### Gap for visual builder
[apakah builder bisa save draft block tree terpisah dari live?]

---

## 4. Rendering Pipeline

### Current flow
[request → controller → data → Blade → HTML]

### Per-block rendering
[BlockRenderer class? Per-block Blade component?]

### Gap for visual builder
[apakah pipeline bisa dipakai ulang di iframe preview?]

---

## 5. Media Library Integration

### Current media picker
[cara kerja, return format]

### Gap for visual builder
[apakah JS canvas bisa memanggil media picker?]

---

## 6. Admin Block Editor Current State

### Current editing UX
[bagaimana block ditambah, diedit, di-reorder]

### Current JS/Alpine architecture
[apa yang sudah ada]

### Gap for visual builder
[apa yang perlu di-extend vs rebuild]

---

## 7. Summary — Readiness Assessment

| Area | Status | Gap | Severity |
|------|--------|-----|----------|
| Block storage | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Block registry | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Draft/publish | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Rendering | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Media picker | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Nesting | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |
| Editor UX | READY / NEEDS WORK | [gap] | HIGH/MED/LOW |

---

## 8. Ranked Backend Changes Needed Before Stage B

| Priority | Change | Type | Files | Risk |
|----------|--------|------|-------|------|
| 1 | [change] | safe / needs-approval / schema-change | [files] | LOW/MED/HIGH |
| 2 | ... | | | |

---

## 9. Recommendation

### Changes to do in Stage A (before builder)
[list]

### Changes that can wait until Stage B
[list]

### Changes that need owner approval (schema/package)
[list]

---

## 10. Next step
Stage A1 audit complete. Owner approval needed before implementing any changes.
```

---

## FASE 7 — Commit report files only

Commit HANYA file dokumentasi (report + skill file + AGENTS.md update).
JANGAN commit perubahan kode produksi.

```bash
git add AGENTS.md
git add ai/skills/phase5-visual-builder-skill.md 2>/dev/null
git add ai/reports/phase-5/a1-backend-readiness-audit.md

git status --short
```

Pastikan HANYA file docs yang ter-stage. Jika ada file lain: unstage dulu.

```bash
git commit -m "docs: Phase 5 Stage A1 backend readiness audit

- Update AGENTS.md phases (Phase 4 COMPLETE, Phase 5 CURRENT)
- Add Phase 5 skill file
- A1 backend audit: block storage, registry, draft/publish, rendering pipeline
- Read-only inspection — no code changes"
```

Jangan push dulu. Owner akan review dan push manual.

---

## FASE 8 — Output final

```
================================================
STAGE A1 — BACKEND READINESS AUDIT COMPLETE
================================================
Branch          : feature/phase-5-stage-a-foundation
HEAD            : [hash]
Phase status    : Phase 5 CURRENT (updated in AGENTS.md)

Audit findings:
  Block storage     : [READY / NEEDS WORK] — [1 line summary]
  Block registry    : [READY / NEEDS WORK] — [1 line summary]
  Draft/publish     : [READY / NEEDS WORK] — [1 line summary]
  Rendering pipeline: [READY / NEEDS WORK] — [1 line summary]
  Media integration : [READY / NEEDS WORK] — [1 line summary]
  Nesting support   : [READY / NEEDS WORK] — [1 line summary]
  Editor UX         : [READY / NEEDS WORK] — [1 line summary]

Backend changes needed before visual builder: [n] items
  Schema changes (need approval): [n]
  Safe changes: [n]

Report: ai/reports/phase-5/a1-backend-readiness-audit.md
Skill file: ai/skills/phase5-visual-builder-skill.md

Status: AUDIT COMPLETE — menunggu owner review
Next: Owner review findings → approve/modify → proceed to A2 (Admin UX Refactor)
================================================
```

STOP. Jangan implementasi perubahan apapun.
Tunggu owner review report dan memberikan approval.
