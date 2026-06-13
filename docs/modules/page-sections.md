# Page Sections Module

Last updated: 2026-06-13

Page Sections provide editable CMS content for fixed public layouts. They are not a page builder. The frontend keeps layout, section order, and Tailwind classes in Blade/CSS while Page Sections supply copy, CTA fields, media, status, ordering metadata, and controlled JSON values.

## Data Model

Main table:

- `page_sections`

Important fields:

- `page_key`
- `section_key`
- `label`
- `title`
- `subtitle`
- `description`
- `button_text`
- `button_url`
- `image`
- `mobile_image`
- `extra_data`
- `is_active`
- `sort_order`

Media table:

- `page_section_media`

Media slots support section-specific roles such as hero backgrounds, frame images, gallery items, and footer CTA visuals.

## Canonical Registry

`App\Support\PageSectionRegistry` is the canonical source for registered page and section keys.

Homepage keys:

- `home.hero`
- `home.popular_tour`
- `home.popular_products_intro`
- `home.manual_ads`
- `home.about_journey`
- `home.categories_intro`
- `home.explore_banner`
- `home.testimonials`
- `home.faq`
- `home.footer_cta`

Product listing/detail keys also live in the registry and should not be renamed without approval.

## Frontend Contract

The homepage uses:

- `App\Support\HomepageSectionData` for display-ready section fallback data.
- `App\Support\HomepageContent` for supported homepage content arrays.
- `App\Support\PageSectionCta` for safe CTA URL normalization.
- `App\Support\HomepageSectionMedia` for media slot definitions.

Blade files render prepared data. They must not query `PageSection`, `DB`, or other models directly.

## CTA Rules

- CTA text and URL come from PageSection fields when present.
- Empty URLs fall back where an approved fallback exists.
- `href=""` should not be rendered.
- Unsafe URL schemes are rejected.
- Footer CTA can fall back to Global Settings WhatsApp booking configuration.

## Media Rules

- Homepage media slots are fixed by section implementation.
- Missing media uses Global Default Media where supported.
- Image alt text should come from media alt text, section title, or safe fallback copy.
- Layout, object-fit defaults, and responsive behavior stay in frontend code.

## Admin Editing Boundary

Admins may edit:

- Copy fields.
- CTA fields.
- Active status.
- Sort order metadata.
- Supported media slots.
- Controlled `extra_data` values.

Admins must not use Page Sections to control:

- Arbitrary Blade partial names.
- Tailwind/CSS classes.
- Query behavior.
- Route names.
- Full page-builder blocks.

## Current Gaps

- Repeatable section-item schema does not exist.
- Testimonials remain static fallback content because no Review/Testimonial module exists.
- Why Choose Us remains fixed copy.
- PageSection inactive behavior currently falls back to registered defaults on the homepage.

## Testing Reference

Homepage regression tests live in `tests/Feature/Frontend/HomepageCmsContentTest.php`.

Admin media slot coverage lives in `tests/Feature/Admin/PageSectionMediaSlotTest.php`.
