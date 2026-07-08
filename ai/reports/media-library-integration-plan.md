# Media Library Integration — Implementation Plan (PROPOSAL)
**Date:** 2026-07-08 | **Status:** ⏳ Awaiting owner approval — nothing implemented yet
**Companion:** `ai/reports/media-library-audit.md`

## Goal
Every image selection in admin goes through **one door** — the Media Library —
without breaking existing functionality or the frontend.

---

## §DB — Database approach (recommendation)

**Recommendation: NO schema change. Keep the existing path-string pattern.**

The task brief proposes a `media_id` FK or a `mediables` pivot. I recommend
**against** that for this codebase, because:

1. **Zero `media_id` FKs exist today.** Every image column is a storage-relative
   **path string**: `products.thumbnail`, `product_images.image`, `pages.og_image`,
   `page_sections.image/.mobile_image`, `site_assets.path`, plus the already-done
   `categories.image` / `destinations.image`.
2. **Usage tracking is path-based.** `MediaService::DIRECT_REFERENCES` +
   `page_blocks` JSON scanning find where a media file is used **by path**, and
   this powers the library's delete-guard and "Used ×N" badges. Switching to
   `media_id` FKs would silently break all of that until rewritten.
3. **Frontend renders paths.** Every Blade reads `asset('storage/'.$path)`.
   FKs would force a join/resolver rewrite across the whole public site.
4. **Phase 6.1 already set the precedent.** Categories & Destinations were
   integrated the path-string way with `<x-admin.media-image-field>` — 0 schema
   change, fully backward compatible.

**Therefore:** the picker returns a **path** (the file already lives in the Media
Library after upload/selection); controllers store that path exactly as today.
This is backward compatible (old stored paths keep rendering) and needs **no
migration**. `media_id` FKs can be revisited later as a separate, isolated
initiative if the owner wants true relational integrity — but it is out of scope
here and would be a large, risky rewrite.

> One optional, low-risk enrichment (only if desired): register each newly
> `DIRECT_REFERENCES` table/column (e.g. `products.thumbnail`, `pages.og_image`,
> `site_assets.path`) so the library's usage tracking covers them. Additive,
> no schema change. Recommended as part of this work.

---

## §Component — picker readiness

`<x-admin.media-image-field>` (Phase 6.1) already covers **single-image** fields.
Before the settings/products work it needs **two additions**:

- **P1 — Multi-select variant** for galleries (`product gallery`, page-section
  `media_uploads[]`): a `multiple` mode that returns an ordered array of paths.
  Reuses the existing gallery-block batch pattern + picker `postMessage` (send an
  array). New component `<x-admin.media-gallery-field>` or a `:multiple` prop.
- **P2 — Wider type support** for favicon (`.ico`, `.svg`): either (a) keep the
  favicon on a raw input (documented exception), or (b) extend
  `MediaService::ALLOWED_EXTENSIONS` to include `ico`/`svg` **with an SVG
  sanitizer** (security-sensitive — SVG can carry script; the media-library-skill
  explicitly forbids unsanitized inline SVG). **Recommendation: (a) leave favicon
  as-is for now**, flag SVG support as a separate staged task.

---

## §Order — implementation order (each = its own commit + AGENTS §11 report)

Priority follows the brief, adjusted for what Phase 6.1 already finished.

**Stage 0 — Prep**
- 0.1 Delete confirmed dead code: `destinations/form.blade.php`,
  `products/partials/products.blade.php` (verify no route references first).
- 0.2 Build `P1` multi-select picker capability. Add a regression test.

**Stage 1 — Settings (highest visibility)** — `SiteSettingController` — ✅ DONE 2026-07-08
- 1.1 ✅ Logo variants (`logos[slug]`) → picker per variant (collection `logo`).
- 1.2 ✅ Social share image + SEO default OG image → picker (collection `content`).
- 1.3 ✅ Default media placeholders (`default_media[slug]`) → picker (collection `content`).
- 1.4 ✅ Favicon → **left raw** (owner decision 2026-07-08 — `.ico`/`.svg` outside library types).
- New service method `PageSectionImageService::setSiteAssetPath()` stores the picked
  path; its `deleteIfLocalSiteAssetImage` guard already protects `media/…` assets
  (only cleans up legacy `site-assets/…` uploads on replace). No schema change.
