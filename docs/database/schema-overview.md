# Database Schema Overview

## Products

`products` stores public/admin product records and publication state.

Current product performance index:

- `products_status_created_at_index` on `products(status, created_at)`.

Purpose:

- Supports published/draft product queries that filter by `status`.
- Supports newest/latest product listing sorted by `created_at`.
- Supports dashboard/admin status counts partially through the leading `status` column.

Notes:

- The index is non-unique.
- No product data or product query behavior is changed by this index.
- Product price range optimization remains deferred; `product_prices(currency, price)` is not added yet.

## Product Prices

`product_prices` stores product prices by currency.

Current rule:

- One product can have one `IDR` price.
- One product can have one `SGD` price.
- The same product cannot have duplicate rows for the same currency.
- Price amounts must be numeric and non-negative through the product Form Request and ProductPrice service flow.

Integrity:

- `product_prices.product_id` references `products.id`.
- Product prices cascade when the parent product is deleted.
- `product_prices(product_id, currency)` is unique through `product_prices_product_id_currency_unique`.
