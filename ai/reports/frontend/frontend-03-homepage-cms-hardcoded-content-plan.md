# STEP FRONTEND-03 - Homepage CMS vs Hardcoded Content Plan

Date: 2026-06-13
Scope: Planning-only audit for homepage CMS content boundaries, hardcoded content, Page Sections usage, and safe future implementation roadmap.

## 1. Executive Summary

The homepage already follows the intended fixed-layout CMS pattern. `HomeController` prepares Page Sections, FAQs, products, categories, destinations, and global settings before rendering. Frontend Blade files do not run database queries directly in the inspected homepage flow.

Most primary homepage copy and media are PageSection-aware, especially Hero, Popular Tour, Popular Products intro, Manual Ads, About Journey intro, Categories intro, Explore Banner, Testimonials intro, FAQ intro, and Footer CTA. The strongest remaining hardcoded areas are booking/search labels and softcopy, Why Choose Us cards, About Journey feature cards, testimonial items, empty/fallback labels, newsletter placeholder behavior, and footer copyright year.

Recommendation: keep the homepage layout fixed and controlled. Move only high-value editable content into existing Page Sections or existing modules in phased steps. Do not build a page builder, do not create new schema in this planning step, and do not move full layout/style control to the database.

## 2. Current Homepage Flow

Route:

- `GET /` uses `Frontend\HomeController@index`.
- View: `resources/views/frontend/home.blade.php`.
- Layout: `resources/views/layouts/frontend.blade.php`.
- Footer is rendered from the frontend layout and receives shared/global data.

Controller data flow:

- `PageSection` records for `page_key = home`, active only, with `media`, ordered by `sort_order`, keyed by `section_key`.
- `GlobalSettingsService::siteAssets()` for global/default media.
- Active FAQs, ordered by `sort_order` and `id`, limited to 6.
- Featured published products for legacy/feature support.
- Latest published products for homepage product cards.
- Product categories derived from homepage products.
- Active categories and destinations with published product counts.

Homepage render flow:

- Hero is inline in `home.blade.php`.
- Booking/search form is inline in `home.blade.php`.
- Popular Tour, Popular Products, Manual Ads, About Journey, Categories, Explore Banner, Testimonials are split into frontend section partials.
- Why Choose Us and FAQ preview are inline in `home.blade.php`.
- Final CTA/Footer CTA is in `resources/views/frontend/partials/footer.blade.php`.

## 3. Homepage Section Map

| Section | File | Main data source | Status |
| --- | --- | --- | --- |
| Booking Form | `resources/views/frontend/home.blade.php` | Categories, destinations, product route | Hybrid/static labels |
| Hero | `resources/views/frontend/home.blade.php` | `home.hero` PageSection, media slots, default media | CMS-managed content with fixed layout |
| Our Service / Popular Tour | `resources/views/frontend/sections/popular-tour.blade.php` | `home.popular_tour` PageSection, media slots, site logo | CMS-managed content with fixed editorial layout |
| Package/Product | `resources/views/frontend/sections/popular-products.blade.php` | `home.popular_products_intro`, products, categories | Hybrid: intro CMS, cards module-driven |
| Manual Ads | `resources/views/frontend/partials/manual-ads.blade.php` | `home.manual_ads`, media slot, `extra_data.overlay_title` | CMS-managed content with fallback text |
| Our Philosophy / About Journey | `resources/views/frontend/sections/about-journey.blade.php` | `home.about_journey`, media slots | Hybrid: intro/media CMS, feature cards hardcoded |
| Destinations / Categories | `resources/views/frontend/sections/categories.blade.php` | `home.categories_intro`, active categories | Hybrid: intro CMS, cards module-driven |
| Explore Banner | `resources/views/frontend/sections/explore-banner.blade.php` | `home.explore_banner`, background media, `extra_data.outline_text` | CMS-managed content with fixed banner layout |
| Guest Review | `resources/views/frontend/sections/testimonials.blade.php` | `home.testimonials`, avatar placeholder | Hybrid: section intro CMS, testimonial cards hardcoded |
| Why Choose Us | `resources/views/frontend/home.blade.php` | Inline hardcoded cards | Hardcoded candidate |
| FAQ Preview | `resources/views/frontend/home.blade.php` | `home.faq`, active FAQ module, fallback FAQ array | Hybrid: intro/media CMS, FAQ module-driven |
| Final CTA / Your Journey Awaits | `resources/views/frontend/partials/footer.blade.php` | `home.footer_cta`, footer/global booking settings | CMS/global-settings hybrid |
| Footer | `resources/views/frontend/partials/footer.blade.php` | Footer settings, business/contact/social settings, site assets | Settings-managed with small static placeholders |

