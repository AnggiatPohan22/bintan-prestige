# Media Library Integration Audit
**Date:** 2026-07-08 | **Branch:** `feature/phase-6.1-dashboard-media-ux` | **Status:** AUDIT — awaiting approval

> Path note: this project keeps admin views under `resources/views/backend/`
> (not `resources/views/admin/`). All greps below were run against the real tree.
> Skill files referenced in the task brief `frontend-skill.md` / `uiux-skill.md`
> do not exist; the actual UI skill is `ai/skills/frontend-design-skill.md`
> (+ root `DESIGN-SYSTEM.md`), both read.

## Summary
- Raw `<input type="file">` instances found: **25** across **15** Blade files.
- After classification, the **net work** (raw inputs still bypassing the Media
  Library) is **13 instances across 5 active files** — the rest are the Media
  Library's own internals, already-integrated block/builder uploads, or dead code.
- **No `media_id` FK or `mediables` pivot exists** anywhere. The whole app stores
  images as **storage-relative path strings** and the Media Library tracks usage
  by path (`MediaService::DIRECT_REFERENCES`). This dictates the recommended
  approach (see Implementation Plan §DB).

### Classification at a glance

| Class | Meaning | Instances | Action |
|-------|---------|-----------|--------|
| A | Media Library internals (the library itself) | 4 | **Keep** |
| B | Block/builder uploads already routed to Media Library (Phase 6.1) | 6 | Optional polish |
| C | Already swapped to `<x-admin.media-image-field>` (Phase 6.1) | 2 files + 1 dead | Done / delete dead |
| D | **Raw inputs still bypassing Media Library — the real work** | 13 | **Replace** |
| E | Out of scope (ZIP import, frontend form) | 2 | **Exclude** |

---

## Audit Results

### CLASS D — Needs replacement (the actual scope)

#### 1. Settings → Global Assets: Logo Variants
- **File:** `resources/views/backend/settings/global-assets.blade.php`
- **Line(s):** 69 (`logos[{{ $slug }}]` — Main/Dark/Light/Icon logo variants, looped)
- **Current behavior:** uploads each brand logo variant.
- **Controller:** `app/Http/Controllers/Admin/SiteSettingController.php` (method `update`, ~line 173 `hasFile("logos.$slug")`)
- **How stored:** `PageSectionImageService::storeSiteAssetUpload()` → `storeAs(...,'public')` → path string saved into a `SiteAsset` row.
- **Priority:** **High**

#### 2. Settings → Global Assets: Favicon
- **File:** `resources/views/backend/settings/global-assets.blade.php`
- **Line(s):** 129 (`favicon`, accepts `.ico,png,svg,webp,jpeg`)
- **Current behavior:** uploads the site favicon.
- **Controller:** `SiteSettingController` (~line 226).
- **How stored:** `storeSiteAssetUpload()` → path string.
- **Priority:** **High** — ⚠️ accepts `.ico` and `.svg`, which are **outside** `MediaService::ALLOWED_EXTENSIONS` (jpg/jpeg/png/gif/webp). Media Library cannot currently store these (see Plan §Risks).

#### 3. Settings → Global Assets: Social Share Image
- **File:** `resources/views/backend/settings/global-assets.blade.php`
- **Line(s):** 231 (`social_share_image`)
- **Controller:** `SiteSettingController` (~line 297). **How stored:** path string.
- **Priority:** **High**

#### 4. Settings → Global Assets: SEO Default OG Image
- **File:** `resources/views/backend/settings/global-assets.blade.php`
- **Line(s):** 956 (`seo_default_og_image`)
- **Controller:** `SiteSettingController` (~line 628). **How stored:** path string.
- **Priority:** **High**

#### 5. Settings → Global Assets: Default Media placeholders
- **File:** `resources/views/backend/settings/global-assets.blade.php`
- **Line(s):** 1227 (`default_media[{{ $slug }}]` — looped placeholders per type)
- **Controller:** `SiteSettingController` (~line 775). **How stored:** path string.
- **Priority:** **Medium**

#### 6. Products → create/edit: Thumbnail
- **File:** `resources/views/backend/products/form.blade.php` (included by `create.blade.php` + `edit.blade.php`)
- **Line(s):** 398 (`thumbnail`)
- **Controller:** `ProductController@store/@update` → `ProductService` → `ProductImageService`.
- **How stored:** `ImageOptimizationService::upload()` → webp path in `products.thumbnail`.
- **Priority:** **High**

