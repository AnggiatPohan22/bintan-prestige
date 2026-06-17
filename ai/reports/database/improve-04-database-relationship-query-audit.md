# STEP IMPROVE-04 - Database Relationship & Query Audit

Date: 2026-06-13
Status: Completed as audit-only.

## 1. Executive Summary

Bintan Prestige CMS has a workable database and relationship foundation for the current Laravel CMS modules. The main content entities are normalized enough for Products, Categories, Destinations, Product Prices, Product Images, Product Highlights, Product Features, Product FAQs, Product Itineraries, Product Notes, Page Sections, Page Section Media, Site Assets, Site Settings, Bookings, and Admin Users.

The strongest parts are Eloquent relationship coverage, cascade cleanup for product-owned child tables, backend-side filtering/searching/sorting, pagination on admin tables, and active admin access fields on users.

The main risks are index readiness, seed/factory drift, repeated global settings queries, broad eager loading for product listing contexts, possible future N+1 from relationship-backed accessors, and schema semantics around category/destination soft delete versus product foreign keys with cascade delete. These are not critical launch blockers, but they should be cleaned before scaling content volume.

No code, database, migration, route, controller, model, view, config, public file, asset, or test was changed during this audit. This report is the only created file.

## 2. Current Database Structure

Core tables:

- `users`
- `categories`
- `destinations`
- `products`
- `product_prices`
- `product_images`
- `product_highlights`
- `product_features`
- `product_faqs`
- `product_itineraries`
- `product_notes`
- `bookings`
- `booking_items`
- `page_sections`
- `page_section_media`
- `faqs`
- `site_assets`
- `site_settings`
- Laravel framework tables: cache, jobs, sessions, password reset tokens.

Migration status:

- `php artisan migrate:status` reports all inspected migrations as `Ran`.
- SECURITY-03 `users.is_admin` migration is applied.
- SECURITY-08 `users.role`, `users.is_active`, and `users.created_by` migration is applied.

## 3. Migration Findings

Strengths:

- Product child tables use `product_id` foreign keys with `cascadeOnDelete()`.
- Page Section Media uses `page_section_id` with `cascadeOnDelete()`.
- Booking Items use `booking_id` and `product_id` foreign keys with `cascadeOnDelete()`.
- Slugs are unique on `categories`, `destinations`, and `products`.
- `page_sections` has a unique pair on `page_key` and `section_key`.
- `site_assets.key` and `site_settings.key` are unique.
- `site_settings.group`, `page_sections.page_key`, `page_sections.section_key`, `page_section_media.role`, `page_section_media.slot_key`, `users.role`, and `users.is_active` have indexes.
- Important boolean/status defaults exist for active fields and admin fields.

Needs review:

- `products.category_id` and `products.destination_id` use `cascadeOnDelete()`, while Category and Destination models use SoftDeletes and Product relationships use `withTrashed()`. This is acceptable while force-delete is guarded, but the schema means a true parent delete can cascade products.
- `product_prices` does not have a unique constraint for `(product_id, currency)`, even though the application treats IDR/SGD as one price per product.
- `product_prices` does not have an explicit composite index for `(currency, price)`, which is relevant for product price range filters and min/max price lookups.
- `products.status`, `products.is_featured`, `products.category_id`, `products.destination_id`, `products.pickup_type`, and `products.duration` are frequently filtered or sorted but do not have explicit secondary indexes beyond FK indexes.
- `sort_order` columns exist across child modules, but most do not have composite indexes with their parent foreign key.
- `site_assets.is_active` is queried globally but not explicitly indexed.
- `faqs.is_active` and `faqs.sort_order` are queried for homepage display but not explicitly indexed.

## 4. Model Relationship Findings

Good:

- `Product` defines relationships for category, destination, images, prices, booking items, highlights, features, itineraries, FAQs, and notes.
- `Category` and `Destination` define `products()` and use SoftDeletes.
- Product child models define `product()` relationships.
- `PageSection` defines `media()` and `activeMedia()`.
- `PageSectionMedia` defines `pageSection()`.
- `Booking` defines `items()`, and `BookingItem` defines `booking()` and `product()`.
- `User` contains role/admin helpers: `isAdmin()`, `isSuperAdmin()`, and `canAccessAdmin()`.

