# Image Upload Guide

This project supports basic PageSection image uploads for the CMS foundation.

## Storage Location

Uploaded PageSection images are stored on the Laravel public disk:

```text
storage/app/public/page-sections/
```

The database stores the relative path:

```text
page-sections/example-file.webp
```

Public URLs are served through:

```text
/storage/page-sections/example-file.webp
```

## Required Command

Run this once per environment so public storage is accessible:

```bash
php artisan storage:link
```

## Accepted Image Types

The PageSection editor accepts:

- JPG
- JPEG
- PNG
- WebP

Maximum upload size:

```text
2MB
```

## Admin Behavior

The PageSection edit form includes:

- `image` upload
- `mobile_image` upload
- current image preview
- current mobile image preview
- manual image path fields for compatibility

Leave an upload field empty to keep the current image.

## Replacement Behavior

When a new image is uploaded, the old image is deleted only when the old path starts with:

```text
page-sections/
```

External URLs and manually typed paths outside `page-sections/` are not deleted.

## Frontend Rendering

PageSection image URLs are resolved by model accessors:

- `image_url`
- `mobile_image_url`

If a path starts with `http://`, `https://`, or `/`, it is used directly.

Otherwise, the frontend renders:

```php
asset('storage/' . $section->image)
```

## Fallback Rule

If `image` or `mobile_image` is empty, the frontend keeps the existing `NO IMAGE` placeholder.

## Future Plan

This is not a full media library. A future media library can add reusable assets, folders, alt text management, search, and image optimization workflows.
