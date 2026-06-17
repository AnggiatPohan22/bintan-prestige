# Categories Module

Last updated: 2026-06-13

Categories group CMS products for admin management and public product filtering.

## Delete Policy

- Categories use soft delete for the normal archive flow.
- Archiving a Category does not delete products.
- A Category cannot be permanently deleted while products reference it.
- Empty archived Categories may be permanently deleted through the existing admin policy.
- Product records keep required `category_id` references and must not become orphaned.

## Database Integrity

`products.category_id` references `categories.id` with restricted parent delete behavior.

This keeps product data safe if future code attempts a hard delete outside the normal controller guard.

## Testing Reference

DB-09 added focused delete integrity coverage:

- Category soft delete does not delete Product or Product children.
- Database rejects hard delete while Products reference the Category.
- Existing controller force-delete guard still rejects permanent delete while Products exist.
- Empty archived Categories can still be permanently deleted through the current admin policy.

## Remaining Risks

- Category permanent delete authorization is still guarded by admin access and controller logic, not a granular Category policy.
- Reassign-products-before-delete workflow is not implemented.