Needs review:

- Some relationships do not declare return types, for example `ProductPrice::product()`, `ProductNote::product()`, and several scopes/accessors. This is a maintainability issue, not a runtime blocker.
- `Product::getIdrPriceAttribute()` and `getSgdPriceAttribute()` read from `$this->prices`. This is efficient only when `prices` is already eager loaded.
- Product categorized feature accessors read from `$this->features`. This is safe in current eager-loaded flows, but risky if reused in new contexts without `features`.
- `PageSection::mediaSlot()` and `galleryMedia()` read from loaded media collections. Current controllers usually load `media`, but future usage must avoid lazy-loading loops.
- `Product::scopeFrontendReady()` eager loads many relationships and is currently reused in listing contexts where not every relationship may be needed.

## 5. Query Flow Findings

Admin:

- `Admin\ProductController@index` uses backend filters for search, category, destination, and status; eager loads category, destination, prices; uses relationship counts; paginates.
- `Admin\CategoryController@index` and `Admin\DestinationController@index` use backend search, `withCount('products')`, archive query, and pagination.
- `Admin\PageSectionController@sections` uses backend page filtering, media counts, custom ordering, and pagination.
- `Admin\UserManagementController@index` uses backend filtering for search, role, status, and pagination.
- `Admin\DashboardController@index` uses separate count queries and eager loads recent product category/destination.
- `Admin\SiteSettingController@edit` performs multiple settings and asset queries per admin settings page.

Frontend:

- `Frontend\HomeController@index` prepares page sections, site assets, FAQs, featured products, home products, category counts, and destination counts before rendering.
- `Frontend\ProductController@index` performs backend filters for price, duration, destination, category, vehicle type; sorts by price/duration/newest; paginates.
- `Frontend\ProductController@show` eager loads product detail relationships and prepares SEO data before rendering.

Query flow is generally backend-first and aligned with AGENTS.md.

## 6. Eager Loading Findings

Good:

- Admin Product index eager loads category, destination, and prices.
- Admin Product edit loads highlights, features, FAQs, itineraries, notes, images, and prices.
- Frontend Product detail loads category, destination, prices, images, highlights, features, FAQs, itineraries, and notes.
- Frontend Home products use `frontendReady()`, then explicitly include category, destination, images.
- Page Sections home query eager loads media.
- Category and Destination home cards use `withCount` for published product counts.

Needs review:

- `frontendReady()` is broad and may over-fetch for product cards/listings.
- Home page runs two separate product queries, `featuredProducts` and `homeProducts`, both with broad eager loading.
- Product listing uses broad eager loading for paginated cards even if some relationships are not always rendered.
- The project does not yet have query count tests to guard against N+1 regressions.

## 7. N+1 Query Risk

Current N+1 posture:

- No direct Blade database queries were found.
- Main product listing/detail flows use eager loading.
- Page Section rendering has media eager loading on homepage and edit screens.

Risk areas:

- Product price and feature accessors can lazy-load relationships in future views if a developer forgets eager loading.
- PageSection media helper methods can lazy-load if called without `media` loaded.
- View composer global settings queries can repeat across multiple composed layouts/partials.
- Dashboard count queries are not N+1, but they are multiple separate queries on every dashboard load.
- PageSectionRegistry sync runs from admin controller entry points and may write/read registry rows during page-section admin requests.

## 8. Pagination Findings

Good:

- Admin Products paginate 10 per page.
- Admin Categories and archived Categories paginate separately.
- Admin Destinations and archived Destinations paginate separately.
- Admin Page Sections paginate 20 per page.
- Admin Users paginate 15 per page.
- Frontend Products paginate 8 per page.
- Admin FAQ index paginates 20 per page.

Needs review:

- Frontend homepage uses `take(6)` and `take(12)`, which is safe for fixed sections.
- Category and Destination filter lists are loaded with `get()`. Safe while small, but if they grow significantly, consider caching or limiting active filter lists.
- Dashboard stats and recent products are fixed-size and safe for now.

## 9. Index Findings

Existing useful indexes/unique constraints:

- Unique: `users.email`, `categories.slug`, `destinations.slug`, `products.slug`, `bookings.booking_code`, `site_assets.key`, `site_settings.key`, `page_sections(page_key, section_key)`.
- Indexes from FK columns: `products.category_id`, `products.destination_id`, product child `product_id`, `booking_items.booking_id`, `booking_items.product_id`, `page_section_media.page_section_id`, `users.created_by`.
- Explicit indexes: `page_sections.page_key`, `page_sections.section_key`, `page_section_media.role`, `page_section_media.slot_key`, `site_settings.group`, `users.role`, `users.is_active`.

Index gaps to consider later:

- `products.status`
- `products.is_featured`
- Composite `products(status, is_featured, created_at)` for homepage featured/latest content.
- Composite `products(status, category_id)` and `products(status, destination_id)` for frontend filters.
- `products.pickup_type`
- `products.duration` if duration filtering remains string-based.
- Composite `product_prices(currency, price)` for price filters/min/max.
- Unique or composite index `product_prices(product_id, currency)`.
- Composite indexes for child ordering: `(product_id, sort_order)` on images, highlights, features, FAQs, itineraries, notes.
- Composite `page_sections(page_key, is_active, sort_order)`.
- Composite `page_section_media(page_section_id, role, slot_key, sort_order)`.
- Composite `site_settings(group, is_active)`.
- `site_assets.is_active`.
- `faqs(is_active, sort_order)`.
- Composite `users(is_admin, role, is_active)` for admin user filtering.

## 10. Data Integrity Findings

Good:

- Product-owned child data cascades when product is deleted.
- Page-section media cascades when page section is deleted.
- Admin user creator reference uses `nullOnDelete()`.
- Unique slugs and keys protect canonical routing/settings identifiers.
- Defaults exist for status, booleans, sort order, and admin active state.
- Admin user model does not allow direct mass assignment for `is_admin`, `role`, or `is_active`.

Risks:

- Category/Destination force delete relies on controller-level guard to avoid product cascade. Database itself still allows cascade product deletion if parent is truly deleted.
- `product_prices` allows duplicate currencies per product at database level.
- `ProductFactory` currently sets `status` to boolean `true`, but current application status logic expects string values such as `draft` and `published`.
- `TravelSeeder` uses `Category::query()->delete()` and `Destination::query()->delete()` while related products can exist. Because product FKs use cascade, rerunning seeders can be destructive.
- `BookingFactory` and `Booking` model both generate booking codes independently; collision probability is low but not guaranteed beyond unique DB constraint.
- Product duration is stored as a string while the frontend sorts via `orderByRaw('CAST(duration AS UNSIGNED)')`; values such as `Half Day` and `Full Day` are not naturally numeric.

## 11. Seeder/Factory Findings

Good:

- Seeders create a coherent demo set for Categories, Destinations, Products, Prices, Features, Highlights, Itineraries, Product FAQs, Product Notes, Product Images, Bookings, Page Sections, and FAQs.
- Home Page Section and FAQ seeders use `updateOrCreate`, which is safer for repeatable canonical content.
- User factory supports non-admin, admin, super admin, and inactive states.

Issues:

- `ProductFactory` sets `status` to `true`; current product publication logic expects `published`.
- CategoryFactory chooses from only three names and uses slug from name. Multiple factory creates can collide on unique slug unless test setup overrides names or creates one row.
- DestinationFactory uses `fake()->unique()->randomElement()` from only three names; more than three generated destinations can fail.
- `TravelSeeder` deletes categories and destinations directly and can cascade-delete products through FK behavior.
- Seed image path `products/demo.webp` may not exist in all environments.

## 12. Backend Data Processing Findings

Good:

