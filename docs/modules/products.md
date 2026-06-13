# Products Module

Last updated: 2026-06-13

## Category and Destination Integrity

Products require both a Category and a Destination.

Rules:

- `category_id` and `destination_id` are required.
- Product parent references are protected by restricted delete foreign keys.
- A Category or Destination archive action does not delete products.
- A Category or Destination cannot be permanently deleted while products reference it.
- Product-owned child data remains governed by Product-level relationships and constraints.

## Status Policy

Products use string publication status values:

- `draft`
- `published`

`ProductFactory` defaults to `published` and provides explicit `draft()` and `published()` states for focused tests and seed/demo data.

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

## Remaining Product Risks

- Product hard delete still cascades product-owned child rows.
- Product price range index `product_prices(currency, price)` remains deferred.
- Public visibility for products under archived/inactive parents remains a future policy decision.