- Tests: updated 3 (logo/social/default-media) to path-based + new
  `GlobalSiteLogoSettingsTest`. Suite 854/854, PHPStan 0. Owner decision (path
  approach) confirmed in `AskUserQuestion` 2026-07-08.

**Stage 2 — Products** — `ProductController`/`ProductService`/`ProductImageService`
- 2.1 Thumbnail → single picker (collection `product`).
- 2.2 Gallery → multi picker (collection `product`).
- Keep `ProductImageService` for existing rows; new selections store paths.

**Stage 3 — Pages OG image** — `PageController`/`PageService`
- 3.1 `pages/form.blade.php` (create) + `pages/edit.blade.php` (edit) og_image → picker (collection `content`).

**Stage 4 — Page Sections (protected module — extra care)** — `PageSectionController`
- 4.1 `image` + `mobile_image` → single picker (collection `section`).
- 4.2 `slot_uploads` + `media_uploads[]` → multi picker.
- Do **last**; most complex + protected (AGENTS.md §5). Full regression pass.

**Stage 5 — Optional polish (Class B)**
- Converge hero/image/gallery/background/builder fields onto the shared component
  (they already register to Media Library — cosmetic consistency only).

---

## §PerPage — the repeatable recipe (applied to each field)

1. Replace `<input type="file" name="x">` with `<x-admin.media-image-field name="x" :value="old('x', $model->x ?? '')" collection="…">` (or the multi variant).
2. Controller: read `$request->input('x')` (a path) instead of `$request->file('x')`; drop the manual `store()` call for that field. Keep the old file branch guarded (`hasFile`) during transition for safety.
3. Form Request: change `['image','mimes:…','max:…']` → `['nullable','string','max:500']` (multi: `['array']` + `.*` `['string','max:500']`).
4. Backward compatibility: existing stored paths still render (no data migration).
5. Register the column in `MediaService::DIRECT_REFERENCES` (usage tracking).
6. Test: open picker → browse/upload → select → save → reload → image persists; existing image still shows; delete-guard works.

---

## §Risks & guardrails

- **Protected modules** (Products, Page Sections, Site Settings): extend only,
  never rebuild (AGENTS.md §5). Keep `enctype="multipart/form-data"` on each form
  until *all* its file inputs are gone.
- **Favicon `.ico`/`.svg`** outside Media Library allowed types → leave raw or
  add sanitized SVG support (separate task).
- **Multi-image ordering** (gallery) must be preserved.
- **No destructive change until the replacement is verified** (brief rule).
- **Schema**: none proposed. If the owner prefers `media_id` FKs, that becomes a
  separate, larger initiative with its own approval + migration plan.
- Each stage: `php artisan test` green + PHPStan level 5 = 0 before commit.

---

## §Deliverables status
1. ✅ `ai/reports/media-library-audit.md`
2. ✅ This plan + DB recommendation (path-string, no schema change)
3. ⏳ **Owner approval** — required before any implementation
4. ⏳ Component multi-select + page-by-page replacement (after approval)

## Open questions for the owner (need answers to finalize scope)
1. **DB approach:** OK to proceed **path-string, no schema change** (recommended)?
   Or do you specifically want `media_id` FKs (larger, riskier rewrite)?
2. **Favicon:** leave as a raw upload for now, or invest in sanitized SVG/ICO
   support in the Media Library?
3. **Class B polish (Stage 5):** worth doing, or leave the block/builder uploads
   as-is since they already register to the Media Library?
4. **Scope of first PR:** do all stages, or ship Stage 1 (Settings) first for review?