- Product filtering, sorting, and searching are performed in controllers/query builder.
- Admin table filtering is backend-side.
- Product price min/max and price sorting are backend-side.
- Category/destination published product counts are backend-side via `withCount`.
- Product detail SEO values are prepared before Blade rendering.
- Page Section data is loaded and keyed before frontend rendering.

Needs review:

- Frontend Product index receives several full filter collections. This is acceptable now but should be cached if categories/destinations/filter options grow.
- Global settings are repeatedly prepared by view composer instead of a cached settings service.
- Some collection grouping/filtering still happens in Blade; not database-heavy, but controller/view-model preparation would be cleaner.

## 13. Blade Query Scan Findings

Result:

- No direct database query calls were found in Blade templates.
- No `DB::` usage was found in Blade templates.
- No model `::query()` or model `::where()` database calls were found in Blade templates.

Observed non-query matches:

- Collection methods such as `get()`, `first()`, `where()`, and `count()` on already-provided variables.
- `PageSection::MEDIA_LIMIT` and `PageSection::ANIMATION_OPTIONS` constants in Page Section admin views.

Recommendation:

- Keep Blade query-free.
- Later move `PageSection::MEDIA_LIMIT`/`ANIMATION_OPTIONS` and repeated collection grouping into controller-prepared view data if doing a cleanup pass.

## 14. Performance Risk

High performance risks:

- View composer in `AppServiceProvider` can run many settings queries per request and per composed view target.
- Product listing price filters rely on `whereHas('prices')` and `withMin` without an obvious `product_prices(currency, price)` index.
- Product listing filters use `status`, `category_id`, `destination_id`, `pickup_type`, and `duration` without clear composite indexing strategy.

Medium performance risks:

- `Product::scopeFrontendReady()` may over-eager-load for listing cards.
- Dashboard runs many separate count queries.
- Home page runs multiple product/section/settings queries, some duplicated with global composer data.
- `orderByRaw('CAST(duration AS UNSIGNED)')` on a string column is not index-friendly.

Low performance risks:

- Admin categories/destinations search uses `%term%` LIKE, which is expected for small admin lists but not index-friendly at scale.
- Small lookup lists using `get()` are acceptable now.

## 15. Security/Data Exposure Risk

Good:

- Admin user data listing is behind `auth`, `admin`, and `can:manage-users`.
- Public product detail aborts non-published products.
- Admin access fields are hidden from mass assignment on the User model.
- No raw SQL with user input was found in inspected controller queries.
- `PageSectionController` uses `orderByRaw` with internally built ordered keys, not direct user input.

Risks:

- Granular policies/gates for module actions are still pending.
- Tracking integration settings can store script/text payloads and need explicit governance.
- Public product index must continue to filter `published()` before showing content.
- Product/category/destination seeders are destructive and should not be used casually in production-like data.

## 16. Testing Gap

Existing coverage:

- Security baseline/admin access tests exist.
- Super Admin user management tests exist.
- Frontend Product index/detail rendering tests exist.
- Page Section sync/media tests exist.
- Global Settings tests exist.
- Product status `published` is used in several frontend tests.

Gaps:

- No dedicated relationship tests for Product child modules and cascade behavior.
- No dedicated tests for duplicate product price currency prevention, because the database does not enforce it yet.
- No query count/N+1 regression tests.
- No explicit tests for draft products staying hidden from all frontend listing/search/filter combinations.
- No tests for price range filtering and sorting correctness.
- No tests for category/destination force delete preserving products beyond controller-level guard.
- No tests proving ProductFactory default status matches current publication rules.
- No tests for index-heavy filter scenarios.

## 17. Critical Issues

No critical issue was found that requires immediate emergency action.

Reason:

- All migrations are applied.
- Core relationships exist.
- Main frontend/admin queries are backend-side.
- Blade templates do not perform direct database queries.
- Admin access/security baseline remains in place.

## 18. High Priority Issues

1. `ProductFactory` uses boolean `true` for `status` even though current product logic expects `published`/`draft`.
2. `product_prices` lacks a unique `(product_id, currency)` constraint, allowing duplicate currency rows per product.
3. `product_prices` lacks a query-friendly index for currency/price filtering.
4. Global settings view composer can issue repeated settings queries and should be replaced or backed by a cached settings service.
5. Category/Destination parent FK cascade semantics should be reviewed because the app also uses SoftDeletes and `withTrashed()`.