## 4. CMS-Managed Content Map

Existing PageSection-backed homepage keys:

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

Existing module-driven homepage data:

- Products: homepage packages and product cards.
- Categories: booking filter, category tabs/data, category cards.
- Destinations: booking filter and counts in controller.
- FAQs: FAQ preview list.
- Global settings/assets: site logo, footer, booking CTA, contact, socials, default media, default media fit.

## 5. Hardcoded Content Map

Hardcoded content that is safe to keep as fixed layout/system copy for now:

- Section IDs and `data-section-key` markers.
- Form field labels such as `Destination` and `Package Type`.
- Empty state labels such as `Products coming soon`.
- UI labels such as `All`, `Details`, and carousel control labels.
- Design-only decorative text such as `TRAVEL`, if treated as layout ornament.

Hardcoded content recommended for future CMS/module migration:

- Booking/search softcopy: `Discover premium Bintan packages...`
- Why Choose Us heading and three cards.
- About Journey feature cards: `Best Travel Agency` and `Secure Journey With Us`.
- Testimonial names, roles, text, ratings, and initials.
- Explore banner fallback outline text mismatch: seeder uses `overlay_title`, Blade reads `outline_text`.
- Footer newsletter placeholder form `action="#"`.
- Footer copyright year `2026`.
- Public fallback labels such as `NO IMAGE`, `No Image`, and `LOGO HERE`.

## 6. Hybrid Content Findings

The current homepage intentionally mixes fixed layout with editable content. This is a good fit for the current CMS maturity level.

Hybrid sections that should remain hybrid:

- Popular Products: PageSection controls section intro, Product module controls cards.
- Categories: PageSection controls intro, Category module controls category cards.
- FAQ Preview: PageSection controls intro/media, FAQ module controls questions.
- Footer CTA: PageSection controls CTA copy/media, global booking settings control WhatsApp action.

Hybrid sections needing cleanup later:

- About Journey: feature cards are hardcoded but related to editable section narrative.
- Testimonials: section intro is editable but review cards are not.
- Booking Form: filter data is dynamic, but copy is hardcoded.

## 7. Page Sections Capability

Current `page_sections` capability:

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
- unique `page_key + section_key`

Current `page_section_media` capability:

- belongs to `page_sections`
- `role`
- `slot_key`
- `label`
- `path`
- `alt`
- `object_fit`
- `object_position`
- `sort_order`
- `is_active`

Current media rules:

- `PageSection::MEDIA_LIMIT` is 10.
- `home.hero` supports legacy images, background slots, and gallery.
- `home.popular_tour` supports four frame slots and uses global site logo.
- `home.manual_ads` supports one main visual.
- `home.about_journey` supports main and secondary visuals.
- `home.explore_banner` supports desktop and mobile background.
- `home.faq` supports one preview image.
- `home.footer_cta` supports one visual.

Current admin capability:

- Admin can select a Page Sections page and manage section records.
- Admin can update label/title/subtitle/description/button fields, extra JSON, active status, sort order, animation, legacy images where supported, slot media, gallery media, object fit, and object position.
- Admin routes are protected under `auth` and `admin` middleware.

Limitations:

- No repeatable text item structure for cards such as Why Choose Us, Journey features, or Testimonials.
- No canonical docs file exists at `docs/modules/page-sections.md`.
- PageSectionRegistry registers product page sections automatically, but homepage records depend on `HomePageSectionSeeder` or existing DB records.

## 8. Fixed Layout vs Dynamic Content Boundary

