# Database Relationships

Last updated: 2026-06-13

This page documents the canonical relationship rules after the DB-01 through DB-09 database improvement phase.

## Products, Categories, and Destinations

`Product` belongs to `Category`.

`Product` belongs to `Destination`.

`Category` has many `Product` rows.

`Destination` has many `Product` rows.

Integrity rules:

- Category and Destination use soft delete for the admin archive flow.
- Soft-deleting a Category or Destination does not delete products.
- Permanent parent delete is restricted while products reference the parent.
- Product parent references are required; `category_id` and `destination_id` are not nullable.
- Product relationships keep `withTrashed()` parent access for admin/history readability.

## Products and Prices

`Product` has many `ProductPrice` rows.

`ProductPrice` belongs to `Product`.

Integrity rules:

- Product price rows are owned by a Product.
- Deleting a Product cascades its price rows.
- A Product may have both `IDR` and `SGD` prices.
- A Product may not have duplicate price rows for the same currency.

Canonical write path:

- Admin product create/update flows use `ProductPriceService::sync()`.
- The service writes prices with `updateOrCreate()` by `product_id` and `currency`.

## Products and Child Content

`Product` has many product child rows:

- `ProductImage`
- `ProductFeature`
- `ProductFaq`
- `ProductItinerary`
- `ProductNote`

Each child row belongs to one Product.

Integrity rules:

- Product child data remains owned by the Product.
- Product child foreign keys still cascade when the Product itself is deleted.
- DB-09 did not change product child foreign keys.

## Global Settings and Assets

`SiteSetting` and `SiteAsset` are read for public display through `App\Services\GlobalSettingsService`.

Cache invalidation rules:

- Saving or deleting `SiteSetting` clears `global_settings.public.v1`.
- Saving or deleting `SiteAsset` clears `global_assets.public.v1`.
- Cache invalidation is scoped and does not use `Cache::flush()`.
