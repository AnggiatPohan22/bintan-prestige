# Database Schema Overview

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