Keep fixed in Blade/CSS:

- Section order as rendered by homepage Blade.
- Layout structure, grid, spacing, responsive behavior, class names, visual frame positions, animation wiring.
- Navigation/header/footer structure.
- Product card component structure.
- Search form route and query parameter names.
- Accessibility landmarks, IDs, and `data-section-key` attributes.

Allow CMS/module data to control:

- Section labels, titles, subtitles, descriptions, button text, button URL.
- Section media, alt text, object fit, and object position.
- Product/category/destination/FAQ records.
- Footer/global contact/social/booking settings.
- Carefully selected extra values such as `outline_text` for banners.

Do not move into CMS yet:

- Tailwind/CSS classes.
- Arbitrary Blade partial selection.
- Query behavior.
- Layout variants.
- Route names.
- Full page builder blocks.

## 9. Existing Module Data Usage

Products:

- Homepage uses latest published products and featured products.
- Product cards use reusable `frontend.components.product-card`.
- Price rendering uses `frontend.components.product-price`.

Categories:

- Booking form uses active categories.
- Homepage product tabs use categories from loaded homepage products.
- Category cards use active categories with published product counts.

Destinations:

- Booking form uses active destinations.
- Controller loads active destinations with published product counts.
- Destination-specific homepage cards are not currently rendered as a dedicated section.

FAQs:

- FAQ preview uses active FAQ records.
- Fallback FAQ array is used when no FAQ records exist.

Global settings:

- Footer, booking CTA, socials, contact, business identity, default media, logo, and default media fit are settings-driven.

## 10. Recommended Content Source per Section

| Section | Recommended source | Future action |
| --- | --- | --- |
| Booking Form | Existing categories/destinations plus fixed labels | Keep layout fixed; optionally move softcopy to PageSection `extra_data` later |
| Hero | Existing `home.hero` PageSection | Keep as-is; only polish fallback/media docs |
| Our Service / Popular Tour | Existing `home.popular_tour` PageSection | Keep as-is; document media slot expectations |
| Package/Product | PageSection intro plus Product module | Keep as-is; later tune product query/visibility policy |
| Manual Ads | Existing `home.manual_ads` PageSection | Keep as-is; normalize `overlay_title`/`outline_text` naming later |
| Our Philosophy | Existing `home.about_journey` PageSection plus fixed feature cards | Move feature card copy into controlled JSON or future repeatable section item only after design approval |
| Destinations/Categories | PageSection intro plus Category module | Keep category cards module-driven; plan separate destination section only if desired |
| Why Choose Us | New PageSection candidate or existing `home.why_choose_us` if created later | Plan first; do not add schema now |
| Guest Review | PageSection intro plus future testimonials module or controlled JSON | Prefer future module if reviews need credibility/admin control |
| FAQ Preview | PageSection intro plus FAQ module | Keep as-is |
| Final CTA | PageSection plus global booking settings | Keep as-is |
| Footer | Global settings plus fixed footer layout | Keep as-is; fix year/newsletter later in a small step |

## 11. Backend/Data Contract Impact

No backend change is recommended in this planning step.

Future implementation should preserve:

- `HomeController@index` route contract.
- Existing view variable names: `sections`, `siteAssets`, `faqs`, `featuredProducts`, `homeProducts`, `homeProductCategories`, `categories`, `destinations`.
- Product/category/destination/FAQ queries prepared in controller.
- No database queries in Blade.
- Existing `PageSection` and `PageSectionMedia` relationships.

Possible future backend additions, after approval:

- Add a small presenter/DTO or support class for homepage section fallback copy if Blade conditionals grow.
- Add homepage tests for expected section keys.
- Add optional PageSection registry coverage for home records if the team wants automatic sync outside seeders.

## 12. Blade/Component Impact

No Blade change is recommended in this planning step.

Future implementation impact should be limited to:

- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/sections/testimonials.blade.php`
- `resources/views/frontend/sections/explore-banner.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- possibly `resources/views/frontend/partials/manual-ads.blade.php`

Avoid touching:

- Header/mobile navigation from FRONTEND-02 unless a later task explicitly targets it.
- Product card component unless the change is directly related to homepage module rendering.
- Product detail/listing pages.

## 13. Database Impact

This step has no database impact.

Future options:

- No schema change is needed to move simple section copy into PageSections.
- Existing `extra_data` can hold small controlled values such as `softcopy`, `feature_cards`, or `outline_text`, but this should be used sparingly to avoid turning PageSections into an unstructured page builder.
- A future testimonials/reviews module would require separate planning and approval because it changes database scope.
- A future repeatable section items table would also require separate planning and approval.

## 14. Cache/Performance Impact

Current performance posture:

- Homepage loads PageSections with media in a single prepared query.
- Products, categories, destinations, and FAQs are loaded in controller.
- Global settings are cached through `GlobalSettingsService`.
- PageSection content does not appear to be cached independently.

Future performance notes:

- Moving more copy into PageSections has low query cost if data remains in the existing loaded `sections` collection.
- Avoid adding repeated queries from Blade partials.
- Avoid loading all testimonials/products if future modules grow; use limits and backend ordering.
- If PageSections become more central, consider a safe read cache for public PageSections only after mutation invalidation is designed.

## 15. Fallback Strategy

Current fallback strategy:

- PageSection fields fall back to hardcoded text.
- Missing media falls back to default media settings where available.
- FAQ preview falls back to a small array when no active FAQ records exist.
- Product/category empty states are rendered.

Recommended fallback policy:

- Keep fallback text for public safety, but make it polished and brand-safe.
- Replace raw placeholder labels such as `NO IMAGE`, `No Image`, and `LOGO HERE` in a later focused polish step.
- Do not hide entire sections automatically just because one CMS field is missing, unless the section is explicitly inactive.
- If a PageSection is inactive, the current `HomeController` excludes it, causing Blade fallback content to render. A future step should decide whether inactive means "hide section" or "show fallback"; current behavior is not a true hide.

## 16. Testing Plan

Recommended tests before/with future implementation:

- Homepage renders all expected `data-section-key` values.
- Homepage uses PageSection title/label/description for `home.hero`.
- Homepage uses PageSection content for Popular Tour, Manual Ads, About Journey, Categories, Explore Banner, Testimonials, FAQ, and Footer CTA.
- Homepage product section renders published products only.
- Homepage product card with missing price shows `Price on request`.
- FAQ preview uses active FAQ records when present.
- FAQ preview fallback appears when no active FAQs exist.
- PageSection media slots render expected image paths and alt text.
- Inactive PageSection behavior is explicitly tested after policy decision.
- No frontend Blade database query pattern appears in homepage files.

Manual QA:

- Check homepage at mobile, tablet, and desktop widths.
- Verify all section anchors and CTAs.
- Verify missing/default media fallbacks.
- Verify no text/image overlap after CMS content changes.

## 17. Files Inspected

References:

- `AGENTS.md`
- `ai/skills/frontend-skill.md`
- `ai/skills/uiux-skill.md`
- `ai/skills/component-library-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/reports/frontend/improve-05-frontend-uiux-data-rendering-audit.md`
- `ai/reports/frontend/frontend-01-mobile-nav-empty-price-product-card-fix-plan.md`
- `ai/reports/frontend/frontend-02-mobile-nav-empty-price-product-card-implementation-report.md`
- `docs/frontend/README.md`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`

Homepage/frontend:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/popular-tour.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/sections/explore-banner.blade.php`
- `resources/views/frontend/sections/testimonials.blade.php`
- `resources/views/frontend/partials/manual-ads.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- `resources/views/frontend/components/section-media-slider.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/components/product-price.blade.php`

Page Sections:

- `routes/admin.php`
- `app/Models/PageSection.php`
- `app/Models/PageSectionMedia.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Services/PageSectionImageService.php`
- `app/Support/HomepageSectionMedia.php`
- `app/Support/PageSectionRegistry.php`
- `database/migrations/2026_06_03_000001_create_page_sections_table.php`
- `database/migrations/2026_06_03_000003_create_page_section_media_table.php`
- `database/migrations/2026_06_03_000004_add_role_slots_to_page_section_media_table.php`
- `database/migrations/2026_06_03_000006_backfill_homepage_media_slots.php`
- `database/migrations/2026_06_07_000001_add_display_options_to_page_section_media_table.php`
- `database/seeders/HomePageSectionSeeder.php`
- `resources/views/backend/page-sections/index.blade.php`
- `resources/views/backend/page-sections/sections.blade.php`
- `resources/views/backend/page-sections/edit.blade.php`

Related models/tests:

- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `tests/Feature/Admin/PageSectionMediaSlotTest.php`
- `tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Performance/GlobalSettingsCacheTest.php`

Missing expected docs:

- `docs/modules/page-sections.md`

## 18. Files Recommended for Implementation

Likely future FRONTEND-04A files:

- `resources/views/frontend/home.blade.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `ai/reports/frontend/frontend-04a-homepage-cms-content-implementation-report.md`