#### 7. Products → create/edit: Gallery images
- **File:** `resources/views/backend/products/form.blade.php`
- **Line(s):** 463 (`gallery[]`, multiple, max 10)
- **Controller:** `ProductController` → `ProductService` → `ProductImageService` (rows in `product_images.image`).
- **Priority:** **High** — needs a **multi-select** picker (see Plan §Multi).

#### 8. Pages → create: OG image
- **File:** `resources/views/backend/pages/form.blade.php` (included by `create.blade.php`)
- **Line(s):** 177 (`og_image`)
- **Controller:** `PageController@store` → `PageService::store()` → `ImageOptimizationService::upload(...,'pages')` → `pages.og_image` path.
- **Priority:** **Medium**

#### 9. Pages → edit: OG image
- **File:** `resources/views/backend/pages/edit.blade.php` (standalone; edit does **not** include `form.blade.php`)
- **Line(s):** 314 (`og_image`)
- **Controller:** `PageController@update` → `PageService::update()`.
- **Priority:** **Medium**

#### 10–13. Page Sections → edit: 4 inputs (protected module)
- **File:** `resources/views/backend/page-sections/edit.blade.php`
- **Line(s):** 76 (`slot_uploads[...]`), 115 (`image`), 121 (`mobile_image`), 140 (`media_uploads[]`, multiple)
- **Current behavior:** hero/section image, mobile variant, media slots, multi-upload gallery.
- **Controller:** `PageSectionController@update` (~lines 130–198) → `PageSectionImageService::storeUploadedImage()` / `storeSlotUpload()`.
- **How stored:** path strings in `page_sections.image` / `.mobile_image` + `page_section_media` rows.
- **Priority:** **Medium** — ⚠️ Page Sections is a **protected module** (AGENTS.md §5). `media_uploads[]` + `slot_uploads` are **multi/keyed** — needs multi-select picker + careful preservation.

---

### CLASS C — Already done in Phase 6.1 (verify only)

| Page | File | State |
|------|------|-------|
| Categories create/edit | `backend/categories/create.blade.php`, `edit.blade.php` | ✅ uses `<x-admin.media-image-field>` (collection `category`) — no raw input |
| Destinations create/edit | `backend/destinations/create.blade.php`, `edit.blade.php` | ✅ uses `<x-admin.media-image-field>` (collection `destination`) |
| Destinations legacy partial | `backend/destinations/form.blade.php:85` | ⚠️ **DEAD CODE** — not `@include`d anywhere. Recommend delete. |

---

### CLASS B — Block / builder uploads already routed to Media Library (Phase 6.1)

These already have a **"Media Library" picker button** AND their file input is a
quick-upload that **registers a Media record** (collection-tagged). They meet the
"one door" goal; converting them to the shared component is optional polish.

| Block/field | File:line | Registers via | Collection |
|-------------|-----------|---------------|------------|
| Visual builder image field | `builder/partials/builder-field.blade.php:115,186` | `uploadInto` → `admin.media.upload-quick` | `content` |
| Hero block image | `pages/partials/blocks/hero.blade.php:35` | `uploadImage` → upload-quick | `hero` |
| Image block | `pages/partials/blocks/image.blade.php:31` | `uploadImage` → upload-quick | `content` |
| Gallery block | `pages/partials/blocks/gallery.blade.php:25` | `uploadBatch` → upload-batch | `gallery` |
| Background picker | `blocks/partials/background.blade.php:116` | `uploadBg` → upload-quick | `section` |

---

### CLASS A — Media Library internals (KEEP — these ARE the library)

| File:line | Role |
|-----------|------|
| `media/partials/upload-modal.blade.php:38` | Library's own drag-drop upload |
| `media/picker.blade.php:57–58` | Picker page's upload-into-library zone |
| `components/admin/media-image-field.blade.php` | The reusable picker component's quick-upload |

---

### CLASS E — Out of scope

| File:line | Why excluded |
|-----------|--------------|
| `themes/index.blade.php:73` (`theme_zip`) | ZIP theme import, not an image/media asset |
| `frontend/blocks/contact-form.blade.php` | Public end-user form (contact submission attachment), not admin |

