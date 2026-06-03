# Modules

## PageSection CMS Foundation

Status: Foundation

`page_sections` controls static homepage marketing copy, buttons, images, active state, and sort order. It must not store product, category, or FAQ item data.

## Homepage Synchronization

Status: Foundation

Homepage sections are modularized under `resources/views/frontend/home/`, with reusable global blocks in `resources/views/frontend/partials/`.

Synchronized section keys:

- `home.hero`
- `home.popular_tour`
- `home.popular_products_intro`
- `home.categories_intro`
- `home.manual_ads`
- `home.about_journey`
- `home.explore_banner`
- `home.testimonials`
- `home.faq`
- `home.footer_cta`

## Hero Section Integration

Status: Foundation

Hero markup lives in `frontend/home/hero.blade.php` and reads `home.hero` PageSection content. Existing `data-hero-*` attributes are preserved so lightweight frontend background behavior can continue working.

Hero can use up to 10 uploaded `PageSectionMedia` images as a slider. Animation is selected in the PageSection admin editor and stored in `extra_data.animation`.

Supported animation values:

- `ken-burns`
- `zoom-in`
- `zoom-out`
- `fade`
- `pan-left`
- `pan-right`
- `none`

Section media uploads are reusable across future pages and sections. Uploaded files are grouped automatically by page and section, for example:

```text
storage/app/public/page-sections/home/hero/
```

## Manual Ads Integration

Status: Foundation

Manual ads lives in `frontend/partials/manual-ads.blade.php`, uses `home.manual_ads`, and keeps the existing `NO IMAGE` placeholder if no image path is available.

## FAQ Module

Status: Foundation

FAQs are stored in the `faqs` table and managed through admin FAQ CRUD. FAQ items are dynamic content and are not stored inside PageSection JSON.
