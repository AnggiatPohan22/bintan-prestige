# STEP FRONTEND-04A - Homepage Section Key and CTA CMS Sync Report

Date: 2026-06-13
Scope: Minimal homepage section-key mapping and CTA CMS sync based on FRONTEND-03.

## Summary

Implemented a focused homepage CMS sync pass. Homepage PageSection keys are now registered in the canonical `PageSectionRegistry`, the homepage controller loads only active registered homepage sections in one query, and homepage CTA buttons can use PageSection-managed text/URL while preserving existing fixed layout and fallbacks.

No database schema, migration, route, model, package, or homepage redesign was changed.

## Files Created/Changed

Created:

- `app/Support/PageSectionCta.php`
- `tests/Feature/Frontend/HomepageCmsContentTest.php`
- `ai/reports/frontend/frontend-04a-homepage-section-key-cta-cms-sync-report.md`

Changed:

- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Support/PageSectionRegistry.php`
- `database/seeders/HomePageSectionSeeder.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- `resources/views/frontend/partials/manual-ads.blade.php`
- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/sections/explore-banner.blade.php`
- `resources/views/frontend/sections/popular-tour.blade.php`
- `docs/changelog/CHANGELOG.md`

Existing unrelated working tree item not touched:

- `ai/readme.md`

## Section Keys Used

Canonical homepage PageSection keys:

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

## Homepage Data Flow

Current flow after this step:

1. `PageSectionRegistry::sections()['home']` defines the canonical homepage section key map and default seed values.
2. `HomeController@index` derives allowed homepage section keys from that registry.
3. The controller loads active `page_sections` for `page_key = home` with `media` in one query.
4. The result is keyed by `section_key` and passed to Blade as `$sections`.
5. Blade partials render prepared data only and do not query the database.

Module data remains separate:

- Products remain product-module driven.
- Categories remain category-module driven.
- Destinations remain destination-module driven.
- FAQs remain FAQ-module driven.
- Footer/booking/contact/social data remain global-settings driven.

## CTA Fields Synchronized

Synchronized PageSection CTA fields:

- `button_text`
- `button_url`

Affected CTA surfaces:

- Hero CTA now renders only when `home.hero.button_text` and a safe `button_url` are present.
- Popular Tour CTA uses `home.popular_tour.button_url` with product route fallback.
- Manual Ads CTA uses `home.manual_ads.button_url` with product route fallback.
- About Journey CTA uses `home.about_journey.button_url` with product route fallback.
- Explore Banner CTA uses `home.explore_banner.button_url` with product route fallback.
- Footer CTA can use `home.footer_cta.button_text` and `home.footer_cta.button_url`; if missing or invalid, it falls back to the existing global WhatsApp booking CTA flow.

## Fallback Behavior

Fallbacks preserved:

- Missing PageSection records still allow homepage fallback content to render.
- Missing or inactive section content does not break homepage rendering.
- Empty Hero CTA stays hidden because there was no previous hero button.
- Section CTA links fall back to `route('products.index')` where that was the existing behavior.
- Footer CTA falls back to global booking/contact WhatsApp URL.

Safety rules added:

- Empty CTA URL returns no URL unless a fallback exists.
- `href=""` is avoided.
- `href="#"` is not introduced by this step.
- `javascript:`, `data:`, and `vbscript:` CTA URLs are rejected.

## Admin-to-Frontend Impact

Admin Page Sections can now rely on the same canonical registry for homepage keys and product page keys. Updating existing PageSection fields from admin will appear on the next frontend request according to the existing uncached PageSection flow.

No admin UI field, route, validation rule, or database field was added.

## Database Impact

No schema changes.

No migration changes.

No data deletion or data mutation was performed by this step.

`HomePageSectionSeeder` now reads homepage defaults from `PageSectionRegistry` to avoid duplicate homepage key/default definitions.

## Performance/Security Impact

Performance:

- Homepage PageSections are still loaded in one query with media eager loading.
- No query-per-section pattern was introduced.
- No new cache layer was added.
- Products, categories, destinations, FAQs, and global settings continue using existing flows.

Security:

- PageSection CTA URLs are normalized through `PageSectionCta::safeUrl()`.
- Unsafe schemes such as `javascript:`, `data:`, and `vbscript:` are rejected.
- Blade output remains escaped by default.
- No raw HTML rendering was added.

## Focused and Full Test Results

Focused tests:

- `php artisan test --filter=HomepageCmsContentTest` -> passed, 4 tests, 41 assertions.
- `php artisan test --filter=PageSectionMediaSlotTest` -> passed, 13 tests, 72 assertions.
- `php artisan test --filter=GlobalBookingCtaSettingsTest` -> passed, 4 tests, 19 assertions.
- `php artisan test --filter=GlobalDefaultMediaAssetsTest` -> passed, 5 tests, 35 assertions.

Full test:

- `php artisan test` -> passed, 153 tests, 735 assertions.

Diff check:

- `git diff --check -- <FRONTEND-04A files>` -> passed, no whitespace errors in files changed by this step.
- Full `git diff --check` -> blocked by known pre-existing `ai/readme.md:9` blank line at EOF issue. `ai/readme.md` was not touched in this step.

## Remaining Hardcoded Content

Still intentionally outside this step:

- Booking/search field labels and softcopy.
- Why Choose Us cards.
- About Journey feature cards.
- Testimonial card names, roles, text, ratings, and initials.
- Footer newsletter placeholder behavior.
- Footer utility placeholder links.
- Footer copyright year.
- Placeholder labels such as `NO IMAGE`, `No Image`, and `LOGO HERE`.

## Rollback Note

To rollback FRONTEND-04A:

1. Revert `PageSectionRegistry` homepage section registration.
2. Revert `HomeController` `whereIn` registry scoping.
3. Revert `HomePageSectionSeeder` registry sourcing.
4. Remove `PageSectionCta` and restore direct CTA URL usage.
5. Remove Hero CTA rendering and `data-section-key` additions for FAQ/Footer CTA if needed.
6. Remove `HomepageCmsContentTest`.
7. Revert changelog/report entries.
8. Run `php artisan test` and `git diff --check`.

No database rollback is required.

## Recommended Next Step

Proceed to `FRONTEND-04B: Homepage Hardcoded Copy Cleanup, No Schema`.

Recommended scope:

- Keep fixed layout unchanged.
- Move only safe, high-value homepage copy into existing PageSection fields or carefully controlled `extra_data`.
- Do not create a testimonials module, page builder, sitemap, dedicated pages, or new database schema in that step.
