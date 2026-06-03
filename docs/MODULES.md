# Modules

## PageSection Image Upload Integration

Status: Foundation

PageSection admin editing supports basic image and mobile image uploads for static homepage marketing sections.

Current behavior:

- Stores files in `storage/app/public/page-sections/`.
- Saves relative paths such as `page-sections/example.webp`.
- Renders uploaded images through PageSection URL accessors.
- Keeps `NO IMAGE` frontend placeholders when image fields are empty.
- Deletes replaced local PageSection images only when the old path is inside `page-sections/`.
- Keeps external URLs and manually typed paths intact.

Notes:

- This is not a full media library yet.
- Product and category data still come from their own tables.
- PageSection remains limited to static or marketing section content.
