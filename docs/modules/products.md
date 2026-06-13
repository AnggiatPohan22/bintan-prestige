# Products Module

## Product Query Index

The Products module has a non-unique composite index:

- `products_status_created_at_index` on `products(status, created_at)`.

This supports common product queries that filter by publication status and read newest products first. It does not change product visibility, sorting behavior, pagination, or draft/published policy.

## Product Price Integrity

The Products module stores prices in `product_prices`.

Supported currencies:

- `IDR`
- `SGD`

Rules:

- A product can store IDR and SGD prices together.
- A product cannot store duplicate rows for the same currency.
- Admin product create/update uses `ProductPriceService::sync()` to update existing currency rows instead of inserting duplicates.
- Price input must be numeric and non-negative.

Current guardrails:

- Database unique index: `product_prices_product_id_currency_unique`.
- Form Request validation for product price amount fields.
- ProductPrice service allowlist for supported currencies.
