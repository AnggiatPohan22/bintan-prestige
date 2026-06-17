# DB-08: Category & Destination Delete Integrity Policy Audit

Date: 2026-06-13
Status: Audit only / planning only
Scope: Category and Destination delete integrity, product dependency impact, and DB-09 policy planning

## 1. Executive Summary

This audit found that the current application-level delete flow is safer than the database-level foreign key behavior.

Category and Destination records use soft deletes. The admin controllers allow archiving through soft delete, allow restore, and block permanent delete when the archived parent still has products. That runtime guard currently protects normal admin UI usage.

However, the database foreign keys from `products.category_id` and `products.destination_id` still use `ON DELETE CASCADE`. If a category or destination is force-deleted outside the guarded controller flow, or if future code bypasses the guard, related products can be deleted by the database. Product-owned child rows would then cascade as well. Storage files are not protected by database cascade, so accidental product cascade can also leave orphaned product media files.

Recommended policy for DB-09: keep category/destination soft delete as the normal admin archive action, prevent permanent parent deletion while products exist at both application and database policy level, and change the product parent foreign keys from cascade to restrict/no action in a new migration after a final duplicate/orphan pre-check.

No code, schema, data, migration, route, controller, model, view, or test files were changed in this step.

## 2. Current Database Foreign Key Map

Observed foreign key behavior:

| Child table | Column | Parent table | Parent column | On delete | Nullable |
| --- | --- | --- | --- | --- | --- |
| products | category_id | categories | id | CASCADE | No |
| products | destination_id | destinations | id | CASCADE | No |
| product_prices | product_id | products | id | CASCADE | No |
| product_images | product_id | products | id | CASCADE | No |
| product_highlights | product_id | products | id | CASCADE | No |
| product_features | product_id | products | id | CASCADE | No |
| product_faqs | product_id | products | id | CASCADE | No |
| product_itineraries | product_id | products | id | CASCADE | No |
| product_notes | product_id | products | id | CASCADE | No |
| booking_items | product_id | products | id | CASCADE | No |

Current FK names inspected:

- `products_category_id_foreign`
- `products_destination_id_foreign`
- `product_prices_product_id_foreign`
- `product_images_product_id_foreign`
- `product_highlights_product_id_foreign`
- `product_features_product_id_foreign`
- `product_faqs_product_id_foreign`
- `product_itineraries_product_id_foreign`
- `product_notes_product_id_foreign`
- `booking_items_product_id_foreign`

## 3. Actual Delete Behavior

Normal admin delete for Category and Destination calls Eloquent `delete()`, which is a soft delete because both models use `SoftDeletes`.

Permanent admin delete uses a dedicated `force-delete` route and controller method. The controller fetches the archived parent with `withCount('products')` and blocks permanent delete when `products_count > 0`.

Actual risk: the database itself does not enforce that restriction. The parent-to-product FKs still cascade on hard delete.

## 4. Soft Delete Findings

`Category` and `Destination` use `SoftDeletes`.

`Product` does not use `SoftDeletes`.

`Product::category()` and `Product::destination()` use `withTrashed()`, which keeps admin/product context readable when a parent category or destination is archived.

Soft delete is currently the safest operational behavior for Category and Destination because it does not delete product rows.

## 5. Controller Delete Flow

Category controller:

- `destroy()` soft deletes the category.
- `restore()` restores an archived category.
- `forceDelete()` blocks permanent delete when products exist.

Destination controller:

- `destroy()` soft deletes the destination.
- `restore()` restores an archived destination.
- `forceDelete()` blocks permanent delete when products exist.
- If an archived destination has no products, it deletes the destination image before force delete.

The controller guard is good, but it is not enough as the only integrity layer.

## 6. Restore/Force Delete Findings

Restore routes exist for both modules and are protected by the admin middleware group.

Force delete routes exist for both modules and are protected by the admin middleware group.

There is no bulk delete route found for categories or destinations.

There is no granular gate/policy found specifically for category/destination permanent delete. Current protection depends on authenticated active admin access and controller logic.

## 7. Existing Data Pre-check

Read-only data check result:

| Check | Result |
| --- | ---: |
| Categories with products | 3 |
| Destinations with products | 3 |
| Soft-deleted categories with products | 0 |
| Soft-deleted destinations with products | 0 |
| Products with invalid category_id | 0 |
| Products with invalid destination_id | 0 |
| Products with soft-deleted category | 0 |
| Products with soft-deleted destination | 0 |

Top dependency counts:

- Category id 2: 14 products
- Category id 1: 10 products
- Category id 3: 8 products
- Destination id 2: 13 products
- Destination id 3: 10 products
- Destination id 1: 9 products

## 8. Orphan Data Findings

No orphan product rows were found for `category_id` or `destination_id`.

