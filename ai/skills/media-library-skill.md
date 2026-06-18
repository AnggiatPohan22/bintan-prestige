# Media Library Skill

## Main Goal
Build the Media Library: a central asset manager where admin uploads, organizes,
searches, and reuses images and files. Integrated with the Block Editor and Page module.
Extends the existing `ImageOptimizationService` — do not rebuild it.

## Required References
- `AGENTS.md` — master rules
- `ai/skills/backend-skill.md` — Laravel MVC patterns
- `ai/skills/database-architecture-skill.md` — schema rules
- `ai/guidelines/05-admin-dashboard-cms-builder.md` — admin UX rules
- `app/Services/ImageOptimizationService.php` — EXISTING image service to extend

---

## Existing Service to Extend

`app/Services/ImageOptimizationService.php` already handles image resizing and
storage. MediaService wraps and extends it — never replaces it.

Before implementing, read `ImageOptimizationService` to understand:
- What disk it uses (local vs public)
- What resize dimensions it applies
- What filename strategy it uses

---

## Database Schema

### Table: `media`
```
id              bigint unsigned PK
filename        varchar(255)          -- stored filename (hashed, e.g. abc123.webp)
original_name   varchar(255)          -- original upload filename (for display)
mime_type       varchar(100)          -- image/jpeg, image/png, image/webp, image/gif
extension       varchar(10)           -- jpg, png, webp, gif
size            bigint unsigned       -- file size in bytes
width           int unsigned nullable -- image width in px (null for non-images)
height          int unsigned nullable -- image height in px
path            varchar(500)          -- relative storage path (e.g. media/2026/06/abc123.webp)
disk            varchar(50) default 'public'
alt             varchar(255) nullable -- alt text (for accessibility + SEO)
caption         varchar(500) nullable -- optional caption
uploaded_by     bigint unsigned nullable FK → users.id ON DELETE SET NULL
created_at      timestamp
updated_at      timestamp
```

### Indexes
- INDEX on `mime_type`
- INDEX on `uploaded_by`
- INDEX on `created_at`
- FULLTEXT or INDEX on `original_name` (for search)

---

## Model: `app/Models/Media.php`

```php
protected $fillable = [
    'filename', 'original_name', 'mime_type', 'extension',
    'size', 'width', 'height', 'path', 'disk', 'alt', 'caption', 'uploaded_by',
];

protected $casts = [
    'size'   => 'integer',
    'width'  => 'integer',
    'height' => 'integer',
];

// Relationships
public function uploader(): BelongsTo    // → User

// Helpers
public function getUrlAttribute(): string     // Storage::disk($this->disk)->url($this->path)
public function getSizeForHumansAttribute(): string  // "1.2 MB", "340 KB"
public function isImage(): bool              // mime_type starts with 'image/'

// Scopes
public function scopeImages($q)             // mime_type LIKE 'image/%'
public function scopeSearch($q, string $term) // original_name LIKE %term%
```

---

## Service: `app/Services/MediaService.php`

```php
/**
 * Upload a file, optimize it, and store a Media record.
 * Uses ImageOptimizationService for image files.
 */
public function store(UploadedFile $file, User $uploader): Media

/**
 * Delete file from storage and remove Media record.
 */
public function delete(Media $media): void

/**
 * Update alt text and caption only.
 */
public function updateMeta(Media $media, array $data): Media

/**
 * Paginated list with optional search and mime_type filter.
 */
public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator

/**
 * Return Storage URL for a media path.
 */
public function url(Media $media): string
```

### Upload Flow in `store()`:
1. Validate file (mime, size) — already done in FormRequest, but double-check here
2. If image: pass through `ImageOptimizationService` (resize, convert to webp if possible)
3. Generate hashed filename (`Str::uuid() . '.' . $extension`)
4. Store to `storage/app/public/media/YYYY/MM/filename`
5. Extract width/height using `getimagesize()` or Intervention Image
6. Create and return `Media` record

---

## Admin Controller: `app/Http/Controllers/Admin/MediaController.php`

```
index(Request $request)         // grid view with search + filter
store(StoreMediaRequest $request) // upload one or more files
update(UpdateMediaRequest $request, Media $media) // edit alt + caption
destroy(Media $media)           // delete file + record
```