## 19. Medium Priority Issues

1. Product listing filters need an index plan for status/category/destination/pickup/duration.
2. Product child tables need composite parent/sort indexes if child content grows.
3. `Product::scopeFrontendReady()` is too broad for every frontend listing context.
4. Duration is string-based but sorted as numeric raw SQL.
5. `TravelSeeder` is destructive and should be marked local/demo only.
6. Category/Destination factories can hit unique slug collisions.
7. Dashboard stats could move to cached aggregate/service if admin usage grows.

## 20. Low Priority Issues

1. Some relationship methods lack explicit return types.
2. Page Section constants are referenced directly in Blade.
3. Admin search with `%term%` LIKE is acceptable now but not full-text/search-index ready.
4. Seed image `products/demo.webp` may not exist in every setup.
5. Booking code collision is low probability but currently protected only by database uniqueness.

## 21. Recommended Database Improvement Roadmap

Phase 1 - Low-risk data correctness cleanup:

- Update ProductFactory default status to `published` or `draft` consistently.
- Mark destructive seeders as local/demo only in docs.
- Add tests for draft/published visibility and product filters.

Phase 2 - Product price integrity:

- Add a new migration for unique `(product_id, currency)` after checking existing duplicates.
- Add or consider composite `product_prices(currency, price)` index for frontend price filters.
- Add tests for IDR/SGD sync behavior.

Phase 3 - Query/index optimization:

- Add indexes for common product filters based on real query usage.
- Consider composite indexes for product child ordering.
- Add `page_sections(page_key, is_active, sort_order)` and `page_section_media(page_section_id, role, slot_key, sort_order)` if page-section content grows.

Phase 4 - Settings query optimization:

- Create a cached global settings reader/service.
- Consolidate repeated SiteSetting queries from `AppServiceProvider`.
- Add invalidation strategy when settings update.

Phase 5 - Relationship and query tests:

- Add relationship/cascade tests.
- Add query count/N+1 smoke tests for home, product index, product detail, admin product index, and page sections.
- Add direct tests for price filter/sort and category/destination filters.

## 22. Files Inspected

Rules, skills, and reports:

- `AGENTS.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/cms-architect-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`

Docs:

- `docs/database/README.md`
- `docs/modules/README.md`

Migrations:

- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_05_21_142735_create_categories_table.php`
- `database/migrations/2026_05_21_142746_create_destinations_table.php`
- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_21_142802_create_product_images_table.php`
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `database/migrations/2026_05_21_142908_create_bookings_table.php`
- `database/migrations/2026_05_21_143012_create_booking_items_table.php`
- `database/migrations/2026_05_24_055929_drop_price_from_coloumn_from_products_table.php`
- `database/migrations/2026_05_24_111108_change_status_coloum_in_products_table.php`
- `database/migrations/2026_05_24_170137_create_product_highlights_table.php`
- `database/migrations/2026_05_24_170152_create_product_itineraries_table.php`
- `database/migrations/2026_05_24_170158_create_product_faqs_table.php`
- `database/migrations/2026_05_24_170203_create_product_notes_table.php`
- `database/migrations/2026_05_24_170208_add_product_content_fields_to_products_table.php`
- `database/migrations/2026_05_24_170635_create_product_features_table.php`
- `database/migrations/2026_05_31_000001_add_deleted_at_to_categories_and_destinations_table.php`
- `database/migrations/2026_06_03_000001_create_page_sections_table.php`
- `database/migrations/2026_06_03_000002_create_faqs_table.php`
- `database/migrations/2026_06_03_000003_create_page_section_media_table.php`
- `database/migrations/2026_06_03_000004_add_role_slots_to_page_section_media_table.php`
- `database/migrations/2026_06_03_000005_create_site_assets_table.php`
- `database/migrations/2026_06_03_000006_backfill_homepage_media_slots.php`
- `database/migrations/2026_06_04_000001_create_site_settings_table.php`
- `database/migrations/2026_06_07_000001_add_display_options_to_page_section_media_table.php`
- `database/migrations/2026_06_12_000001_add_is_admin_to_users_table.php`
- `database/migrations/2026_06_12_000002_add_admin_role_status_to_users_table.php`

