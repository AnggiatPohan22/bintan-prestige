# Categories Module

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
