# Database Indexes

Last updated: 2026-06-13

This page lists implemented indexes only. Deferred indexes are listed as deferred and must not be treated as existing schema.

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

## Product Prices

### `product_prices_product_id_currency_unique`

Columns:

1. `product_id`
2. `currency`

Reason:

- Enforces one price row per Product per currency.
- Supports the admin product price update flow that writes by `product_id` and `currency`.

Verification:

- DB-04 focused tests confirm duplicate Product/Currency rows are rejected by the database.
- Admin product price sync uses `ProductPriceService::sync()` and `updateOrCreate()`.

## Deferred Indexes

The following indexes were reviewed but not implemented:

- `product_prices(currency, price)`
- `products(status, is_featured, created_at)`
- `products(status, category_id, created_at)`
- `products(status, destination_id, created_at)`
- `products(status, pickup_type, created_at)`
- `products(status, duration)`

Reason:

- DB-07 intentionally added only `products(status, created_at)` to avoid over-indexing.
- Price filter/sort performance should get focused tests before adding `product_prices(currency, price)`.
