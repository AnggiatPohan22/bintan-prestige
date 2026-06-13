# STEP FRONTEND-04B - Homepage Hardcoded Copy Cleanup Report

Date: 2026-06-13
Scope: Minimal homepage hardcoded business copy cleanup using existing PageSection fields and controlled `extra_data`. No schema changes.

## Summary

Implemented a no-schema homepage copy cleanup pass. Supported homepage business copy is now prepared through `App\Support\HomepageContent` from the existing PageSection map created in FRONTEND-04A. Blade keeps the fixed luxury layout and receives display-ready copy arrays with existing fallback text preserved.

No route, model, database schema, migration, package, homepage redesign, or page builder work was introduced.

## Files Created/Changed

Created:

- `app/Support/HomepageContent.php`
- `ai/reports/frontend/frontend-04b-homepage-hardcoded-copy-cleanup-report.md`

Changed:

- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Support/PageSectionRegistry.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `docs/changelog/CHANGELOG.md`

## Sections Updated

- `home.hero`
  - Search field labels, placeholders, submit label, and supporting copy can come from `extra_data`.

- `home.popular_products_intro`
  - Product section view-all CTA and empty-state copy can come from PageSection fields/`extra_data`.

- `home.about_journey`
  - Journey feature card copy can come from `extra_data.features`.

- `home.categories_intro`
  - Category empty-state title can come from `extra_data.empty_title`.

- `home.faq`
  - FAQ fallback items can come from `extra_data.fallback_items` when no FAQ module records exist.

## Hardcoded Copy Moved to CMS

Moved to existing PageSection data contracts:

- Booking/search softcopy.
- Booking/search labels and placeholders.
- Booking/search submit text.
- Popular products view-all button text/URL.
- Popular products empty title/text.
- About Journey feature card title/text.
- Category empty state title.
- FAQ fallback questions/answers.

These are backed by existing `page_sections.extra_data` or existing `button_text`/`button_url` fields. No columns or tables were added.

## Hardcoded Copy Intentionally Retained

Retained for now:

- Why Choose Us cards because there is no current dedicated PageSection key or module for the section.
- Testimonial names, roles, text, ratings, and initials because moving them into CMS needs a credibility/content strategy first.
- Footer newsletter placeholder form.
- Footer utility links that still use `#`.
- Footer copyright year.
- Placeholder labels such as `NO IMAGE`, `No Image`, and `LOGO HERE`.
- Fixed layout/design labels and visual structure.

## Content Source per Section

- Hero/Search: `home.hero.extra_data` with existing Blade fallbacks.
- Popular Products: `home.popular_products_intro` fields and `extra_data`, with Product module data for cards.
- About Journey: `home.about_journey` fields and `extra_data.features`.
- Categories: `home.categories_intro` fields and `extra_data`, with Category module data for cards.
- FAQ: `home.faq` fields and FAQ module data; fallback FAQ items use `extra_data.fallback_items`.
- Footer CTA: unchanged from FRONTEND-04A, using `home.footer_cta` plus global Booking CTA fallback.

## Fallback Behavior

- Missing PageSection records still allow homepage to render with safe fallback copy.
- Empty string `extra_data` values fall back to existing copy.
- Explicit empty `features` array hides Journey feature cards without leaving empty card wrappers.
- Module-driven data remains preferred over fallback content.
- No empty CTA URL or unsafe CTA URL behavior was introduced.

## Backend Data Contract Impact

`HomeController` now prepares:

- `$sections`
- `$homepageContent`

`$homepageContent` is a display-ready array built by `HomepageContent::fromSections($sections)`. It does not query the database.

Existing variables remain available:

- `$featuredProducts`
- `$homeProducts`
- `$homeProductCategories`
- `$categories`
- `$destinations`
- `$heroBackgroundUrl`
- `$sections`
- `$siteAssets`
- `$faqs`

## Blade/Component Impact

Blade impact is limited to existing homepage files:

- Homepage search and FAQ fallback copy now read from `$homepageContent`.
- Popular Products CTA/empty state now reads from `$homepageContent`.
- About Journey feature cards now read from `$homepageContent`.
- Categories empty state now reads from `$homepageContent`.

No Tailwind classes, layout wrappers, animation behavior, routes, or module queries were moved into the database.

## Database/Schema Impact: None

No schema changes.

No migration changes.

No new table or column.

No data deletion or update was performed.

`PageSectionRegistry` default values now include controlled `extra_data` defaults for future seeding/sync behavior.

## Performance/Security Impact

Performance:

- No new queries were added.
- Homepage PageSections are still loaded once in `HomeController`.
- `HomepageContent` uses the already-loaded `$sections` collection.

Security:

- Blade output remains escaped.
- No raw HTML rendering was added.
- No scriptable CMS layout/style behavior was introduced.
- Existing FRONTEND-04A CTA URL safety remains in place.

## Focused and Full Test Results

Focused tests:

- `php artisan test --filter=HomepageCmsContentTest` -> passed, 8 tests, 99 assertions.
- `php artisan test --filter=PageSectionMediaSlotTest` -> passed, 13 tests, 72 assertions.
- `php artisan test --filter=GlobalDefaultMediaAssetsTest` -> passed, 5 tests, 35 assertions.
- `php artisan test --filter=ProductIndexUiTest` -> passed, 3 tests, 20 assertions.

Full test:

- `php artisan test` -> passed, 157 tests, 793 assertions.

Diff/status:

- `git diff --check` -> passed, no whitespace errors.
- `git status --short` -> shows only FRONTEND-04B changed/created files.

## Deferred CMS Gaps

Deferred:

- Dedicated CMS strategy for Why Choose Us.
- Dedicated testimonials/reviews content strategy.
- Footer newsletter behavior.
- Footer utility placeholder links.
- Footer copyright year.
- Placeholder label polish.
- `docs/modules/page-sections.md` remains missing and should be created in a documentation sync step.

## Rollback Note

To rollback FRONTEND-04B:

1. Remove `App\Support\HomepageContent`.
2. Remove `$homepageContent` preparation from `HomeController`.
3. Restore the previous hardcoded copy in the affected homepage Blade files.
4. Revert `PageSectionRegistry` `extra_data` default additions.
5. Revert `HomepageCmsContentTest` additions.
6. Revert changelog/report entries.
7. Run `php artisan test` and `git diff --check`.

No database rollback is required.

## Recommended Next Step

Proceed to `FRONTEND-04C: Testimonials Content Strategy Plan`.

Recommended scope:

- Planning only.
- Decide whether testimonials stay fixed credibility copy, use controlled PageSection `extra_data`, or become a dedicated future module.
- Do not create reviews CRUD/schema until the content governance decision is approved.
