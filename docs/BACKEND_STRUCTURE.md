# Backend Structure

The backend is organized by responsibility so future CMS modules can be added without mixing frontend, admin, and business logic.

## Controllers

Admin controllers live in:

```text
app/Http/Controllers/Admin/
```

Frontend controllers live in:

```text
app/Http/Controllers/Frontend/
```

Keep admin behavior out of frontend controllers, and keep frontend rendering logic out of admin controllers.

## Routes

Public frontend routes are defined in:

```text
routes/frontend.php
```

Admin routes are defined in:

```text
routes/admin.php
```

Admin routes should stay inside the existing admin prefix, name, and middleware group.

## Services

Reusable business logic belongs in:

```text
app/Services/
```

`PageSectionService` centralizes PageSection queries:

- `getPageSections($pageKey)` returns active sections keyed by `section_key`.
- `getHomeSections()` returns homepage sections.
- `getAdminSections()` returns records for the admin testing list.
- `tableExists()` lets the frontend/admin fail gracefully before migration.

## Requests

Validation request classes belong in:

```text
app/Http/Requests/
```

Admin-specific requests should use:

```text
app/Http/Requests/Admin/
```

`UpdatePageSectionRequest` validates PageSection CMS updates and converts `extra_data` JSON into an array before persistence.

## Models

Models live in:

```text
app/Models/
```

Models should describe database fields, casts, accessors, relationships, and scopes. They should not contain controller-specific workflow logic.

`PageSection` owns the `page_sections` table and casts:

- `extra_data` to `array`
- `is_active` to `boolean`

## PageSection CMS Foundation

`page_sections` is for editable static marketing content only:

- label
- title
- subtitle
- description
- button text and URL
- image paths
- extra JSON data
- active status
- sort order

It must not store product cards or category cards. Products and categories continue using their existing database tables and frontend query logic.

## Current Admin View Note

Most existing admin views are stored in `resources/views/backend`. The PageSection testing views currently live in `resources/views/admin/page-sections` as a lightweight CMS foundation. If more CMS admin modules are added later, standardize admin view naming in one follow-up refactor.