---

## Known Locations (expected hits) — reconciled

| Expected (from brief) | Found? | Where |
|---|---|---|
| Settings > Site Logo Variants | ✅ | global-assets.blade.php:69 (D#1) |
| Settings > Site Assets (favicon, OG, social) | ✅ | global-assets.blade.php:129/231/956/1227 (D#2–5) |
| Products > Product Images (gallery) | ✅ | products/form.blade.php:463 (D#7) |
| Products > thumbnail | ✅ | products/form.blade.php:398 (D#6) |
| Categories (image) | ✅ done | already `<x-admin.media-image-field>` (Class C) |
| Destinations (image) | ✅ done | already `<x-admin.media-image-field>` (Class C) |
| Page Sections > Media | ✅ | page-sections/edit.blade.php (D#10–13) |
| User profile / avatar | ❌ none | no avatar upload exists in admin |
| Pages OG image (extra) | ✅ | pages/form.blade.php:177, edit.blade.php:314 (D#8–9) |
| Legacy dead partials (extra) | ⚠️ | destinations/form.blade.php, products/partials/products.blade.php |

---

## Backend Impact

### Controllers using `$request->file()` / `hasFile()`
- `Admin/SiteSettingController.php` — logos, favicon, social_share_image, seo_default_og_image, default_media (5 fields)
- `Admin/ProductController.php` (+ `ProductService`, `ProductImageService`) — thumbnail, gallery[]
- `Admin/PageController.php` (+ `PageService`) — og_image
- `Admin/PageSectionController.php` (+ `PageSectionImageService`) — image, mobile_image, slot_uploads, media_uploads[]
- `Admin/MediaController.php` — **keep** (this is the library ingest)
- `Admin/ThemeController.php` — **keep/exclude** (ZIP import)

### Form Requests with file/image rules
- `StoreProductRequest`, `UpdateProductRequest` — thumbnail/gallery `image|mimes`
- `StorePageRequest`, `UpdatePageRequest` — og_image `image|mimes`
- `StoreCategoryRequest`, `UpdateCategoryRequest` — ✅ already `image` = `nullable|string|max:500` (Phase 6.1)
- `StoreDestinationRequest`, `UpdateDestinationRequest` — ✅ already accept `image_path` string (Phase 6.1)
- `ImportThemeRequest` — ZIP (exclude)
- `StoreMediaRequest` — library ingest (keep)
- Site settings: validated inline in `SiteSettingController` (no dedicated FormRequest)

### Services that store files
- `ImageOptimizationService::upload()` — shared webp optimizer (products, pages, media)
- `ProductImageService` — product thumbnail + gallery rows
- `PageSectionImageService::storeUploadedImage/storeSlotUpload/storeSiteAssetUpload` — page sections + site assets
- `DestinationService` — ✅ already accepts picked path (Phase 6.1)
- `MediaService::store()` — library ingest (collection-aware, Phase 6.1)

---

## Reusable picker component — already exists ✅

Built in Phase 6.1. Documenting its real interface (differs from the brief's
proposed `media_id` shape — this one is **path-based**, matching the codebase):

```blade
<x-admin.media-image-field
    name="image"                 {{-- form field; submits a storage-relative PATH string --}}
    :value="old('image', $model->image ?? '')"
    label="Category Image"
    collection="category"        {{-- routes uploads to media/{collection}/YYYY/MM --}}
    hint="Shown on the frontend card." />
```

- Renders preview + hidden/visible **path** text input + **Upload** (quick-upload
  → registers Media, collection-tagged) + **Media Library** button.
- Media Library button dispatches `open-media-picker` with a `collection` hint;
  the picker modal (`media/partials/picker-modal.blade.php`) opens the library
  iframe (`media/picker.blade.php`), user browses **or uploads**, selection posts
  back via `postMessage` → fills the path input + preview.
- Includes the picker modal `@once` per page.
- **Gap:** single-image only. No multi-select (gallery), no `.ico`/`.svg`.

**File:** `resources/views/components/admin/media-image-field.blade.php`

---

## Database approach — recommendation

**No schema change. Keep path strings.** (Detailed rationale in the Implementation Plan.)