Models:

- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/ProductPrice.php`
- `app/Models/ProductImage.php`
- `app/Models/ProductHighlight.php`
- `app/Models/ProductFeature.php`
- `app/Models/ProductFaq.php`
- `app/Models/ProductItinerary.php`
- `app/Models/ProductNote.php`
- `app/Models/PageSection.php`
- `app/Models/PageSectionMedia.php`
- `app/Models/Faq.php`
- `app/Models/SiteAsset.php`
- `app/Models/SiteSetting.php`
- `app/Models/Booking.php`
- `app/Models/BookingItem.php`
- `app/Models/User.php`

Controllers/providers/services/support:

- `app/Http/Controllers/Admin/*`
- `app/Http/Controllers/Frontend/*`
- `app/Providers/AppServiceProvider.php`
- `app/Services/*`
- `app/Support/*`

Seeders/factories/tests/views:

- `database/seeders/*`
- `database/factories/*`
- `tests/*`
- `resources/views/**/*.blade.php`

Commands/scans:

- `php artisan migrate:status`
- `rg` schema/index scan
- `rg` controller query scan
- `rg` Blade query scan
- `rg` test coverage scan

## 23. Files Recommended for Future Changes

Do not change these in one broad pass. Use scoped future steps.

Possible future code/database files:

- `database/factories/ProductFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/DestinationFactory.php`
- `database/seeders/TravelSeeder.php`
- New migration for product price uniqueness/indexes.
- New migration for product filter indexes after confirming query patterns.
- New migration for page-section/media/settings indexes if volume grows.
- `app/Models/Product.php`
- `app/Models/ProductPrice.php`
- `app/Providers/AppServiceProvider.php`
- New global settings service/cache class.
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/DashboardController.php`

Possible future tests/docs:

- `tests/Feature/Frontend/ProductFilterQueryTest.php`
- `tests/Feature/Frontend/ProductVisibilityTest.php`
- `tests/Feature/Admin/ProductRelationshipTest.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`
- `tests/Feature/Performance/QueryCountTest.php`
- `docs/database/schema-overview.md`
- `docs/database/relationships.md`
- `docs/database/index-plan.md`
- `docs/modules/products.md`

## 24. Safe Next Step

Recommended next step:

STEP DB-01: Product Factory, Seeder, and Product Price Integrity Plan.

Safe sequence:

1. Planning-only pass for ProductFactory status, destructive seeder posture, and product price uniqueness.
2. If approved, implement factory/seeder cleanup first because it is lower risk than schema changes.
3. Inspect existing data for duplicate `(product_id, currency)` rows before proposing a uniqueness migration.
4. Add focused tests for published/draft visibility and product price sync.
5. Only then consider index migrations based on confirmed query usage.

## Scores

| Area | Score |
| --- | ---: |
| Database Structure | 78/100 |
| Relationship Quality | 82/100 |
| Query Efficiency | 72/100 |
| Eager Loading Readiness | 78/100 |
| Index Readiness | 62/100 |
| Data Integrity | 70/100 |
| Backend Data Processing | 80/100 |
| Performance Scalability | 68/100 |
| Testing Coverage | 70/100 |

Overall database/query readiness: 73/100.

## Audit Notes

- Files changed: only this report.
- Files created: `ai/reports/database/improve-04-database-relationship-query-audit.md`.
- Files deleted: none.
- Database impact: none.
- Migration impact: none.
- Route impact: none.
- Controller/model/view/config/public/asset/test impact: none.
- Testing performed: static scans and `php artisan migrate:status`; full test suite was not run because runtime code was not changed.
- Rollback note: remove this report file only if this audit record should be discarded. No runtime rollback is required.
