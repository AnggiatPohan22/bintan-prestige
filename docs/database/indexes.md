# Database Indexes

Last updated: 2026-06-13

## Products

### `products_status_created_at_index`

Columns:

1. `status`
2. `created_at`

Reason:

- Public product listing filters published products and defaults to newest ordering.
- Homepage product sections read published products with `latest()`.
- Admin product listing can filter by status and uses `latest()`.
- Admin dashboard counts products by status and reads recent products by latest order.

Verification:

- `SHOW INDEX FROM products` confirms `status` is sequence 1 and `created_at` is sequence 2.
- EXPLAIN for `WHERE status = 'published' ORDER BY created_at DESC LIMIT ...` uses `products_status_created_at_index`.

Deferred:

- `product_prices(currency, price)` remains deferred until focused price filter/sort tests are in place.
- Additional product indexes for `is_featured`, `category_id`, `destination_id`, `pickup_type`, or `duration` are not added in DB-07.
