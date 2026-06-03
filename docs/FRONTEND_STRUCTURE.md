# Frontend Structure

Frontend Blade files live in `resources/views/frontend`.

## Directory Roles

- `frontend/home/` contains homepage-only sections.
- `frontend/partials/` contains reusable global blocks such as header and footer.
- `frontend/components/` contains small reusable UI pieces, such as product cards.
- `frontend/products/` contains product listing and detail pages.

## Homepage Sections

Homepage-only sections should stay in:

```text
resources/views/frontend/home/
```

Current homepage sections:

- `about-journey.blade.php`
- `categories.blade.php`
- `explore-banner.blade.php`
- `popular-products.blade.php`
- `popular-tour.blade.php`
- `testimonials.blade.php`

`home.blade.php` should include sections and avoid holding long section markup directly. This keeps the homepage easy to scan and safer to edit.

## Global Partials

Global reusable sections should stay in:

```text
resources/views/frontend/partials/
```

Examples:

- `header.blade.php`
- `footer.blade.php`

Do not duplicate header or footer markup across frontend pages.

## Components

Small reusable UI blocks should stay in:

```text
resources/views/frontend/components/
```

Product cards and similar repeated UI should use components instead of duplicated markup.

## Data Source Rules

- Products must come from the `products` table.
- Categories must come from the `categories` table.
- Product and category cards must not be duplicated into `page_sections`.
- `page_sections` is only for static or marketing content such as labels, titles, descriptions, buttons, images, overlay text, and active status.

## PageSection Fallback Rules

Frontend sections should read database values first and use hardcoded fallback content when a record is missing or inactive.

Mixed sections can use PageSection only for intro/static content:

- `popular-products.blade.php` may use PageSection for heading, label, description, button, and image fields.
- Product cards still come from `$homeProducts`.
- `categories.blade.php` may use PageSection for heading, label, and description.
- Category cards still come from `$categories`.

## Performance Notes

- Keep homepage product queries limited.
- Use eager loading for product relationships used by frontend cards.
- Use `withCount` for category product counts.
- Keep placeholder image frames lightweight.
- Avoid adding heavy JavaScript to static sections.