Rules:
- `index` supports: `?search=term`, `?type=image` (mime filter), paginated 24/page
- `store` accepts `files[]` (multiple upload)
- `destroy` checks if media is referenced before deleting (Phase 2: warn but allow)
- Return JSON for upload (used by block editor image picker) AND standard redirect for standalone page

---

## Form Requests

### `app/Http/Requests/Admin/StoreMediaRequest.php`
```php
'files'          => ['required', 'array', 'min:1', 'max:10'],
'files.*'        => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,svg', 'max:5120'],
```

### `app/Http/Requests/Admin/UpdateMediaRequest.php`
```php
'alt'     => ['nullable', 'string', 'max:255'],
'caption' => ['nullable', 'string', 'max:500'],
```

---

## Admin Blade Views

### `resources/views/backend/media/index.blade.php`

Layout:
```
[ Search bar ]  [ Type filter: All / Images / Files ]  [ Upload button ]

┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│  [thumb] │ │  [thumb] │ │  [thumb] │ │  [thumb] │
│ name.jpg │ │ name.jpg │ │ name.jpg │ │ name.jpg │
│  340 KB  │ │  1.2 MB  │ │  80 KB   │ │  210 KB  │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
(4 columns grid, responsive to 2 on mobile)

[ Pagination ]
```

Clicking a thumbnail opens a detail panel:
- Full image preview
- Fields: alt text, caption (editable inline)
- Meta: filename, size, dimensions, uploaded_by, date
- Copy URL button
- Delete button (with confirm)

### `resources/views/backend/media/partials/upload-modal.blade.php`
- Drag-and-drop zone + file input fallback
- Progress indicator per file (Alpine.js + Fetch API)
- After upload: refresh grid or prepend new items

### `resources/views/backend/media/partials/grid.blade.php`
- Reusable grid partial (used in index + modal picker)
- Used by Block Editor image picker (loaded via `?picker=1` query param that hides nav)

---

## Media Picker for Block Editor

When Block Editor needs to pick an image (e.g. hero block `image` field):
- Open `admin.media.index?picker=1` in a modal iframe or Alpine.js panel
- User selects an image → JS `postMessage` sends `{ id, url, alt }` back to parent
- Parent form fills `data[image]` with the selected URL

This avoids duplicating the media UI. Keep the `?picker=1` mode minimal (grid only, no sidebar).

---

## Sidebar Addition

Add "Media" to `resources/views/backend/partials/sidebar.blade.php`:
```html
<!-- Under Content section -->
<a href="{{ route('admin.media.index') }}">Media Library</a>
```

---

## Routes

In `routes/admin.php`, inside existing auth+admin middleware group:
```php
Route::resource('media', MediaController::class)->only(['index', 'store', 'update', 'destroy']);
```

---

## Storage Path Convention

```
storage/app/public/media/YYYY/MM/filename.ext
```

Symlink must exist: `php artisan storage:link`

Public URL pattern:
```
/storage/media/2026/06/abc123.webp
```

---

## Security Checklist
- Validate `mimes` in FormRequest — never trust `Content-Type` header alone
- Validate `max:5120` (5 MB per file) in FormRequest
- Store files in `storage/app/public/media/` only — never in `public/` directly
- `destroy` action protected: only admin can delete
- Uploaded filename is hashed (UUID) — original filename stored in DB only, not used in path
- No SVG active content execution risk — store SVGs but never render inline without sanitization
- `?picker=1` mode still requires `auth` + `admin` middleware

---

## Module Pattern Checklist (from AGENTS.md §6)
- [ ] Migration: media table
- [ ] Model: Media (url accessor, size_for_humans, isImage, scopes)
- [ ] MediaController (index, store, update, destroy)
- [ ] MediaService (store, delete, updateMeta, list, url)
- [ ] StoreMediaRequest + UpdateMediaRequest
- [ ] Admin Blade: media/index, media/partials/upload-modal, media/partials/grid
- [ ] Sidebar updated
- [ ] Routes added
- [ ] Media Picker integration spec for Block Editor
- [ ] Storage path convention documented
- [ ] Security checks
- [ ] Tests

---

## Forbidden
- Storing files directly in `public/` folder
- Using original filename as storage path (collision + security risk)
- Rebuilding or replacing `ImageOptimizationService` — extend it only
- DB queries in Blade — use MediaService
- Allowing unrestricted file types — only `jpg, jpeg, png, gif, webp, pdf, svg`
- Rendering user-uploaded SVG inline without sanitization
