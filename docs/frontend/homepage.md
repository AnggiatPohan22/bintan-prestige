# Homepage CMS Integration

Last updated: 2026-06-13

The public homepage uses a fixed luxury travel layout with backend-managed content. The CMS may control copy, CTA fields, media, and module records, but it does not control layout classes, section order, Blade partial selection, or arbitrary page-builder blocks.

## Data Flow

1. `App\Http\Controllers\Frontend\HomeController@index` loads active registered homepage Page Sections in one query with media eager loading.
2. `App\Support\HomepageContent` prepares homepage display data and fallback copy.
3. `App\Support\HomepageSectionData` normalizes section labels, titles, descriptions, CTA text/URLs, extra data, and fallback defaults from `PageSectionRegistry`.
4. Product, Category, Destination, FAQ, and Global Settings data remain module-driven.
5. Blade views render the prepared values and must not query the database.

## Section Sources

| Homepage section | Section key | Content source |
| --- | --- | --- |
| Hero/Search | `home.hero` | PageSection fields, PageSection media, controlled `extra_data` for search labels/copy |
| Popular Tour | `home.popular_tour` | PageSection fields, media slots, site logo/default media |
| Popular Products | `home.popular_products_intro` | PageSection intro/CTA plus published Product module records |
| Manual Ads | `home.manual_ads` | PageSection fields/media plus controlled overlay title |
| About Journey | `home.about_journey` | PageSection fields/media plus controlled feature-card fallback data |
| Destination Cards | `home.categories_intro` | PageSection intro plus active Destination module records |
| Explore Banner | `home.explore_banner` | PageSection fields, background media, controlled outline text |
| Testimonials | `home.testimonials` | PageSection intro plus static fallback testimonial source |
| FAQ Preview | `home.faq` | PageSection intro/media plus active FAQ module records or fallback items |
| Footer CTA | `home.footer_cta` | PageSection fields/media plus Global Settings WhatsApp fallback |

## Fixed Layout Boundary

Fixed in Blade/CSS:

- Section order and responsive layout.
- Tailwind/CSS classes and visual hierarchy.
- Header/footer structure.
- Product card component structure.
- Search form route and query parameter names.
- Section IDs and `data-section-key` attributes.

CMS/module managed:

- Section label, title, subtitle, description, button text, button URL.
- Section media slots and alt text.
- Products, Categories, Destinations, and FAQs.
- Global business identity, contact details, default media, and booking CTA settings.
- Controlled `extra_data` values documented by the section implementation.

## Fallback Behavior

- Missing or inactive PageSection records fall back to registered homepage defaults.
- Empty CTA URLs are normalized and do not render `href=""`.
- Unsafe CTA schemes such as `javascript:`, `data:`, and `vbscript:` are rejected.
- Product cards without price render `Price on request`, not `Rp 0`.
- Missing Product/Destination/Section/Avatar images use Global Default Media when configured.
- Empty Products, Destinations, or FAQs render safe empty/fallback states.
- No Review/Testimonial module exists yet; testimonials remain static fallback credibility copy.

## Remaining Gaps

- Why Choose Us cards are still fixed copy.
- Testimonials do not have dedicated CMS CRUD or schema.
- Footer newsletter remains placeholder behavior.
- Footer utility links may still include placeholders.
- Placeholder text polish can be handled in a later frontend refinement step.

## Testing Reference

FRONTEND-04D added focused homepage regression coverage for:

- Full homepage render with CMS/module data.
- Missing/inactive section fallback behavior.
- CMS copy and CTA safety.
- Published vs draft Product visibility.
- Missing Product price empty state.
- Active/inactive/soft-deleted Destination visibility.
- Static testimonial fallback.
- Global default media fallback.
- Frontend/admin route contract.
- No direct database queries in homepage Blade files.
