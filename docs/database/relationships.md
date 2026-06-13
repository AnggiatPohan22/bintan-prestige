# Database Relationships

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