No products currently reference soft-deleted categories or destinations.

The current database state is clean enough to plan a restrictive parent FK migration in DB-09, subject to one final pre-migration pre-check immediately before applying the migration.

## 9. Product Dependency Chain

Current product dependency counts:

| Dependency | Current rows |
| --- | ---: |
| products | 32 |
| product_prices | 64 |
| product_images | 34 |
| product_highlights | 93 |
| product_features | 123 |
| product_faqs | 32 |
| product_itineraries | 63 |
| product_notes | 32 |
| booking_items | 15 |
| products with thumbnail | 4 |
| products with og image | 0 |
| product_images with path | 34 |

If a category/destination hard delete cascades to products, the product-owned database rows can also cascade. This includes prices, images, highlights, features, FAQs, itineraries, notes, and booking items.

## 10. File/Storage Impact

Database cascade does not delete files from storage.

Destination permanent delete currently deletes the destination image only when the destination has no products.

Product image deletion through the admin product image flow deletes the storage file. But product hard delete itself only calls `$product->delete()` and product cascade caused by a parent FK would not run file cleanup for product thumbnails, gallery images, or future product media.

This makes parent hard-delete cascade risky for both data integrity and storage hygiene.

## 11. Option Comparison

| Option | Benefit | Risk | Recommendation |
| --- | --- | --- | --- |
| Keep cascade | Simple database cleanup when parent is deleted | Can delete products and product children; can orphan files; unsafe for CMS history | Not recommended |
| Restrict permanent delete | Aligns DB with CMS safety; protects products | Requires new migration and rollback care | Recommended for DB-09 |
| Set product parent to null | Preserves products after parent deletion | Current columns are not nullable; frontend/admin need orphan handling; weak content taxonomy | Not recommended now |
| Reassign products before permanent delete | Useful for content consolidation | Needs explicit admin UX, validation, transaction, and tests | Future enhancement after restrict policy |

## 12. Recommended Delete Integrity Policy

Recommended policy:

- Soft delete Category and Destination as the normal admin archive action.
- Do not delete products when a Category or Destination is archived.
- Do not allow permanent Category/Destination delete while products still reference it.
- Enforce permanent delete restriction in both controller logic and database FK behavior.
- Keep product-owned child rows cascading from Product for now, but only when Product itself is intentionally deleted.
- Keep `Product::category()` and `Product::destination()` with `withTrashed()` for admin/history readability.
- For public frontend, only expose products whose parent category and destination are active and not deleted, unless a future business rule explicitly allows published products under archived parents.

## 13. Recommended Database Changes

For DB-09, create a new migration that:

1. Runs only after a final read-only orphan/dependency pre-check.
2. Drops `products_category_id_foreign`.
3. Drops `products_destination_id_foreign`.
4. Recreates `category_id` and `destination_id` FKs with restrict/no action behavior on delete.
5. Keeps both columns not nullable.
6. Does not change existing product data.

Do not modify old migrations.

Keep product child FK cascade behavior unchanged in DB-09 unless product deletion policy becomes part of a separate approved step.

## 14. Recommended Runtime Changes

For DB-09 or follow-up implementation:

- Keep the existing `products_count > 0` guard in Category and Destination force delete.
- Prefer `products()->exists()` for direct dependency checks when no count needs to be rendered.
- Add focused tests proving parent force delete is blocked when products exist.
- Consider adding a shared helper/service only if duplicate delete policy logic grows.
- Consider frontend product scopes that require active, non-deleted Category and Destination for public listing/detail pages.

## 15. Recommended Admin UX Changes

Recommended future admin UX:

- Keep Archive action for active categories/destinations.
- Keep Restore action for archived categories/destinations.
- Disable or clearly block Permanent Delete when products exist.
- Show product count in the permanent delete warning.
- Link admins to a filtered product list for the affected category/destination.
- Add a stronger confirmation message for permanent delete of empty parents.
- Add a future "Reassign products before delete" workflow only after DB restrict policy is in place.

## 16. Authorization Impact

Current authorization:

- Routes are inside the admin middleware group.
- No module-specific policy/gate was found for category/destination restore or permanent delete.

Recommended future authorization:

- Keep admin middleware.
- Add granular policy/gate for destructive taxonomy actions when authorization is expanded.
- Consider restricting permanent delete to Super Admin, while allowing normal Admin to create/update/archive if that matches the CMS operations policy.

## 17. Testing Plan

Recommended DB-09 tests:

- Category soft delete does not delete products.
- Destination soft delete does not delete products.
- Category permanent delete is blocked when products exist.
- Destination permanent delete is blocked when products exist.
- Database rejects parent hard delete when products exist after restrict FK migration.
- Empty archived Category can be permanently deleted.
- Empty archived Destination can be permanently deleted and its destination image cleanup still works.
- Public product listing does not expose products under inactive/deleted parents if that policy is implemented.
- Admin product listing still displays products with archived parents for historical/admin context.
- CSRF and admin auth still protect restore and force-delete routes.

## 18. Files Inspected

- `AGENTS.md`
- `ai/skills/database-architecture-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/cms-architect-skill.md`
- `ai/skills/security-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `ai/reports/database/db-05-product-index-global-settings-performance-plan.md`
- `ai/reports/database/db-07-product-query-index-implementation-report.md`
- `docs/database/relationships.md`
- `routes/admin.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/Product.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Requests/StoreCategoryRequest.php`
- `app/Http/Requests/UpdateCategoryRequest.php`
- `app/Http/Requests/StoreDestinationRequest.php`
- `app/Http/Requests/UpdateDestinationRequest.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Services/CategoryService.php`
- `app/Services/DestinationService.php`
- `database/factories/ProductFactory.php`
- `database/seeders/TravelSeeder.php`
- `database/migrations/2026_05_21_142735_create_categories_table.php`
- `database/migrations/2026_05_21_142746_create_destinations_table.php`
- `database/migrations/2026_05_31_000001_add_deleted_at_to_categories_and_destinations_table.php`
- `database/migrations/2026_05_21_142755_create_products_table.php`
- `database/migrations/2026_05_21_142802_create_product_images_table.php`
- `database/migrations/2026_05_21_142810_create_product_prices_table.php`
- `database/migrations/2026_05_24_170137_create_product_highlights_table.php`
- `database/migrations/2026_05_24_170635_create_product_features_table.php`
- `database/migrations/2026_05_24_170158_create_product_faqs_table.php`
- `database/migrations/2026_05_24_170152_create_product_itineraries_table.php`
- `database/migrations/2026_05_24_170203_create_product_notes_table.php`
- `database/migrations/2026_05_21_143012_create_booking_items_table.php`
- `resources/views/backend/categories/index.blade.php`
- `resources/views/backend/destinations/index.blade.php`

## 19. Files Recommended for DB-09 Changes

Potential DB-09 files, pending approval:

- `database/migrations/*_change_products_category_destination_foreign_keys_to_restrict.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `resources/views/backend/categories/index.blade.php`
- `resources/views/backend/destinations/index.blade.php`
- `tests/Feature/Database/CategoryDestinationDeleteIntegrityTest.php`
- `tests/Feature/Admin/CategoryDestinationDeletePolicyTest.php`
- `docs/database/relationships.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/changelog/CHANGELOG.md`
- `ai/reports/database/db-09-category-destination-delete-integrity-implementation-report.md`

Optional future authorization files:

- `app/Policies/CategoryPolicy.php`
- `app/Policies/DestinationPolicy.php`
- `app/Providers/AppServiceProvider.php`

## 20. Risk Level

Current risk level: Medium to High.

Normal admin UI usage is currently guarded, so day-to-day risk is reduced. The risk becomes high if a future feature, command, raw query, seed/reset workflow, or controller change hard-deletes a category/destination without the current controller guard.

The highest-impact risk is accidental cascade deletion of products and their dependent rows.

## 21. Migration Risk

DB-09 migration risk: Medium.

Risk drivers:

- Existing FKs must be dropped and recreated correctly.
- MySQL/MariaDB may require exact FK names.
- A final pre-check must confirm there are no orphan products before adding restrict/no action FKs.
- Rollback would restore cascade behavior, which is riskier but technically possible.

Mitigation:

- Use a new migration only.
- Do not change existing migrations.
- Run pre-check before migrate.
- Run full test suite after migration.
- Keep rollback explicit and documented.

## 22. Rollback Plan

For DB-08 itself:

- Delete only this audit report if rollback is required.
- No runtime files, database schema, or data were changed.

For future DB-09:

- Roll back the new FK migration.
- Restore the previous cascade FK definitions only if rollback is explicitly approved.
- Re-run product orphan checks after rollback.
- Confirm admin Category/Destination archive/restore/permanent-delete routes still behave as expected.

## 23. Recommended Next Step

Proceed to DB-09: Category & Destination Delete Integrity Implementation.

Recommended DB-09 scope:

1. Run final read-only orphan/dependency pre-check.
2. Add a new migration changing `products.category_id` and `products.destination_id` delete behavior from cascade to restrict/no action.
3. Keep application-level force delete guard.
4. Add focused integrity tests.
5. Improve admin permanent delete warning/disabled state without redesigning the dashboard.
6. Update database/module docs and changelog.

Do not implement product soft delete, reassignment workflows, or frontend visibility scope changes in the same DB-09 migration unless separately approved.
