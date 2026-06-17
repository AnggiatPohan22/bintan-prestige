# DB-09: Category & Destination Foreign Key Restriction Implementation Report

Date: 2026-06-13
Status: Completed
Scope: Change `products.category_id` and `products.destination_id` parent delete behavior from cascade to restrict.

## 1. Summary

DB-09 implemented the selected delete integrity policy from DB-08.

Category and Destination still use soft delete as the normal archive flow. Product rows are no longer at risk of automatic database cascade deletion when a Category or Destination is permanently deleted outside the controller guard. The database now restricts hard parent deletion while products reference the parent.

No existing data was deleted or modified.

## 2. Pre-check Result

Pre-check confirmed the target constraints existed and matched the DB-08 audit:

| Column | Previous constraint | Previous delete rule |
| --- | --- | --- |
| `products.category_id` | `products_category_id_foreign` | `CASCADE` |
| `products.destination_id` | `products_destination_id_foreign` | `CASCADE` |

No duplicate or malformed target constraints were found.

## 3. Orphan Data Result

Read-only orphan check before migration:

| Check | Result |
| --- | ---: |
| Products with invalid `category_id` | 0 |
| Products with invalid `destination_id` | 0 |

Read-only orphan check after migration:

| Check | Result |
| --- | ---: |
| Products with invalid `category_id` | 0 |
| Products with invalid `destination_id` | 0 |

## 4. Previous Foreign Key Behavior

Before DB-09:

- `products.category_id` referenced `categories.id` with `ON DELETE CASCADE`.
- `products.destination_id` referenced `destinations.id` with `ON DELETE CASCADE`.

This meant a hard delete of a Category or Destination could cascade into Product deletion if application-level guards were bypassed.

## 5. New Foreign Key Behavior

After DB-09:

| Column | Constraint | New delete rule | Update rule |
| --- | --- | --- | --- |
| `products.category_id` | `products_category_id_foreign` | `RESTRICT` | `NO ACTION` |
| `products.destination_id` | `products_destination_id_foreign` | `RESTRICT` | `NO ACTION` |

Product parent references remain required and non-nullable.

## 6. Migration Created

Created:

- `database/migrations/2026_06_13_000003_restrict_category_destination_deletes_on_products_table.php`

Migration behavior:

- `up()` drops the existing Category/Destination product FKs and recreates them with `restrictOnDelete()`.
- `down()` drops the restricted FKs and restores the previous `cascadeOnDelete()` behavior.
- Columns are not dropped.
- Column types, nullability, defaults, and existing data are not changed.
- Product child foreign keys are not changed.

Implementation note:

- The migration drops foreign keys using Laravel column references so the test suite's SQLite driver can run the migration.
- The resulting MySQL constraints keep the existing names: `products_category_id_foreign` and `products_destination_id_foreign`.

## 7. Files Created

- `database/migrations/2026_06_13_000003_restrict_category_destination_deletes_on_products_table.php`
- `tests/Feature/Database/CategoryDestinationDeleteIntegrityTest.php`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `ai/reports/database/db-09-category-destination-fk-restriction-implementation-report.md`

## 8. Files Changed

- `docs/database/schema-overview.md`
- `docs/database/relationships.md`
- `docs/modules/products.md`
- `docs/changelog/CHANGELOG.md`

No controller, route, model, view, public asset, config, old migration, or product child FK file was changed.

## 9. Database Impact

Changed:

- Category/Destination parent hard delete behavior for Product FKs changed from cascade to restrict.

Unchanged:

- Product data.
- Category data.
- Destination data.
- Product child data.
- Product child FK cascade behavior.
- `products.category_id` and `products.destination_id` nullability.
- Product CRUD schema.

## 10. Runtime Impact

Normal runtime behavior remains the same for admin users:

- Category soft delete still archives a Category.
- Destination soft delete still archives a Destination.
- Products remain attached to archived parents.
- Empty archived Categories/Destinations can still be permanently deleted through the existing controller policy.
- A hard parent delete with products is now rejected by the database as an additional safety layer.

Public frontend visibility was not changed in DB-09.

## 11. Controller Impact

No controller code was changed.

Existing controller guard remains active:

- `CategoryController::forceDelete()` blocks permanent delete when `products_count > 0`.
- `DestinationController::forceDelete()` blocks permanent delete when `products_count > 0`.

DB-09 adds database-level enforcement beneath the existing controller guard.

## 12. Focused Test Result

Command:

```bash
php artisan test tests\Feature\Database\CategoryDestinationDeleteIntegrityTest.php
```

Result:

- Passed: 9 tests
- Assertions: 64

Covered behavior:

- Soft delete Category does not delete Product or Product children.
- Soft delete Destination does not delete Product or Product children.
- Database rejects hard delete Category with products.
- Database rejects hard delete Destination with products.
- Controller rejects force delete Category with products.
- Controller rejects force delete Destination with products.
- Empty Category can be permanently deleted.
- Empty Destination can be permanently deleted.
- Products do not have orphaned Category/Destination references.

## 13. Full php artisan test Result

Command:

```bash
php artisan test
```

Result:

- Passed: 145 tests
- Assertions: 667

## 14. Constraint Verification Result

Command used:

- Laravel bootstrapped read-only query against `information_schema`.

Verified result:

- `products.category_id` uses `products_category_id_foreign` with `DELETE_RULE = RESTRICT`.
- `products.destination_id` uses `products_destination_id_foreign` with `DELETE_RULE = RESTRICT`.
- Both constraints remain attached to the same parent tables and columns.
- Post-migration orphan check remains clean.

## 15. Rollback Procedure

Rollback command:

```bash
php artisan migrate:rollback --step=1
```

Rollback effect:

- Drops the restricted Product parent FKs.
- Restores the previous cascade delete FKs for `products.category_id` and `products.destination_id`.

Rollback caution:

- Restoring cascade behavior reintroduces the DB-08 risk where a hard parent delete can cascade into Product deletion.
- Run orphan/dependency checks again after any rollback.

## 16. Remaining Risks

- Product itself still uses hard delete and product child rows still cascade when Product is deleted. That is outside DB-09 scope.
- Public product listing/detail visibility was not changed; published products under archived parents remain a future policy decision.
- Category/Destination permanent delete authorization is still guarded by admin access and controller checks, not granular per-module policies.
- Reassign-before-delete workflow is not implemented.

## 17. Recommended Next Step

Recommended next step:

- STEP DB-10 or SECURITY/AUTH follow-up: decide whether Category/Destination permanent delete should require Super Admin or a granular gate.

Safe future database/backend steps:

1. Add a Product delete policy audit before changing Product hard-delete behavior.
2. Add public visibility policy for products whose parent Category/Destination is archived or inactive.
3. Consider a reassign-products-before-parent-delete workflow only after admin UX planning.
4. Keep all future changes scoped and covered by focused tests.
