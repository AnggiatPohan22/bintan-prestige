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

## Public Listing Visibility Policy

The public Product Listing uses `Product::publiclyVisible()`.

Rules:

- Product status must be `published`.
- The related Category must be active and not archived.
- The related Destination must be active and not archived.
- Archiving or deactivating a Category/Destination hides related Products from the public listing without deleting Product records.
- Admin Product queries are not changed by this public listing scope.

The Product Listing card query uses `Product::frontendListingReady()` to eager load only listing-card relations: Category, Destination, Prices, and Images.

## Public Listing CMS Content

The public Product Listing uses Page Sections for editable intro/catalog content while keeping listing behavior in application code.

Page Section keys:

- `products.index.hero`
- `products.index.catalog`
- `products.index.filter_modal`
- `products.index.sort_modal`

Runtime behavior:

- `products.index.hero` can provide hero label, title, subtitle, description, and optional legacy image.
- `products.index.catalog` can provide catalog heading, optional supporting paragraph, and optional CTA when both button text and a safe URL are present.
- Missing, inactive, or empty sections use code fallbacks.
- Page Sections do not control Product query, visibility, filters, sorting, pagination, currency, duration, Product cards, or route names.

## Public Listing Price and Sorting Policy

The public Product Listing keeps the existing multi-currency display while using an explicit IDR price context for price filtering and price sorting.

Display rules:

- Products with IDR and SGD prices show IDR as the primary listing price and SGD as the secondary price.
- Products with IDR only show IDR.
- Products with SGD only show SGD and remain visible in the general listing.
- Products without price rows show `Price on request`.
- Missing price is not treated as zero.

Filter/sort rules:

- `min_price` and `max_price` check only `IDR` price rows.
- Products without an IDR price do not match IDR price range filters.
- `price_low` sorts by IDR price ascending.
- `price_high` sorts by IDR price descending.
- Products without an IDR price are placed after products with IDR prices for both price sorts.
- Duration remains display/filter text only; duration sorting is disabled until normalized duration data exists.

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
- Public Product Detail parent-visibility behavior remains separate from the Product Listing policy and is deferred until a Product Detail step.
- Public duration sorting requires a future approved normalized duration field or controlled duration taxonomy.