Likely future FRONTEND-04B files:

- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/home.blade.php`
- `database/seeders/HomePageSectionSeeder.php` only if approved and needed for new default PageSection keys or extra_data defaults
- `tests/Feature/Frontend/HomepageCmsContentTest.php`

Likely future docs files:

- `docs/modules/page-sections.md`
- `docs/frontend/homepage.md`
- `docs/changelog/CHANGELOG.md`

Files that should not be changed in the next small frontend step unless explicitly approved:

- Database migrations.
- Routes.
- Product/category/destination models.
- Header/mobile navigation files.
- Product detail/listing pages.

## 19. Phased Implementation Roadmap

Recommended sequence:

1. `FRONTEND-04A: Homepage Section Key and CMS Content Tests`
   - Add tests around current PageSection-backed homepage behavior.
   - No visual changes.
   - Confirm hardcoded vs CMS content contract before moving anything.

2. `FRONTEND-04B: Homepage Hardcoded Copy Cleanup, No Schema`
   - Move booking softcopy, Why Choose Us heading/card copy, and small CTA/fallback labels into existing PageSection fields or controlled `extra_data` only where safe.
   - Keep layout fixed.
   - Avoid adding repeatable schema.

3. `FRONTEND-04C: Testimonials Content Strategy Plan`
   - Decide whether testimonials should be PageSection `extra_data`, a dedicated module, or static credibility copy.
   - Planning only before any database change.

4. `FRONTEND-04D: Page Sections Documentation Sync`
   - Create `docs/modules/page-sections.md`.
   - Document homepage section keys, media slots, fallback behavior, and admin editing rules.

5. `FRONTEND-04E: Homepage Fallback Polish`
   - Replace raw placeholder labels with polished empty/fallback states.
   - Fix footer year behavior if approved.
   - Keep this as presentation-only.

## 20. Risks and Rollback Plan

Risks:

- Moving too much content into `extra_data` can create an unstructured page builder by accident.
- Inactive PageSection behavior is ambiguous because excluded records currently cause fallback content to render.
- Testimonial content has credibility/SEO implications and should not be invented as fake managed reviews.
- Footer newsletter form is currently a placeholder; making it functional would be a separate backend/security task.
- Extra PageSection keys without seed/admin docs can confuse future editors.

Rollback plan for future implementation:

- Revert Blade changes for the affected section.
- Revert any seeder/default content changes if used.
- Keep existing PageSection database rows unless a migration/data change was explicitly introduced and separately approved.
- Run homepage feature tests and `php artisan test`.
- Run `git diff --check`.

## 21. Recommended Next Step

Proceed with `FRONTEND-04A: Homepage Section Key and CMS Content Tests`.

Suggested scope:

- Add focused tests that document current homepage CMS behavior.
- Verify key PageSection content renders.
- Verify module-driven products/categories/FAQs still render.
- Verify hardcoded candidates are documented but not moved yet.
- Do not change database schema, routes, or homepage layout.

## Verification

- Planning-only report created.
- No Laravel runtime code was changed.
- No database, migration, route, model, controller, Blade, CSS, JS, asset, or test file was changed in this step.
