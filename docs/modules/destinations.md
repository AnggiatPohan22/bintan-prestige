# Destinations Module

Last updated: 2026-06-13

Destinations group CMS products by travel destination for admin management and public product filtering.

## Delete Policy

- Destinations use soft delete for the normal archive flow.
- Archiving a Destination does not delete products.
- A Destination cannot be permanently deleted while products reference it.
- Empty archived Destinations may be permanently deleted through the existing admin policy.
- Product records keep required `destination_id` references and must not become orphaned.

## Database Integrity

`products.destination_id` references `destinations.id` with restricted parent delete behavior.

This keeps product data safe if future code attempts a hard delete outside the normal controller guard.

When an empty archived Destination is permanently deleted, the existing destination image cleanup flow remains responsible for removing the Destination image.

## Testing Reference

DB-09 added focused delete integrity coverage:

- Destination soft delete does not delete Product or Product children.
- Database rejects hard delete while Products reference the Destination.
- Existing controller force-delete guard still rejects permanent delete while Products exist.
- Empty archived Destinations can still be permanently deleted through the current admin policy.

## Remaining Risks

- Destination permanent delete authorization is still guarded by admin access and controller logic, not a granular Destination policy.
- Reassign-products-before-delete workflow is not implemented.
